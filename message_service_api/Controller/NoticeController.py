# _*_ coding: utf-8 _*_
import json

from flask import Blueprint, jsonify
from flask import request
from mongoengine import Q

from Controller import service_logger
from Controller.BaseController import response_data
from Model.MessageModel import UsersMessage
from Model.UserReadMessageLogModel import UserReadMessageLog
from RequestForm.GetNoticesRequestForm import GetNoticesRequestForm
from RequestForm.PostNoticesRequestForm import PostNoticesRequestForm
from RequestForm.PutNoticesRequestForm import PutNoticesRequestForm

notice_controller = Blueprint('NoticeController', __name__)


# CMS 发送公告
@notice_controller.route('/v4/notice', methods=['POST'])
def v4_cms_post_notice():
    """
    apk_id
    area
    user_type
    vip
    ucid

    :return:
    """
    form = PostNoticesRequestForm(request.form)  # POST 表单参数封装
    if not form.validate():
        print form.errors
        return response_data(400, 400, '客户端请求错误')
    else:
        print form.data['title']
        print form.data['apk_id']
        print form.data['area']
        from run import kafka_producer
        kafka_producer.send('message-service', b'收到数据 %s, %s' % (form.data['title'], form.data['area']))
        return response_data(http_code=200)


# SDK 获取公告列表
@notice_controller.route('/v4/notice', methods=['GET'])
def v4_sdk_get_notice_list():
        """
        apk_id
        area
        user_type
        vip
        ucid

        page
        count
        :return:
        """
        form = GetNoticesRequestForm(request.args)  # GET 参数封装
        if not form.validate():
            print form.errors
            return response_data(400, 400, '客户端请求错误')
        else:
            start_index = (form.data['page'] - 1) * form.data['count']
            end_index = start_index + form.data['count']
            # 查询用户相关的公告列表
            message_list_total_count = UsersMessage.objects(
                                    (Q(type='notice')
                                  & Q(apk_id=form.data['apk_id'])
                                  & Q(area=form.data['area'])
                                  & Q(user_type=form.data['user_type'])
                                  & Q(vip=form.data['vip']))
                                 | Q(ucid_list=form.data['ucid']))\
                .count()
            message_list = UsersMessage.objects(Q(type='notice')
                                & (Q(apk_id=form.data['apk_id'])
                                & Q(area=form.data['area'])
                                & Q(user_type=form.data['user_type'])
                                & Q(vip=form.data['vip']))
                                | Q(ucid_list=form.data['ucid']))\
                .order_by('-create_time')\
                [start_index:end_index]
            data = {
                "total_count": message_list_total_count,
                "data": message_list
            }
            return response_data(http_code=200, data=data)


# SDK 设置公告已读
@notice_controller.route('/v4/notice', methods=['PUT'])
def v4_sdk_set_notice_have_read():
            """
            notice_id
            ucid
            :return:
            """
            form = PutNoticesRequestForm(request.form)  # POST 表单参数封装
            if not form.validate():
                print form.errors
                return response_data(400, 400, '客户端请求错误')
            else:
                userReadMessageLog = UserReadMessageLog(notice_id=form.data['notice_id'], ucid=form.data['ucid'])
                try:
                    userReadMessageLog.save()
                except Exception as err:
                    service_logger.error(err)
                    return response_data(http_code=500, message="服务器出错啦/(ㄒoㄒ)/~~")
                return response_data(http_code=204)


# 检查消息是否存在用户消息表中
def check_is_exist_in_users_message(type=None, message_id=None):
    if type is None or message_id is None:
        return False
    pass