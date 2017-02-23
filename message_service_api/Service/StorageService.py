# _*_ coding: utf-8 _*_
import json

import time

from Controller import service_logger
from Model.MessageModel import SystemAnnouncements


def system_announcements_persist(data_json=None):
    if data_json is not None:
        system_announcements = SystemAnnouncements()
        system_announcements.id = data_json['id']
        system_announcements.atype = data_json['type']
        system_announcements.show_times = data_json['show_times']
        system_announcements.start_time = data_json['stime']
        system_announcements.end_time = data_json['etime']
        system_announcements.title = data_json['title']
        system_announcements.button_content = data_json['button_content']
        system_announcements.button_type = data_json['button_type']
        system_announcements.url_type = data_json['url_type']
        system_announcements.create_time = data_json['create_time']
        system_announcements.enter_status = data_json['enter_status']
        system_announcements.content = data_json['content']
        system_announcements.sortby = data_json['sortby']
        system_announcements.button_url = data_json['button_url']
        system_announcements.open_type = data_json['open_type']
        system_announcements.img = data_json['img']
        system_announcements.url = data_json['url']
        system_announcements.users = data_json['specify_user'].split(",")
        system_announcements.rtype = data_json['users_type'].split(",")
        system_announcements.app = json.loads(data_json['game'])
        system_announcements.vip = data_json['vip_user']
        system_announcements.expire_at = system_announcements.end_time
        try:
            system_announcements.save()
        except Exception, err:
            service_logger.error(err.message)