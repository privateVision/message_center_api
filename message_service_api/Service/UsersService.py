# _*_ coding: utf-8 _*_
from functools import wraps

import time

import datetime
from flask import request
from mongoengine import Q

from Controller.BaseController import response_data
from MiddleWare import service_logger, hdfs_logger
from MongoModel.AppRulesModel import AppVipRules
from MongoModel.MessageModel import UsersMessage
from MongoModel.UserMessageModel import UserMessage
from MongoModel.UserReadMessageLogModel import UserReadMessageLog
from Utils.RedisUtil import RedisHandle
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
                        find_users_in_game_area_sql = "select distinct(ucid) from ucuser_role_%s where pid = %s " \
                                                      "and zoneName = '%s'" \
                                                      % (int(int(game_info['apk_id']) / 30), game_info['apk_id'], zone)
                        try:
                            tmp_user_list = mysql_session.execute(find_users_in_game_area_sql).fetchall()
                            for item in tmp_user_list:
                                game_users_list.append(item['ucid'])
                        except Exception, err:
                            service_logger.error(err.message)
                            mysql_session.rollback()
                        finally:
                            mysql_session.close()
                else:  # 没传区服信息，那就所有区服咯
                    find_users_in_game_area_sql = "select distinct(ucid) from ucuser_role_%s where pid = %s " \
                                                  % (int(int(game_info['apk_id']) / 30), game_info['apk_id'])
                    try:
                        tmp_user_list = mysql_session.execute(find_users_in_game_area_sql).fetchall()
                        for item in tmp_user_list:
                            game_users_list.append(item['ucid'])
                    except Exception, err:
                        service_logger.error(err.message)
                        mysql_session.rollback()
                    finally:
                        mysql_session.close()
            return list(set(user_type_users_list).intersection(set(game_users_list)).intersection(set(vip_users_list)))
        else:  # 所有游戏，太可怕了
            find_game_list_sql = "select pid from procedures"
            game_list = mysql_session.execute(find_game_list_sql).fetchall()
            for game in game_list:
                pid = game['pid']
                find_all_game_users_sql = "select distinct(ucid) from ucuser_role_%s " % (int(int(pid) / 30),)
                try:
                    tmp_user_list = mysql_session.execute(find_all_game_users_sql).fetchall()
                    for item in tmp_user_list:
                        game_users_list.append(item['ucid'])
                except Exception, err:
                    service_logger.error(err.message)
                    mysql_session.rollback()
                finally:
                    mysql_session.close()
                return list(
                    set(game_users_list).intersection(set(user_type_users_list)).intersection(set(vip_users_list)))
    # 游戏区服为空
    else:
        return []


def get_user_type_users(user_type):
    users_list = []
    if user_type is not None:
        from run import mysql_session
        for type in user_type:
            find_users_by_user_type_sql = "select distinct(ucid) from ucusers as u, retailers as r where u.rid = r.rid " \
                                          "and r.rtype = %s" % (type,)
            try:
                origin_list = mysql_session.execute(find_users_by_user_type_sql).fetchall()
                for item in origin_list:
                    users_list.append(item['ucid'])
            except Exception, err:
                service_logger.error(err.message)
                mysql_session.rollback()
            finally:
                mysql_session.close()
    return users_list


# 实时计算用户vip等级，然后筛选
def get_vip_users(vips):
    users_list = []
    if vips is not None:
        from run import mysql_session
        for vip in vips:
            find_users_by_vip_sql = "select distinct(ucid) from ucuser_info as u where u.vip >= %s " % (vip,)
            try:
                origin_list = mysql_session.execute(find_users_by_vip_sql).fetchall()
                for item in origin_list:
                    users_list.append(item['ucid'])
            except Exception, err:
                service_logger.error(err.message)
                mysql_session.rollback()
            finally:
                mysql_session.close()
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
    ucid = RedisHandle.get_ucid_from_redis_by_token(access_token)
    if ucid is not None:
        return ucid
    find_ucid_sql = "select ucid from session where token = '%s'" % (access_token,)
    from run import mysql_session
    try:
        user_info = mysql_session.execute(find_ucid_sql).first()
        if user_info is not None:
            if 'ucid' in user_info:
                return user_info['ucid']
    except Exception, err:
        service_logger.error(err.message)
        mysql_session.rollback()
    finally:
        mysql_session.close()
    return None


