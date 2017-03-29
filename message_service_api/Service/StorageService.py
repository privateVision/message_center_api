# _*_ coding: utf-8 _*_
import json
import threading

import time

import datetime
from mongoengine import Q

from MiddleWare import service_logger
from MongoModel.MessageModel import UsersMessage
from MongoModel.UserMessageModel import UserMessage
from Service.NotifyEmail import send_notify
from Service.UsersService import get_game_and_area_and_user_type_and_vip_users
from Utils.RedisUtil import RedisHandle


# def add_message_to_user_message_list(game, users_type, vip_user, specify_user, type, msg_id,
#                                      start_time, end_time, is_time):
#     users_list = get_game_and_area_and_user_type_and_vip_users(game, users_type, vip_user)
#     specify_user_list = get_ucid_list_by_user_uid_name_list(specify_user)
#     users_list.extend(specify_user_list)
#     try:
#         for user in users_list:
#             user_message = UserMessage()
#             user_message.id = "%s%s%s" % (user, type, msg_id)
#             user_message.ucid = user
#             user_message.type = type
#             user_message.mysql_id = msg_id
#             user_message.start_time = start_time
#             user_message.end_time = end_time
#             user_message.is_time = is_time
#             user_message.expireAt = datetime.datetime.utcfromtimestamp(user_message.end_time)
#             user_message.save()
#             add_mark_to_user_redis(user, type)
#     except Exception, err:
#         send_notify("添加消息到每个用户的消息列表发生异常：%s" % (err.message,))
#         service_logger.error("添加消息到每个用户的消息列表发生异常：%s" % (err.message,))


#  目标用户太多，改成分批发送
def add_message_to_user_message_list(game, users_type, vip_user, specify_user, type, msg_id,
                                     start_time, end_time, is_time):
    send_message_to_game_area_and_user_type_and_vip_users(game, users_type, vip_user, type, msg_id,
                                                          is_time, start_time, end_time)
    send_message_to_spcify_users(specify_user, game, type, msg_id, is_time, start_time, end_time)


#  分批向指定游戏区服的各种用户类型及等级发送消息
def send_message_to_game_area_and_user_type_and_vip_users(game, users_type, vip_user, type, msg_id,
                                                          is_time, start_time, end_time):
    if game is not None:
        from run import mysql_session
        # 不是所有游戏区服
        if game[0]['apk_id'] != 'all':
            for game_info in game:
                if game_info.has_key('zone_id_list'):
                    for zone in game_info['zone_id_list']:
                        find_user_count_in_game_area_sql = "select count(distinct(ucid)) from roleDatas where vid = %s " \
                                                           "and zoneName = '%s'" \
                                                           % (game_info['apk_id'], zone)
                        try:
                            total_count = mysql_session.execute(find_user_count_in_game_area_sql).scalar()
                            total_page = int(total_count / 100) + 1
                            for i in range(total_page):
                                start_index = i * 100
                                find_users_in_game_area_sql = "select distinct(ucid) from roleDatas where vid = %s " \
                                                              "and zoneName = '%s' limit %s, 100 " \
                                                              % (game_info['apk_id'], zone, start_index)
                                tmp_user_list = mysql_session.execute(find_users_in_game_area_sql).fetchall()
                                for item in tmp_user_list:
                                    ucid = item['ucid']
                                    is_right = check_user_type_and_vip(ucid, users_type, vip_user[0])
                                    if is_right:
                                        add_user_messsage(ucid, type, msg_id, is_time, start_time, end_time, game)
                        except Exception, err:
                            service_logger.error(err.message)
                            mysql_session.rollback()
                        finally:
                            mysql_session.close()
                else:  # 没传区服信息，那就所有区服咯
                    find_user_count_in_game_area_sql = "select count(distinct(ucid)) from roleDatas where vid = %s " \
                                                       % (game_info['apk_id'])
                    try:
                        total_count = mysql_session.execute(find_user_count_in_game_area_sql).scalar()
                        total_page = int(total_count / 100) + 1
                        for i in range(total_page):
                            start_index = i * 100
                            find_users_in_game_area_sql = "select distinct(ucid) from roleDatas where vid = %s " \
                                                          "limit %s, 100" % (game_info['apk_id'], start_index)
                            tmp_user_list = mysql_session.execute(find_users_in_game_area_sql).fetchall()
                            for item in tmp_user_list:
                                ucid = item['ucid']
                                is_right = check_user_type_and_vip(ucid, users_type, vip_user[0])
                                if is_right:
                                    add_user_messsage(ucid, type, msg_id, is_time, start_time, end_time, game)
                    except Exception, err:
                        service_logger.error(err.message)
                        mysql_session.rollback()
                    finally:
                        mysql_session.close()
        else:  # 所有游戏，太可怕了
            find_user_count_in_game_area_sql = "select count(distinct(ucid)) from roleDatas"

            try:
                total_count = mysql_session.execute(find_user_count_in_game_area_sql).scalar()
                total_page = int(total_count / 100) + 1
                for i in range(total_page):
                    start_index = i * 100
                    find_all_game_users_sql = "select distinct(ucid) from roleDatas limit %s, 100 " \
                                              % (start_index)
                    tmp_user_list = mysql_session.execute(find_all_game_users_sql).fetchall()
                    for item in tmp_user_list:
                        ucid = item['ucid']
                        is_right = check_user_type_and_vip(ucid, users_type, vip_user[0])
                        if is_right:
                            add_user_messsage(ucid, type, msg_id, is_time, start_time, end_time, game)
            except Exception, err:
                service_logger.error(err.message)
                mysql_session.rollback()
            finally:
                mysql_session.close()


