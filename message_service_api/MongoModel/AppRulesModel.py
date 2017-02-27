# _*_ coding: utf-8 _*_
from mongoengine import StringField, DynamicDocument, IntField


# MongoDB app_vip_rules 对应模型
class AppVipRules(DynamicDocument):
    id = IntField(required=True, primary_key=True)
    fee = IntField(required=True)
    name = StringField(required=True)