def get_username_by_ucid(ucid=None):
    find_ucid_sql = "select nickname from ucusers where ucid = %s" % (ucid,)
    from run import mysql_session
    try:
        user_info = mysql_session.execute(find_ucid_sql).first()
        if user_info is not None:
            if 'nickname' in user_info:
                return user_info['nickname']
    except Exception, err:
        service_logger.error(err.message)
        mysql_session.rollback()
    finally:
        mysql_session.close()
    return ''


def is_session_expired_by_access_token(access_token=None):
    expired_ts = RedisHandle.get_expired_ts_from_redis_by_token(access_token)
    now = int(time.time())
    if expired_ts is not None:
        if expired_ts < now:
            return True
        else:
            return False
    find_expired_ts_sql = "select expired_ts from session where token = '%s'" % (access_token,)
    from run import mysql_session
    try:
        user_info = mysql_session.execute(find_expired_ts_sql).first()
        if user_info is not None:
            if 'expired_ts' in user_info:
                if user_info['expired_ts'] < now:
                    return True
                return False
    except Exception, err:
        service_logger.error(err.message)
        mysql_session.rollback()
    finally:
        mysql_session.close()
    return True


def get_user_is_freeze_by_access_token(access_token=None):
    freeze = RedisHandle.get_user_is_freeze_from_redis_by_token(access_token)
    if freeze is not None:
        return freeze
    find_freeze_sql = "select freeze from session where token = '%s'" % (access_token,)
    from run import mysql_session
    try:
        user_info = mysql_session.execute(find_freeze_sql).first()
        if user_info is not None:
            if 'freeze' in user_info:
                return user_info['freeze']
    except Exception, err:
        service_logger.error(err.message)
        mysql_session.rollback()
    finally:
        mysql_session.close()
    return None


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
    data_list = []
    for message in broadcast_list:
        message_info = get_broadcast_message_detail_info(message['mysql_id'])
        message_resp = {
            "content": message_info['content'],
            "close_time": message_info['close_time']
        }
        UserMessage.objects(Q(type='broadcast') & Q(ucid=ucid)).update(set__is_read=1)
        data_list.append(message_resp)
    return data_list


# 根据用户id获取未读消息数
def get_user_unread_message_count(ucid=None):
    count = UserMessage.objects(
        Q(type='message')
        & Q(closed=0)
        & Q(is_read=0)
        & Q(ucid=ucid)).count()
    return count


