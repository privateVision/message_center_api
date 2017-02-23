# _*_ coding: utf-8 _*_
from wtforms import Form, IntegerField, StringField
from wtforms.validators import InputRequired


class PostNoticesRequestForm(Form):
    game = StringField('area', [InputRequired()])
    users_type = StringField('user_type', [InputRequired()])
    vip_user = StringField('vip_user', [InputRequired()])
    specify_user = StringField('specify_user', [InputRequired()])

    id = IntegerField('id', [InputRequired()])
    title = StringField('title', [InputRequired()])
    content = StringField('content')
    type = IntegerField('type', [InputRequired()])
    show_times = IntegerField('show_times', default=0)  # 0 表示一直显示
    enter_status = StringField('enter_status', default='normal')  # 'normal' 允许进入游戏
    stime = IntegerField('stime', [InputRequired()])
    etime = IntegerField('etime', [InputRequired()])
    create_time = IntegerField('create_time', [InputRequired()])
    img = StringField('img')
    url = StringField('url')
    open_type = IntegerField('open_type')
    url_type = StringField('url_type')
    button_content = StringField('button_content')
    button_url = StringField('button_url')
    button_type = StringField('button_type')
    sortby = IntegerField('sortby')