# 指定的用户就一起发送算了
def send_message_to_spcify_users(specify_user, game, type, msg_id, is_time, start_time, end_time):
    specify_user_list = get_ucid_list_by_user_uid_name_list(specify_user)
    if game is not None:
        if game[0]['apk_id'] != 'all':
            for game_info in game:
                if game_info.has_key('zone_id_list'):
                    for zone in game_info['zone_id_list']:
                        try:
                            for user in specify_user_list:
                                is_right = check_user_is_in_game(user, game_info['apk_id'], zone)
                                if is_right:
                                    add_user_messsage(user, type, msg_id, is_time, start_time, end_time, game)
                        except Exception, err:
                            send_notify("添加消息到每个用户的消息列表发生异常：%s" % (err.message,))
                            service_logger.error("添加消息到每个用户的消息列表发生异常：%s" % (err.message,))
        else:
            try:
                for user in specify_user_list:
                    add_user_messsage(user, type, msg_id, is_time, start_time, end_time, game)
            except Exception, err:
                send_notify("添加消息到每个用户的消息列表发生异常：%s" % (err.message,))
                service_logger.error("添加消息到每个用户的消息列表发生异常：%s" % (err.message,))


def add_user_messsage(ucid, type, msg_id, is_time, start_time, end_time, game):
    if type != 'coupon':
        service_logger.info("mongo添加用户消息：%s-%s-%s" % (ucid, type, msg_id))
        user_message = UserMessage()
        user_message.id = "%s%s%s" % (ucid, type, msg_id)
        user_message.ucid = ucid
        user_message.type = type
        user_message.mysql_id = msg_id
        user_message.start_time = start_time
        user_message.end_time = end_time
        user_message.is_time = is_time
        user_message.expireAt = datetime.datetime.utcfromtimestamp(user_message.end_time)
        user_message.save()
        add_mark_to_user_redis(ucid, type)
    else:
        service_logger.info("mysql添加用户消息：%s-%s-%s" % (ucid, type, msg_id))
        from run import mysql_session
        pid = 0
        if game[0]['apk_id'] != 'all':
            pid = game[0]['apk_id']
        try:
            insert_user_coupon_sql = "insert into zy_coupon_log(ucid, coupon_id, pid, is_time, start_time, end_time)" \
                                     " values(%s, %s, %s, %s, %s, %s)" \
                                     % (ucid, msg_id, pid, is_time, start_time, end_time)
            mysql_session.execute(insert_user_coupon_sql)
            mysql_session.commit()
        except Exception, err:
            send_notify("添加卡券到每个用户的mysql列表发生异常：%s" % (err.message,))
            service_logger.error("添加卡券到每个用户的mysql列表发生异常：%s" % (err.message,))
            mysql_session.rollback()
        finally:
            mysql_session.close()


