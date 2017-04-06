# _*_ coding: utf-8 _*_
import time
from flask import Blueprint
from flask import request

from Controller.BaseController import response_data
from MiddleWare import service_logger
from Service.UsersService import get_ucid_by_access_token, sdk_api_request_check, get_username_by_ucid, \
    get_game_info_by_appid
from Utils.SystemUtils import log_exception

gift_controller = Blueprint('GiftController', __name__)


# SDK 根据游戏 id 获取未领取礼包列表
@gift_controller.route('/msa/v4/gifts', methods=['POST'])
@sdk_api_request_check
def v4_sdk_get_gifts_list():
    from run import mysql_cms_session
    from run import SDK_PLATFORM_ID
    if 'platform_id' in request.form:
        SDK_PLATFORM_ID = int(request.form['platform_id'])
    ucid = get_ucid_by_access_token(request.form['_token'])
    appid = request.form['_appid']  # 要根据appid找游戏id
    page = request.form['page'] if request.form.has_key('page') and request.form['page'] else 1
    count = request.form['count'] if request.form.has_key('count') and request.form['count'] else 10
    start_index = (int(page) - 1) * int(count)
    end_index = int(count)
    service_logger.info("用户：%s 获取礼包列表，数据从%s到%s" % (ucid, start_index, end_index))
    # 查询游戏信息
    game = get_game_info_by_appid(appid)
    if game is None:
        return response_data(200, 0, '游戏未找到')
    now = int(time.time())
    start_timestamp = int(now - (now % 86400) + time.timezone)
    end_timestamp = int(start_timestamp + 86399)
    find_today_gifts_count_sql = "select count(*) from cms_gameGift where gameId = %s and status = 'normal' " \
                                 "and publishTime >= %s and publishTime <= %s " % (
                                     game['id'], start_timestamp, end_timestamp)
    today_gift_count = mysql_cms_session.execute(find_today_gifts_count_sql).scalar()

    unget_gifts_count_sql = "select count(*) as num from (select ifnull(c.code,'') as code,b.num from cms_gameGift " \
                            "as a join cms_gameGiftAssign as b on a.id = b.giftId left outer join cms_gameGiftLog " \
                            "as c on c.giftId=a.id and c.uid= %s where a.gameId= %s and a.failTime > %s " \
                            "and b.platformId=%s and a.status='normal') as d " \
                            "where d.code<>'' or (d.num>0 and d.code='')" % (ucid, game['id'], now, SDK_PLATFORM_ID)
    unget_gifts_count = mysql_cms_session.execute(unget_gifts_count_sql).scalar()

    unget_gifts_page_list_sql = "select * from (select a.id,a.gameId,a.gameName,a.name,a.gift,a.isAfReceive," \
                                "a.content,a.label,a.uid,a.publishTime,a.failTime,a.createTime,a.updateTime,a.status," \
                                "b.num, b.assignNum, ifnull(c.code,'') as code,if(c.code<>'', '1', '0') " \
                                "as is_get from cms_gameGift as a join cms_gameGiftAssign as b on a.id=b.giftId " \
                                "left outer join cms_gameGiftLog as c on c.giftId=a.id and c.uid= %s " \
                                "where a.gameId=%s and a.failTime > %s and b.platformId=%s and a.status='normal' " \
                                "order by is_get asc , c.forTime desc, a.id desc) as d " \
                                "where d.code<>'' or (d.assignNum>0 and d.code='') limit %s, %s " \
                                % (ucid, game['id'], now, SDK_PLATFORM_ID, start_index, end_index)
    unget_gifts_page_list = mysql_cms_session.execute(unget_gifts_page_list_sql).fetchall()
    data_list = []
    for gift in unget_gifts_page_list:
        info = {
            'id': gift['id'],
            'name': gift['name'],
            'game_id': gift['gameId'],
            'game_name': gift['gameName'],
            'gift': gift['gift'],
            'content': gift['content'],
            'publish_time': gift['publishTime'],
            'fail_time': gift['failTime'],
            'code': gift['code'],
            'num': gift['assignNum'],
            'total': int(gift['assignNum'])+int(gift['num']),
            'is_get': gift['is_get'],
            'is_af_receive': gift['isAfReceive']
        }
        data_list.append(info)
    data = {
        "game": game,
        "today_count": today_gift_count,
        "gift_count": unget_gifts_count,
        "data": data_list
    }
    return response_data(http_code=200, data=data)


