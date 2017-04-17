# _*_ coding: utf-8 _*_
import json

from flask import Blueprint
from flask import request
from mongoengine import Q

from Controller.BaseController import response_data
from LanguageConf import get_tips
from MiddleWare import service_logger
from MongoModel.MessageModel import UsersMessage
from MongoModel.UserMessageModel import UserMessage
from RequestForm.PostBroadcastsRequestForm import PostBroadcastsRequestForm
from Service.StorageService import system_broadcast_update
from Service.UsersService import get_ucid_by_access_token, get_broadcast_message_detail_info, \
    find_user_account_is_freeze, sdk_api_request_check, cms_api_request_check
from Utils.RedisUtil import RedisHandle
from Utils.SystemUtils import get_current_timestamp, log_exception

broadcast_controller = Blueprint('BroadcastController', __name__)


# CMS 发送广播
@broadcast_controller.route('/msa/v4/add_broadcast', methods=['POST'])
@cms_api_request_check
def v4_cms_post_broadcast():
    form = PostBroadcastsRequestForm.from_json(request.json)
    if not form.validate():
        log_exception(request, '客户端请求错误: %s' % (json.dumps(form.errors)))
        return response_data(200, 0, get_tips('common', 'client_request_error'))
    else:
        from run import kafka_producer
        try:
            message_info = {
                "type": "broadcast",
                "message": form.data
            }
            message_str = json.dumps(message_info)
            service_logger.info("发送广播：%s" % (message_str,))
            kafka_producer.send('message-service', message_str)
        except Exception, err:
            log_exception(request, "发送广播异常：%s" % (err.message,))
            return response_data(http_code=200, code=0, message=get_tips('cms', 'kafka_service_exception'))
        return response_data(http_code=200)


# CMS 更新广播
@broadcast_controller.route('/msa/v4/update_broadcast', methods=['POST'])
@cms_api_request_check
def v4_cms_update_broadcast():
    form = PostBroadcastsRequestForm.from_json(request.json)
    if not form.validate():
        log_exception(request, '客户端请求错误: %s' % (json.dumps(form.errors)))
        return response_data(200, 0, get_tips('common', 'client_request_error'))
    else:
        try:
            service_logger.info("更新广播：%s" % (json.dumps(form.data),))
            system_broadcast_update(form.data)
        except Exception, err:
            log_exception(request, "更新广播异常：%s" % (err.message,))
            return response_data(http_code=200, code=0, message="mongo更新数据异常")
    return response_data(http_code=200)


# CMS 删除广播
@broadcast_controller.route('/msa/v4/delete_broadcast', methods=['POST'])
@cms_api_request_check
def v4_cms_delete_post_broadcast():
    broadcast_id = request.json.get('id')
    if broadcast_id is None or broadcast_id == '':
        log_exception(request, '客户端请求错误-广播id为空')
        return response_data(200, 0, '客户端请求错误')
    try:
        UsersMessage.objects(Q(type='broadcast') & Q(mysql_id=broadcast_id)).delete()
        UserMessage.objects(Q(type='broadcast') & Q(mysql_id=broadcast_id)).delete()
    except Exception, err:
        log_exception(request, "删除广播异常：%s" % (err.message,))
        return response_data(http_code=200, code=0, message="删除广播失败")
    return response_data(http_code=200)


@broadcast_controller.route('/msa/v4/broadcasts', methods=['POST'])
@sdk_api_request_check
def v4_sdk_get_broadcast_list():
    ucid = get_ucid_by_access_token(request.form['_token'])
    page = request.form['page'] if request.form.has_key('page') and request.form['page'] else 1
    count = request.form['count'] if request.form.has_key('count') and request.form['count'] else 10
    start_index = (int(page) - 1) * int(count)
    end_index = start_index + int(count)
    service_logger.info("用户：%s 获取广播列表，数据从%s到%s" % (ucid, start_index, end_index))
    # 查询用户相关的广播列表
    current_timestamp = get_current_timestamp()
    message_list_total_count = UserMessage.objects(
        Q(type='broadcast')
        & Q(closed=0)
        & Q(is_read=0)
        & Q(start_time__lte=current_timestamp)
        & Q(end_time__gte=current_timestamp)
        & Q(ucid=ucid)) \
        .count()
    message_list = UserMessage.objects(
        Q(type='broadcast')
        & Q(closed=0)
        & Q(is_read=0)
        & Q(start_time__lte=current_timestamp)
        & Q(end_time__gte=current_timestamp)
        & Q(ucid=ucid)).order_by('-start_time')[start_index:end_index]
    data_list = []
    for message in message_list:
        message_info = get_broadcast_message_detail_info(message['mysql_id'])
        message_resp = {
            "meta_info": {},
            "head": {},
            "body": {}
        }
        message_resp['meta_info']['app'] = message_info['app']
        message_resp['meta_info']['rtype'] = message_info['rtype']
        message_resp['meta_info']['vip'] = message_info['vip']
        message_resp['head']['title'] = message_info['title']
        message_resp['head']['type'] = message_info['type']
        message_resp['head']['mysql_id'] = message_info['mysql_id']
        message_resp['body']['content'] = message_info['content']
        message_resp['body']['start_time'] = message_info['start_time']
        # message_resp['body']['end_time'] = message_info['end_time']
        message_resp['body']['close_time'] = message_info['close_time']
        message['is_read'] = 1
        message.save()
        data_list.append(message_resp)
    # 重置用户广播标记
    RedisHandle.clear_user_data_mark_in_redis(ucid, 'broadcast')
    data = {
        "total_count": message_list_total_count,
        "data": data_list
    }
    return response_data(http_code=200, data=data)