# 检查用户的类型和vip是否符合
def check_user_type_and_vip(ucid=None, user_type=None, vip=None):
    from run import mysql_session
    user_type_str = ",".join(user_type)
    find_users_by_user_type_sql = "select count(*) from ucusers as u, retailers as r where u.rid = r.rid " \
                                  "and r.rtype in (%s) and u.ucid = %s " % (user_type_str, ucid)
    find_users_by_vip_sql = "select count(*) from user as u where u.ucid = %s and u.vip >= %s " % (ucid, vip)
    try:
        is_exist = mysql_session.execute(find_users_by_user_type_sql).scalar()
        if is_exist is None or is_exist == 0:
            return False
        is_exist = mysql_session.execute(find_users_by_vip_sql).scalar()
        if is_exist is None or is_exist == 0:
            return False
        return True
    except Exception, err:
        service_logger.error(err.message)
        mysql_session.rollback()
    finally:
        mysql_session.close()


# 检查用户是否在某个游戏下
def check_user_is_in_game(ucid=None, game_id=None, zone=None):
    from run import mysql_session
    if zone is None:
        find_users_by_game_sql = "select count(*) from roleDatas as u where u.ucid = %s and u.vid = %s " \
                                 % (ucid, game_id)
    else:
        find_users_by_game_sql = "select count(*) from roleDatas as u where u.ucid = %s and u.vid = %s " \
                                 "and u.zoneName = '%s' " % (ucid, game_id, zone)
    try:
        is_exist = mysql_session.execute(find_users_by_game_sql).scalar()
        if is_exist is None or is_exist == 0:
            return False
        return True
    except Exception, err:
        service_logger.error(err.message)
        mysql_session.rollback()
    finally:
        mysql_session.close()


# 根据用户名获取用户ucid
def get_ucid_list_by_user_uid_name_list(specify_user):
    ucid_list = []
    from run import mysql_session
    if specify_user is not None and specify_user != '':
        for uid in specify_user:
            find_ucid_sql = "select ucid from user where uid = '%s'" % (uid,)
            try:
                user_info = mysql_session.execute(find_ucid_sql).fetchone()
                if user_info:
                    ucid_list.append(user_info['ucid'])
            except Exception, err:
                service_logger.error(err.message)
                mysql_session.rollback()
            finally:
                mysql_session.close()
    return ucid_list


# 给用户在redis中设置标记位
def add_mark_to_user_redis(ucid, message_type):
    if message_type == 'message':
        RedisHandle.hincrby(ucid, message_type, 1)  # 自增数量，表示消息的未读数量


def add_to_every_related_users_message_list(users_message):
    if 'distribute' not in users_message:
        users_message.distribute = None
    add_user_message_thread = threading.Thread(target=add_message_to_user_message_list,
                                               args=(users_message.app, users_message.rtype,
                                                     users_message.vip, users_message.users,
                                                     users_message.type, users_message.mysql_id,
                                                     users_message.start_time, users_message.end_time,
                                                     users_message.is_time))
    add_user_message_thread.setDaemon(True)
    add_user_message_thread.start()
    # if users_message.type != 'coupon':
    #     add_user_message_thread = threading.Thread(target=add_message_to_user_message_list,
    #                                                args=(users_message.app, users_message.rtype,
    #                                                      users_message.vip, users_message.users,
    #                                                      users_message.type, users_message.mysql_id,
    #                                                      users_message.start_time, users_message.end_time,
    #                                                      users_message.is_time))
    #     add_user_message_thread.setDaemon(True)
    #     add_user_message_thread.start()
    # else:
    #     #  卡券的数据存放到mysql
    #     from run import mysql_session
    #     pid = 0
    #     if users_message.app[0]['apk_id'] != 'all':
    #         pid = users_message.app[0]['apk_id']
    #     users_list = get_game_and_area_and_user_type_and_vip_users(users_message.app, users_message.rtype, users_message.vip)
    #     specify_user_list = get_ucid_list_by_user_uid_name_list(users_message.users)
    #     users_list.extend(specify_user_list)
    #     try:
    #         for user in users_list:
    #             insert_user_coupon_sql = "insert into zy_coupon_log values(%s, %s, %s, %s, %s, %s)" \
    #                                      % (user, users_message.mysql_id, pid, users_message.is_time,
    #                                         users_message.start_time, users_message.end_time)
    #             mysql_session.execute(insert_user_coupon_sql)
    #         mysql_session.commit()
    #     except Exception, err:
    #         send_notify("添加卡券到每个用户的mysql列表发生异常：%s" % (err.message,))
    #         service_logger.error("添加卡券到每个用户的mysql列表发生异常：%s" % (err.message,))
    #         mysql_session.rollback()
    #     finally:
    #         mysql_session.close()


