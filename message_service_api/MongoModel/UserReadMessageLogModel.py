# _*_ coding: utf-8 _*_
import datetime
from flask_mongoengine import Document
from mongoengine import IntField, DateTimeField, StringField


class UserReadMessageLog(Document):
    meta = {'collection': 'sdk_api_user_read_message_log'}
    type = StringField(required=True)
    message_id = IntField(required=True)
    ucid = IntField(required=True)
    read_time = DateTimeField(default=datetime.datetime.now)