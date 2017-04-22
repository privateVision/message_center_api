# _*_ coding: utf-8 _*_
import json

import time
from flask import Blueprint
from flask import request

from Controller.BaseController import response_data
from Service.UsersService import get_game_info_by_gameid, anfeng_helper_request_check, get_game_info_by_appid

anfeng_controller = Blueprint('AnfengController', __name__)


# 安锋助手获取卡券列表
@anfeng_controller.route('/msa/anfeng_helper/get_user_coupon', methods=['POST'])
@anfeng_helper_request_check
def v4_anfeng_helper_get_user_coupon():
    from run import mysql_session
    ucid = request.json.get('uid')
    status = int(request.json.get('status'))
    page = int(request.json.get('page'))
    count = int(request.json.get('pagesize'))
    start_index = (page - 1) * count
    end_index = start_index + count
    now = int(time.time())
    coupon_list = []
    if status == 0:  # 正常的卡券
        get_user_coupon_total_count_sql = "select count(coupon.id) from zy_coupon_log as log join zy_coupon as coupon " \
                              "on log.coupon_id=coupon.id where coupon.status='normal' and log.is_used = 0 " \
                              "and log.ucid=%s " \
                              "and ((coupon.is_time = 0) or ((coupon.is_time = 1) " \
                              "and coupon.start_time <= %s " \
                              "and coupon.end_time >= %s))" \
                              % (ucid, now, now)
        get_user_coupon_sql = "select coupon.* from zy_coupon_log as log join zy_coupon as coupon " \
                              "on log.coupon_id=coupon.id where coupon.status='normal' and log.is_used = 0 " \
                              "and log.ucid=%s " \
                              "and ((coupon.is_time = 0) or ((coupon.is_time = 1) " \
                              "and coupon.start_time <= %s " \
                              "and coupon.end_time >= %s)) order by log.id desc limit %s, %s" \
                              % (ucid, now, now, start_index, end_index)
    else:  # 过期的卡券
        get_user_coupon_total_count_sql = "select count(coupon.id) from zy_coupon_log as log join zy_coupon as coupon " \
                              "on log.coupon_id=coupon.id where coupon.status='normal' and log.is_used = 0 " \
                              "and log.ucid=%s " \
                              "and ((coupon.is_time = 1) " \
                              "and coupon.end_time < %s)" \
                              % (ucid, now)
        get_user_coupon_sql = "select coupon.* from zy_coupon_log as log join zy_coupon as coupon " \
                              "on log.coupon_id=coupon.id where coupon.status='normal' and log.is_used = 0 " \
                              "and log.ucid=%s " \
                              "and ((coupon.is_time = 1) " \
                              "and coupon.end_time < %s) order by log.id desc limit %s, %s" \
                              % (ucid, now, start_index, end_index)
    user_coupon_count = mysql_session.execute(get_user_coupon_total_count_sql).scalar()
    user_coupon_list = mysql_session.execute(get_user_coupon_sql).fetchall()
    for coupon in user_coupon_list:
        coupon_info = {
            'coupon_id': coupon['id'],
            'coupon_name': coupon['name'],
            'lock_time': coupon['is_time'],
            'start_time': coupon['start_time'],
            'end_time': coupon['end_time'],
            'amount': coupon['money']
        }
        # 查询游戏信息
        game = get_game_info_by_appid(coupon['game'])
        if game is None:
            coupon_info['lock_apk_id'] = coupon['game']
            coupon_info['game_name'] = game['name']
            coupon_info['game_id'] = game['id']
        coupon_list.append(coupon_info)
    data = {
        'total_count': user_coupon_count,
        'data': coupon_list
    }
    return response_data(http_code=200, data=data)


# 安锋助手用户获取已领礼包列表
@anfeng_controller.route('/msa/anfeng_helper/get_user_gifts', methods=['POST'])
@anfeng_helper_request_check
def v4_anfeng_helper_get_user_gifts():
    from run import mysql_cms_session
    ucid = request.json.get('ucid')
    page = int(request.json.get('page'))
    count = int(request.json.get('count'))
    start_index = (page - 1) * count
    end_index = start_index + count
    gift_list = []
    user_gift_total_count_sql = "select count(gift.*) from cms_gameGiftLog as log join cms_gameGift as gift" \
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
