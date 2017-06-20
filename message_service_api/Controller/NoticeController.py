# _*_ coding: utf-8 _*_
import json

from flask import Blueprint
from flask import request
from mongoengine import Q

from MiddleWare import service_logger
from Controller.BaseController import response_data
from MongoModel.MessageModel import UsersMessage
from MongoModel.UserMessageModel import UserMessage
from MongoModel.UserReadMessageLogModel import UserReadMessageLog
from RequestForm.PostNoticesRequestForm import PostNoticesRequestForm
from Service.StorageService import system_notices_update, check_user_has_notice
from Service.UsersService import get_notice_message_detail_info, get_ucid_by_access_token, \
    sdk_api_request_check, cms_api_request_check, set_message_readed, find_is_message_readed, is_user_in_apks
from Utils.RedisUtil import RedisHandle
from Utils.SystemUtils import get_current_timestamp, log_exception

notice_controller = Blueprint('NoticeController', __name__)


# CMS 发送公告
@notice_controller.route('/msa/v4/add_notice', methods=['POST'])
@cms_api_request_check
def v4_cms_post_notice():
    form = PostNoticesRequestForm.from_json(request.json)  # JSON 数据转换
    if not form.validate():
        log_exception(request, "发送公告请求校验异常：%s" % (form.errors,))
        return response_data(200, 0, '客户端请求错误')
    else:
        from run import kafka_producer
        try:
            message_info = {
                "type": "notice",
                "message": form.data
            }
            message_str = json.dumps(message_info)
            service_logger.info("发送公告：%s" % (message_str,))
            kafka_producer.send('message-service', message_str)
        except Exception, err:
            log_exception(request, "发送公告异常：%s" % (err.message,))
            return response_data(http_code=200, code=0, message="kafka服务异常")
        return response_data(http_code=200)


# CMS 更新公告
@notice_controller.route('/msa/v4/update_notice', methods=['POST'])
@cms_api_request_check
def v4_cms_update_post_notice():
    form = PostNoticesRequestForm.from_json(request.json)  # JSON 数据转换
    if not form.validate():
        log_exception(request, "更新公告请求校验异常：%s" % (form.errors,))
        return response_data(200, 0, '客户端请求错误')
    else:
        try:
            service_logger.info("更新公告：%s" % (json.dumps(form.data),))
            system_notices_update(form.data)
        except Exception, err:
            log_exception(request, "更新公告异常：%s" % (err.message,))
    return response_data(http_code=200)


# CMS 关闭公告
@notice_controller.route('/msa/v4/notice/close', methods=['POST'])
@cms_api_request_check
def v4_cms_set_post_notice_closed():
    notice_id = request.json.get('id')
    if notice_id is None or notice_id == '':
        log_exception(request, "客户端请求错误-notice_id为空")
        return response_data(200, 0, '客户端请求错误')
    try:
        UsersMessage.objects(Q(type='notice') & Q(mysql_id=notice_id)).update(set__closed=1)
        UserMessage.objects(Q(type='notice') & Q(mysql_id=notice_id)).update(set__closed=1)
    except Exception, err:
        service_logger.error("关闭公告异常：%s" % (err.message,))
        return response_data(200, 0, '服务端异常')
    return response_data(http_code=200)


# CMS 打开公告
@notice_controller.route('/msa/v4/notice/open', methods=['POST'])
@cms_api_request_check
def v4_cms_set_post_notice_open():
    notice_id = request.json.get('id')
    if notice_id is None or notice_id == '':
        log_exception(request, "客户端请求错误-notice_id为空")
        return response_data(200, 0, '客户端请求错误')
    try:
        UsersMessage.objects(Q(type='notice') & Q(mysql_id=notice_id)).update(set__closed=0)
        UserMessage.objects(Q(type='notice') & Q(mysql_id=notice_id)).update(set__closed=0)
    except Exception, err:
        service_logger.error("打开公告异常：%s" % (err.message,))
        return response_data(200, 0, '服务端异常')
    return response_data(http_code=200)


