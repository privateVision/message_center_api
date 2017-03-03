# _*_ coding: utf-8 _*_
import json

from flask import Blueprint
from flask import request
from mongoengine import Q

from Controller.BaseController import response_data
from MiddleWare import service_logger
from MongoModel.MessageModel import UsersMessage
from MongoModel.UserMessageModel import UserMessage
from RequestForm.PostRebatesRequestForm import PostRebatesRequestForm
from Service.StorageService import system_rebate_persist

rebate_controller = Blueprint('RebateController', __name__)


# CMS 添加优惠券
@rebate_controller.route('/v4/rebate', methods=['POST'])
def v4_cms_add_rebate():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    form = PostRebatesRequestForm(request.form)  # POST 表单参数封装
    if not form.validate():
        service_logger.error("优惠券请求校验异常：%s" % (form.errors,))
        return response_data(200, 0, '客户端请求错误')
    else:
        from run import kafka_producer
        try:
            message_info = {
                "type": "rebate",
                "message": form.data
            }
            message_str = json.dumps(message_info)
            service_logger.info("发送优惠券：%s" % (message_str,))
            kafka_producer.send('message-service', message_str)
        except Exception, err:
            service_logger.error("发送优惠券异常：%s" % (err.message,))
            return response_data(http_code=200, code=0, message="kafka服务异常")
        return response_data(http_code=200)


# CMS 更新优惠券
@rebate_controller.route('/v4/rebate', methods=['PUT'])
def v4_cms_update_coupon():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    form = PostRebatesRequestForm(request.form)  # POST 表单参数封装
    if not form.validate():
        service_logger.error("优惠券请求校验异常：%s" % (form.errors,))
        return response_data(200, 0, '客户端请求错误')
    else:
        try:
            system_rebate_persist(form.data, False)
        except Exception, err:
            service_logger.error("更新优惠券异常：%s" % (err.message,))
    return response_data(http_code=200)


# CMS 删除优惠券
@rebate_controller.route('/v4/rebate', methods=['DELETE'])
def v4_cms_delete_coupon():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    rebate_id = request.form['id']
    if rebate_id is None or rebate_id == '':
        return response_data(200, 0, '客户端请求错误')
    try:
        UsersMessage.objects(Q(type='rebate') & Q(mysql_id=rebate_id)).delete()
        UserMessage.objects(Q(type='rebate') & Q(mysql_id=rebate_id)).delete()
    except Exception, err:
        service_logger.error("删除优惠券异常：%s" % (err.message,))
        return response_data(http_code=200, code=0, message="删除卡券失败")
    return response_data(http_code=204)