# 获取用户可领的礼包数
def get_user_gift_count(ucid=None, appid=None):
    from run import mysql_cms_session
    from run import SDK_PLATFORM_ID
    now = int(time.time())
    game = get_game_info_by_appid(appid)
    if game is None:
        return 0

    # 找到用户已经领取的礼包，需要排除掉
    find_user_already_get_gift_id_sql = "select distinct(giftId) from cms_gameGiftLog where gameId = %s" \
                                        " and status = 'normal' and uid = %s " % (game['id'], ucid)
    gift_id_list = mysql_cms_session.execute(find_user_already_get_gift_id_sql).fetchall()
    already_get_gift_id_list = []
    for gift_id in gift_id_list:
        already_get_gift_id_list.append(str(gift_id['giftId']))
    already_get_gift_id_list_str = ",".join(already_get_gift_id_list)

    # 找到游戏下的礼包列表
    from Service.StorageService import get_uid_by_ucid
    uid = get_uid_by_ucid(ucid)
    game_gift_list_sql = "select id from cms_gameGift where status = 'normal' and gameId = %s" % (game['id'],)
    game_gift_list = mysql_cms_session.execute(game_gift_list_sql).fetchall()
    game_gift_array = []
    for game_gift in game_gift_list:
        game_gift_array.append(str(game_gift['id']))
    game_gift_array_str = ",".join(game_gift_array)

    # 找到指定用户的礼包，并且当前用户在指定列表之中的礼包
    specify_user_gift_id_list = []
    if len(game_gift_array) > 0:
        find_specify_user_gift_id_sql = "select distinct(giftId) from cms_gameGiftSpecify where giftId in (%s)" \
                                        " and value = '%s' " % (game_gift_array_str, uid)
        append_gift_id_list = mysql_cms_session.execute(find_specify_user_gift_id_sql).fetchall()
        for gift_id in append_gift_id_list:
            specify_user_gift_id_list.append(str(gift_id['giftId']))
        specify_user_gift_id_list_str = ",".join(specify_user_gift_id_list)

    if len(already_get_gift_id_list) > 0:
        # 获取没有指定用户的还有剩余礼包的礼包数
        get_not_specify_user_gift_count_sql = "select count(*) from cms_gameGift as gift join cms_gameGiftAssign " \
                                              "as assign" \
                                              " on gift.id=assign.giftId where gift.status='normal' " \
                                              "and gift.failTime > %s and assign.platformId = %s " \
                                              "and assign.status='normal' and assign.assignNum>0 " \
                                              "and gift.isSpecify=0 " \
                                              "and gift.id not in(%s)" % (
                                                  now, SDK_PLATFORM_ID, already_get_gift_id_list_str)
        if len(specify_user_gift_id_list) > 0:
            # 获取指定了用户的还有剩余礼包的礼包数
            get_specify_user_gift_count_sql = "select count(*) from cms_gameGift as gift join cms_gameGiftAssign " \
                                              "as assign" \
                                              " on gift.id=assign.giftId where gift.status='normal' " \
                                              "and gift.failTime > %s and assign.platformId = %s " \
                                              "and assign.status='normal' and assign.assignNum>0 " \
                                              "and gift.isSpecify=1 " \
                                              "and gift.id in(%s) and gift.id not in(%s) " \
                                              % (now, SDK_PLATFORM_ID, specify_user_gift_id_list_str,
                                                 already_get_gift_id_list_str)
        else:
            # 获取指定了用户的还有剩余礼包的礼包数
            get_specify_user_gift_count_sql = None
    else:
        # 获取没有指定用户的还有剩余礼包的礼包数
        get_not_specify_user_gift_count_sql = "select count(*) from cms_gameGift as gift join cms_gameGiftAssign " \
                                              "as assign" \
                                              " on gift.id=assign.giftId where gift.status='normal' " \
                                              "and gift.failTime > %s and assign.platformId = %s " \
                                              "and assign.status='normal' and assign.assignNum>0 " \
                                              "and gift.isSpecify=0 " \
                                              % (now, SDK_PLATFORM_ID)
        if len(specify_user_gift_id_list) > 0:
            # 获取指定了用户的还有剩余礼包的礼包数
            get_specify_user_gift_count_sql = "select count(*) from cms_gameGift as gift join cms_gameGiftAssign" \
                                              " as assign" \
                                              " on gift.id=assign.giftId where gift.status='normal' " \
                                              "and gift.failTime > %s and assign.platformId = %s " \
                                              "and assign.status='normal' and assign.assignNum>0 " \
                                              "and gift.isSpecify=1 " \
                                              "and gift.id in(%s)" \
                                              % (now, SDK_PLATFORM_ID, specify_user_gift_id_list_str)
        else:
            # 获取指定了用户的还有剩余礼包的礼包数
            get_specify_user_gift_count_sql = None
    not_specify_count = mysql_cms_session.execute(get_not_specify_user_gift_count_sql).scalar()
    if get_specify_user_gift_count_sql is not None:
        specify_count = mysql_cms_session.execute(get_specify_user_gift_count_sql).scalar()
    else:
        specify_count = 0
    return not_specify_count + specify_count


# 获取用户可以看到的礼包数
def get_user_can_see_gift_count(ucid=None, appid=None):
    from run import mysql_cms_session
    game = get_game_info_by_appid(appid)
    if game is None:
        return 0
    find_user_already_get_gift_count_sql = "select count(distinct(log.giftId)) from cms_gameGiftLog as log " \
                                           "join cms_gameGift as gift on log.giftId = gift.id where log.gameId = %s" \
                                           " and gift.status='normal' and log.status = 'normal'" \
                                           " and log.uid = %s " % (game['id'], ucid)
    already_get_gift_count = mysql_cms_session.execute(find_user_already_get_gift_count_sql).scalar()
    unget_count = get_user_gift_count(ucid, appid)
    return unget_count + already_get_gift_count