# 添加公告信息，并完成用户分发
def system_notices_persist(data_json=None):
    if data_json is not None:
        users_message = UsersMessage()
        users_message.id = '%s%s' % ('notice', data_json['id'])
        users_message.mysql_id = data_json['id']
        users_message.type = 'notice'
        users_message.atype = data_json['type']
        users_message.show_times = data_json['show_times']
        users_message.start_time = data_json['stime']
        users_message.end_time = data_json['etime']
        users_message.title = data_json['title']
        users_message.button_content = data_json['button_content']
        users_message.button_type = data_json['button_type']
        users_message.url_type = data_json['url_type']
        users_message.create_time = data_json['create_time']
        users_message.enter_status = data_json['enter_status']
        users_message.content = data_json['content']
        users_message.sortby = data_json['sortby']
        users_message.button_url = data_json['button_url']
        users_message.open_type = data_json['open_type']
        users_message.img = data_json['img']
        users_message.url = data_json['url']
        users_message.users = None
        if 'specify_user' in data_json and data_json['specify_user'] is not None:
            users_message.users = data_json['specify_user'].split(",")
        users_message.rtype = None
        if 'users_type' in data_json and data_json['users_type'] is not None:
            users_message.rtype = data_json['users_type'].split(",")
        users_message.app = json.loads(data_json['game'])
        users_message.vip = None
        if 'vip_user' in data_json and data_json['vip_user'] is not None:
            users_message.vip = data_json['vip_user'].split(",")
        users_message.is_time = 1
        users_message.expireAt = datetime.datetime.utcfromtimestamp(users_message.end_time)
        try:
            users_message.save()
        except Exception, err:
            service_logger.error("mongodb保存公告异常：%s" % (err.message,))
            return
        add_to_every_related_users_message_list(users_message)


def system_notices_update(data_json=None):
    if data_json is not None:
        users_message = UsersMessage()
        users_message.id = '%s%s' % ('notice', data_json['id'])
        users_message.mysql_id = data_json['id']
        users_message.type = 'notice'
        users_message.atype = data_json['type']
        users_message.show_times = data_json['show_times']
        users_message.start_time = data_json['stime']
        users_message.end_time = data_json['etime']
        users_message.title = data_json['title']
        users_message.button_content = data_json['button_content']
        users_message.button_type = data_json['button_type']
        users_message.url_type = data_json['url_type']
        users_message.create_time = data_json['create_time']
        users_message.enter_status = data_json['enter_status']
        users_message.content = data_json['content']
        users_message.sortby = data_json['sortby']
        users_message.button_url = data_json['button_url']
        users_message.open_type = data_json['open_type']
        users_message.img = data_json['img']
        users_message.url = data_json['url']
        users_message.users = None
        if 'specify_user' in data_json and data_json['specify_user'] is not None:
            users_message.users = data_json['specify_user'].split(",")
        users_message.rtype = None
        if 'users_type' in data_json and data_json['users_type'] is not None:
            users_message.rtype = data_json['users_type'].split(",")
        users_message.app = json.loads(data_json['game'])
        users_message.vip = None
        if 'vip_user' in data_json and data_json['vip_user'] is not None:
            users_message.vip = data_json['vip_user'].split(",")
        users_message.is_time = 1
        users_message.expireAt = datetime.datetime.utcfromtimestamp(users_message.end_time)
        try:
            users_message.save()
            UserMessage.objects(Q(type=users_message.type) & Q(mysql_id=users_message.mysql_id)).update(
                start_time=users_message.start_time,
                end_time=users_message.end_time,
                upsert=False)
        except Exception, err:
            service_logger.error("mongodb保存公告异常：%s" % (err.message,))


