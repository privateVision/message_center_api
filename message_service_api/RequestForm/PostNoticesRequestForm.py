# _*_ coding: utf-8 _*_
from wtforms import Form, IntegerField, StringField
from wtforms.validators import InputRequired


class PostNoticesRequestForm(Form):
    game = StringField('game', [InputRequired()])
    users_type = StringField('user_type')
    vip_user = StringField('vip_user')
    specify_user = StringField('specify_user')

    id = IntegerField('id', [InputRequired()])
    title = StringField('title', [InputRequired()])
    content = StringField('content')
    type = IntegerField('type', [InputRequired()])
    show_times = IntegerField('show_times', default=0)  # 0 表示一直显示
    enter_status = StringField('enter_status', default='normal')  # 'normal' 允许进入游戏
    stime = IntegerField('stime', [InputRequired()])
    etime = IntegerField('etime', [InputRequired()])
    create_time = IntegerField('create_time')
    img = StringField('img')
    url = StringField('url')
    is_time = IntegerField('is_time', default=1)
    open_type = IntegerField('open_type')
    url_type = StringField('url_type')
    button_content = StringField('button_content')
    button_url = StringField('button_url')
    button_type = StringField('button_type')
    sortby = IntegerField('sortby')

    def set_game(self, game):
        self.game = game
