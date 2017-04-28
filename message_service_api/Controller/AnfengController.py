# _*_ coding: utf-8 _*_
import json

import time
from flask import Blueprint
from flask import request

from Controller.BaseController import response_data
from MiddleWare import service_logger
from MysqlModel.GameGiftLog import GameGiftLog
from Service.UsersService import get_game_info_by_gameid, anfeng_helper_request_check, get_game_info_by_appid, \
    get_ucid_by_access_token, get_stored_value_card_list, get_user_all_coupons, get_username_by_ucid, \
    get_user_tao_gift_total_count, get_gift_real_time_count

anfeng_controller = Blueprint('AnfengController', __name__)


# 安锋助手获取卡券列表
@anfeng_controller.route('/msa/anfeng_helper/get_user_coupon', methods=['POST'])
@anfeng_helper_request_check
def v4_sdk_get_user_coupon():
    if 'ucid' not in request.form and 'token' not in request.form:
        response_data(200, 0, 'ucid和token不能同时为空')
    ucid = 0
    if 'ucid' in request.form:
        ucid = request.form['ucid']
    if ucid == 0 and '_token' in request.form:
        ucid = get_ucid_by_access_token(request.form['_token'])
    status = int(request.form['status'])
    page = request.form['page'] if request.form.has_key('page') and request.form.get('page') else 1
    count = request.form['pagesize'] if request.form.has_key('pagesize') and request.form.get('pagesize') else 10
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
        head_count = (value_card_total_count / end_index + 1) * end_index - value_card_total_count
        tmp_count = left_count / end_index
        if tmp_count == 0:
            coupon_start_index = 0
            coupon_end_index = left_count
        else:
            coupon_start_index = (tmp_count - 1) * end_index + head_count
            coupon_end_index = coupon_start_index + end_index
        # 查询用户相关的卡券列表
        coupon_total_count, new_coupon_list = get_user_all_coupons(ucid, status, coupon_start_index, coupon_end_index)
        # 拼接储值卡和卡券列表返回
        value_card_list.extend(new_coupon_list)
        data['total_count'] = value_card_total_count + coupon_total_count
        data['coupon_list'] = value_card_list
    return response_data(http_code=200, data=data)


# 安锋助手领取卡券
@anfeng_controller.route('/msa/anfeng_helper/coupon', methods=['POST'])
@anfeng_helper_request_check
def v4_sdk_acheive_coupon():
    if 'ucid' not in request.form and 'token' not in request.form:
        response_data(200, 0, 'ucid和token不能同时为空')
    ucid = 0
    if 'ucid' in request.form:
        ucid = request.form['ucid']
    if ucid == 0 and '_token' in request.form:
        ucid = get_ucid_by_access_token(request.form['_token'])
    order_id = request.form['order_id']
    notify_url = request.form['notify_url']
    channel = request.form['channel']
    if 'coupon_id' not in request.form:
        return response_data(200, 0, '参数异常：缺少卡券id')
    coupon_id = int(request.form['coupon_id'])
    if coupon_id == 0:
        return response_data(200, 0, '参数异常：卡券id不能为零')
    try:
        from run import mysql_session
        find_coupon_info_sql = "select id, name, game, is_time, start_time, end_time from zy_coupon" \
                               " where status = 'normal' and id = %s limit 1" % (coupon_id,)
        coupon_info = mysql_session.execute(find_coupon_info_sql).fetchone()
        if coupon_info is None:
            return response_data(200, 0, '卡券不存在或已经被删除')
        find_is_get_coupon_sql = "select count(*) from zy_coupon_log " \
                                 " where ucid = %s and coupon_id = %s" % (ucid, coupon_id)
        is_get = mysql_session.execute(find_is_get_coupon_sql).scalar()
        if is_get > 0:
            return response_data(200, 0, '你已经领取过了')
        coupon_info = mysql_session.execute(find_coupon_info_sql).fetchone()
        insert_user_coupon_sql = "insert into zy_coupon_log(ucid, coupon_id, pid, is_time, start_time, end_time)" \
                                 " values(%s, %s, %s, %s, %s, %s)" \
                                 % (ucid, coupon_id, coupon_info['game'], coupon_info['is_time'],
                                    coupon_info['start_time'], coupon_info['end_time'])
        mysql_session.execute(insert_user_coupon_sql)
        mysql_session.commit()
        message_info = {
            "type": "coupon_notify",
            "message": {
                'order_id': order_id,
                'notify_url': notify_url,
                'channel': channel,
                'ucid': ucid,
                'coupon_id': coupon_id
            }
        }
        message_str = json.dumps(message_info)
        service_logger.info("发送领取卡券回调到队列：%s" % (message_str,))
        from run import kafka_producer
        kafka_producer.send('message-service', message_str)
        return response_data(200, 1, '领取成功')
    except Exception, err:
        service_logger.error("用户领取卡券，存储的mysql发生异常：%s" % (err.message,))
        mysql_session.rollback()
    finally:
        mysql_session.close()
    return response_data(200, 0, '领取失败')


