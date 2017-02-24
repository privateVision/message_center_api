# _*_ coding: utf-8 _*_
import json

from flask import Blueprint
from flask import request
from mongoengine import Q

from Controller import service_logger
from Controller.BaseController import response_data
from Model.MessageModel import UsersMessage
from RequestForm.PostMessagesRequestForm import PostMessagesRequestForm

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
            kafka_producer.send('message-service', json.dumps(message_info))
        except Exception, err:
            service_logger.error(err.message)
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
        service_logger.error(err.message)
        return response_data(http_code=500, code=500002, message="删除消息失败")
    return response_data(http_code=204)
