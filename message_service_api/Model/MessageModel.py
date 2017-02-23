# _*_ coding: utf-8 _*_
from mongoengine import StringField, ListField, IntField, DateTimeField, EmbeddedDocumentField, DynamicDocument


# MongoDB system_Announcements 对应模型
class SystemAnnouncements(DynamicDocument):
    id = IntField(required=True, primary_key=True)
    title = StringField(required=True)
    # atype = IntField(required=True)
    # content = StringField(required=True)
    # button_type = StringField()
    # button_content = StringField()
    # button_url = StringField()
    # url_type = StringField()
    # start_time = IntField(required=True)
    # end_time = IntField(required=True)
    # users = ListField(StringField)
    # rtype = ListField(IntField)
    # vip = IntField()
    # show_times = IntField()
    # open_type = IntField()
    # sortby = IntField()
    # img = StringField()
    # url = StringField()
    # enter_status = StringField()
    # create_time = IntField(required=True)
    # expire_at = DateTimeField(required=True)
    # app = ListField(EmbeddedDocumentField)
