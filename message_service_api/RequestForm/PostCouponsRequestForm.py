# _*_ coding: utf-8 _*_
from wtforms import Form, IntegerField, StringField
from wtforms.validators import InputRequired


class PostCouponsRequestForm(Form):
    game = StringField('game', [InputRequired()])
    users_type = StringField('user_type', [InputRequired()])
    vip_user = StringField('vip_user', [InputRequired()])
    specify_user = StringField('specify_user', [InputRequired()])

    id = IntegerField('id', [InputRequired()])
    title = StringField('title', [InputRequired()])
    is_time = StringField('is_time', [InputRequired()])
    stime = IntegerField('stime', [InputRequired()])
    etime = IntegerField('etime', [InputRequired()])
    is_first = IntegerField('is_first')
    info = StringField('info', [InputRequired()])
    num = IntegerField('num', [InputRequired()])
    full = IntegerField('full')
    money = IntegerField('money')
    method = StringField('method')