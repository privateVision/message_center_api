# _*_ coding: utf-8 _*_
from flask_wtf import FlaskForm
from mongoengine import IntField
from wtforms.validators import DataRequired


class GetNoticesRequestForm(FlaskForm):
    apk_id = IntField('apk_id', validators=[DataRequired()])
    area = IntField('area', validators=[DataRequired()])
    user_type = IntField('user_type', validators=[DataRequired()])
    vip = IntField('vip', validators=[DataRequired('vip不能为空')])
    ucid = IntField('ucid', validators=[DataRequired()])
