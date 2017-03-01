# _*_ coding: utf-8 _*_
from mongoengine import StringField, IntField, DynamicDocument


# MongoDB users_message 对应模型
class UsersMessage(DynamicDocument):
    id = StringField(required=True, primary_key=True)
    mysql_id = IntField(required=True)
    type = StringField(required=True)
    closed = IntField(required=True, default=0)
    title = StringField(required=True)

