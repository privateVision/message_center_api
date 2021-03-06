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
from Service.UsersService import cms_api_request_check
from Utils.SystemUtils import log_exception

rebate_controller = Blueprint('RebateController', __name__)


# CMS 添加优惠券
@rebate_controller.route('/msa/v4/add_recharge', methods=['POST'])
@cms_api_request_check
def v4_cms_add_rebate():
    form = PostRebatesRequestForm.from_json(request.json)
    if not form.validate():
        log_exception(request, "优惠券请求校验异常：%s" % (form.errors,))
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
            log_exception(request, "发送优惠券异常：%s" % (err.message,))
            return response_data(http_code=200, code=0, message="kafka服务异常")
        return response_data(http_code=200)


# CMS 更新优惠券
@rebate_controller.route('/msa/v4/update_recharge', methods=['POST'])
@cms_api_request_check
def v4_cms_update_coupon():
    form = PostRebatesRequestForm.from_json(request.json)
    if not form.validate():
        log_exception(request, "优惠券请求校验异常：%s" % (form.errors,))
        return response_data(200, 0, '客户端请求错误')
    else:
        try:
            system_rebate_persist(form.data, False)
        except Exception, err:
            log_exception(request, "更新优惠券异常：%s" % (err.message,))
    return response_data(http_code=200)


# CMS 删除优惠券
@rebate_controller.route('/msa/v4/delete_recharge', methods=['POST'])
@cms_api_request_check
def v4_cms_delete_coupon():
    rebate_id = request.json.get('id')
    if rebate_id is None or rebate_id == '':
        log_exception(request, "客户端请求错误-rebate_id为空")
        return response_data(200, 0, '客户端请求错误')
    try:
        UsersMessage.objects(Q(type='rebate') & Q(mysql_id=rebate_id)).delete()
        UserMessage.objects(Q(type='rebate') & Q(mysql_id=rebate_id)).delete()
    except Exception, err:
        log_exception(request, "删除优惠券异常：%s" % (err.message,))
        return response_data(http_code=200, code=0, message="删除卡券失败")
    return response_data(http_code=200)

