# _*_ coding: utf-8 _*_
import json

from flask import Blueprint
from flask import request

from Controller.BaseController import response_data
from MongoModel.AppRulesModel import AppVipRules
from MongoModel.AppsModel import Apps
from MongoModel.ZonelistsModel import Zonelists
from RequestForm.GetDataListRequestForm import GetDataListRequestForm

app_controller = Blueprint('AppController', __name__)


# 获取游戏列表
@app_controller.route('/v4/apps', methods=['GET'])
def v4_get_app_list():
    from Service.UsersService import getGameAndAreaUsers
    getGameAndAreaUsers()
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
        app_vip_rules.id = item['id']
        app_vip_rules.fee = item['fee']
        app_vip_rules.name = item['name']
        app_vip_rules.save()
    return response_data(http_code=200, message="更新VIP规则成功")