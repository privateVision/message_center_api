# _*_ coding: utf-8 _*_
import json
import threading

from flask import Blueprint
from flask import request
from mongoengine import Q

from Controller.BaseController import response_data
from MiddleWare import service_logger
from MongoModel.AppRulesModel import AppVipRules
from MongoModel.AppsModel import Apps
from MongoModel.MessageModel import UsersMessage
from MongoModel.MessageRevocationModel import MessageRevocation
from MongoModel.UserMessageModel import UserMessage
from MongoModel.ZonelistsModel import Zonelists
from RequestForm.GetDataListRequestForm import GetDataListRequestForm

app_controller = Blueprint('AppController', __name__)


# 获取游戏列表
@app_controller.route('/v4/apps', methods=['GET'])
def v4_get_app_list():
    form = GetDataListRequestForm(request.args)
    start_index = (form.data['page'] - 1) * form.data['count']
    end_index = start_index + form.data['count']
    if not form.validate():
        print form.errors
        return response_data(400, 400, '客户端请求错误')
    else:
        total_count = Apps.objects.count()
        data_list = Apps.objects[start_index: end_index]
        data = {
            "total_count": total_count,
            "data": data_list
        }
        return response_data(http_code=200, data=data)


# 获取游戏区服列表
@app_controller.route('/v4/app/<int:app_id>/zones', methods=['GET'])
def v4_get_app_zone_list(app_id=None):
    if app_id is None or app_id <= 0:
        return response_data(400, 400002, 'app_id不能为空')
    data = Zonelists.objects.get(_id=app_id)
    return response_data(http_code=200, data=data)


# 设置VIP规则
@app_controller.route('/v4/app/vip_rules', methods=['POST'])
def v4_cms_set_vip_rules():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    data = json.loads(request.form['data'])
    if data is None or data == '':
        return response_data(400, 400, '客户端请求错误')
    app_vip_rules = AppVipRules()
    app_vip_rules.drop_collection()
    for item in data:
        app_vip_rules.level = item['level']
        app_vip_rules.fee = item['fee']
        app_vip_rules.name = item['name']
        app_vip_rules.save()
    return response_data(http_code=200, message="更新VIP规则成功")


@app_controller.route('/v4/app/message_revocation', methods=['POST'])
def v4_cms_message_revocation():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    message_type = request.form['type']
    msg_id = request.form['mysql_id']
    if type is None or type == '' or msg_id is None or msg_id == '':
        return response_data(400, 400, '客户端请求错误')
    message_revocation = MessageRevocation()
    message_revocation.id = "%s%s" % (message_type, msg_id)
    message_revocation.type = message_type
    message_revocation.mysql_id = msg_id
    try:
        message_revocation.save()
        UsersMessage.objects(Q(type=message_type) & Q(mysql_id=msg_id)).update(set__closed=1)
        UserMessage.objects(Q(type=message_type) & Q(mysql_id=msg_id)).update(set__closed=1)
    except Exception, err:
        service_logger.error(err.message)
        return response_data(http_code=500, code=500003, message="mongo写入失败")
    return response_data(http_code=200, message="消息撤回成功")


# 心跳
@app_controller.route('/v4/app/heartbeat', methods=['POST'])
def v4_sdk_heartbeat():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    return response_data(data=None)