# 获取用户可以看到的礼包列表
def get_user_can_see_gift_list(ucid=None, game_id=None, start_index=None, end_index=None):
    from run import mysql_cms_session
    from run import SDK_PLATFORM_ID
    now = int(time.time())
    find_user_already_get_gift_id_sql = "select distinct(giftId) from cms_gameGiftLog where gameId = %s" \
                                        " and status = 'normal' and uid = %s " % (game_id, ucid)
    gift_id_list = mysql_cms_session.execute(find_user_already_get_gift_id_sql).fetchall()
    already_get_gift_id_list = []
    for gift_id in gift_id_list:
        already_get_gift_id_list.append(str(gift_id['giftId']))
    already_get_gift_id_list_str = ",".join(already_get_gift_id_list)

    from Service.StorageService import get_uid_by_ucid
    uid = get_uid_by_ucid(ucid)
    game_gift_list_sql = "select id from cms_gameGift where status = 'normal' and gameId = %s" % (game_id,)
    game_gift_list = mysql_cms_session.execute(game_gift_list_sql).fetchall()
    game_gift_array = []
    for game_gift in game_gift_list:
        game_gift_array.append(str(game_gift['id']))
    game_gift_array_str = ",".join(game_gift_array)

    append_gift_id_list = []
    if len(game_gift_array) > 0:
        find_specify_user_gift_id_sql = "select distinct(giftId) from cms_gameGiftSpecify where giftId in (%s)" \
                                        " and value = '%s' " % (game_gift_array_str, uid)
        append_gift_id_list = mysql_cms_session.execute(find_specify_user_gift_id_sql).fetchall()

    specify_user_gift_id_list = []
    for gift_id in append_gift_id_list:
        specify_user_gift_id_list.append(str(gift_id['giftId']))
    specify_user_gift_id_list_str = ",".join(specify_user_gift_id_list)

    if len(already_get_gift_id_list) > 0:
        if len(specify_user_gift_id_list) > 0:
            unget_gifts_page_list_sql = "select * from (select a.id,a.gameId,a.gameName,a.name,a.gift," \
                                        "a.isAfReceive, a.isBindPhone," \
                                        "a.content,a.label,a.uid,a.publishTime,a.failTime,a.status," \
                                        "b.num, b.assignNum, ifnull(c.code,'') as code,if(c.code<>'', 1, 0) " \
                                        "as is_get from cms_gameGift as a join cms_gameGiftAssign as b " \
                                        "on a.id=b.giftId " \
                                        "left outer join cms_gameGiftLog as c on c.giftId=a.id and c.uid= %s " \
                                        "where a.gameId=%s and a.failTime > %s and b.platformId=%s " \
                                        "and a.status='normal' and b.status='normal' " \
                                        "and ((a.assignNum > 0 and b.assignNum > 0) " \
                                        "and ((a.isSpecify=1 and a.id in (%s)) or (a.isSpecify=0)) or a.id in (%s) ) " \
                                        "order by is_get asc , c.forTime desc, a.id desc) as d " \
                                        "where d.code<>'' or (d.assignNum>0 and d.code='') limit %s, %s " \
                                        % (ucid, game_id, now, SDK_PLATFORM_ID, specify_user_gift_id_list_str,
                                           already_get_gift_id_list_str, start_index, end_index)
        else:
            unget_gifts_page_list_sql = "select * from (select a.id,a.gameId,a.gameName,a.name,a.gift," \
                                        "a.isAfReceive, a.isBindPhone," \
                                        "a.content,a.label,a.uid,a.publishTime,a.failTime,a.status," \
                                        "b.num, b.assignNum, ifnull(c.code,'') as code,if(c.code<>'', 1, 0) " \
                                        "as is_get from cms_gameGift as a join cms_gameGiftAssign as b " \
                                        "on a.id=b.giftId " \
                                        "left outer join cms_gameGiftLog as c on c.giftId=a.id and c.uid= %s " \
                                        "where a.gameId=%s and a.failTime > %s and b.platformId=%s " \
                                        "and a.status='normal' and b.status='normal' " \
                                        "and ((a.assignNum > 0 and b.assignNum > 0) " \
                                        "and (a.isSpecify=0) or a.id in (%s) ) " \
                                        "order by is_get asc , c.forTime desc, a.id desc) as d " \
                                        "where d.code<>'' or (d.assignNum>0 and d.code='') limit %s, %s " \
                                        % (ucid, game_id, now, SDK_PLATFORM_ID,
                                           already_get_gift_id_list_str, start_index, end_index)
    else:
        if len(specify_user_gift_id_list) > 0:
            unget_gifts_page_list_sql = "select * from (select a.id,a.gameId,a.gameName,a.name,a.gift," \
                                        "a.isAfReceive, a.isBindPhone," \
                                        "a.content,a.label,a.uid,a.publishTime,a.failTime,a.status," \
                                        "b.num, b.assignNum, ifnull(c.code,'') as code,if(c.code<>'', 1, 0) " \
                                        "as is_get from cms_gameGift as a join cms_gameGiftAssign as b " \
                                        "on a.id=b.giftId " \
                                        "left outer join cms_gameGiftLog as c on c.giftId=a.id and c.uid= %s " \
                                        "where ((a.isSpecify = 1 and a.id in (%s)) or (a.isSpecify = 0)) " \
                                        "and a.gameId=%s and a.failTime > %s and b.platformId=%s " \
                                        "and a.status='normal' and b.status='normal' " \
                                        "and (a.assignNum > 0 and b.assignNum > 0) " \
                                        "order by is_get asc , c.forTime desc, a.id desc) as d " \
                                        "where d.code<>'' or (d.assignNum>0 and d.code='') limit %s, %s " \
                                        % (ucid, specify_user_gift_id_list_str, game_id, now, SDK_PLATFORM_ID,
                                           start_index, end_index)
        else:
            unget_gifts_page_list_sql = "select * from (select a.id,a.gameId,a.gameName,a.name,a.gift," \
                                        "a.isAfReceive, a.isBindPhone," \
                                        "a.content,a.label,a.uid,a.publishTime,a.failTime,a.status," \
                                        "b.num, b.assignNum, ifnull(c.code,'') as code,if(c.code<>'', 1, 0) " \
                                        "as is_get from cms_gameGift as a join cms_gameGiftAssign as b " \
                                        "on a.id=b.giftId " \
                                        "left outer join cms_gameGiftLog as c on c.giftId=a.id and c.uid= %s " \
                                        "where a.isSpecify = 0 " \
                                        "and a.gameId=%s and a.failTime > %s and b.platformId=%s " \
                                        "and a.status='normal' and b.status='normal' " \
                                        "and (a.assignNum > 0 and b.assignNum > 0) " \
                                        "order by is_get asc , c.forTime desc, a.id desc) as d " \
                                        "where d.code<>'' or (d.assignNum>0 and d.code='') limit %s, %s " \
                                        % (ucid, game_id, now, SDK_PLATFORM_ID, start_index, end_index)
    unget_gifts_page_list = mysql_cms_session.execute(unget_gifts_page_list_sql).fetchall()
    return unget_gifts_page_list


