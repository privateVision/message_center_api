# _*_ coding: utf-8 _*_
from wtforms import Form, IntegerField, StringField
from wtforms.validators import ValidationError, InputRequired


class PutMessageReadRequestForm(Form):
    message_info = StringField('message_info', [InputRequired()])
    ucid = IntegerField([InputRequired()])