# 领取礼包
@gift_controller.route('/msa/v4/get_gift', methods=['POST'])
@sdk_api_request_check
def v4_sdk_user_get_gift():
    from run import mysql_cms_session
    from run import SDK_PLATFORM_ID
    ucid = get_ucid_by_access_token(request.form['_token'])
    appid = int(request.form['_appid'])  # 根据appid来获取游戏id
    gift_id = request.form['gift_id']
    table_num = int(gift_id) % 10  # 获取分表名
    username = get_username_by_ucid(ucid)
    ip = request.remote_addr  # 请求源ip
    mac = request.form['_device_id']  # 通用参数中的device_id
    end_for_time = int(time.time()) - 3600 * 24  # 限制24小时的时间间隔
    if appid is None or gift_id is None or ip is None or mac is None:
        log_exception(request, '客户端请求错误-appid或giftid或ip\mac为空')
        return response_data(200, 0, '客户端参数错误')
    if 'platform_id' in request.form:
        SDK_PLATFORM_ID = int(request.form['platform_id'])
    # 查询游戏信息
    game = get_game_info_by_appid(appid)
    if game is None:
        return response_data(200, 0, '游戏未找到')
    game_id = game['id']
    find_game_gift_info_sql = "select * from cms_gameGift where id= %s limit 1" % (gift_id,)
    game_gift_info = mysql_cms_session.execute(find_game_gift_info_sql).fetchone()
    if game_gift_info is not None:
        if game_gift_info['gameId'] != game_id or game_gift_info['status'] != 'normal':
            return response_data(200, 0, '礼包不存在或者礼包已经过期')
        if game_gift_info['assignNum'] < 1:
            return response_data(200, 0, '礼包被领取完了')
        if game_gift_info['failTime'] < int(time.time()):
            return response_data(200, 0, '礼包已经过期了')
        if game_gift_info['isBindPhone'] == 1:  # 需要绑定手机
            from run import mysql_session
            find_users_mobile_sql = "select mobile from ucusers as u where u.ucid = %s limit 1" % (ucid,)
            try:
                user_mobile_info = mysql_session.execute(find_users_mobile_sql).fetchone()
                if user_mobile_info is not None:
                    if user_mobile_info['mobile'] == '':
                        return response_data(200, 0, '该礼包需要绑定手机的用户才能领取，请先绑定手机!')
            except Exception, err:
                service_logger.error(err.message)
                mysql_session.rollback()
            finally:
                mysql_session.close()
        if game_gift_info['memberLevel'] is not None:  # 有用户等级要求
            level_list = game_gift_info['memberLevel'].split(',')
            from run import mysql_session
            find_users_vip_sql = "select vip from ucuser_info as u where u.ucid = %s limit 1" % (ucid,)
            try:
                user_vip_info = mysql_session.execute(find_users_vip_sql).fetchone()
                if user_vip_info is not None:
                    if user_vip_info['vip'] not in level_list:
                        return response_data(200, 0, '该礼包需要特定等级的用户才可领取，您的等级不符合要求！')
            except Exception, err:
                service_logger.error(err.message)
                mysql_session.rollback()
            finally:
                mysql_session.close()
        if game_gift_info['isSpecify'] == 1:  # 是否指定用户领取
            from run import mysql_session
            find_game_gift_user_list_sql = "select value from cms_gameGiftSpecify where giftId= %s" \
                                           % (gift_id,)
            specify_user_list = mysql_cms_session.execute(find_game_gift_user_list_sql).fetchall()
            find_users_uid_sql = "select uid from ucusers as u where u.ucid = %s limit 1" % (ucid,)
            try:
                user_uid_info = mysql_session.execute(find_users_uid_sql).fetchone()
                if user_uid_info is not None:
                    _uid = user_uid_info['uid']
                    if _uid not in specify_user_list:
                        return response_data(200, 0, '该礼包需要在指定的用户中才可领取！')
            except Exception, err:
                service_logger.error(err.message)
                mysql_session.rollback()
            finally:
                mysql_cms_session.close()
                mysql_session.close()
        # 检查领取记录
        find_game_gift_log_count_sql = "select count(*) from cms_gameGiftLog where gameId = %s " \
                                       "and giftId = %s and platformId = %s and forTime >= %s and forIp = '%s' " \
                                       "and forMac = '%s' " % (game_id, gift_id, SDK_PLATFORM_ID,
                                                               end_for_time, ip, mac)
        game_gift_log_count = mysql_cms_session.execute(find_game_gift_log_count_sql).scalar()
        if game_gift_log_count > 3:
            return response_data(200, 0, '已经领取过了')
        # 检查礼包获取资格
        find_user_game_gift_log_sql = "select count(*) from cms_gameGiftLog where gameId = %s " \
                                      "and giftId = %s and platformId = %s and forTime >= %s " \
                                      "and uid = %s" \
                                      % (game_id, gift_id, SDK_PLATFORM_ID, end_for_time, ucid)
        user_game_gift_log_count = mysql_cms_session.execute(find_user_game_gift_log_sql).scalar()
        if user_game_gift_log_count == 0:
            find_game_gift_fortype_info_sql = "select assignNum from cms_gameGiftAssign where platformId = %s and" \
                                              " giftId= %s " % (SDK_PLATFORM_ID, gift_id)
            game_gift_assign_info = mysql_cms_session.execute(find_game_gift_fortype_info_sql).fetchone()
            if game_gift_assign_info:
                if game_gift_info['assignNum'] >= 1 and game_gift_assign_info['assignNum'] >= 1:
                    # 抽取礼包代码
                    find_game_gift_code_sql = "select id, code from cms_gameGiftCode_%s where status = 0 and" \
                                              " gameId= %s and giftId = %s order by id asc limit 1" \
                                              % (table_num, game_id, gift_id)
                    game_gift_code = mysql_cms_session.execute(find_game_gift_code_sql).fetchone()
                    if game_gift_code:
                        if game_gift_code['code'] is not None and game_gift_code['code'] != '':
                            try:
                                update_gift_code_sql = "update cms_gameGiftCode_%s set status=1, uid=%s, username='%s'," \
                                                       " forTime=%s, platformId=%s where id=%s " \
                                                       % (table_num, ucid, username, int(time.time()),
                                                          SDK_PLATFORM_ID, game_gift_code['id'])
                                update_gift_code_result = mysql_cms_session.execute(update_gift_code_sql)
                                if update_gift_code_result:
                                    insert_get_gift_log_sql = "insert into cms_gameGiftLog(gameId, giftId, platformId, " \
                                                          "code, uid, username, forTime, forIp, forMac) " \
                                                          "values(%s, %s, %s, '%s', %s, '%s', %s, '%s', '%s')" \
                                                          % (game_id, gift_id, SDK_PLATFORM_ID, game_gift_code['code'],
                                                              ucid, username, int(time.time()), ip, mac)
                                    mysql_cms_session.execute(insert_get_gift_log_sql)
                                    # 礼包个数减一
                                    update_gift_count_sql = "update cms_gameGift set num = num+1, " \
                                                            "assignNum = assignNum-1 where id=%s " \
                                                            % (gift_id,)
                                    update_gift_assign_count_sql = "update cms_gameGiftAssign set num = num+1," \
                                                                   " assignNum = assignNum-1 where platformId = %s" \
                                                                   " and giftId = %s " % (SDK_PLATFORM_ID, gift_id)
                                    mysql_cms_session.execute(update_gift_assign_count_sql)
                                    mysql_cms_session.execute(update_gift_count_sql)
                                    mysql_cms_session.commit()
                                    data = {'code': game_gift_code['code']}
                                    return response_data(200, 1, '领取成功', data)
                            except Exception, err:
                                service_logger.error(err.message)
                                mysql_cms_session.rollback()
                            finally:
                                mysql_cms_session.close()
                return response_data(200, 0, '礼包被领取完了')
            else:
                return response_data(200, 0, '该游戏未找到该平台可用的礼包')
        else:
            return response_data(200, 0, '已经领取过了')
    else:
        return response_data(200, 0, '礼包不存在')


