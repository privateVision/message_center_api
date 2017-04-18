# _*_ coding: utf-8 _*_
from datetime import datetime
import time

from mongoengine import StringField, IntField, DynamicDocument, DateTimeField, LongField


# MongoDB user_message 对应模型
class UserMessage(DynamicDocument):
    meta = {
        'collection': 'sdk_api_user_message',
        'indexes': [
            {'fields': ['expireAt'], 'expireAfterSeconds': 0}
        ]
    }
    id = StringField(required=True, primary_key=True)
    ucid = LongField(required=True)
    type = StringField(required=True)
    mysql_id = IntField(required=True)
    closed = IntField(required=True, default=0)
    is_read = IntField(required=True, default=0)
    start_time = IntField(required=True, default=0)
    end_time = IntField(required=True, default=0)
    # show_times = IntField(required=True, default=0)
    # read_times = IntField(required=True, default=0)
    create_time = DateTimeField(required=True, default=datetime.now())
    create_timestamp = IntField(required=True, default=0)

