# _*_ coding: utf-8 _*_
import json

from flask import Blueprint
from flask import request
from mongoengine import Q

from Controller import service_logger
from Controller.BaseController import response_data
from MongoModel.MessageModel import UsersMessage
from RequestForm.GetMessagesRequestForm import GetMessagesRequestForm
from RequestForm.PostBroadcastsRequestForm import PostBroadcastsRequestForm
from Service.StorageService import system_broadcast_persist

broadcast_controller = Blueprint('BroadcastController', __name__)


# CMS 发送广播
@broadcast_controller.route('/v4/broadcast', methods=['POST'])
def v4_cms_post_broadcast():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    form = PostBroadcastsRequestForm(request.form)  # POST 表单参数封装
    if not form.validate():
        print form.errors
        return response_data(400, 400, '客户端请求错误')
    else:
        from run import kafka_producer
        try:
            message_info = {
                "type": "broadcast",
                "message": form.data
            }
            kafka_producer.send('message-service', json.dumps(message_info))
        except Exception, err:
            service_logger.error(err.message)
            return response_data(http_code=500, code=500001, message="kafka服务异常")
        return response_data(http_code=200)


# CMS 广播
@broadcast_controller.route('/v4/broadcast', methods=['PUT'])
def v4_cms_update_broadcast():
    pass
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    form = PostBroadcastsRequestForm(request.form)  # POST 表单参数封装
    if not form.validate():
        print form.errors
        return response_data(400, 400, '客户端请求错误')
    else:
        try:
            system_broadcast_persist(form.data)
        except Exception, err:
            service_logger.error(err.message)
    return response_data(http_code=200)


# CMS 删除广播
@broadcast_controller.route('/v4/broadcast', methods=['DELETE'])
def v4_cms_delete_post_broadcast():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    broadcast_id = request.form['id']
    if broadcast_id is None or broadcast_id == '':
        return response_data(400, 400, '客户端请求错误')
    try:
        UsersMessage.objects(Q(type='broadcast') & Q(mysql_id=broadcast_id)).delete()
    except Exception, err:
        service_logger.error(err.message)
        return response_data(http_code=500, code=500002, message="删除广播失败")
    return response_data(http_code=204)


# SDK 获取广播列表
@broadcast_controller.route('/v4/broadcast', methods=['GET'])
def v4_sdk_get_broadcast_list():
    form = GetMessagesRequestForm(request.args)  # GET 参数封装
    if not form.validate():
        print form.errors
        return response_data(400, 400, '客户端请求错误')
    else:
        start_index = (form.data['page'] - 1) * form.data['count']
        end_index = start_index + form.data['count']
        # 查询用户相关的公告列表
        message_list_total_count = UsersMessage.objects(
            Q(type='broadcast')
            & Q(closed=0)
            & ((Q(app__apk_id=form.data['apk_id'])
                & Q(app__zone_id_list=form.data['area'])
                & Q(rtype=form.data['user_type'])
                & Q(vip=form.data['vip']))
               | Q(users=form.data['ucid']))) \
            .count()
        message_list = UsersMessage.objects(
            Q(type='broadcast')
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