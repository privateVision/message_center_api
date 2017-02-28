# _*_ coding: utf-8 _*_
import json

from Controller import service_logger
from Service.StorageService import system_announcements_persist, system_broadcast_persist, system_message_persist, \
    system_coupon_persist


def kafka_consume_func(kafka_consumer):
    for msg in kafka_consumer:
        consume_handler(msg)


def consume_handler(message=None):
    message_info = json.loads(message.value)
    service_logger.info(message_info['type'])
    service_logger.info(message.value)
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


