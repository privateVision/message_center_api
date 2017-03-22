# _*_ coding: utf-8 _*_
from functools import wraps

from flask import request
from mongoengine import Q

from Controller.BaseController import response_data
from MiddleWare import service_logger
from MongoModel.AppRulesModel import AppVipRules
from MongoModel.MessageModel import UsersMessage
from MongoModel.UserMessageModel import UserMessage
from Utils.SystemUtils import get_current_timestamp, log_exception


def get_game_and_area_and_user_type_and_vip_users(game=None, user_type=None, vips=None):
    game_users_list = []
    user_type_users_list = get_user_type_users(user_type)
    vip_users_list = get_vip_users(vips)
    # 游戏区服不为空
    # game的结构可能不一样，卡券只有单个游戏，没有区服，其他的有多个游戏和区服
    if game is not None:
        from run import mysql_session
        # 不是所有游戏区服
        if game[0]['apk_id'] != 'all':
            for game_info in game:
                if game_info.has_key('zone_id_list'):
                    for zone in game_info['zone_id_list']:
                        find_users_in_game_area_sql = "select ucid from roleDatas where vid = %s and zoneName = '%s'"\
                                                      % (game_info['apk_id'], zone)
                        try:
                            tmp_user_list = mysql_session.execute(find_users_in_game_area_sql)
                            for ucid in tmp_user_list:
                                game_users_list.append(ucid[0])
                        except Exception, err:
                            service_logger.error(err.message)
                            mysql_session.rollback()
                        finally:
                            mysql_session.close()
            return list(set(user_type_users_list).intersection(set(game_users_list)).intersection(set(vip_users_list)))
        else:
            return list(set(user_type_users_list).intersection(set(vip_users_list)))
    # 游戏区服为空
    else:
        return []


def get_user_type_users(user_type):
    users_list = []
    if user_type is not None:
        from run import mysql_session
        for type in user_type:
            find_users_by_user_type_sql = "select ucid from ucusers as u, retailers as r where u.rid = r.rid " \
                                          "and r.rtype = %s" % (type,)
            try:
                origin_list = mysql_session.execute(find_users_by_user_type_sql)
            except Exception, err:
                service_logger.error(err.message)
                mysql_session.rollback()
            finally:
                mysql_session.close()
            for ucid in origin_list:
                users_list.append(ucid[0])
    return users_list


# 实时计算用户vip等级，然后筛选
def get_vip_users(vips):
    users_list = []
    if vips is not None:
        from run import mysql_session
        for vip in vips:
            vip_rule_info = AppVipRules.objects(level=vip).first()
            if vip_rule_info is not None:
                fee = vip_rule_info['fee']
                find_users_by_vip_sql = "select ucid from ucuser_total_pay as u where u.pay_fee >= %s " % (fee,)
                try:
                    origin_list = mysql_session.execute(find_users_by_vip_sql)
                except Exception, err:
                    service_logger.error(err.message)
                    mysql_session.rollback()
                finally:
                    mysql_session.close()
                for ucid in origin_list:
                    users_list.append(ucid[0])
    return users_list


def get_notice_message_detail_info(msg_id=None):
    return UsersMessage.objects(Q(type='notice') & Q(mysql_id=msg_id)).first()


def get_broadcast_message_detail_info(msg_id=None):
    return UsersMessage.objects(Q(type='broadcast') & Q(mysql_id=msg_id)).first()


def get_message_detail_info(msg_id=None):
    return UsersMessage.objects(Q(type='message') & Q(mysql_id=msg_id)).first()


def get_coupon_message_detail_info(msg_id=None):
    return UsersMessage.objects(Q(type='coupon') & Q(mysql_id=msg_id)).first()


def get_ucid_by_access_token(access_token=None):
    find_ucid_sql = "select ucid from session where token = '%s'" % (access_token,)
    from run import mysql_session
    try:
        user_info = mysql_session.execute(find_ucid_sql).first()
    except Exception, err:
        service_logger.error(err.message)
        mysql_session.rollback()
    finally:
        mysql_session.close()
    if user_info:
        if user_info['ucid']:
            return user_info['ucid']
    return False


# 根据用户id获取广播列表
def get_user_broadcast_list(ucid=None):
    current_timestamp = get_current_timestamp()
    broadcast_list = UserMessage.objects(
        Q(type='broadcast')
        & Q(closed=0)
        & Q(is_read=0)
        & Q(start_time__lte=current_timestamp)
        & Q(end_time__gte=current_timestamp)
        & Q(ucid=ucid)).order_by('-start_time')[:1]
    for message in broadcast_list:
        message_info = get_broadcast_message_detail_info(message['mysql_id'])
        message_resp = {
            "content": message_info['content'],
            "close_time": message_info['close_time']
        }
        UserMessage.objects(Q(type='broadcast') & Q(ucid=ucid)).update(set__is_read=1)
        return message_resp
    return None


# 检查用户账号是否被冻结
def find_user_account_is_freeze(ucid=None):
    find_is_freeze_sql = "select is_freeze from user where ucid = %s" % (ucid,)
    from run import mysql_session
    try:
        user_info = mysql_session.execute(find_is_freeze_sql).first()
        if user_info:
            if user_info['is_freeze'] == 1:
                return True
    except Exception, err:
        service_logger.error(err.message)
        mysql_session.rollback()
    finally:
        mysql_session.close()
    return False


# 检查用户子账号是否被冻结
def find_user_child_account_is_freeze(ucid=None):
    db_num = int(int(ucid) % 30)
    find_is_freeze_sql = "select id from user_procedure_%s where is_freeze=1 and ucid = %s" % (db_num, ucid)
    from run import mysql_session
    try:
        user_info_list = mysql_session.execute(find_is_freeze_sql)
        child_id_list = []
        if user_info_list:
            for info in user_info_list:
                child_id_list.append(info['id'])
            return True, child_id_list
    except Exception, err:
        service_logger.error(err.message)
        mysql_session.rollback()
    finally:
        mysql_session.close()
    return False, []


# sdk api 请求通用装饰器
def sdk_api_request_check(func):
    @wraps(func)
    def wraper(*args, **kwargs):
        from Utils.EncryptUtils import sdk_api_params_check, sdk_api_check_sign
        is_params_checked = sdk_api_params_check(request)
        if is_params_checked is False:
            log_exception(request, '客户端请求错误-appid或sign或token为空')
            return response_data(200, 0, '客户端参数错误')
        is_sign_true = sdk_api_check_sign(request)
        if is_sign_true is True:
            ucid = get_ucid_by_access_token(request.form['_token'])
            if ucid:
                if find_user_account_is_freeze(ucid):
                    return response_data(200, 101, '账号被冻结')
            else:
                log_exception(request, "根据token: %s 获取ucid失败" % (request.form['_token'],))
                return response_data(200, 0, '根据token获取ucid失败')
        return func(*args, **kwargs)
    return wraper


# cms api 请求通用装饰器
def cms_api_request_check(func):
    @wraps(func)
    def wraper(*args, **kwargs):
        from Utils.EncryptUtils import generate_checksum
        check_result, check_exception = generate_checksum(request)
        if not check_result:
            return check_exception
        return func(*args, **kwargs)
    return wraper

