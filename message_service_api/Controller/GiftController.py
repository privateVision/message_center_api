# _*_ coding: utf-8 _*_

from flask import Blueprint
from flask import request

from Controller.BaseController import response_data
from MiddleWare import service_logger
from Service.UsersService import get_ucid_by_access_token, sdk_api_request_check

gift_controller = Blueprint('GiftController', __name__)


# SDK 获取礼包列表
@gift_controller.route('/msa/v4/gifts', methods=['POST'])
@sdk_api_request_check
def v4_sdk_get_broadcast_list():
    ucid = get_ucid_by_access_token(request.form['_token'])
    page = request.form['page'] if request.form.has_key('page') and request.form['page'] else 1
    count = request.form['count'] if request.form.has_key('count') and request.form['count'] else 10
    start_index = (int(page) - 1) * int(count)
    end_index = start_index + int(count)
    service_logger.info("用户：%s 获取礼包列表，数据从%s到%s" % (ucid, start_index, end_index))
    # 查询用户相关的公告列表
    data_list = []
    data = {
        "data": data_list
    }
    return response_data(http_code=200, data=data)

