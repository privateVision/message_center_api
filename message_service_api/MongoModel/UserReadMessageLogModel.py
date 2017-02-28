# _*_ coding: utf-8 _*_
import datetime
from flask_mongoengine import Document
from mongoengine import IntField, DateTimeField, StringField


class UserReadMessageLog(Document):
    type = StringField(required=True)
    message_id = IntField(required=True)
    ucid = IntField(required=True)
    read_time = DateTimeField(default=datetime.datetime.now)