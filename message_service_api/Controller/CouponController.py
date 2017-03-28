# _*_ coding: utf-8 _*_
import json

import time
from flask import Blueprint
from flask import request
from mongoengine import Q

from Controller.BaseController import response_data
from MiddleWare import service_logger
from MongoModel.MessageModel import UsersMessage
from MongoModel.UserMessageModel import UserMessage
from RequestForm.PostCouponsRequestForm import PostCouponsRequestForm
from Service.StorageService import system_coupon_update
from Service.UsersService import get_ucid_by_access_token, get_coupon_message_detail_info, \
    sdk_api_request_check, cms_api_request_check, get_stored_value_card_list
from Utils.SystemUtils import get_current_timestamp, log_exception

coupon_controller = Blueprint('CouponController', __name__)


# CMS 添加卡券
@coupon_controller.route('/msa/v4/add_coupon', methods=['POST'])
@cms_api_request_check
def v4_cms_add_coupon():
    form = PostCouponsRequestForm.from_json(request.json)
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
            coupon_str = json.dumps(message_info)
            service_logger.info("发送卡券：%s" % (coupon_str,))
            kafka_producer.send('message-service', coupon_str)
        except Exception, err:
            log_exception(request, err.message)
            return response_data(http_code=200, code=0, message="kafka服务异常")
        return response_data(http_code=200)


# CMS 更新卡券
@coupon_controller.route('/msa/v4/update_coupon', methods=['POST'])
@cms_api_request_check
def v4_cms_update_coupon():
    form = PostCouponsRequestForm.from_json(request.json)
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
@coupon_controller.route('/msa/v4/delete_coupon', methods=['POST'])
@cms_api_request_check
def v4_cms_delete_coupon():
    coupon_id = request.json.get('id')
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
@coupon_controller.route('/msa/v4/coupons', methods=['POST'])
@sdk_api_request_check
def v4_sdk_get_broadcast_list():
    ucid = get_ucid_by_access_token(request.form['_token'])
    page = request.form['page'] if request.form.has_key('page') and request.form['page'] else 1
    count = request.form['count'] if request.form.has_key('count') and request.form['count'] else 10
    need_total_count = (int(page) * int(count))  # 需要的数据总数
    start_index = (int(page) - 1) * int(count)
    end_index = start_index + int(count)
    service_logger.info("用户：%s 获取卡券列表，数据从%s到%s" % (ucid, start_index, end_index))
    # 获取用户的储值卡数据列表
    value_card_total_count, value_card_list = get_stored_value_card_list(ucid, start_index, end_index)
    # 储值卡数据足够一页数据
    if value_card_total_count >= need_total_count:
        return response_data(http_code=200, data=value_card_list)
    # 储值卡数据不够一页，用卡券数据补充
    else:
        left_count = int(need_total_count) - int(value_card_total_count)  # 还缺少的数据量
        left_page = int(left_count/int(count))
        if left_page == 0:
            coupon_start_index = 0
            coupone_end_index = left_count
        else:
            head_count = ((int(value_card_total_count/10) + 1) * int(count)) - int(value_card_total_count)
            if left_page == 0:
                coupon_start_index = int(left_page)*head_count
            else:
                coupon_start_index = int(left_page) * head_count + (int(left_page) - 1) * int(count)
            coupone_end_index = left_count
        # 查询用户相关的卡券列表
        current_timestamp = get_current_timestamp()
        message_list = UserMessage.objects(
            (Q(type='coupon') & Q(closed=0) & Q(is_read=0) & Q(is_time=0) & Q(ucid=ucid))
            |
            (Q(type='coupon') & Q(closed=0) & Q(is_read=0) & Q(is_time=1) & Q(ucid=ucid)
             & Q(start_time__lte=current_timestamp) & Q(end_time__gte=current_timestamp))) \
                           .order_by('-start_time')[coupon_start_index:coupone_end_index]
        new_coupon_list = []
        for message in message_list:
            message_info = get_coupon_message_detail_info(message['mysql_id'])
            unlimited_time = True
            if message_info['is_time'] == 0:
                unlimited_time = False
            time_out = False
            now = int(time.time())
            if message_info['end_time'] < now:
                time_out = True
            message_resp = {
                'id': message_info['mysql_id'],
                'name': message_info['name'],
                'type': 2,
                'start_time': message_info['start_time'],
                'end_time': message_info['end_time'],
                'desc': message_info['info'],
                'fee': message_info['money'],
                'method': message_info['method'],
                'use_condition': "满%s可用" % (message_info['full'],),
                'unlimited_time': unlimited_time,
                'time_out': time_out
            }
            new_coupon_list.append(message_resp)
        if left_page == 0:
            value_card_list.extend(new_coupon_list)
            return response_data(http_code=200, data=value_card_list)
        else:
            return response_data(http_code=200, data=new_coupon_list)
