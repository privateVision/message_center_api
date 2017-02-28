# _*_ coding: utf-8 _*_
import json

from mongoengine import Q

from Controller import service_logger
from MongoModel.MessageRevocationModel import MessageRevocation
from Service.StorageService import system_announcements_persist, system_broadcast_persist, system_message_persist, \
    system_coupon_persist


def kafka_consume_func(kafka_consumer):
    for msg in kafka_consumer:
        consume_handler(msg)


def consume_handler(message=None):
    message_info = json.loads(message.value)
    service_logger.info(message_info['type'])
    service_logger.info(message_info['message'])
    check_result = message_revocation_check(message_info['type'], message_info['message']['id'])
    if check_result:
        service_logger.info("该消息已被撤回，停止发送！")
        return
    if message_info['type'] == 'notice':
        system_announcements_persist(message_info['message'])
    elif message_info['type'] == 'broadcast':
        system_broadcast_persist(message_info['message'])
    elif message_info['type'] == 'message':
        system_message_persist(message_info['message'])
    elif message_info['type'] == 'coupon':
        system_coupon_persist(message_info['message'])
    else:
        pass


def message_revocation_check(message_type=None, message_id=None):
    is_exist = MessageRevocation.objects(Q(type=message_type) & Q(mysql_id=message_id)).count()
    if is_exist > 0:
        return True
    return False

