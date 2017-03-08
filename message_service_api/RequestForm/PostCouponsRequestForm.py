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
    is_time = IntegerField('is_time', default=1)  # 是否限制时间
    stime = IntegerField('stime', default=0)    # 开始时间
    etime = IntegerField('etime', default=0)    # 结束时间
    is_first = IntegerField('is_first', default=0)  # 0为优惠券,1为首充券,2为充值优惠券
    info = StringField('info')  # 描述
    num = IntegerField('num', default=0)    # 已经使用的个数
    full = IntegerField('full')
    money = IntegerField('money')
    method = StringField('method')