# 检查用户账号是否被冻结
def find_user_account_is_freeze(ucid=None):
    find_is_freeze_sql = "select is_freeze from ucusers where ucid = %s" % (ucid,)
    from run import mysql_session
    try:
        user_info = mysql_session.execute(find_is_freeze_sql).first()
        if user_info is not None:
            if 'is_freeze' in user_info:
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
    find_is_freeze_sql = "select id from ucuser_sub_%s where is_freeze=1 and ucid = %s" % (db_num, ucid)
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


def get_stored_value_card_list(ucid, start_index, end_index):
    from run import mysql_session
    total_count = 0
    value_card_list = []
    time_now = datetime.datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    find_user_store_value_card_list_sql = "select vc.*, uvc.balance from ucusersVC as uvc, " \
                                          "virtualCurrencies as vc where " \
                                          "uvc.vcid = vc.vcid and uvc.balance > 0 and uvc.ucid = %s and " \
                                          "((vc.untimed = 1) or ((vc.untimed = 0) and " \
                                          "unix_timestamp(vc.startTime) <= unix_timestamp('%s') " \
                                          "and unix_timestamp(vc.endTime) >= unix_timestamp('%s'))) " \
                                          "limit %s, %s " % \
                                          (ucid, time_now, time_now, start_index, end_index)
    find_user_store_value_card_count_sql = "select count(*) from ucusersVC as uvc, virtualCurrencies as vc where " \
                                           "uvc.vcid = vc.vcid and uvc.balance > 0 and uvc.ucid = %s and " \
                                           "((vc.untimed = 1) or ((vc.untimed = 0) " \
                                           "and unix_timestamp(vc.startTime) <= unix_timestamp('%s')" \
                                           " and unix_timestamp(vc.endTime) >= unix_timestamp('%s'))) " \
                                           % (ucid, time_now, time_now)
    try:
        total_count = mysql_session.execute(find_user_store_value_card_count_sql).scalar()
        card_list = mysql_session.execute(find_user_store_value_card_list_sql).fetchall()
        if total_count > 0:
            for card in card_list:
                find_game_name_sql = 'select pname from procedures where pid = %s limit 1' % (card['lockApp'],)
                game_info = mysql_session.execute(find_game_name_sql).fetchone()
                item = {
                    'id': card['vcid'],
                    'name': card['vcname'],
                    'supportDivide': card['supportDivide'],
                    'start_time': 0,
                    'end_time': 0,
                    'lock_app': card['lockApp'],
                    'desc': card['descript'],
                    'type': 1,
                    'fee': card['balance'] * 100,  # 单位由元转成分
                    'unlimited_time': False,
                    'user_condition': '',
                    'time_out': False,
                    'method': ''
                }
                if card['startTime']:
                    item['start_time'] = card['startTime']
                if card['endTime']:
                    item['end_time'] = card['endTime']
                if card['untimed'] == 1:
                    item['unlimited_time'] = True
                if game_info:
                    item['lock_app'] = game_info['pname']
                value_card_list.append(item)
    except Exception, err:
        service_logger.error(err.message)
        mysql_session.rollback()
    finally:
        mysql_session.close()
    return total_count, value_card_list


