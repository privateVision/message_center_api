# _*_ coding: utf-8 _*_

from mongoengine import Q

from MiddleWare import redis_store, service_logger
from MongoModel.AppRulesModel import AppVipRules
from MongoModel.MessageModel import UsersMessage


def get_game_and_area_and_user_type_and_vip_users(game=None, user_type=None, vips=None):
    game_users_list = []
    user_type_users_list = get_user_type_users(user_type)
    vip_users_list = get_vip_users(vips)
    # 游戏区服不为空
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
                        except Exception, err:
                            service_logger.error(err.message)
                            mysql_session.rollback()
                        finally:
                            mysql_session.close()
                        for ucid in tmp_user_list:
                            game_users_list.append(ucid[0])
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
    find_ucid_sql = "select ucid from session where access_token = '%s'" % (access_token,)
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


def get_user_data_mark_in_redis(ucid):
    if redis_store.exists(ucid):
        return redis_store.hgetall(ucid)
    return None


def clear_user_data_mark_in_redis(ucid, message_type):
    redis_store.hdel(ucid, message_type)
