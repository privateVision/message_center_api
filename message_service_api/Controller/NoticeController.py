# _*_ coding: utf-8 _*_
import json

from flask import Blueprint, jsonify
from flask import request

from Controller import service_logger
from Controller.BaseController import response_data
from Model.MessageModel import UsersMessage
from RequestForm.GetNoticesRequestForm import GetNoticesRequestForm

notice_controller = Blueprint('NoticeController', __name__)


# SDK 获取公告列表
@notice_controller.route('/v4/notice', methods=['GET', 'POST'])
def v4_sdk_get_notice_list():
    """
    apk_id
    area
    user_type
    vip
    ucid

    :return:
    """
    form = GetNoticesRequestForm()
    if form.validate_on_submit():
        pass
    request_parameters = request.args
    return response_data(http_code=200, data=request_parameters)