def get_user_coupons_by_game(ucid, appid, start_index, end_index):
    from run import mysql_session
    now = int(time.time())
    not_show_coupon_list = get_first_coupon_id_list(ucid, appid)
    if len(not_show_coupon_list) > 0:
        not_show_coupon_list_str = ",".join(not_show_coupon_list)
        get_user_coupon_sql = "select log.coupon_id from zy_coupon_log as log join zy_coupon as coupon " \
                              "on log.coupon_id=coupon.id where coupon.status='normal' and log.is_used = 0 " \
                              "and log.ucid=%s and coupon.id not in (%s) and ( (log.pid=0) or (log.pid=%s) )" \
                              "and ((coupon.is_time = 0) or ((coupon.is_time = 1) " \
                              "and coupon.start_time <= %s " \
                              "and coupon.end_time >= %s )) order by log.id desc limit %s, %s" \
                              % (ucid, not_show_coupon_list_str, appid, now, now, start_index, end_index)
    else:
        get_user_coupon_sql = "select log.coupon_id from zy_coupon_log as log join zy_coupon as coupon " \
                              "on log.coupon_id=coupon.id where coupon.status='normal' and log.is_used = 0 " \
                              "and log.ucid=%s and ( (log.pid=0) or (log.pid=%s) )" \
                              "and ((coupon.is_time = 0) or ((coupon.is_time = 1) " \
                              "and coupon.start_time <= %s " \
                              "and coupon.end_time >= %s)) order by log.id desc limit %s, %s" \
                              % (ucid, appid, now, now, start_index, end_index)
    coupon_list = mysql_session.execute(get_user_coupon_sql).fetchall()
    new_coupon_list = []
    for coupon in coupon_list:
        get_user_coupon_sql = "select * from zy_coupon where id=%s limit 1" % (coupon['coupon_id'])
        coupon_info = mysql_session.execute(get_user_coupon_sql).fetchone()
        game = coupon_info['game']
        desc = "所有游戏"
        game_info = get_game_info_by_appid(game)
        if game_info is not None:
            desc = game_info['name']
        if coupon_info is not None:
            info = {
                'id': coupon_info['id'],
                'name': coupon_info['name'],
                'type': 2,
                'start_time': coupon_info['start_time'],
                'end_time': coupon_info['end_time'],
                'desc': desc,
                'fee': coupon_info['money'],
                'method': coupon_info['method'],
                'user_condition': "满%s可用" % (coupon_info['full'] / 100,),
                'lock_app': '',
                'supportDivide': 0
            }
            if coupon_info['full'] == 0:
                info['user_condition'] = '通用'
            if coupon_info['is_first'] == 1:
                info['user_condition'] = '首充券'
            unlimited_time = True
            if coupon_info['is_time'] == 1:
                unlimited_time = False
            time_out = False
            if coupon_info['is_time'] == 1 and coupon_info['end_time'] < now:
                time_out = True
            info['unlimited_time'] = unlimited_time
            info['time_out'] = time_out
            new_coupon_list.append(info)
    return new_coupon_list


