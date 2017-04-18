# _*_ coding: utf-8 _*_
import time
from mongoengine import StringField, IntField, DynamicDocument


# MongoDB users_message 对应模型
class UsersMessage(DynamicDocument):
    meta = {
        'collection': 'sdk_api_users_message',
        'indexes': [
            {'fields': ['expireAt'], 'expireAfterSeconds': 0}
        ]
    }
    id = StringField(required=True, primary_key=True)
    mysql_id = IntField(required=True)
    type = StringField(required=True)
    closed = IntField(required=True, default=0)
    name = StringField()
    start_time = IntField(required=True, default=0)
    end_time = IntField(required=True, default=0)
    show_times = IntField(required=True, default=0)
    # create_timestamp = IntField(required=True, default=int(time.time()))

