# _*_ coding: utf-8 _*_
import json

from flask import Blueprint
from flask import request
from mongoengine import Q

from Controller import service_logger
from Controller.BaseController import response_data
from MongoModel.MessageModel import UsersMessage
from MongoModel.UserMessageModel import UserMessage
from RequestForm.PostCouponsRequestForm import PostCouponsRequestForm
from Service.StorageService import system_coupon_persist

coupon_controller = Blueprint('CouponController', __name__)


# CMS 添加卡券
@coupon_controller.route('/v4/coupon', methods=['POST'])
def v4_cms_add_coupon():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    form = PostCouponsRequestForm(request.form)  # POST 表单参数封装
    if not form.validate():
        service_logger.error(form.errors)
        return response_data(400, 400, '客户端请求错误')
    else:
        from run import kafka_producer
        try:
            message_info = {
                "type": "coupon",
                "message": form.data
            }
            kafka_producer.send('message-service', json.dumps(message_info))
        except Exception, err:
            service_logger.error(err.message)
            return response_data(http_code=500, code=500001, message="kafka服务异常")
        return response_data(http_code=200)


# CMS 更新卡券
@coupon_controller.route('/v4/coupon', methods=['PUT'])
def v4_cms_update_coupon():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    form = PostCouponsRequestForm(request.form)  # POST 表单参数封装
    if not form.validate():
        print form.errors
        return response_data(400, 400, '客户端请求错误')
    else:
        try:
            system_coupon_persist(form.data, False)
        except Exception, err:
            service_logger.error(err.message)
    return response_data(http_code=200)


# CMS 删除卡券
@coupon_controller.route('/v4/coupon', methods=['DELETE'])
def v4_cms_delete_coupon():
    from Utils.EncryptUtils import generate_checksum
    check_result, check_exception = generate_checksum(request)
    if not check_result:
        return check_exception
    coupon_id = request.form['id']
    if coupon_id is None or coupon_id == '':
        return response_data(400, 400, '客户端请求错误')
    try:
        UsersMessage.objects(Q(type='coupon') & Q(mysql_id=coupon_id)).delete()
        UserMessage.objects(Q(type='coupon') & Q(mysql_id=coupon_id)).delete()
    except Exception, err:
        service_logger.error(err.message)
        return response_data(http_code=500, code=500002, message="删除卡券失败")
    return response_data(http_code=204)

