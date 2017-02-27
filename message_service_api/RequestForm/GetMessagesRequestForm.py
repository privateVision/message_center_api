# _*_ coding: utf-8 _*_
from wtforms import Form, IntegerField, StringField
from wtforms.validators import ValidationError, InputRequired


class GetMessagesRequestForm(Form):
    # apk_id = IntegerField('apk_id', [InputRequired()])
    # area = StringField('area', [InputRequired()])
    # user_type = IntegerField('user_type', [InputRequired()])
    # vip = IntegerField('vip', [InputRequired()])
    access_token = StringField('ucid', [InputRequired()])

    page = IntegerField('page', default=1)
    count = IntegerField('count', default=5)
