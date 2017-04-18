# _*_ coding: utf-8 _*_
from mongoengine import StringField, DynamicDocument


# MongoDB apps 对应模型
class Apps(DynamicDocument):
    meta = {'collection': 'sdk_api_apps'}
    id = StringField(required=True, primary_key=True)
    app_name = StringField(required=True)
    rsa_key = StringField(required=True)
    sign_key = StringField(required=True)
    app_key = StringField(required=True)
