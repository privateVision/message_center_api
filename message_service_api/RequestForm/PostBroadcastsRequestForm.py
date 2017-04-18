# _*_ coding: utf-8 _*_
from wtforms import Form, IntegerField, StringField
from wtforms.validators import InputRequired


class PostBroadcastsRequestForm(Form):
    game = StringField('game', [InputRequired()])
    users_type = StringField('user_type')
    vip_user = StringField('vip_user')
    specify_user = StringField('specify_user')

    id = IntegerField('id', [InputRequired()])
    title = StringField('title', [InputRequired()])
    content = StringField('content', [InputRequired()])
    close_time = IntegerField('close_time')
    stime = IntegerField('stime')
    is_time = IntegerField('is_time', default=1)
    # etime = IntegerField('etime')
    create_time = IntegerField('create_time')
