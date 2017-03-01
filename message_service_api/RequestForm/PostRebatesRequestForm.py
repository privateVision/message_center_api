# _*_ coding: utf-8 _*_
from wtforms import Form, IntegerField, StringField
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
    rules = StringField('rules')
