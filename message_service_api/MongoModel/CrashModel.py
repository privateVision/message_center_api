# _*_ coding: utf-8 _*_
from mongoengine import StringField, DynamicDocument, IntField, DynamicField


class CrashUserInfoModel(DynamicDocument):
    meta = {'collection': 'crash_user_info'}
    device_id = StringField(required=True)
    data = DynamicField(required=True)


class CrashUserAppInfoModel(DynamicDocument):
    meta = {'collection': 'crash_user_app_info'}
    device_id = StringField(required=True)
    data = DynamicField(required=True)


class CrashAppInfoModel(DynamicDocument):
    meta = {'collection': 'crash_app_info'}
    id = StringField(required=True, primary_key=True)
    package_name = StringField(required=True)
    data = DynamicField(required=True)


class CrashLogModel(DynamicDocument):
    meta = {'collection': 'crash_log_info'}
    device_id = StringField(required=True)
    data = DynamicField(required=True)
