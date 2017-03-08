# _*_ coding: utf-8 _*_
from mongoengine import StringField, DynamicDocument, ListField


# MongoDB apps 对应模型
class Zonelists(DynamicDocument):
    meta = {'collection': 'sdk_api_zone_lists'}
    id = StringField(required=True, primary_key=True)
    zones = ListField()