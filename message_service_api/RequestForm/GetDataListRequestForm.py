# _*_ coding: utf-8 _*_
from wtforms import Form, IntegerField, StringField
from wtforms.validators import ValidationError, InputRequired


class GetDataListRequestForm(Form):
    page = IntegerField('page', default=1)
    count = IntegerField('count', default=5)
