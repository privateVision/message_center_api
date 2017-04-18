# _*_ coding: utf-8 _*_
import json

from flask import Blueprint
from flask import request
from mongoengine import Q

from LanguageConf import get_tips
from MiddleWare import service_logger
from Controller.BaseController import response_data
from MongoModel.MessageModel import UsersMessage
from MongoModel.UserMessageModel import UserMessage
from RequestForm.PostMessagesRequestForm import PostMessagesRequestForm
from Service.UsersService import get_ucid_by_access_token, get_message_detail_info, find_user_account_is_freeze, \
    sdk_api_request_check, cms_api_request_check
from Utils.SystemUtils import get_current_timestamp, log_exception

message_controller = Blueprint('MessageController', __name__)


# CMS 发送消息
@message_controller.route('/msa/v4/add_message', methods=['POST'])
@cms_api_request_check
def v4_cms_post_broadcast():
    form = PostMessagesRequestForm.from_json(request.json)
    if not form.validate():
        log_exception(request, '客户端请求错误: %s' % (json.dumps(form.errors)))
        return response_data(200, 0, get_tips('common', 'client_request_error'))
    else:
        from run import kafka_producer
        try:
            message_info = {
                "type": "message",
                "message": form.data
            }
            message_str = json.dumps(message_info)
            service_logger.info("发送消息：%s" % (message_str,))
            kafka_producer.send('message-service', message_str)
        except Exception, err:
            log_exception(request, err.message)
            return response_data(http_code=200, code=0, message=get_tips('cms', 'kafka_service_exception'))
        return response_data(http_code=200)


# CMS 删除消息
@message_controller.route('/msa/v4/delete_message', methods=['POST'])
@cms_api_request_check
def v4_cms_delete_post_broadcast():
    message_id = request.json.get('id')
    if message_id is None or message_id == '':
        log_exception(request, '客户端请求错误-message_id为空')
        return response_data(200, 0, '客户端请求错误')
    try:
        UsersMessage.objects(Q(type='message') & Q(mysql_id=message_id)).delete()
        UserMessage.objects(Q(type='message') & Q(mysql_id=message_id)).delete()
    except Exception, err:
        log_exception(request, "删除消息异常：%s" % (err.message,))
        return response_data(http_code=200, code=0, message="删除消息失败")
    return response_data(http_code=200)


# SDK 获取消息列表
@message_controller.route('/msa/v4/messages', methods=['POST'])
@sdk_api_request_check
def v4_sdk_get_message_list():
    ucid = get_ucid_by_access_token(request.form['_token'])
    page = request.form['page'] if request.form.has_key('page') and request.form['page'] else 1
    count = request.form['count'] if request.form.has_key('count') and request.form['count'] else 10
    start_index = (int(page) - 1) * int(count)
    end_index = start_index + int(count)
    service_logger.info("用户：%s 获取消息列表，数据从%s到%s" % (ucid, start_index, end_index))
    # 查询用户相关的公告列表
    current_timestamp = get_current_timestamp()
    message_list = UserMessage.objects(
        Q(type='message')
        & Q(closed=0)
        # & Q(is_read=0)
        & Q(start_time__lte=current_timestamp)
        # & Q(end_time__gte=current_timestamp)  # 消息发送后是一直存在的
        & Q(ucid=ucid)).order_by('-create_timestamp')[start_index:end_index]
    data_list = []
    for message in message_list:
        message_info = get_message_detail_info(message['mysql_id'])
        if message_info is not None:
            if int(message['is_read']) == 1:
                message['is_read'] = True
            else:
                message['is_read'] = False
            message_resp = {
                'title': message_info['title'],
                'summary': message_info['description'],
                'type': message_info['atype'],
                'id': message_info['mysql_id'],
                'img': '',
                'url': '',
                'content': '',
                'is_read': message['is_read']
            }
            if 'img' in message_info:
                message_resp['img'] = message_info['img']
            if 'content' in message_info:
                message_resp['content'] = message_info['content']
            if 'url' in message_info:
                message_resp['url'] = message_info['url']
            data_list.append(message_resp)
    return response_data(http_code=200, data=data_list)
