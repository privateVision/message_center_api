# _*_ coding: utf-8 _*_
from wtforms import Form, IntegerField, StringField
from wtforms.validators import InputRequired


class PostMessagesRequestForm(Form):
    game = StringField('game', [InputRequired()])
    users_type = StringField('user_type', [InputRequired()])
    vip_user = StringField('vip_user', [InputRequired()])
    specify_user = StringField('specify_user', [InputRequired()])

    id = IntegerField('id', [InputRequired()])
    title = StringField('title', [InputRequired()])
    description = StringField('description')
    content = StringField('content')
    url = StringField('url')
    img = StringField('img')
    msg_type = StringField('msg_type')
    send_time = IntegerField('send_time')
    create_time = IntegerField('create_time')
    sys = IntegerField('sys')