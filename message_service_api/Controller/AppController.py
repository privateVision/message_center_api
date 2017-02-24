# _*_ coding: utf-8 _*_

from flask import Blueprint
from flask import request

from Controller import service_logger
from Controller.BaseController import response_data
from MongoModel.AppsModel import Apps
from MongoModel.ZonelistsModel import Zonelists
from MysqlModel.RoleDatasModel import RoleDatas
from RequestForm.GetDataListRequestForm import GetDataListRequestForm

app_controller = Blueprint('AppController', __name__)


# 获取游戏列表
@app_controller.route('/v4/apps', methods=['GET'])
def v4_get_app_list():
    admin = RoleDatas.query.filter_by(ucid=1978293).first()
    print admin
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