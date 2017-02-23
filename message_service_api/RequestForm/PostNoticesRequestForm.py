# _*_ coding: utf-8 _*_
from wtforms import Form, validators, IntegerField, StringField
from wtforms.validators import ValidationError, InputRequired


class PostNoticesRequestForm(Form):
    apk_id = IntegerField('apk_id', [InputRequired()])
    area = StringField('area', [InputRequired()])
    user_type = StringField('user_type', [InputRequired()])
    vip = StringField('vip', [InputRequired()])
    ucid = StringField('ucid', [InputRequired()])

    id = IntegerField('id', [InputRequired()])
    title = StringField('title', [InputRequired()])
    content = StringField('content')
    type = IntegerField('type', [InputRequired()])
    show_times = IntegerField('show_times', default=0)  # 0 表示一直显示
    enter_status = StringField('enter_status', default=1)  # 1 允许进入游戏
    img = StringField('img')
    jump_url = StringField('jump_url')
    stime = IntegerField('stime', [InputRequired()])
    etime = IntegerField('etime', [InputRequired()])
    create_time = IntegerField('create_time', [InputRequired()])