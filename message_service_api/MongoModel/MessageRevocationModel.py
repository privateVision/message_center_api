# _*_ coding: utf-8 _*_
from datetime import datetime
import time

from mongoengine import StringField, DynamicDocument, IntField, DateTimeField


# MongoDB MessageRevocationModel 对应模型
class MessageRevocation(DynamicDocument):
    meta = {'collection': 'sdk_api_message_revocation'}
    id = StringField(required=True, primary_key=True)
    type = StringField(required=True)
    mysql_id = IntField(required=True)
    create_time = DateTimeField(required=True, default=datetime.now())
    create_timestamp = IntField(required=True, default=int(time.time()))
