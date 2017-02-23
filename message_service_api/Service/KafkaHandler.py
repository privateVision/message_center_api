# _*_ coding: utf-8 _*_
import json

from Controller import service_logger
from Service.StorageService import system_announcements_persist


def kafka_consume_func(kafka_consumer):
    for msg in kafka_consumer:
        ConsumeHandler(msg)


def ConsumeHandler(message=None):
    service_logger.info(message.value)
    message_info = json.loads(message.value)
    service_logger.info(message_info['type'])
    service_logger.info(message_info['message'])
    if message_info['type'] == 'notice':
        system_announcements_persist(message_info['message'])
    elif message_info['type'] == 'broadcast':
        pass
    elif message_info['type'] == 'message':
        pass
    elif message_info['type'] == 'coupon':
        pass
    else:
        pass


