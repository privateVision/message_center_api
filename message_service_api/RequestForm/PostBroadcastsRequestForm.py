# _*_ coding: utf-8 _*_
from wtforms import Form, IntegerField, StringField
from wtforms.validators import InputRequired


class PostBroadcastsRequestForm(Form):
    game = StringField('game', [InputRequired()])
    users_type = StringField('user_type', [InputRequired()])
    vip_user = StringField('vip_user', [InputRequired()])
    specify_user = StringField('specify_user', [InputRequired()])

    id = IntegerField('id', [InputRequired()])
    title = StringField('title', [InputRequired()])
    content = StringField('content', [InputRequired()])
    close_time = IntegerField('close_time')
    stime = IntegerField('stime')
    # etime = IntegerField('etime')
    create_time = IntegerField('create_time')
