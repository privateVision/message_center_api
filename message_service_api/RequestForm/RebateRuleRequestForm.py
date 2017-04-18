# _*_ coding: utf-8 _*_
from wtforms import Form, IntegerField, StringField, FieldList, FormField
from wtforms.validators import InputRequired


class RebateRuleRequestForm(Form):
    id = IntegerField('id', [InputRequired()])
    recharge_id = IntegerField('recharge_id')
    full = IntegerField('full')
    dis = IntegerField('dis')
    vip = IntegerField('vip')