# 安锋助手获取礼包列表
@anfeng_controller.route('/msa/anfeng_helper/gifts', methods=['POST'])
@anfeng_helper_request_check
def v4_anfeng_helper_gifts():
    page = request.form['page'] if request.form.has_key('page') and request.form.get('page') else 1
    count = request.form['pagesize'] if request.form.has_key('pagesize') and request.form.get('pagesize') else 10
    start_index = (int(page) - 1) * int(count)
    end_index = int(count)
    from run import mysql_cms_session
    find_anfeng_helper_total_count = "select count(*) from cms_gameGift as gift join cms_gameGiftAssign" \
                                     " as assign on gift.id=assign.giftId where assign.platformId = 4 " \
                                     "and gift.status='normal' and assign.status='normal' "
    find_anfeng_helper_gift_list = "select gift.*, assign.assignNum as a_assignNum, assign.num as a_num" \
                                   " from cms_gameGift as gift join cms_gameGiftAssign as assign " \
                                   "on gift.id=assign.giftId where assign.platformId = 4 and gift.status='normal'" \
                                   " and assign.status='normal' limit %s, %s " % (start_index, end_index)
    total_count = mysql_cms_session.execute(find_anfeng_helper_total_count).scalar()
    data_list = mysql_cms_session.execute(find_anfeng_helper_gift_list).fetchall()
    gift_list = []
    for data in data_list:
        gift = {
            'id': data['id'],
            'game_id': data['gameId'],
            'game_name': data['gameName'],
            'name': data['name'],
            'gift': data['gift'],
            'content': data['content'],
            'label': data['label'],
            'total': data['total'],
            'num': data['num'],
            'assign_num': data['assignNum'],
            'publish_time': data['publishTime'],
            'fail_time': data['failTime'],
            'create_time': data['createTime'],
            'is_tao_num': data['isTaoNum'],
            'is_af_receive': data['isAfReceive'],
            'is_bind_phone': data['isBindPhone'],
            'member_level': data['memberLevel'],
            'is_specify': data['isSpecify'],
            'a_assign_num': data['a_assignNum'],
            'a_num': data['a_num']
        }
        gift_list.append(gift)
    data = {
        'total_count': total_count,
        'gift_list': gift_list
    }
    return response_data(http_code=200, data=data)


# 安锋助手获取礼包的实时数量
@anfeng_controller.route('/msa/anfeng_helper/gifts_real_time_count', methods=['POST'])
@anfeng_helper_request_check
def v4_anfeng_helper_gifts_real_time_count():
    if 'gift_ids' not in request.form:
        return response_data(200, 0, '礼包id不能为空')
    gift_ids = request.form['gift_ids']
    ids_list = gift_ids.split('|')
    ids_list_str = ",".join(ids_list)
    from run import mysql_cms_session
    find_gift_info_sql = "select giftId, assignNum, num from cms_gameGiftAssign where platformId = 4 " \
                         "and giftId in (%s)" % (ids_list_str,)
    gift_info_list = mysql_cms_session.execute(find_gift_info_sql).fetchall()
    data_list = []
    for data in gift_info_list:
        count_info = {
            'gift_id': data['giftId'],
            'assign_num': data['assignNum'],
            'num': data['num']
        }
        data_list.append(count_info)
    return response_data(http_code=200, data=data_list)


