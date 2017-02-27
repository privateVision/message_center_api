# _*_ coding: utf-8 _*_
import json

from flask import Blueprint
from flask import request
from mongoengine import Q

from Controller import service_logger
from Controller.BaseController import response_data
from MongoModel.MessageModel import UsersMessage
from MongoModel.UserMessageModel import UserMessage
from MongoModel.UserReadMessageLogModel import UserReadMessageLog
from RequestForm.GetMessagesRequestForm import GetMessagesRequestForm
from RequestForm.PostNoticesRequestForm import PostNoticesRequestForm
from RequestForm.PutMessageReadRequestForm import PutMessageReadRequestForm
from Service.StorageService import system_announcements_persist
from Service.UsersService import getNoticeMessageDetailInfo, getUcidByAccessToken

notice_controller = Blueprint('NoticeController', __name__)


# CMS 发送公告
@notice_controller.route('/v4/notice', methods=['POST'])
def v4_cms_post_notice():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    form = PostNoticesRequestForm(request.form)  # POST 表单参数封装
    if not form.validate():
        print form.errors
        return response_data(400, 400, '客户端请求错误')
    else:
        from run import kafka_producer
        try:
            message_info = {
                "type": "notice",
                "message": form.data
            }
            kafka_producer.send('message-service', json.dumps(message_info))
        except Exception, err:
            service_logger.error(err.message)
            return response_data(http_code=500, code=500001, message="kafka服务异常")
        return response_data(http_code=200)


# CMS 更新公告
@notice_controller.route('/v4/notice', methods=['PUT'])
def v4_cms_update_post_notice():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    form = PostNoticesRequestForm(request.form)  # POST 表单参数封装
    if not form.validate():
        print form.errors
        return response_data(400, 400, '客户端请求错误')
    else:
        try:
            system_announcements_persist(form.data, False)
        except Exception, err:
            service_logger.error(err.message)
    return response_data(http_code=200)


# CMS 关闭公告
@notice_controller.route('/v4/notice/close', methods=['POST'])
def v4_cms_set_post_notice_closed():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    notice_id = request.form['id']
    if notice_id is None or notice_id == '':
        return response_data(400, 400, '客户端请求错误')
    UsersMessage.objects(Q(type='notice') & Q(mysql_id=notice_id)).update_one(set__closed=1)
    return response_data(http_code=204)


# CMS 打开公告
@notice_controller.route('/v4/notice/open', methods=['POST'])
def v4_cms_set_post_notice_open():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    notice_id = request.form['id']
    if notice_id is None or notice_id == '':
        return response_data(400, 400, '客户端请求错误')
    UsersMessage.objects(Q(type='notice') & Q(mysql_id=notice_id)).update_one(set__closed=0)
    return response_data(http_code=204)


# SDK 获取公告列表
@notice_controller.route('/v4/notices', methods=['POST'])
def v4_sdk_get_notice_list():
    appid = request.form['appid']
    param = request.form['param']
    if appid is None or param is None:
        return response_data(400, 400, '客户端请求错误')
    from Utils.EncryptUtils import sdk_api_check_key
    params = sdk_api_check_key(request)
    if params:
        ucid = getUcidByAccessToken(params['access_token'])
        if ucid:
            page = params['page'] if params.has_key('page') and params['page'] else 1
            count = params['count'] if params.has_key('count') and params['count'] else 10
            start_index = (page - 1) * count
            end_index = start_index + count
            # 查询用户相关的公告列表
            message_list_total_count = UserMessage.objects(
                Q(type='notice')
                & Q(closed=0)
                & Q(ucid=ucid)) \
                .count()
            message_list = UserMessage.objects(
                Q(type='notice')
                & Q(closed=0)
                & Q(ucid=ucid)).order_by('-create_time')[start_index:end_index]
            data_list = []
            for message in message_list:
                message_info = getNoticeMessageDetailInfo(message['mysql_id'])
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
                message_resp['head']['atype'] = message_info['atype']
                message_resp['body']['content'] = message_info['content']
                message_resp['body']['button_content'] = message_info['button_content']
                message_resp['body']['button_type'] = message_info['button_type']
                message_resp['body']['button_url'] = message_info['button_url']
                message_resp['body']['end_time'] = message_info['end_time']
                message_resp['body']['enter_status'] = message_info['enter_status']
                message_resp['body']['img'] = message_info['img']
                message_resp['body']['mysql_id'] = message_info['mysql_id']
                message_resp['body']['open_type'] = message_info['open_type']
                message_resp['body']['start_time'] = message_info['start_time']
                message_resp['body']['url'] = message_info['url']
                message_resp['body']['url_type'] = message_info['url_type']
                data_list.append(message_resp)
            data = {
                "total_count": message_list_total_count,
                "data": data_list
            }
            return response_data(http_code=200, data=data)
        else:
            return response_data(400, 400, '根据access_token获取用户id失败，请重新登录')


# SDK 设置消息已读（消息通用）
@notice_controller.route('/v4/message/read', methods=['POST'])
def v4_sdk_set_notice_have_read():
    appid = request.form['appid']
    param = request.form['param']
    if appid is None or param is None:
        return response_data(400, 400, '客户端请求错误')
    from Utils.EncryptUtils import sdk_api_check_key
    params = sdk_api_check_key(request)
    ucid = params['ucid']
    message_info = params['message_info']
    if message_info['type'] is None or message_info['message_ids'] is None:
        return response_data(400, 400, '客户端请求错误')
    message_info_json = json.loads(message_info)
    for message in message_info_json:
        for message_id in message['message_ids']:
            is_exist = UserReadMessageLog.objects(Q(type=message['type'])
                                                  & Q(message_id=message_id)
                                                  & Q(ucid=ucid)).count()
            if is_exist == 0:
                user_read_message_log = UserReadMessageLog(type=message['type'],
                                                           message_id=message_id,
                                                           ucid=ucid)
                try:
                    user_read_message_log.save()
                except Exception as err:
                    service_logger.error(err)
                    return response_data(http_code=500, message="服务器出错啦/(ㄒoㄒ)/~~")
    return response_data(http_code=204)
