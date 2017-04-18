# _*_ coding: utf-8 _*_
import time
from wtforms import Form, IntegerField, StringField
from wtforms.validators import InputRequired


class PostMessagesRequestForm(Form):
    game = StringField('game', [InputRequired()])
    users_type = StringField('user_type')
    vip_user = StringField('vip_user')
    specify_user = StringField('specify_user')

    id = IntegerField('id', [InputRequired()])
    title = StringField('title', [InputRequired()])
    description = StringField('description')
    content = StringField('content')
    url = StringField('url')
    img = StringField('img')
    msg_type = StringField('msg_type')
    send_time = IntegerField('send_time', default=int(time.time()))
    create_time = IntegerField('create_time')
    is_time = IntegerField('is_time', default=1)
    sys = IntegerField('sys')