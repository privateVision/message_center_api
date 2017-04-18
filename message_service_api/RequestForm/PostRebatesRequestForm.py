# _*_ coding: utf-8 _*_
from wtforms import Form, IntegerField, StringField, FieldList
from wtforms.validators import InputRequired


class PostRebatesRequestForm(Form):
    users_type = StringField('user_type')
    vip_user = StringField('vip_user')
    specify_user = StringField('specify_user')

    id = IntegerField('id', [InputRequired()])
    title = StringField('title')
    content = StringField('content')
    stime = IntegerField('stime')
    etime = IntegerField('etime')
    is_time = IntegerField('is_time', default=1)
    rule = StringField('rule')