# 查询游戏是否有未领取礼包
@gift_controller.route('/msa/v4/unget_game_gift', methods=['POST'])
@sdk_api_request_check
def v4_sdk_user_unget_gift():
    from run import mysql_cms_session
    from run import SDK_PLATFORM_ID
    ucid = get_ucid_by_access_token(request.form['_token'])
    appid = request.form['_appid']  # 根据appid获取游戏id
    if appid is None:
        log_exception(request, '客户端请求错误-appid为空')
        return response_data(200, 0, '客户端参数错误')
    if 'platform_id' in request.form:
        SDK_PLATFORM_ID = int(request.form['platform_id'])
    # 查询游戏信息
    game = get_game_info_by_appid(appid)
    if game is None:
        return response_data(200, 0, '游戏未找到')
    game_id = game['id']
    now = int(time.time())
    raw_sql = "select count(*) as num from cms_gameGift as a join cms_gameGiftAssign as b" \
              " on a.id = b.giftId where a.id not in(select giftId from cms_gameGift as gift " \
              "join cms_gameGiftLog as log on gift.id = log.giftId where log.uid = %s and gift.gameId = %s) " \
              "and a.gameId = %s and a.failTime > %s and b.platformId = %s and b.assignNum>0 and a.status='normal'" \
              % (ucid, game_id, game_id, now, SDK_PLATFORM_ID)
    gift_num = mysql_cms_session.execute(raw_sql).scalar()
    if gift_num > 0:
        data = {
            'new_gift': True,
            'num': gift_num
        }
    else:
        data = {
            'new_gift': False,
            'num': 0
        }
    return response_data(http_code=200, data=data)