def system_broadcast_persist(data_json=None):
    if data_json is not None:
        users_message = UsersMessage()
        users_message.id = '%s%s' % ('broadcast', data_json['id'])
        users_message.mysql_id = data_json['id']
        users_message.type = 'broadcast'
        users_message.title = data_json['title']
        users_message.content = data_json['content']
        users_message.start_time = data_json['stime']
        users_message.end_time = int(data_json['stime']) + 30
        users_message.close_time = data_json['close_time']
        users_message.users = None
        if 'specify_user' in data_json and data_json['specify_user'] is not None:
            users_message.users = data_json['specify_user'].split(",")
        users_message.rtype = None
        if 'users_type' in data_json and data_json['users_type'] is not None:
            users_message.rtype = data_json['users_type'].split(",")
        users_message.app = json.loads(data_json['game'])
        users_message.vip = None
        if 'vip_user' in data_json and data_json['vip_user'] is not None:
            users_message.vip = data_json['vip_user'].split(",")
        users_message.is_time = 1
        users_message.expireAt = datetime.datetime.utcfromtimestamp(users_message.end_time)
        try:
            users_message.save()
        except Exception, err:
            service_logger.error("mongodb保存广播异常：%s" % (err.message,))
            return
        add_to_every_related_users_message_list(users_message)


def system_broadcast_update(data_json=None, update_user_message=True):
    if data_json is not None:
        users_message = UsersMessage()
        users_message.id = '%s%s' % ('broadcast', data_json['id'])
        users_message.mysql_id = data_json['id']
        users_message.type = 'broadcast'
        users_message.title = data_json['title']
        users_message.content = data_json['content']
        users_message.start_time = data_json['stime']
        users_message.end_time = int(data_json['stime']) + 30
        users_message.close_time = data_json['close_time']
        users_message.users = None
        if 'specify_user' in data_json and data_json['specify_user'] is not None:
            users_message.users = data_json['specify_user'].split(",")
        users_message.rtype = None
        if 'users_type' in data_json and data_json['users_type'] is not None:
            users_message.rtype = data_json['users_type'].split(",")
        users_message.app = json.loads(data_json['game'])
        users_message.vip = None
        if 'vip_user' in data_json and data_json['vip_user'] is not None:
            users_message.vip = data_json['vip_user'].split(",")
        users_message.is_time = 1
        users_message.expireAt = datetime.datetime.utcfromtimestamp(users_message.end_time)
        try:
            users_message.save()
            UserMessage.objects(Q(type=users_message.type) & Q(mysql_id=users_message.mysql_id)).update(
                start_time=users_message.start_time,
                end_time=users_message.end_time,
                upsert=False)
        except Exception, err:
            service_logger.error("mongodb保存广播异常：%s" % (err.message,))
            return
        if update_user_message:
            add_to_every_related_users_message_list(users_message)


def system_message_persist(data_json=None, update_user_message=True):
    if data_json is not None:
        users_message = UsersMessage()
        users_message.id = '%s%s' % ('message', data_json['id'])
        users_message.mysql_id = data_json['id']
        users_message.type = 'message'
        if data_json['msg_type'] == 'image_text':
            users_message.atype = 2
        if data_json['msg_type'] == 'html_text':
            users_message.atype = 1
        users_message.title = data_json['title']
        users_message.description = data_json['description']
        users_message.content = data_json['content']
        users_message.img = data_json['img']
        users_message.url = data_json['url']
        if data_json['send_time'] == 0:
            users_message.start_time = int(time.time())
        else:
            users_message.start_time = data_json['send_time']
        users_message.sys = data_json['sys']
        users_message.users = None
        if 'specify_user' in data_json and data_json['specify_user'] is not None:
            users_message.users = data_json['specify_user'].split(",")
        users_message.rtype = None
        if 'users_type' in data_json and data_json['users_type'] is not None:
            users_message.rtype = data_json['users_type'].split(",")
        users_message.app = json.loads(data_json['game'])
        users_message.vip = None
        if 'vip_user' in data_json and data_json['vip_user'] is not None:
            users_message.vip = data_json['vip_user'].split(",")
        users_message.is_time = 1
        users_message.expireAt = datetime.datetime.utcfromtimestamp(users_message.end_time)
        try:
            users_message.save()
        except Exception, err:
            service_logger.error("mongodb保存消息异常：%s" % (err.message,))
            return
        if update_user_message:
            add_to_every_related_users_message_list(users_message)


