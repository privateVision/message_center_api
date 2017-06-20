# _*_ coding: utf-8 _*_
import json

import time
from flask import Blueprint
from flask import request
from mongoengine import Q

from LanguageConf import get_tips
from MiddleWare import service_logger
from Controller.BaseController import response_data
from MongoModel.MessageModel import UsersMessage
from MongoModel.UserMessageModel import UserMessage
from RequestForm.PostAccountMessagesRequestForm import PostAccountMessagesRequestForm
from RequestForm.PostMessagesRequestForm import PostMessagesRequestForm
from Service.StorageService import check_user_has_notice
from Service.UsersService import get_ucid_by_access_token, get_message_detail_info, \
    sdk_api_request_check, cms_api_request_check, anfeng_helper_request_check, set_message_readed
from Utils.RedisUtil import RedisHandle
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


# 业务系统 发送消息
@message_controller.route('/msa/v4.2/account_message', methods=['POST'])
@anfeng_helper_request_check
def v4_api_post_account_message():
    form = PostAccountMessagesRequestForm(request.form)
    if not form.validate():
        log_exception(request, '客户端请求错误: %s' % (json.dumps(form.errors)))
        return response_data(200, 0, get_tips('common', 'client_request_error'))
    else:
        try:
            users_message = UsersMessage()
            users_message.create_timestamp = int(time.time())
            users_message.id = '%s-%s-%s' % ('message', form.data['ucid'], users_message.create_timestamp)
            users_message.mysql_id = users_message.create_timestamp
            users_message.type = 'message'
            users_message.atype = 2
            users_message.show_times = 0
            users_message.start_time = users_message.create_timestamp
            users_message.end_time = 0
            users_message.title = form.data['title']
            users_message.description = form.data['description']
            users_message.content = form.data['content']
            users_message.is_time = 0
            users_message.users = None
            users_message.rtype = None
            users_message.vip = None
            users_message.sortby = 0
            users_message.app = None
            users_message.save()
            # --------------------------------
            user_message = UserMessage()
            user_message.id = users_message.id
            user_message.ucid = form.data['ucid']
            user_message.type = 'message'
            user_message.mysql_id = users_message.mysql_id
            user_message.start_time = users_message.start_time
            user_message.end_time = 0
            user_message.is_time = 0
            user_message.create_timestamp = users_message.create_timestamp
            user_message.save()
        except Exception, err:
            service_logger.error("添加账户消息异常：%s" % (err.message,))
            return response_data(http_code=200, code=0, message=get_tips('cms', 'add_account_message_exception'))
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
    stime = time.time()
    ucid = get_ucid_by_access_token(request.form['_token'])
    page = request.form['page'] if request.form.has_key('page') and request.form['page'] else 1
    count = request.form['count'] if request.form.has_key('count') and request.form['count'] else 10
    start_index = (int(page) - 1) * int(count)
    end_index = start_index + int(count)
    service_logger.info("用户：%s 获取消息列表，数据从%s到%s" % (ucid, start_index, end_index))
    # 查询用户相关的公告列表
    current_timestamp = get_current_timestamp()
    if start_index == 0:
        check_user_has_notice(ucid, 'message')
    message_list = UserMessage.objects(
        Q(type='message')
        & Q(ucid=ucid)
        & Q(closed=0)
        & Q(start_time__lte=current_timestamp)).order_by('-create_timestamp')[start_index:end_index]
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
    etime = time.time()
    service_logger.info("消息列表耗时：%s" % (etime-stime,))
    return response_data(http_code=200, data=data_list)


# SDK-4.2 消息删除
@message_controller.route('/msa/v4.2/messages', methods=['DELETE'])
@sdk_api_request_check
def v4_sdk_delete_message_list():
    ucid = get_ucid_by_access_token(request.form['_token'])
    if 'id_list' in request.form:
        id_list = json.loads(request.form['id_list'])
        if len(id_list) > 0:
            service_logger.info("用户：%s 删除消息列表，数据id为：%s" % (ucid, id_list))
            try:
                for mysql_id in id_list:
                    msg_info = UserMessage.objects(Q(type='message') & Q(mysql_id=mysql_id)
                                                   & Q(ucid=ucid) & Q(closed=0)).first()
                    if msg_info is not None:
                        UserMessage.objects(
                            Q(type='message')
                            & Q(ucid=ucid)
                            & Q(mysql_id=mysql_id)).update(set__closed=1)
                        is_msg_readed = UserMessage.objects(Q(type='message') & Q(mysql_id=mysql_id)
                                                            & Q(ucid=ucid) & Q(is_read=0)).first()
                        # 减少缓存未读消息数
                        if is_msg_readed:
                            RedisHandle.hdecrby(ucid, 'message')
            except Exception, e:
                service_logger.error("sdk删除用户消息异常：%s", e.message)
                response_data(200, 0, get_tips('message', 'delete_user_message_exception'))
    if 'delete_all' in request.form:
        service_logger.info("用户：%s 删除全部消息列表" % (ucid,))
        if int(request.form['delete_all']) == 1:
            try:
                UserMessage.objects(
                    Q(type='message')
                    & Q(ucid=ucid)).update(set__closed=1)
                RedisHandle.hset(ucid, 'message', 0)
            except Exception, e:
                service_logger.error("sdk删除用户消息异常：%s", e.message)
                response_data(200, 0, get_tips('message', 'delete_user_message_exception'))
    return response_data(http_code=200)


# SDK-4.2 消息已读
@message_controller.route('/msa/v4.2/message/read', methods=['POST'])
@sdk_api_request_check
def v4_sdk_set_message_list_readed():
    ucid = get_ucid_by_access_token(request.form['_token'])
    if 'id_list' in request.form:
        id_list = json.loads(request.form['id_list'])
        if len(id_list) > 0:
            service_logger.info("用户：%s 设置消息已读，数据id为：%s" % (ucid, id_list))
            try:
                for mysql_id in id_list:
                    msg_info = UserMessage.objects(Q(type='message') & Q(mysql_id=mysql_id) & Q(ucid=ucid)
                                                    & Q(closed=0) & Q(is_read=0)).first()
                    if msg_info is not None:
                        UserMessage.objects(
                            Q(type='message')
                            & Q(ucid=ucid)
                            & Q(mysql_id=mysql_id)).update(set__is_read=1)
                        # 减少缓存未读消息数
                        RedisHandle.hdecrby(ucid, 'message')
            except Exception, e:
                service_logger.error("sdk删除用户消息异常：%s", e.message)
                response_data(200, 0, get_tips('message', 'delete_user_message_exception'))
    if 'read_all' in request.form:
        service_logger.info("用户：%s 设置全部消息列表已读" % (ucid,))
        if int(request.form['read_all']) == 1:
            try:
                UserMessage.objects(
                    Q(type='message')
                    & Q(ucid=ucid)).update(set__is_read=1)
                RedisHandle.hset(ucid, 'message', 0)
            except Exception, e:
                service_logger.error("sdk删除用户消息异常：%s", e.message)
                response_data(200, 0, get_tips('message', 'delete_user_message_exception'))
    return response_data(http_code=200)