#  获取用户首充之后，剩下的首充券的id
def get_first_coupon_id_list(ucid=None, pid=None):
    from run import mysql_session
    coupon_id_list = []
    get_user_first_coupon_count_sql = "select count(*) from zy_coupon as coupon join zy_coupon_log as log " \
                                      "on coupon.id = log.coupon_id where coupon.is_first = 1 " \
                                      "and log.ucid = %s and  ( (log.pid=0) or (log.pid=%s) ) and log.is_used = 1" % (
                                          ucid, pid)
    get_user_first_coupon_sql = "select distinct(log.coupon_id) from zy_coupon as coupon join zy_coupon_log as log " \
                                "on coupon.id = log.coupon_id where coupon.is_first = 1 " \
                                "and log.ucid = %s and  ( (log.pid=0) or (log.pid=%s) )" % (ucid, pid)
    count = mysql_session.execute(get_user_first_coupon_count_sql).scalar()
    if count > 0:
        coupon_list = mysql_session.execute(get_user_first_coupon_sql).fetchall()
        for coupon in coupon_list:
            coupon_id_list.append(str(coupon['coupon_id']))
    return coupon_id_list


#  用户领取卡券的逻辑
def user_get_coupon(ucid=None, coupon_id=None, app_id=None):
    from run import mysql_session
    now = int(time.time())
    get_coupon_info_sql = "select game, users_type, vip_user, specify_user from zy_coupon where status = 'normal' " \
                          "and ( (is_time=0) or (is_time=1 and start_time <= %s and end_time >= %s) ) and id = %s " \
                          % (now, now, coupon_id)
    coupon_info = mysql_session.execute(get_coupon_info_sql).fetchone()
    if coupon_info is not None:
        if coupon_info['game'] == 0:  # 卡券适用全部游戏
            pass
        else:
            if coupon_info['game'] == app_id:
                pass
            return False
    return False


# 根据appid获取游戏信息
def get_game_info_by_appid(appid=None):
    from run import mysql_session
    find_game_info_sql = "select p.gameCenterId as id, game.name, game.cover from procedures as p, zy_game as game " \
                         "where p.gameCenterId = game.id and p.pid= %s limit 1" % (appid,)
    game_info = mysql_session.execute(find_game_info_sql).fetchone()
    game = {}
    if game_info is not None:
        game['id'] = game_info['id']
        game['name'] = game_info['name']
        game['cover'] = game_info['cover']
        return game
    return None


def get_game_info_by_gameid(gameid=None):
    from run import mysql_session
    find_game_info_sql = "select id, name, cover from zy_game " \
                         "where id = %s limit 1" % (gameid,)
    game_info = mysql_session.execute(find_game_info_sql).fetchone()
    game = {}
    if game_info is not None:
        game['id'] = game_info['id']
        game['name'] = game_info['name']
        game['cover'] = game_info['cover']
        return game
    return None


# 根据token获取用户当前所在的区服
def get_user_current_game_and_area_by_token(token=None):
    from run import mysql_session
    find_user_current_game_area_info_sql = "select s.token, s.zone_id, s.zone_name from session as s " \
                                           "where s.token = %s limit 1" % (token,)
    game_info = mysql_session.execute(find_user_current_game_area_info_sql).fetchone()
    game = {}
    if game_info is not None:
        game['token'] = game_info['token']
        game['zone_id'] = game_info['zone_id']
        game['zone_name'] = game_info['zone_name']
        return game
    return None


# 从mysql中查看是否有用户相关的公告
def get_user_notice_from_mysql(username=None, rtype=None, vip=None, appid=None, cur_zone=None):
    now = int(time.time())
    data_list = UsersMessage.objects(Q(type='notice')
                                     & (
                                         Q(is_time=0) | (Q(is_time=1) & Q(end_time__gte=now))
                                     )
                                     )
    notice_list = []
    for data in data_list:
        # 检查当前游戏
        app_list = data['app']
        for app in app_list:
            if app['apk_id'] == 'all':
                if rtype in data['rtype']:
                    if vip >= int(data['vip'][0]):
                        notice_list.append(data)
                        continue
                if username in data['users']:
                    notice_list.append(data)
                    continue
            if 'zone_id_list' in app:
                zone_id_list = app['zone_id_list']
                for zone in zone_id_list:
                    if zone == cur_zone and app['apk_id'] == appid:
                        if rtype in data['rtype']:
                            if vip >= int(data['vip'][0]):
                                notice_list.append(data)
                                continue
                        if username in data['users']:
                            notice_list.append(data)
                            continue
            else:
                if app['apk_id'] == appid:
                    if rtype in data['rtype']:
                        if vip >= int(data['vip'][0]):
                            notice_list.append(data)
                            continue
                    if username in data['users']:
                        notice_list.append(data)
                        continue
    return notice_list


