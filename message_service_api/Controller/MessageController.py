# _*_ coding: utf-8 _*_
import json

from flask import Blueprint
from flask import request
from mongoengine import Q

from MiddleWare import service_logger
from Controller.BaseController import response_data
from MongoModel.MessageModel import UsersMessage
from MongoModel.UserMessageModel import UserMessage
from RequestForm.PostMessagesRequestForm import PostMessagesRequestForm
from Service.UsersService import get_ucid_by_access_token, get_message_detail_info
from Utils.SystemUtils import get_current_timestamp

message_controller = Blueprint('MessageController', __name__)


# CMS 发送消息
@message_controller.route('/v4/message', methods=['POST'])
def v4_cms_post_broadcast():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    form = PostMessagesRequestForm(request.form)  # POST 表单参数封装
    if not form.validate():
        print form.errors
        return response_data(400, 400, '客户端请求错误')
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
            service_logger.error("发送消息异常：%s" % (err.message,))
            return response_data(http_code=500, code=500001, message="kafka服务异常")
        return response_data(http_code=200)


# CMS 删除消息
@message_controller.route('/v4/message', methods=['DELETE'])
def v4_cms_delete_post_broadcast():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    message_id = request.form['id']
    if message_id is None or message_id == '':
        return response_data(400, 400, '客户端请求错误')
    try:
        UsersMessage.objects(Q(type='message') & Q(mysql_id=message_id)).delete()
    except Exception, err:
        service_logger.error("删除消息异常：%s" % (err.message,))
        return response_data(http_code=500, code=500002, message="删除消息失败")
    return response_data(http_code=204)


# SDK 获取消息列表
@message_controller.route('/v4/messages', methods=['POST'])
def v4_sdk_get_message_list():
    appid = request.form['appid']
    param = request.form['param']
    if appid is None or param is None:
        return response_data(400, 400, '客户端请求错误')
    from Utils.EncryptUtils import sdk_api_check_key
    params = sdk_api_check_key(request)
    if params:
        ucid = get_ucid_by_access_token(params['access_token'])
        if ucid:
            page = params['page'] if params.has_key('page') and params['page'] else 1
            count = params['count'] if params.has_key('count') and params['count'] else 10
            start_index = (page - 1) * count
            end_index = start_index + count
            service_logger.info("用户：%s 获取消息列表，数据从%s到%s" % (ucid, start_index, end_index))
            # 查询用户相关的公告列表
            current_timestamp = get_current_timestamp()
            message_list_total_count = UserMessage.objects(
                Q(type='message')
                & Q(closed=0)
                & Q(is_read=0)
                & Q(start_time__lte=current_timestamp)
                & Q(end_time__gte=current_timestamp)
                & Q(ucid=ucid)) \
                .count()
            message_list = UserMessage.objects(
                Q(type='message')
                & Q(closed=0)
                & Q(is_read=0)
                & Q(start_time__lte=current_timestamp)
                & Q(end_time__gte=current_timestamp)
                & Q(ucid=ucid)).order_by('-start_time')[start_index:end_index]
            data_list = []
            for message in message_list:
                message_info = get_message_detail_info(message['mysql_id'])
                message_resp = {
                    "meta_info": {},
                    "head": {},
                    "body": {}
                }
                message_resp['meta_info']['app'] = message_info['app']
                message_resp['meta_info']['rtype'] = message_info['rtype']
                message_resp['meta_info']['vip'] = message_info['vip']
                message_resp['head']['title'] = message_info['title']
                message_resp['head']['description'] = message_info['description']
                message_resp['head']['type'] = message_info['type']
                message_resp['head']['mysql_id'] = message_info['mysql_id']
                message_resp['body']['content'] = message_info['content']
                message_resp['body']['img'] = message_info['img']
                message_resp['body']['url'] = message_info['url']
                message_resp['body']['start_time'] = message_info['start_time']
                data_list.append(message_resp)
            data = {
                "total_count": message_list_total_count,
                "data": data_list
            }
            return response_data(http_code=200, data=data)
        else:
            return response_data(400, 400, '根据access_token获取用户id失败，请重新登录')