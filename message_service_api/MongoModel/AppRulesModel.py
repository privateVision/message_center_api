# _*_ coding: utf-8 _*_
from mongoengine import StringField, DynamicDocument, IntField


# MongoDB app_vip_rules 对应模型
class AppVipRules(DynamicDocument):
    meta = {'collection': 'sdk_api_app_vip_rules'}
    level = IntField(required=True, primary_key=True)
    fee = IntField(required=True)
    name = StringField(required=True)
