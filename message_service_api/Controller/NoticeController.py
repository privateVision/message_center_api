# _*_ coding: utf-8 _*_
import json

from flask import Blueprint
from flask import request
from mongoengine import Q

from Controller import service_logger
from Controller.BaseController import response_data
from Model.MessageModel import UsersMessage
from Model.UserReadMessageLogModel import UserReadMessageLog
from RequestForm.GetMessagesRequestForm import GetMessagesRequestForm
from RequestForm.PostNoticesRequestForm import PostNoticesRequestForm
from RequestForm.PutMessageReadRequestForm import PutMessageReadRequestForm
from Service.StorageService import system_announcements_persist

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
            system_announcements_persist(form.data)
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
@notice_controller.route('/v4/notice', methods=['GET'])
def v4_sdk_get_notice_list():
    form = GetMessagesRequestForm(request.args)  # GET 参数封装
    if not form.validate():
        print form.errors
        return response_data(400, 400, '客户端请求错误')
    else:
        start_index = (form.data['page'] - 1) * form.data['count']
        end_index = start_index + form.data['count']
        # 查询用户相关的公告列表
        message_list_total_count = UsersMessage.objects(
            Q(type='notice')
            & Q(closed=0)
            & ((Q(app__apk_id=form.data['apk_id'])
                & Q(app__zone_id_list=form.data['area'])
                & Q(rtype=form.data['user_type'])
                & Q(vip=form.data['vip']))
               | Q(users=form.data['ucid']))) \
            .count()
        message_list = UsersMessage.objects(
            Q(type='notice')
            & Q(closed=0)
            & ((Q(app__apk_id=form.data['apk_id'])
                & Q(app__zone_id_list=form.data['area'])
                & Q(rtype=form.data['user_type'])
                & Q(vip=form.data['vip']))
               | Q(users=form.data['ucid']))) \
                           .order_by('-create_time') \
            [start_index:end_index]
        data = {
            "total_count": message_list_total_count,
            "data": message_list
        }
        return response_data(http_code=200, data=data)


# SDK 设置消息已读（消息通用）
@notice_controller.route('/v4/message/read', methods=['POST'])
def v4_sdk_set_notice_have_read():
    form = PutMessageReadRequestForm(request.form)  # POST 表单参数封装
    if not form.validate():
        print form.errors
        return response_data(400, 400, '客户端请求错误')
    else:
        message_info = form.data['message_info']
        message_info_json = json.loads(message_info)
        for message in message_info_json:
            for message_id in message['message_ids']:
                is_exist = UserReadMessageLog.objects(Q(type=message['type'])
                                     & Q(message_id=message_id)
                                     & Q(ucid=form.data['ucid'])).count()
                print is_exist
                if is_exist == 0:
                    user_read_message_log = UserReadMessageLog(type=message['type'],
                                                               message_id=message_id,
                                                               ucid=form.data['ucid'])
                    try:
                        user_read_message_log.save()
                    except Exception as err:
                        service_logger.error(err)
                        return response_data(http_code=500, message="服务器出错啦/(ㄒoㄒ)/~~")
        return response_data(http_code=204)
