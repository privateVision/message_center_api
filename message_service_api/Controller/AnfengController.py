# _*_ coding: utf-8 _*_
import json

import time
from flask import Blueprint

from Controller.BaseController import response_data
from Service.UsersService import get_game_info_by_gameid

anfeng_controller = Blueprint('AnfengController', __name__)


# 安锋助手获取卡券列表
@anfeng_controller.route('/msa/anfeng_helper/get_user_coupon/<ucid>', methods=['POST'])
# @anfeng_helper_api_request_check
def v4_anfeng_helper_get_user_coupon(ucid=None):
    from run import mysql_session
    now = int(time.time())
    coupon_list = []
    get_user_coupon_sql = "select coupon.* from zy_coupon_log as log join zy_coupon as coupon " \
                          "on log.coupon_id=coupon.id where coupon.status='normal' and log.is_used = 0 " \
                          "and log.ucid=%s " \
                          "and ((coupon.is_time = 0) or ((coupon.is_time = 1) " \
                          "and coupon.start_time <= %s " \
                          "and coupon.end_time >= %s)) order by log.id desc" \
                          % (ucid, now, now)
    user_coupon_list = mysql_session.execute(get_user_coupon_sql).fetchall()
    for coupon in user_coupon_list:
        coupon_info = {
            'id': coupon['id'],
            'name': coupon['name'],
            'is_time': coupon['is_time'],
            'start_time': coupon['start_time'],
            'end_time': coupon['end_time'],
            'game': coupon['game'],
            'is_first': coupon['is_first'],
            'info': coupon['info'],
            'num': coupon['num'],
            'full': coupon['full'],
            'money': coupon['money'],
            'method': coupon['method'],
            'users_type': coupon['users_type'],
            'vip_user': coupon['vip_user'],
            'specify_user': coupon['specify_user']
        }
        coupon_list.append(coupon_info)
    return response_data(http_code=200, data=coupon_list)


# 安锋助手用户获取已领礼包列表
@anfeng_controller.route('/msa/anfeng_helper/get_user_gifts/<ucid>', methods=['POST'])
# @anfeng_helper_api_request_check
def v4_anfeng_helper_get_user_gifts(ucid=None):
    from run import mysql_cms_session
    gift_list = []
    get_user_gift_sql = "select gift.* from cms_gameGiftLog as log join cms_gameGift as gift on log.giftId = gift.id" \
                        " where gift.status = 'normal' and log.status = 'normal' and log.uid = %s limit 0, 10" % (ucid,)
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
    return response_data(http_code=200, data=gift_list)


# 安锋助手获取礼包是否被领取
@anfeng_controller.route('/msa/anfeng_helper/is_gift_get/<ucid>/<gift_id>', methods=['POST'])
# @anfeng_helper_api_request_check
def v4_anfeng_helper_is_user_gift_get(ucid=None, gift_id=None):
    from run import mysql_cms_session
    is_exist_sql = "select count(*) from cms_gameGiftLog as log where log.status = 'normal'" \
                   " and log.uid = %s and log.giftId = %s " % (ucid, gift_id)
    is_exist = mysql_cms_session.execute(is_exist_sql).scalar()
    data = {
        'is_get': False
    }
    if is_exist > 0:
        data['is_get'] = True
    return response_data(http_code=200, data=data)
