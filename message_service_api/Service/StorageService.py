# _*_ coding: utf-8 _*_
import json
import threading

from MiddleWare import service_logger, redis_store
from MongoModel.MessageModel import UsersMessage
from MongoModel.UserMessageModel import UserMessage
from Service.UsersService import get_game_and_area_and_user_type_and_vip_users


def add_message_to_user_message_list(game, users_type, vip_user, specify_user, type, msg_id,
                                     start_time, end_time, is_time):
    users_list = get_game_and_area_and_user_type_and_vip_users(game, users_type, vip_user)
    users_list.extend(specify_user)
    try:
        for user in users_list:
            user_message = UserMessage()
            user_message.id = "%s%s%s" % (user, type, msg_id)
            user_message.ucid = user
            user_message.type = type
            user_message.mysql_id = msg_id
            user_message.start_time = start_time
            user_message.end_time = end_time
            user_message.is_time = is_time
            user_message.save()
            add_mark_to_user_redis(user, type)
    except Exception, err:
        service_logger.error("添加消息到每个用户的消息列表发生异常：%s" % (err.message,))


# 给用户在redis中设置标记位
def add_mark_to_user_redis(ucid, message_type):
    redis_store.hset(ucid, message_type, 1)


def add_to_every_related_users_message_list(users_message):
    add_user_message_thread = threading.Thread(target=add_message_to_user_message_list,
                                               args=(users_message.app, users_message.rtype,
                                                     users_message.vip, users_message.users,
                                                     users_message.type, users_message.mysql_id,
                                                     users_message.start_time, users_message.end_time,
                                                     users_message.is_time))
    add_user_message_thread.setDaemon(True)
    add_user_message_thread.start()


def system_announcements_persist(data_json=None, update_user_message=True):
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
        users_message.users = data_json['specify_user'].split(",")
        users_message.rtype = data_json['users_type'].split(",")
        users_message.app = json.loads(data_json['game'])
        users_message.vip = data_json['vip_user'].split(",")
        users_message.is_time = 1
        users_message.expire_at = users_message.end_time
        try:
            users_message.save()
        except Exception, err:
            service_logger.error("mongodb保存公告异常：%s" % (err.message,))
        if update_user_message:
            add_to_every_related_users_message_list(users_message)


def system_broadcast_persist(data_json=None, update_user_message=True):
    if data_json is not None:
        users_message = UsersMessage()
        users_message.id = '%s%s' % ('broadcast', data_json['id'])
        users_message.mysql_id = data_json['id']
        users_message.type = 'broadcast'
        users_message.title = data_json['title']
        users_message.content = data_json['content']
        users_message.start_time = data_json['stime']
        # users_message.end_time = data_json['etime']
        users_message.end_time = int(data_json['stime']) + 5
        users_message.close_time = data_json['close_time']
        users_message.users = data_json['specify_user'].split(",")
        users_message.rtype = data_json['users_type'].split(",")
        users_message.app = json.loads(data_json['game'])
        users_message.vip = data_json['vip_user'].split(",")
        users_message.is_time = 1
        users_message.expire_at = users_message.end_time
        try:
            users_message.save()
        except Exception, err:
            service_logger.error("mongodb保存广播异常：%s" % (err.message,))
        if update_user_message:
            add_to_every_related_users_message_list(users_message)


def system_message_persist(data_json=None, update_user_message=True):
    if data_json is not None:
        users_message = UsersMessage()
        users_message.id = '%s%s' % ('message', data_json['id'])
        users_message.mysql_id = data_json['id']
        users_message.type = 'message'
        users_message.title = data_json['title']
        users_message.description = data_json['description']
        users_message.content = data_json['content']
        users_message.img = data_json['img']
        users_message.url = data_json['url']
        users_message.start_time = data_json['send_time']
        users_message.end_time = int(data_json['send_time']) + 5
        users_message.sys = data_json['sys']
        users_message.users = data_json['specify_user'].split(",")
        users_message.rtype = data_json['users_type'].split(",")
        users_message.app = json.loads(data_json['game'])
        users_message.vip = data_json['vip_user'].split(",")
        users_message.is_time = 1
        users_message.expire_at = users_message.end_time
        try:
            users_message.save()
        except Exception, err:
            service_logger.error("mongodb保存消息异常：%s" % (err.message,))
        if update_user_message:
            add_to_every_related_users_message_list(users_message)


def system_coupon_persist(data_json=None, update_user_message=True):
    if data_json is not None:
        users_message = UsersMessage()
        users_message.id = '%s%s' % ('coupon', data_json['id'])
        users_message.mysql_id = data_json['id']
        users_message.type = 'coupon'
        users_message.title = data_json['title']
        users_message.is_time = data_json['is_time']
        users_message.start_time = data_json['stime']
        users_message.end_time = data_json['etime']
        users_message.is_first = data_json['is_first']
        users_message.info = data_json['info']
        users_message.num = data_json['num']
        users_message.full = data_json['full']
        users_message.money = data_json['money']
        users_message.method = data_json['method']
        users_message.users = data_json['specify_user'].split(",")
        users_message.rtype = data_json['users_type'].split(",")
        users_message.app = json.loads(data_json['game'])
        users_message.vip = data_json['vip_user'].split(",")
        users_message.expire_at = users_message.end_time
        try:
            users_message.save()
        except Exception, err:
            service_logger.error("mongodb保存卡券异常：%s" % (err.message,))
        if update_user_message:
            add_to_every_related_users_message_list(users_message)


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
        users_message.rules = json.loads(data_json['rules'])
        users_message.users = data_json['specify_user'].split(",")
        users_message.rtype = data_json['users_type'].split(",")
        users_message.vip = data_json['vip_user'].split(",")
        users_message.expire_at = users_message.end_time
        users_message.app = None
        try:
            users_message.save()
        except Exception, err:
            service_logger.error("mongodb保存优惠券异常：%s" % (err.message,))
        if update_user_message:
            add_to_every_related_users_message_list(users_message)
