# _*_ coding: utf-8 _*_
import json

import time
from flask import Blueprint
from flask import request

from Controller.BaseController import response_data
from MiddleWare import service_logger
from Service.UsersService import get_game_info_by_gameid, anfeng_helper_request_check, get_game_info_by_appid, \
    get_ucid_by_access_token, get_stored_value_card_list, get_user_all_coupons

anfeng_controller = Blueprint('AnfengController', __name__)


# 安锋助手获取卡券列表
@anfeng_controller.route('/msa/anfeng_helper/get_user_coupon', methods=['POST'])
@anfeng_helper_request_check
def v4_sdk_get_broadcast_list():
    ucid = get_ucid_by_access_token(request.json.get('_token'))
    status = int(request.json.get('status'))
    page = request.json.get('page') if request.json.has_key('page') and request.json.get('page') else 1
    count = request.json.get('pagesize') if request.json.has_key('pagesize') and request.json.get('pagesize') else 10
    need_total_count = (int(page) * int(count))  # 需要的数据总数
    start_index = (int(page) - 1) * int(count)
    end_index = int(count)
    service_logger.info("安锋助手用户：%s 获取卡券列表，数据从%s到%s" % (ucid, start_index, end_index))
    data = {
        'total_count': 0,
        'coupon_list': []
    }
    # 获取用户的储值卡数据列表
    value_card_total_count, value_card_list = get_stored_value_card_list(ucid, status, start_index, end_index)
    # 储值卡没有数据,直接拿卡券的数据
    if value_card_total_count == 0:
        coupon_total_count, new_coupon_list = get_user_all_coupons(ucid, status, start_index, end_index)
        data['total_count'] = coupon_total_count
        data['coupon_list'] = new_coupon_list
    # 储值卡数据足够一页数据
    if value_card_total_count >= need_total_count:
        coupon_total_count, new_coupon_list = get_user_all_coupons(ucid, status, start_index, end_index)
        data['total_count'] = value_card_total_count + coupon_total_count
        data['coupon_list'] = value_card_list
    # 储值卡数据不够，用卡券数据补充
    else:
        left_count = int(need_total_count) - int(value_card_total_count)  # 还缺少的数据量
        if int(page) == 1:
            coupon_start_index = 0
            coupon_end_index = left_count
        else:
            coupon_start_index = (int(page)-1)*int(count) - value_card_total_count
            coupon_end_index = end_index
        # 查询用户相关的卡券列表
        coupon_total_count, new_coupon_list = get_user_all_coupons(ucid, status, coupon_start_index, coupon_end_index)
        # 拼接储值卡和卡券列表返回
        value_card_list.extend(new_coupon_list)
        data['total_count'] = value_card_total_count + coupon_total_count
        data['coupon_list'] = value_card_list
    return response_data(http_code=200, data=data)


# 安锋助手用户获取已领礼包列表
@anfeng_controller.route('/msa/anfeng_helper/get_user_gifts', methods=['POST'])
@anfeng_helper_request_check
def v4_anfeng_helper_get_user_gifts():
    from run import mysql_cms_session
    ucid = request.json.get('uid')
    page = int(request.json.get('page'))
    count = int(request.json.get('pagesize'))
    start_index = (page - 1) * count
    end_index = start_index + count
    gift_list = []
    user_gift_total_count_sql = "select count(gift.id) from cms_gameGiftLog as log join cms_gameGift as gift" \
                                " on log.giftId = gift.id where gift.status = 'normal' and " \
                                "log.status = 'normal' and log.uid = %s " % (ucid,)
    get_user_gift_sql = "select gift.* from cms_gameGiftLog as log join cms_gameGift as gift on log.giftId = gift.id" \
                        " where gift.status = 'normal' and log.status = 'normal' and log.uid = %s limit %s, %s"\
                        % (ucid, start_index, end_index)
    total_count = mysql_cms_session.execute(user_gift_total_count_sql).scalar()
    user_gift_list = mysql_cms_session.execute(get_user_gift_sql).fetchall()
    for gift in user_gift_list:
        game = get_game_info_by_gameid(gift['gameId'])
        gift_info = {
            'id': gift['id'],
            'gameId': gift['gameId'],
            'gameName': gift['gameName'],
            'gameCover': game['cover'],
            'name': gift['name'],
            'gift': gift['gift'],
            'content': gift['content'],
            'label': gift['label'],
            'total': gift['total'],
            'num': gift['num'],
            'assignNum': gift['assignNum']
        }
        gift_list.append(gift_info)
    data = {
        'total_count': total_count,
        'gift_list': gift_list
    }
    return response_data(http_code=200, data=data)


# 安锋助手获取礼包是否被领取
@anfeng_controller.route('/msa/anfeng_helper/is_gift_get', methods=['POST'])
@anfeng_helper_request_check
def v4_anfeng_helper_is_user_gift_get():
    from run import mysql_cms_session
    ucid = request.json.get('ucid')
    gift_id = request.json.get('gift_id')
    is_exist_sql = "select count(*) from cms_gameGiftLog as log where log.status = 'normal'" \
                   " and log.uid = %s and log.giftId = %s " % (ucid, gift_id)
    is_exist = mysql_cms_session.execute(is_exist_sql).scalar()
    data = {
        'is_get': False
    }
    if is_exist > 0:
        data['is_get'] = True
    return response_data(http_code=200, data=data)
