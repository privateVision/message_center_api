# _*_ coding: utf-8 _*_
from datetime import datetime

from mongoengine import StringField, IntField, DynamicDocument, DateTimeField, LongField


# MongoDB user_message 对应模型
class UserMessage(DynamicDocument):
    id = StringField(required=True, primary_key=True)
    ucid = LongField(required=True)
    type = StringField(required=True)
    mysql_id = IntField(required=True)
    closed = IntField(required=True, default=0)
    is_read = IntField(required=True, default=0)
    create_time = DateTimeField(required=True, default=datetime.now())