# 安锋助手用户获取已领礼包列表
@anfeng_controller.route('/msa/anfeng_helper/get_user_gifts', methods=['POST'])
@anfeng_helper_request_check
def v4_anfeng_helper_get_user_gifts():
    from run import mysql_cms_session
    if 'ucid' not in request.form and '_token' not in request.form:
        response_data(200, 0, 'ucid和token不能同时为空')
    ucid = 0
    if 'ucid' in request.form:
        ucid = request.form['ucid']
    if ucid == 0 and '_token' in request.form:
        ucid = get_ucid_by_access_token(request.form['_token'])
    page = request.form['page'] if request.form.has_key('page') and request.form.get('page') else 1
    count = request.form['pagesize'] if request.form.has_key('pagesize') and request.form.get('pagesize') else 10
    start_index = (int(page) - 1) * int(count)
    end_index = start_index + int(count)
    gift_list = []
    user_gift_total_count_sql = "select count(gift.id) from cms_gameGiftLog as log join cms_gameGift as gift" \
                                " on log.giftId = gift.id where gift.status = 'normal' and " \
                                "log.status = 'normal' and log.uid = %s " % (ucid,)
    get_user_gift_sql = "select gift.*, log.code, log.forTime, log.type from cms_gameGiftLog as log join " \
                        "cms_gameGift as gift on log.giftId = gift.id" \
                        " where gift.status = 'normal' and log.status = 'normal' and log.uid = %s limit %s, %s" \
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
            'assignNum': gift['assignNum'],
            'code': gift['code'],
            'for_time': gift['forTime'],
            'type': gift['type'],
            'publish_time': gift['publishTime'],
            'fail_time': gift['failTime']
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
    if 'ucid' not in request.form and '_token' not in request.form:
        response_data(200, 0, 'ucid和token不能同时为空')
    ucid = 0
    if 'ucid' in request.form:
        ucid = request.form['ucid']
    if ucid == 0 and '_token' in request.form:
        ucid = get_ucid_by_access_token(request.form['_token'])
    gift_id = int(request.form['gift_id'])
    is_exist_sql = "select count(*) from cms_gameGiftLog as log where log.status = 'normal'" \
                   " and log.uid = %s and log.giftId = %s " % (ucid, gift_id)
    is_exist = mysql_cms_session.execute(is_exist_sql).scalar()
    find_code_sql = "select code from cms_gameGiftLog as log where log.status = 'normal'" \
                    " and log.uid = %s and log.giftId = %s limit 1" % (ucid, gift_id)
    code_info = mysql_cms_session.execute(find_code_sql).fetchone()
    assign_num, num = get_gift_real_time_count(4, gift_id)
    data = {
        'is_get': False,
        'code': '',
        'assign_num': assign_num,
        'num': num
    }
    if is_exist > 0:
        data['is_get'] = True
        data['code'] = code_info['code']
    return response_data(http_code=200, data=data)


# 安锋助手淘号
@anfeng_controller.route('/msa/anfeng_helper/tao_gift', methods=['POST'])
@anfeng_helper_request_check
def v4_anfeng_helper_tao_gift():
    if 'ucid' not in request.form and '_token' not in request.form:
        response_data(200, 0, 'ucid和token不能同时为空')
    ucid = 0
    if 'ucid' in request.form:
        ucid = request.form['ucid']
    if ucid == 0 and '_token' in request.form:
        ucid = get_ucid_by_access_token(request.form['_token'])
    ip = request.remote_addr  # 请求源ip
    mac = request.form['_device_id']  # 通用参数中的device_id
    gift_id = int(request.form['gift_id'])
    now = int(time.time())
    for_time = now - 7200
    if ucid is None:
        return response_data(200, 0, '用户不存在或未登录')
    from run import mysql_cms_session
    tao_gift_sql = "select log.* from cms_gameGiftLog as log join cms_gameGift as gift on log.giftId = gift.id" \
                   " where log.status = 'normal' and gift.status = 'normal' and gift.isTaoNum = 1 " \
                   " and log.platformId = 4 and log.giftId = %s and forTime < %s and log.uid != %s " \
                   "order by num limit 1" % (gift_id, for_time, ucid)
    tao_info = mysql_cms_session.execute(tao_gift_sql).fetchone()
    if tao_info is not None:
        username = get_username_by_ucid(ucid)
        game_gift_log = GameGiftLog(gameId=tao_info['gameId'], giftId=int(tao_info['giftId']),
                                    platformId=tao_info['platformId'], code=tao_info['code'],
                                    uid=ucid, username=username, forTime=now,
                                    forIp=ip, forMac=mac, type=1)
        mysql_cms_session.add(game_gift_log)
        mysql_cms_session.commit()
        tao_gift_total_count = get_user_tao_gift_total_count(ucid)
        data = {
            "code": tao_info['code'],
            "tao_gift_total_count": tao_gift_total_count
        }
        return response_data(200, data=data)
    return response_data(http_code=200, data='没有礼包可以淘了')