def system_coupon_persist(data_json=None):
    if data_json is not None:
        users_message = UsersMessage()
        users_message.distribute = int(time.time())
        users_message.id = '%s%s%s' % ('coupon', data_json['id'], users_message.distribute)
        users_message.mysql_id = data_json['id']
        users_message.type = 'coupon'
        users_message.name = data_json['name']
        users_message.is_time = data_json['is_time']
        users_message.start_time = data_json['start_time']
        users_message.end_time = data_json['end_time']
        users_message.is_first = data_json['is_first']
        users_message.info = data_json['info']
        users_message.num = data_json['num']
        users_message.full = data_json['full']
        users_message.money = data_json['money']
        users_message.method = data_json['method']
        users_message.users = None
        if 'specify_user' in data_json and data_json['specify_user'] is not None:
            users_message.users = data_json['specify_user'].split(",")
        users_message.rtype = None
        if 'users_type' in data_json and data_json['users_type'] is not None:
            users_message.rtype = data_json['users_type'].split(",")
        users_message.app = json.loads(data_json['game'])
        users_message.vip = None
        if 'vip_user' in data_json and data_json['vip_user'] is not None:
            users_message.vip = data_json['vip_user'].split(",")
        users_message.expireAt = datetime.datetime.utcfromtimestamp(users_message.end_time)
        try:
            users_message.save()
        except Exception, err:
            service_logger.error("mongodb保存卡券异常：%s" % (err.message,))
            return
        add_to_every_related_users_message_list(users_message)


def system_coupon_update(data_json=None):
    if data_json is not None:
        users_message = UsersMessage()
        users_message.id = '%s%s' % ('coupon', data_json['id'])
        users_message.mysql_id = data_json['id']
        users_message.type = 'coupon'
        users_message.name = data_json['name']
        users_message.is_time = data_json['is_time']
        users_message.start_time = data_json['start_time']
        users_message.end_time = data_json['end_time']
        users_message.is_first = data_json['is_first']
        users_message.info = data_json['info']
        users_message.num = data_json['num']
        users_message.full = data_json['full']
        users_message.money = data_json['money']
        users_message.method = data_json['method']
        users_message.users = None
        if 'specify_user' in data_json and data_json['specify_user'] is not None:
            users_message.users = data_json['specify_user'].split(",")
        users_message.rtype = None
        if 'users_type' in data_json and data_json['users_type'] is not None:
            users_message.rtype = data_json['users_type'].split(",")
        users_message.app = json.loads(data_json['game'])
        users_message.vip = None
        if 'vip_user' in data_json and data_json['vip_user'] is not None:
            users_message.vip = data_json['vip_user'].split(",")
        users_message.expireAt = datetime.datetime.utcfromtimestamp(users_message.end_time)
        try:
            users_message.save()
            UserMessage.objects(Q(type=users_message.type) & Q(mysql_id=users_message.mysql_id)).update(
                start_time=users_message.start_time,
                end_time=users_message.end_time,
                upsert=False)
        except Exception, err:
            service_logger.error("mongodb保存卡券异常：%s" % (err.message,))


def system_rebate_persist(data_json=None, update_user_message=True):
    if data_json is not None:
        users_message = UsersMessage()
        users_message.id = '%s%s' % ('rebate', data_json['id'])
        users_message.mysql_id = data_json['id']
        users_message.type = 'rebate'
        users_message.title = data_json['title']
        users_message.content = data_json['content']
        users_message.start_time = data_json['stime']
        users_message.end_time = data_json['etime']
        users_message.rule = json.loads(data_json['rule'])
        users_message.users = None
        if 'specify_user' in data_json and data_json['specify_user'] is not None:
            users_message.users = data_json['specify_user'].split(",")
        users_message.rtype = None
        if 'users_type' in data_json and data_json['users_type'] is not None:
            users_message.rtype = data_json['users_type'].split(",")
        users_message.vip = None
        users_message.expire_at = users_message.end_time
        users_message.app = None
        users_message.is_time = 1
        try:
            users_message.save()
        except Exception, err:
            service_logger.error("mongodb保存优惠券异常：%s" % (err.message,))
        if update_user_message:
            add_to_every_related_users_message_list(users_message)
