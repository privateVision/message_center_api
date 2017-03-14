# _*_ coding: utf-8 _*_
import json

from flask import Blueprint
from flask import request
from mongoengine import Q

from Controller.BaseController import response_data
from MiddleWare import service_logger
from MongoModel.MessageModel import UsersMessage
from MongoModel.UserMessageModel import UserMessage
from RequestForm.PostCouponsRequestForm import PostCouponsRequestForm
from Service.StorageService import system_coupon_update
from Service.UsersService import get_ucid_by_access_token, get_coupon_message_detail_info
from Utils.SystemUtils import get_current_timestamp, log_exception

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
        log_exception(request, '客户端请求错误: %s' % (json.dumps(form.errors)))
        return response_data(200, 0, '客户端请求错误')
    else:
        from run import kafka_producer
        try:
            message_info = {
                "type": "coupon",
                "message": form.data
            }
            kafka_producer.send('message-service', json.dumps(message_info))
        except Exception, err:
            log_exception(request, err.message)
            return response_data(http_code=200, code=0, message="kafka服务异常")
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
        log_exception(request, '客户端请求错误: %s' % (json.dumps(form.errors)))
        return response_data(200, 0, '客户端请求错误')
    else:
        try:
            system_coupon_update(form.data)
        except Exception, err:
            log_exception(request, err.message)
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
        log_exception(request, '客户端请求错误-coupon_id为空')
        return response_data(200, 0, '客户端请求错误')
    try:
        UsersMessage.objects(Q(type='coupon') & Q(mysql_id=coupon_id)).delete()
        UserMessage.objects(Q(type='coupon') & Q(mysql_id=coupon_id)).delete()
    except Exception, err:
        log_exception(request, err.message)
        return response_data(http_code=200, code=0, message="删除卡券失败")
    return response_data(http_code=200)


# SDK 获取卡券列表
@coupon_controller.route('/v4/coupons', methods=['POST'])
def v4_sdk_get_broadcast_list():
    appid = request.form['appid']
    param = request.form['param']
    if appid is None or param is None:
        log_exception(request, '客户端请求错误-appid或param为空')
        return response_data(200, 0, '客户端请求错误')
    from Utils.EncryptUtils import sdk_api_check_key
    params = sdk_api_check_key(request)
    if params:
        ucid = get_ucid_by_access_token(params['token'])
        if ucid:
            page = params['page'] if params.has_key('page') and params['page'] else 1
            count = params['count'] if params.has_key('count') and params['count'] else 10
            start_index = (int(page) - 1) * int(count)
            end_index = start_index + int(count)
            service_logger.info("用户：%s 获取卡券列表，数据从%s到%s" % (ucid, start_index, end_index))
            # 查询用户相关的公告列表
            current_timestamp = get_current_timestamp()
            message_list_total_count = UserMessage.objects(
                (Q(type='coupon')&Q(closed=0)&Q(is_read=0)&Q(is_time=0)&Q(ucid=ucid))
                |
                (Q(type='coupon')&Q(closed=0)&Q(is_read=0)&Q(is_time=1)&Q(ucid=ucid)
                 &Q(start_time__lte=current_timestamp)&Q(end_time__gte=current_timestamp)))\
                .count()
            message_list = UserMessage.objects(
                (Q(type='coupon') & Q(closed=0) & Q(is_read=0) & Q(is_time=0) & Q(ucid=ucid))
                |
                (Q(type='coupon') & Q(closed=0) & Q(is_read=0) & Q(is_time=1) & Q(ucid=ucid)
                 & Q(start_time__lte=current_timestamp) & Q(end_time__gte=current_timestamp))) \
                .order_by('-start_time')[start_index:end_index]
            data_list = []
            for message in message_list:
                message_info = get_coupon_message_detail_info(message['mysql_id'])
                message_resp = {
                    "meta_info": {},
                    "head": {},
                    "body": {}
                }
                message_resp['meta_info']['app'] = message_info['app']
                message_resp['meta_info']['rtype'] = message_info['rtype']
                message_resp['meta_info']['vip'] = message_info['vip']
                message_resp['head']['title'] = message_info['title']
                message_resp['head']['type'] = message_info['type']
                message_resp['head']['mysql_id'] = message_info['mysql_id']
                message_resp['body']['is_first'] = message_info['is_first']
                message_resp['body']['info'] = message_info['info']
                message_resp['body']['num'] = message_info['num']
                message_resp['body']['full'] = message_info['full']
                message_resp['body']['money'] = message_info['money']
                message_resp['body']['method'] = message_info['method']
                message_resp['body']['start_time'] = message_info['start_time']
                message_resp['body']['end_time'] = message_info['end_time']
                message_resp['body']['is_time'] = message_info['is_time']
                data_list.append(message_resp)
            data = {
                "total_count": message_list_total_count,
                "data": data_list
            }
            return response_data(http_code=200, data=data)
        else:
            log_exception(request, '根据token获取用户id失败，请重新登录')
            return response_data(200, 0, '根据token获取用户id失败，请重新登录')