# CMS 设置礼包优先级排序
@notice_controller.route('/msa/v4/notice/sort', methods=['POST'])
@cms_api_request_check
def v4_cms_set_post_notice_sort():
    sort_data = request.json.get('sort_data')
    if sort_data is None or sort_data == '':
        log_exception(request, "客户端请求错误-sort_data为空")
        return response_data(200, 0, '客户端请求错误')
    try:
        for sort in sort_data:
            notice_id = sort['id']
            sortby = sort['sortby']
            UsersMessage.objects(Q(type='notice') & Q(mysql_id=notice_id)).update(set__sortby=sortby)
            UserMessage.objects(Q(type='notice') & Q(mysql_id=notice_id)).update(set__sortby=sortby)
    except Exception, err:
        service_logger.error("设置公告优先级异常：%s" % (err.message,))
        return response_data(200, 0, '服务端异常')
    return response_data(http_code=200)


# SDK 获取公告列表
@notice_controller.route('/msa/v4/notices', methods=['POST'])
@sdk_api_request_check
def v4_sdk_get_notice_list():
    ucid = get_ucid_by_access_token(request.form['_token'])
    appid = int(request.form['_appid'])
    # 查询用户相关的公告列表
    current_timestamp = get_current_timestamp()
    check_user_has_notice(ucid, 'notice')
    message_list = UserMessage.objects(
        Q(type='notice')
        & Q(closed=0)
        & Q(start_time__lte=current_timestamp)
        & Q(end_time__gte=current_timestamp)
        & Q(ucid=ucid)).order_by('-sortby', '-create_timestamp')[0:1]
    data_list = []
    for message in message_list:
        message_resp = {
            'id': '',
            'title': '',
            'type': '',
            'content': '',
            'button_content': '',
            'button_type': '',
            'button_url': '',
            # 'end_time': '',
            # 'enter_status': '',
            'img': '',
            # 'open_type': '',
            'show_times': '',
            'url': '',
            # 'url_type': ''
        }
        message_info = get_notice_message_detail_info(message['mysql_id'])
        if message_info is not None:
            message_resp['id'] = message_info['mysql_id']
            message_resp['title'] = message_info['title']
            message_resp['type'] = message_info['atype']
            message_resp['show_times'] = message_info['show_times']
            if 'content' in message_info:
                message_resp['content'] = message_info['content']
            if message_resp['type'] == 2:  # 图片类型的公告
                if 'img' in message_info:
                    message_resp['content'] = message_info['img']
            if 'url' in message_info:
                message_resp['url'] = message_info['url']
            if 'button_type' in message_info:
                message_resp['button_type'] = message_info['button_type']
            if 'button_content' in message_info:
                message_resp['button_content'] = message_info['button_content']
            if 'button_url' in message_info:
                message_resp['button_url'] = message_info['button_url']
            if is_user_in_apks(appid, message_info['app']):
                data_list.append(message_resp)
    return response_data(http_code=200, data=data_list)


# SDK 设置消息已读（消息通用）
@notice_controller.route('/msa/v4/message/read', methods=['POST'])
@sdk_api_request_check
def v4_sdk_set_notice_have_read():
    ucid = get_ucid_by_access_token(request.form['_token'])
    message_type = request.form['type']
    message_id = int(request.form['message_id'])
    if message_type is None or message_id is None:
        log_exception(request, '客户端请求错误-type或message_id为空')
        return response_data(200, 0, '客户端请求错误')
    if not find_is_message_readed(ucid, message_type, message_id):
        try:
            UserMessage.objects(Q(type=message_type)
                                & Q(mysql_id=message_id)
                                & Q(ucid=ucid)).update(set__is_read=1)
            # 减少缓存未读消息数
            RedisHandle.hdecrby(ucid, message_type)
            # user_read_message_log.save()
            set_message_readed(ucid, message_type, message_id)
        except Exception as err:
            log_exception(request, "设置消息已读异常：%s" % (err.message,))
            return response_data(http_code=200, code=0, message="服务器出错啦/(ㄒoㄒ)/~~")
    return response_data(http_code=200)
