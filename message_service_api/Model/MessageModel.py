# _*_ coding: utf-8 _*_
from flask_mongoengine import Document
from mongoengine import StringField, ListField, IntField


class UsersMessage(Document):
    apk_id = ListField(IntField(), required=True)
    area = ListField(StringField(), required=True)
    user_type = ListField(IntField(), required=True)
    vip = ListField(IntField(), required=True)
    ucid_list = ListField(IntField(), required=True)

    type = StringField(required=True)
    message_id = IntField(required=True)