# _*_ coding: utf-8 _*_
import time
from wtforms import Form, IntegerField, StringField
from wtforms.validators import InputRequired


class PostAccountMessagesRequestForm(Form):
    title = StringField('title', [InputRequired()])
    description = StringField('description', [InputRequired()])
    content = StringField('content', [InputRequired()])
    ucid = StringField('url', [InputRequired()])
