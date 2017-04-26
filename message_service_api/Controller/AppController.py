# _*_ coding: utf-8 _*_
import json

import time

import datetime
from flask import Blueprint
from flask import request
from mongoengine import Q

from Controller.BaseController import response_data, response_ok
from LanguageConf import get_tips
from MiddleWare import service_logger
from MongoModel.AppRulesModel import AppVipRules
from MongoModel.MessageModel import UsersMessage
from MongoModel.MessageRevocationModel import MessageRevocation
from MongoModel.UserMessageModel import UserMessage
from Service.UsersService import get_ucid_by_access_token, sdk_api_request_check, cms_api_request_check, \
    get_user_is_freeze_by_access_token
from Utils.RedisUtil import RedisHandle
from Utils.SystemUtils import log_exception

app_controller = Blueprint('AppController', __name__)


# 获取游戏列表
@app_controller.route('/msa/v4/apps', methods=['POST'])
def v4_get_app_list():
    from run import mysql_session
    app_list = []
    find_users_by_user_type_sql = "select pid as id, pname as app_name, priKey as rsa_key, psingKey as sign_key " \
                                  "from procedures as p where p.status = 1 "
    try:
        origin_list = mysql_session.execute(find_users_by_user_type_sql)
        for app in origin_list:
            game = {
                'id': app['id'],
                'app_name': app['app_name'],
                'rsa_key': app['rsa_key'],
                'sign_key': app['sign_key']
            }
            app_list.append(game)
    except Exception, err:
        log_exception(request, err.message)
        mysql_session.rollback()
    finally:
        mysql_session.close()
    return response_data(http_code=200, data=app_list)


# 获取游戏区服列表
@app_controller.route('/msa/v4/app/<int:app_id>/zones', methods=['POST'])
def v4_get_app_zone_list(app_id=None):
    if app_id is None or app_id <= 0:
        log_exception(request, 'app_id不能为空')
        return response_data(200, 0, 'app_id不能为空')
    zone_list = []
    from run import mysql_session
    find_users_by_user_type_sql = "select distinct(zoneId), zoneName from ucuser_role_%s as r where r.pid = %s " \
                                  % (int(int(app_id)/30), app_id)
    try:
        origin_list = mysql_session.execute(find_users_by_user_type_sql)
        for zone in origin_list:
            zone_list.append(zone['zoneName'])
    except Exception, err:
        log_exception(request, err.message)
        mysql_session.rollback()
    finally:
        mysql_session.close()
    return response_data(http_code=200, data=zone_list)


# 设置VIP规则
@app_controller.route('/msa/v4/app/vip_rules', methods=['POST'])
@cms_api_request_check
def v4_cms_set_vip_rules():
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
@app_controller.route('/msa/v4/app/user/close_account', methods=['POST'])
@cms_api_request_check
def v4_cms_close_user_account():
    ucid = request.form['ucid']
    if ucid is None or ucid == '':
        log_exception(request, '客户端请求错误')
        return response_data(200, 0, '客户端请求错误')
    RedisHandle.hset(ucid, 'is_account_close', 1)
    return response_data(http_code=200, message="账号冻结成功")


# 账号解冻
@app_controller.route('/msa/v4/app/user/open_closed_account', methods=['POST'])
@cms_api_request_check
def v4_cms_open_closed_user_account():
    ucid = request.form['ucid']
    if ucid is None or ucid == '':
        log_exception(request, '客户端请求错误')
        return response_data(200, 0, '客户端请求错误')
    RedisHandle.hset(ucid, 'is_account_close', 0)
    return response_data(http_code=200, message="解冻账号成功")


# 消息撤回
@app_controller.route('/msa/v4/app/message_revocation', methods=['POST'])
@cms_api_request_check
def v4_cms_message_revocation():
    message_type = request.json.get('type')
    msg_id = request.json.get('mysql_id')
    if type is None or type == '' or msg_id is None or msg_id == '':
        log_exception(request, '客户端请求错误-消息类型或消息id为空')
        return response_data(200, 0, get_tips('common', 'client_request_error'))
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
        return response_data(http_code=200, code=0, message=get_tips('cms', 'mongo_write_exception'))
    return response_data(http_code=200, message=get_tips('cms', 'message_revocation_success'))


# 心跳
@app_controller.route('/msa/v4/app/heartbeat', methods=['POST'])
@sdk_api_request_check
def v4_sdk_heartbeat():
    stime = time.time()
    if 'num' in request.form:
        num = int(request.form['num'])
    else:
        num = 1
    if RedisHandle.exists('REFRESH_INTERVAL'):
        refresh_interval = int(RedisHandle.get('REFRESH_INTERVAL'))
    else:
        refresh_interval = 60000
    if 'interval' in request.form:
        interval_ms = int(request.form['interval'])
    else:
        interval_ms = 2000
    appid = request.form['_appid']
    is_need_refresh_data = (num * interval_ms) % refresh_interval
    ucid = get_ucid_by_access_token(request.form['_token'])
    if is_need_refresh_data == 0:
        if ucid:
            # 获取用户相关广播和未读消息数
            data = RedisHandle.get_user_data_mark_in_redis(ucid, appid)
            service_logger.info("%s - %s" % (ucid, json.dumps(data)))
            return response_data(data=data)
    data = {
        "broadcast": [],
        "message": 0,
        "gift_num": 0
    }
    if RedisHandle.exists(ucid):
        cache_data = RedisHandle.hgetall(ucid)
        if cache_data.has_key('message'):
            data['message'] = int(cache_data['message'])
        if cache_data.has_key('gift_num'):
            data['gift_num'] = int(cache_data['gift_num'])
    etime = time.time()
    service_logger.info("心跳逻辑处理时间：%s" % (etime-stime,))
    return response_data(data=data)


# CMS 更新心跳数据刷新间隔
@app_controller.route('/msa/v4/refresh_heart_beat_data_interval', methods=['POST'])
@cms_api_request_check
def v4_cms_update_refresh_heart_beat_data_interval():
    refresh_interval = int(request.json.get('refresh_interval'))
    RedisHandle.set('REFRESH_INTERVAL', refresh_interval)
    return response_data(http_code=200)


#  用于监控程序判断该进程是否存在
@app_controller.route('/msa/v4/is_process_ok')
def v4_process_ok():
    return response_ok()
