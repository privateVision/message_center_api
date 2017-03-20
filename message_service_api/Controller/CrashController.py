# _*_ coding: utf-8 _*_
import json

import time
from flask import Blueprint
from flask import request
from mongoengine import Q

from Controller.BaseController import response_data, response_ok
from MiddleWare import service_logger
from MongoModel.AppRulesModel import AppVipRules
from MongoModel.CrashModel import CrashUserInfoModel, CrashAppInfoModel, CrashLogModel, CrashUserAppInfoModel
from MongoModel.MessageModel import UsersMessage
from MongoModel.MessageRevocationModel import MessageRevocation
from MongoModel.UserMessageModel import UserMessage
from Service.UsersService import get_ucid_by_access_token, sdk_api_request_check, cms_api_request_check
from Utils.RedisUtil import RedisHandle
from Utils.SystemUtils import log_exception

crash_controller = Blueprint('CrashController', __name__)


@crash_controller.route('/crash/userinfo', methods=['POST'])
def crash_userinfo():
    device_id = request.form['device_id']
    data = request.form['data']
    if data is None or data == '' or device_id is None:
        log_exception(request, '缺参数')
        return response_data(200, 0, '客户端请求错误')
    crash_user_info = CrashUserInfoModel()
    crash_user_info['device_id'] = device_id
    crash_user_info['data'] = json.loads(data)
    crash_user_info['timestamp'] = int(time.time())
    crash_user_info['report_date'] = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))
    crash_user_info.save()
    log_info = "crash-user_info-%s-%s" % (device_id, data)
    service_logger.info(log_info)
    return response_data(http_code=200, message="上传用户信息成功")


@crash_controller.route('/crash/appinfo', methods=['POST'])
def crash_appinfo():
    device_id = request.form['device_id']
    data = request.form['data']
    if data is None or data == '' or device_id is None:
        log_exception(request, '缺参数')
        return response_data(200, 0, '客户端请求错误')
    crash_user_app_info = CrashUserAppInfoModel()
    crash_user_app_info['device_id'] = device_id
    crash_user_app_info['data'] = json.loads(data)
    crash_user_app_info['timestamp'] = int(time.time())
    crash_user_app_info['report_date'] = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))
    crash_user_app_info.save()

    for app in crash_user_app_info['data']:
        crash_app_info = CrashAppInfoModel()
        crash_app_info['id'] = "%s-%s" % (app['PackageName'], app['VersionName'])
        crash_app_info['package_name'] = app['PackageName']
        crash_app_info['data'] = app
        crash_app_info.save()
    log_info = "crash-app_info-%s-%s" % (device_id, data)
    service_logger.info(log_info)
    return response_data(http_code=200, message="上传用户应用列表成功")


@crash_controller.route('/crash/log', methods=['POST'])
def crash_log():
    device_id = request.form['device_id']
    data = request.form['data']
    if data is None or data == '' or device_id is None:
        log_exception(request, '缺参数')
        return response_data(200, 0, '客户端请求错误')
    crash_log_info = CrashLogModel()
    crash_log_info['device_id'] = device_id
    crash_log_info['data'] = json.loads(data)
    crash_log_info['timestamp'] = int(time.time())
    crash_log_info['report_date'] = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(time.time()))
    crash_log_info.save()
    log_info = "crash-log-%s-%s" % (device_id, data)
    service_logger.info(log_info)
    return response_data(http_code=200, message="上传崩溃日志成功")