# 检查消息是否已读
def find_is_message_readed(ucid=None, message_type=None, message_id=None):
    is_exist = UserReadMessageLog.objects(Q(type=message_type)
                                          & Q(message_id=message_id)
                                          & Q(ucid=ucid)).count()
    if is_exist == 0:
        return False
    return True


# 设置消息已读
def set_message_readed(ucid=None, message_type=None, message_id=None):
    is_exist = UserReadMessageLog.objects(Q(type=message_type)
                                          & Q(message_id=message_id)
                                          & Q(ucid=ucid)).count()
    if is_exist == 0:
        user_read_message_log = UserReadMessageLog(type=message_type,
                                                   message_id=message_id,
                                                   ucid=ucid)
        user_read_message_log.save()


def get_user_user_type_and_vip_and_uid_by_ucid(ucid=None):
    from run import mysql_session
    find_users_type_info_sql = "select u.ucid, u.uid, r.rtype from ucusers as u, retailers as r where u.rid = r.rid " \
                               "and u.ucid = %s" % (ucid,)
    user_type_info = mysql_session.execute(find_users_type_info_sql).fetchone()
    if user_type_info is not None:
        find_users_vip_info_sql = "select vip from ucuser_info as u where u.ucid = %s" % (ucid,)
        user_vip_info = mysql_session.execute(find_users_vip_info_sql).fetchone()
        if user_vip_info is not None:
            return user_type_info['rtype'], user_vip_info['vip'], user_type_info['uid']
    return None


# sdk api 请求通用装饰器
def sdk_api_request_check(func):
    @wraps(func)
    def wraper(*args, **kwargs):
        # 数据库连接状态检测
        from run import mysql_session
        from run import mysql_cms_session
        try:
            mysql_session.execute('select 1').scalar()
            mysql_cms_session.execute('select 1').scalar()
        except Exception, err:
            mysql_session.rollback()
            mysql_cms_session.rollback()
            service_logger.error(err.message)
        finally:
            mysql_session.close()
            mysql_cms_session.close()
        from Utils.EncryptUtils import sdk_api_params_check, sdk_api_check_sign
        is_params_checked = sdk_api_params_check(request)
        if is_params_checked is False:
            log_exception(request, '客户端请求错误-appid或sign或token为空')
            return response_data(200, 0, '客户端参数错误')
        # 会话是否过期判断
        is_session_expired = is_session_expired_by_access_token(request.form['_token'])
        if is_session_expired:
            return response_data(200, 102, '用户未登录或session已过期')
        is_sign_true = sdk_api_check_sign(request)
        if is_sign_true is True:
            ucid = get_ucid_by_access_token(request.form['_token'])
            interval = 2000
            if 'interval' in request.form:
                interval = request.form['interval']
            if ucid:
                if find_user_account_is_freeze(ucid):
                    return response_data(200, 101, '账号被冻结')
                hdfs_logger.info("ucid-%s-uri-%s-interval-%s" % (ucid, request.url, interval))
            else:
                log_exception(request, "根据token: %s 获取ucid失败" % (request.form['_token'],))
                return response_data(200, 0, '根据token获取ucid失败')
            return func(*args, **kwargs)
        else:
            return response_data(200, 0, '请求校验错误')
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


# anfeng helper api 请求通用装饰器
def anfeng_helper_request_check(func):
    @wraps(func)
    def wraper(*args, **kwargs):
        from Utils.EncryptUtils import anfeng_helper_api_check_sign
        is_sign_true = anfeng_helper_api_check_sign(request)
        if is_sign_true is True:
            return func(*args, **kwargs)
        else:
            return response_data(200, 0, '请求校验错误')
    return wraper

