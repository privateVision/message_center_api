# _*_ coding: utf-8 _*_
from wtforms import Form, IntegerField, StringField
from wtforms.validators import ValidationError, InputRequired


class PutNoticesRequestForm(Form):
    notice_id = IntegerField('notice_id', [InputRequired()])
    ucid = IntegerField('ucid', [InputRequired()])
