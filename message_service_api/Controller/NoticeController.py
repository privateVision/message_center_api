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
from Service.StorageService import system_notices_update
from Service.UsersService import get_notice_message_detail_info, get_ucid_by_access_token, \
    sdk_api_request_check, cms_api_request_check
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


# SDK 获取公告列表
@notice_controller.route('/msa/v4/notices', methods=['POST'])
@sdk_api_request_check
def v4_sdk_get_notice_list():
    ucid = get_ucid_by_access_token(request.form['_token'])
    # page = request.form['page'] if request.form.has_key('page') and request.form['page'] else 1
    # count = request.form['count'] if request.form.has_key('count') and request.form['count'] else 10
    # start_index = (int(page) - 1) * int(count)
    # end_index = start_index + int(count)
    # service_logger.info("用户：%s 获取公告列表，数据从%s到%s" % (ucid, start_index, end_index))
    # 查询用户相关的公告列表
    current_timestamp = get_current_timestamp()
    # message_list_total_count = UserMessage.objects(
    #     Q(type='notice')
    #     & Q(closed=0)
    #     & Q(is_read=0)
    #     & Q(start_time__lte=current_timestamp)
    #     & Q(end_time__gte=current_timestamp)
    #     & Q(ucid=ucid)) \
    #     .count()
    message_list = UserMessage.objects(
        Q(type='notice')
        & Q(closed=0)
        # & Q(is_read=0)
        & Q(start_time__lte=current_timestamp)
        & Q(end_time__gte=current_timestamp)
        & Q(ucid=ucid)).order_by('sortby, -create_time')
    data_list = []
    message_resp = {
        'title': '',
        'type': '',
        'id': '',
        'content': '',
        'button_content': '',
        'button_type': '',
        'button_url': '',
        'end_time': '',
        'enter_status': '',
        'img': '',
        'open_type': '',
        'show_times': '',
        'url': '',
        'url_type': ''
    }
    for message in message_list:
        message_info = get_notice_message_detail_info(message['mysql_id'])
        message_resp['id'] = message_info['mysql_id']
        message_resp['type'] = message_info['atype']
        message_resp['title'] = message_info['title']
        message_resp['content'] = message_info['content']
        message_resp['url'] = message_info['url']
        message_resp['show_times'] = message_info['show_times']
        message_resp['button_content'] = message_info['button_content']
        message_resp['button_url'] = message_info['button_url']
        # message_resp = {
        #     "meta_info": {},
        #     "head": {},
        #     "body": {}
        # }
        # message_resp['meta_info']['app'] = message_info['app']
        # message_resp['meta_info']['rtype'] = message_info['rtype']
        # message_resp['meta_info']['vip'] = message_info['vip']
        # message_resp['head']['title'] = message_info['title']
        # message_resp['head']['type'] = message_info['type']
        # message_resp['head']['mysql_id'] = message_info['mysql_id']
        # message_resp['head']['atype'] = message_info['atype']
        # message_resp['body']['content'] = message_info['content']
        # message_resp['body']['button_content'] = message_info['button_content']
        # message_resp['body']['button_type'] = message_info['button_type']
        # message_resp['body']['button_url'] = message_info['button_url']
        # message_resp['body']['end_time'] = message_info['end_time']
        # message_resp['body']['enter_status'] = message_info['enter_status']
        # message_resp['body']['img'] = message_info['img']
        # message_resp['body']['open_type'] = message_info['open_type']
        # message_resp['body']['start_time'] = message_info['start_time']
        # message_resp['body']['end_time'] = message_info['end_time']
        # message_resp['body']['show_times'] = message_info['show_times']
        # message_resp['body']['url'] = message_info['url']
        # message_resp['body']['url_type'] = message_info['url_type']
        data_list.append(message_resp)
    # message_list_total_count = len(message_list)
    # 用户没有公告，重设redis标记，避免再次获取
    # if message_list_total_count == 0:
    #     message_resp = []
    #     RedisHandle.clear_user_data_mark_in_redis(ucid, 'notice')
    # data = {
    #     "total_count": message_list_total_count,
    #     "data": message_resp
    # }
    return response_data(http_code=200, data=data_list)


# SDK 设置消息已读（消息通用）
@notice_controller.route('/msa/v4/message/read', methods=['POST'])
@sdk_api_request_check
def v4_sdk_set_notice_have_read():
    ucid = get_ucid_by_access_token(request.form['_token'])
    message_type = request.form['type']
    message_id = request.form['message_id']
    if message_type is None or message_id is None:
        log_exception(request, '客户端请求错误-type或message_id为空')
        return response_data(200, 0, '客户端请求错误')

    is_exist = UserReadMessageLog.objects(Q(type=message_type)
                                          & Q(message_id=message_id)
                                          & Q(ucid=ucid)).count()
    if is_exist == 0:
        user_read_message_log = UserReadMessageLog(type=message_type,
                                                   message_id=message_id,
                                                   ucid=ucid)
        try:
            UserMessage.objects(Q(type=message_type)
                                & Q(mysql_id=message_id)
                                & Q(ucid=ucid)).update(set__is_read=1)
            # 减少缓存未读消息数
            RedisHandle.hdecrby(ucid, message_type)
            user_read_message_log.save()
        except Exception as err:
            log_exception(request, "设置消息已读异常：%s" % (err.message,))
            return response_data(http_code=200, code=0, message="服务器出错啦/(ㄒoㄒ)/~~")
    return response_data(http_code=200)
