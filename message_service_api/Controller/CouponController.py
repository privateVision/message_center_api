# _*_ coding: utf-8 _*_
import json

import time
from flask import Blueprint
from flask import request
from mongoengine import Q

from Controller.BaseController import response_data
from LanguageConf import get_tips
from MiddleWare import service_logger
from MongoModel.MessageModel import UsersMessage
from MongoModel.UserMessageModel import UserMessage
from RequestForm.PostCouponsRequestForm import PostCouponsRequestForm
from Service.StorageService import system_coupon_update
from Service.UsersService import get_ucid_by_access_token, get_coupon_message_detail_info, \
    sdk_api_request_check, cms_api_request_check, get_stored_value_card_list, get_user_coupons_by_game, user_get_coupon
from Utils.SystemUtils import get_current_timestamp, log_exception

coupon_controller = Blueprint('CouponController', __name__)


# CMS 添加卡券
@coupon_controller.route('/msa/v4/add_coupon', methods=['POST'])
@cms_api_request_check
def v4_cms_add_coupon():
    form = PostCouponsRequestForm.from_json(request.json)
    if not form.validate():
        log_exception(request, '客户端请求错误: %s' % (json.dumps(form.errors)))
        return response_data(200, 0, get_tips('common', 'client_request_error'))
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
            return response_data(http_code=200, code=0, message=get_tips('cms', 'kafka_service_exception'))
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


# api 领取卡券
@coupon_controller.route('/msa/v4/get_coupon', methods=['POST'])
@sdk_api_request_check
def v4_user_get_coupon():
    ucid = get_ucid_by_access_token(request.form['_token'])
    game_id = int(request.form['_appid'])
    coupon_id = int(request.form['coupon_id'])
    if coupon_id is None or coupon_id == '':
        log_exception(request, '客户端请求错误-coupon_id为空')
        return response_data(200, 0, '客户端请求错误')
    result = user_get_coupon(ucid, coupon_id, game_id)
    if result:
        return response_data(http_code=200, message="领取成功")
    return response_data(http_code=200, code=0, message="领取失败")


# SDK 获取卡券列表
@coupon_controller.route('/msa/v4/coupons', methods=['POST'])
@sdk_api_request_check
def v4_sdk_get_broadcast_list():
    stime = time.time()
    ucid = get_ucid_by_access_token(request.form['_token'])
    game_id = int(request.form['_appid'])
    page = request.form['page'] if request.form.has_key('page') and request.form['page'] else 1
    count = request.form['count'] if request.form.has_key('count') and request.form['count'] else 10
    need_total_count = (int(page) * int(count))  # 需要的数据总数
    start_index = (int(page) - 1) * int(count)
    end_index = int(count)
    service_logger.info("用户：%s 获取卡券列表，数据从%s到%s" % (ucid, start_index, end_index))
    # 获取用户的储值卡数据列表
    value_card_total_count, value_card_list = get_stored_value_card_list(ucid, 0, start_index, end_index)
    # 储值卡没有数据,直接拿卡券的数据
    if value_card_total_count == 0:
        new_coupon_list = get_user_coupons_by_game(ucid, game_id, start_index, end_index)
        return response_data(http_code=200, data=new_coupon_list)
    # 储值卡数据足够一页数据
    if value_card_total_count >= need_total_count:
        return response_data(http_code=200, data=value_card_list)
    # 储值卡数据不够，用卡券数据补充
    else:
        left_count = int(need_total_count) - int(value_card_total_count)  # 还缺少的数据量
        head_count = (value_card_total_count / end_index + 1) * end_index - value_card_total_count
        tmp_count = left_count / end_index
        if tmp_count == 0:
            coupon_start_index = 0
            coupon_end_index = left_count
        else:
            coupon_start_index = (tmp_count - 1) * end_index + head_count
            coupon_end_index = coupon_start_index + end_index
        # 查询用户相关的卡券列表
        new_coupon_list = get_user_coupons_by_game(ucid, game_id, coupon_start_index, coupon_end_index)
        # 拼接储值卡和卡券列表返回
        value_card_list.extend(new_coupon_list)
        etime = time.time()
        service_logger.info("卡券列表耗时: %s" % (etime-stime,))
        return response_data(http_code=200, data=value_card_list)
