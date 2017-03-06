# _*_ coding: utf-8 _*_
import json

from flask import Blueprint
from flask import request
from mongoengine import Q

from Controller.BaseController import response_data, response_ok
from MiddleWare import redis_store
from MongoModel.AppRulesModel import AppVipRules
from MongoModel.MessageModel import UsersMessage
from MongoModel.MessageRevocationModel import MessageRevocation
from MongoModel.UserMessageModel import UserMessage
from Service.UsersService import get_user_data_mark_in_redis, get_ucid_by_access_token, clear_user_data_mark_in_redis
from Utils.SystemUtils import log_exception

app_controller = Blueprint('AppController', __name__)


# 获取游戏列表
@app_controller.route('/v4/apps', methods=['GET'])
def v4_get_app_list():
    from run import mysql_session
    find_users_by_user_type_sql = "select pid as id, pname as app_name, priKey as rsa_key, psingKey as sign_key " \
                                  "from procedures as p where p.status = 1 "
    try:
        origin_list = mysql_session.execute(find_users_by_user_type_sql)
    except Exception, err:
        log_exception(request, err.message)
        mysql_session.rollback()
    finally:
        mysql_session.close()
    app_list = []
    for app in origin_list:
        game = {
            'id': app['id'],
            'app_name': app['app_name'],
            'rsa_key': app['rsa_key'],
            'sign_key': app['sign_key']
        }
        app_list.append(game)
    return response_data(http_code=200, data=app_list)
    # form = GetDataListRequestForm(request.args)
    # start_index = (form.data['page'] - 1) * form.data['count']
    # end_index = start_index + form.data['count']
    # if not form.validate():
    #     print form.errors
    #     return response_data(400, 400, '客户端请求错误')
    # else:
    #     total_count = Apps.objects.count()
    #     data_list = Apps.objects[start_index: end_index]
    #     data = {
    #         "total_count": total_count,
    #         "data": data_list
    #     }
    #     return response_data(http_code=200, data=data)


# 获取游戏区服列表
@app_controller.route('/v4/app/<int:app_id>/zones', methods=['GET'])
def v4_get_app_zone_list(app_id=None):
    if app_id is None or app_id <= 0:
        log_exception(request, 'app_id不能为空')
        return response_data(200, 0, 'app_id不能为空')
    # data = Zonelists.objects.get(_id=app_id)
    from run import mysql_session
    find_users_by_user_type_sql = "select distinct(zoneId), zoneName from roleDatas as r where r.vid = %s " % (app_id,)
    try:
        origin_list = mysql_session.execute(find_users_by_user_type_sql)
    except Exception, err:
        log_exception(request, err.message)
        mysql_session.rollback()
    finally:
        mysql_session.close()
    zone_list = []
    for zone in origin_list:
        zone_list.append(zone['zoneName'])
    return response_data(http_code=200, data=zone_list)


# 设置VIP规则
@app_controller.route('/v4/app/vip_rules', methods=['POST'])
def v4_cms_set_vip_rules():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    data = json.loads(request.form['data'])
    if data is None or data == '':
        log_exception(request, '客户端请求错误')
        return response_data(200, 0, '客户端请求错误')
    app_vip_rules = AppVipRules()
    app_vip_rules.drop_collection()
    for item in data:
        app_vip_rules.level = item['level']
        app_vip_rules.fee = item['fee']
        app_vip_rules.name = item['name']
        app_vip_rules.save()
    return response_data(http_code=200, message="更新VIP规则成功")


# 账号冻结
@app_controller.route('/v4/app/user/close_account', methods=['POST'])
def v4_cms_close_user_account():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    ucid = request.form['ucid']
    redis_store.hincrby(ucid, 'coupon', 1)
    if ucid is None or ucid == '':
        log_exception(request, '客户端请求错误')
        return response_data(200, 0, '客户端请求错误')
    redis_store.hset(ucid, 'is_account_close', 1)
    return response_data(http_code=200, message="账号冻结成功")


# 账号解冻
@app_controller.route('/v4/app/user/open_closed_account', methods=['POST'])
def v4_cms_open_closed_user_account():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    ucid = request.form['ucid']
    if ucid is None or ucid == '':
        log_exception(request, '客户端请求错误')
        return response_data(200, 0, '客户端请求错误')
    redis_store.hset(ucid, 'is_account_close', 0)
    return response_data(http_code=200, message="解冻账号成功")


# 消息撤回
@app_controller.route('/v4/app/message_revocation', methods=['POST'])
def v4_cms_message_revocation():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    message_type = request.form['type']
    msg_id = request.form['mysql_id']
    if type is None or type == '' or msg_id is None or msg_id == '':
        log_exception(request, '客户端请求错误-消息类型或消息id为空')
        return response_data(200, 0, '客户端请求错误')
    message_revocation = MessageRevocation()
    message_revocation.id = "%s%s" % (message_type, msg_id)
    message_revocation.type = message_type
    message_revocation.mysql_id = msg_id
    try:
        message_revocation.save()
        UsersMessage.objects(Q(type=message_type) & Q(mysql_id=msg_id)).update(set__closed=1)
        UserMessage.objects(Q(type=message_type) & Q(mysql_id=msg_id)).update(set__closed=1)
    except Exception, err:
        log_exception(request, 'mongo写入失败: %s' % (err.message,))
        return response_data(http_code=200, code=0, message="mongo写入失败")
    return response_data(http_code=200, message="消息撤回成功")


# 心跳
@app_controller.route('/v4/app/heartbeat/<int:ucid>', methods=['GET'])
def v4_sdk_heartbeat(ucid):
    # appid = request.form['appid']
    # param = request.form['param']
    # if appid is None or param is None:
    #     return response_data(400, 400, '客户端请求错误')
    # from Utils.EncryptUtils import sdk_api_check_key
    # params = sdk_api_check_key(request)
    # ucid = get_ucid_by_access_token(params['access_token'])
    data = get_user_data_mark_in_redis(ucid)
    return response_data(data=data)


# 心跳ACK
@app_controller.route('/v4/app/heartbeat/ack', methods=['POST'])
def v4_sdk_heartbeat_ack():
    appid = request.form['appid']
    param = request.form['param']
    if appid is None or param is None:
        log_exception(request, '客户端请求错误-appid或param为空')
        return response_data(200, 0, '客户端请求错误')
    from Utils.EncryptUtils import sdk_api_check_key
    params = sdk_api_check_key(request)
    ucid = get_ucid_by_access_token(params['access_token'])
    clear_user_data_mark_in_redis(ucid, params['type'])
    return response_ok()
