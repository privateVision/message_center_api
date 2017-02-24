# _*_ coding: utf-8 _*_
import json

import time

from mongoengine import Q

from Controller import service_logger
from MongoModel.MessageModel import UsersMessage


def system_announcements_persist(data_json=None):
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
        users_message.expire_at = users_message.end_time
        try:
            users_message.save()
        except Exception, err:
            service_logger.error(err.message)


def system_broadcast_persist(data_json=None):
    if data_json is not None:
        users_message = UsersMessage()
        users_message.id = '%s%s' % ('broadcast', data_json['id'])
        users_message.mysql_id = data_json['id']
        users_message.type = 'broadcast'
        users_message.title = data_json['title']
        users_message.content = data_json['content']
        users_message.start_time = data_json['stime']
        users_message.end_time = data_json['etime']
        users_message.close_time = data_json['close_time']
        users_message.users = data_json['specify_user'].split(",")
        users_message.rtype = data_json['users_type'].split(",")
        users_message.app = json.loads(data_json['game'])
        users_message.vip = data_json['vip_user'].split(",")
        users_message.expire_at = users_message.end_time
        try:
            users_message.save()
        except Exception, err:
            service_logger.error(err.message)


def system_message_persist(data_json=None):
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
        users_message.dis_time = data_json['send_time']
        users_message.sys = data_json['sys']
        users_message.users = data_json['specify_user'].split(",")
        users_message.rtype = data_json['users_type'].split(",")
        users_message.app = json.loads(data_json['game'])
        users_message.vip = data_json['vip_user'].split(",")
        users_message.expire_at = users_message.dis_time
        try:
            users_message.save()
        except Exception, err:
            service_logger.error(err.message)