# 推荐游戏列表
@gift_controller.route('/msa/v4/recommend_game_list', methods=['POST'])
@sdk_api_request_check
def v4_sdk_user_get_recommend_game_list():
    ucid = get_ucid_by_access_token(request.form['_token'])
    page = request.form['page'] if request.form.has_key('page') and request.form['page'] else 1
    count = request.form['count'] if request.form.has_key('count') and request.form['count'] else 10
    start_index = (int(page) - 1) * int(count)
    end_index = int(count)
    service_logger.info("用户：%s 获取推荐游戏列表，数据从%s到%s" % (ucid, start_index, end_index))
    from run import mysql_session
    find_game_count_sql = "select count(*) from zy_gameRecom where status = 'normal' "
    game_count = mysql_session.execute(find_game_count_sql).scalar()
    find_game_info_sql = "select * from zy_gameRecom where status = 'normal' order by sort asc " \
                         "limit %s, %s" % (start_index, end_index)
    game_info_list = mysql_session.execute(find_game_info_sql).fetchall()
    game_list = []
    for game in game_info_list:
        game = {
            'id': game['game_id'],
            'name': game['name'],
            'down_url': game['down_url'],
            'package_name': game['package_name'],
            'filesize': game['filesize'] * 1024,
            'description': game['description'],
            'cover': 'http://sdkadm.zhuayou.com' + game['cover'],
            'category_name': game['category_name'],
            'run_status_name': game['run_status_name'],
            'publish_time': int(time.time()),
            'version': '',
            'sort': game['sort']
        }
        game_list.append(game)
    data = {
        'total_count': game_count,
        'game': game_list
    }
    return response_data(http_code=200, data=data)
