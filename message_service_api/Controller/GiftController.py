# _*_ coding: utf-8 _*_
import time
from flask import Blueprint
from flask import request

from Controller.BaseController import response_data
from MiddleWare import service_logger
from Service.UsersService import get_ucid_by_access_token, sdk_api_request_check
from Utils.SystemUtils import log_exception

gift_controller = Blueprint('GiftController', __name__)


# SDK 根据游戏 id 获取未领取礼包列表
@gift_controller.route('/msa/v4/gifts', methods=['POST'])
@sdk_api_request_check
def v4_sdk_get_gifts_list():
    ucid = get_ucid_by_access_token(request.form['_token'])
    game_id = request.form['_appid']  # 游戏id
    page = request.form['page'] if request.form.has_key('page') and request.form['page'] else 1
    count = request.form['count'] if request.form.has_key('count') and request.form['count'] else 10
    start_index = (int(page) - 1) * int(count)
    end_index = start_index + int(count)
    service_logger.info("用户：%s 获取礼包列表，数据从%s到%s" % (ucid, start_index, end_index))
    # 查询游戏信息
    from run import zhuayou_sdk_mysql_session
    find_game_info_sql = "select apk.name, apk.id, game.cover, game.category_id, game.cnname" \
                         " from af_game_apk as apk join af_game as game on apk.game_id=game.id" \
                         " where apk.id= %s limit 1" % (game_id,)
    game_info = zhuayou_sdk_mysql_session.execute(find_game_info_sql).fetchone()
    game = {}
    if game_info:
        game['id'] = game_info['id']
        game['name'] = game_info['name']
        game['cover'] = game_info['cover']
        game['category_id'] = game_info['category_id']
        game['cnname'] = game_info['cnname']
    else:
        return response_data(200, 0, '游戏未找到')
    now = time.time()
    start_timestamp = int(now - (now % 86400) + time.timezone)
    end_timestamp = int(start_timestamp + 86399)
    find_today_gifts_count_sql = "select count(*) from af_game_gift where game_id = %s and status = 'normal' " \
                                 "and publish_time >= %s and publish_time <= %s " % (
                                     game_id, start_timestamp, end_timestamp)
    today_gift_count = zhuayou_sdk_mysql_session.execute(find_today_gifts_count_sql).scalar()

    unget_gifts_count_sql = "select count(*) as num from (select ifnull(c.code,'') as code,b.num from af_game_gift " \
                            "as a join af_game_gift_fortype as b on a.id = b.gift_id left outer join af_game_gift_log " \
                            "as c on c.gift_id=a.id and c.uid= %s where a.game_id= %s and a.fail_time > %s " \
                            "and b.fortype=2 and a.status='normal') as d " \
                            "where d.code<>'' or (d.num>0 and d.code='')" % (ucid, game_id, now)
    unget_gifts_count = zhuayou_sdk_mysql_session.execute(unget_gifts_count_sql).scalar()

    unget_gifts_page_list_sql = "select * from (select a.id,a.afid,a.game_id,a.game_name,a.name,a.gift," \
                                "a.content,a.type,a.type_data,a.fortype,a.label,a.ios,a.tags,a.uid,a.publish_time," \
                                "a.fail_time,a.create_time,a.update_time,a.status,a.dig,a.gift_range," \
                                "a.release_limit,b.num,b.total,ifnull(c.code,'') as code,if(c.code<>'', '1', '0') " \
                                "as is_get from af_game_gift as a join af_game_gift_fortype as b on a.id=b.gift_id " \
                                "left outer join af_game_gift_log as c on c.gift_id=a.id and c.uid= %s " \
                                "where a.game_id=%s and a.fail_time > %s and b.fortype=2 and a.status='normal' " \
                                "order by is_get asc , c.for_time desc, a.id desc) as d " \
                                "where d.code<>'' or (d.num>0 and d.code='') limit %s, %s " % (ucid, game_id, now,
                                                                                               start_index, end_index)
    unget_gifts_page_list = zhuayou_sdk_mysql_session.execute(unget_gifts_page_list_sql).fetchall()
    data_list = []
    for gift in unget_gifts_page_list:
        info = {
            'id': gift['id'],
            'name': gift['name'],
            'game_id': gift['game_id'],
            'game_name': gift['game_name'],
            'gift': gift['gift'],
            'content': gift['content'],
            'publish_time': gift['publish_time'],
            'fail_time': gift['fail_time'],
            'code': gift['code'],
            'type': gift['type'],
            'num': gift['num'],
            'total': gift['total'],
            'is_get': gift['is_get']
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
    ucid = get_ucid_by_access_token(request.form['_token'])
    game_id = request.form['_appid']  # 游戏id
    gift_id = request.form['gift_id']
    username = request.form['username']
    ip = request.remote_addr  # 请求源ip
    mac = request.form['_device_id']  # 通用参数中的device_id
    end_for_time = int(time.time()) - 3600 * 24  # 限制24小时的时间间隔
    if game_id is None or gift_id is None or ip is None or mac is None:
        log_exception(request, '客户端请求错误-gameid或giftid或ip\mac为空')
        return response_data(200, 0, '客户端参数错误')
    from run import zhuayou_sdk_mysql_session
    find_game_gift_info_sql = "select * from af_game_gift where id= %s " % (gift_id,)
    game_gift_info = zhuayou_sdk_mysql_session.execute(find_game_gift_info_sql).fetchone()
    if game_gift_info:
        if game_gift_info['game_id'] != game_id or game_gift_info['status'] != 'normal':
            return response_data(200, 0, '礼包不存在或者礼包已经过期')
        if game_gift_info['num'] < 1:
            return response_data(200, 0, '礼包被领取完了')
        if game_gift_info['fail_time'] < int(time.time()):
            return response_data(200, 0, '礼包已经过期了')
        # 检查领取记录
        find_game_gift_log_count_sql = "select count(*) from af_game_gift_log where game_id = %s " \
                                       "and gift_id = %s and fortype = 2 and for_time >= %s and for_ip = %s " \
                                       "and for_mac = %s " % (game_id, gift_id, end_for_time, ip, mac)
        game_gift_log_count = zhuayou_sdk_mysql_session.execute(find_game_gift_log_count_sql).scalar()
        if game_gift_log_count > 3:
            return response_data(200, 0, '已经领取过了')
        # 检查礼包获取资格
        find_user_game_gift_log_sql = "select count(*) from af_game_gift_log where game_id = %s " \
                                      "and gift_id = %s and fortype = 2 and for_time >= %s " \
                                      "and uid = %s order by for_time desc " % (game_id, gift_id, end_for_time, ucid)
        user_game_gift_log_count = zhuayou_sdk_mysql_session.execute(find_user_game_gift_log_sql).scalar()
        if user_game_gift_log_count == 0:
            # 检查礼包剩余个数
            find_game_gift_fortype_info_sql = "select num from af_game_gift_fortype where fortype =2 and" \
                                              " id= %s " % (gift_id,)
            game_gift_fortype_info = zhuayou_sdk_mysql_session.execute(find_game_gift_fortype_info_sql).fetchone()
            if game_gift_fortype_info:
                if game_gift_info['sum'] >= 1 and game_gift_fortype_info['sum'] >= 1:
                    # 抽取礼包代码
                    find_game_gift_code_sql = "select id, code from af_game_gift_code where status = 0 and" \
                                                      " game_id= %s and gift_id = %s limit 1 order by id asc" \
                                              % (game_id, gift_id)
                    game_gift_code = zhuayou_sdk_mysql_session.execute(find_game_gift_code_sql).fetchone()
                    if game_gift_code:
                        if game_gift_code['code'] is not None and game_gift_code['code'] != '':
                            update_gift_code_sql = "update af_game_gift_code set status=1, uid=%s, username=%s," \
                                                   " for_time=%s, fortype=2 where id=%s "\
                                                   % (ucid, username, int(time.time()), game_gift_code['id'])
                            result = zhuayou_sdk_mysql_session.execute(update_gift_code_sql)
                            if result:
                                insert_get_gift_log = "insert into af_game_gift_log values(%s, %s, %s, %s, %s, %s," \
                                                      "%s, %s, %s)" % (game_id, gift_id, 2, game_gift_code['code'],
                                                                       ucid, username, int(time.time()), ip, mac)
                                zhuayou_sdk_mysql_session.execute(insert_get_gift_log)
                                # 礼包个数减一
                                update_gift_count_sql = "update af_game_gift set num = num-1 where id=%s " \
                                                       % (gift_id)
                                update_gift_fortype_count_sql = "update af_game_gift_fortype set num = num-1 " \
                                                                "where fortype=2 and gift_id=%s " % (gift_id,)
                                zhuayou_sdk_mysql_session.execute(update_gift_count_sql)
                                zhuayou_sdk_mysql_session.execute(update_gift_fortype_count_sql)
                    else:
                        return response_data(200, 0, '礼包被领取完了')
                else:
                    return response_data(200, 0, '礼包被领取完了')
            else:
                return response_data(200, 0, '礼包被领取完了')
        else:
            return response_data(200, 0, '已经领取过了')
    else:
        return response_data(200, 0, '礼包不存在')
    return response_data(http_code=200, data=None)


# # 查询游戏是否有未领取礼包
# @gift_controller.route('/msa/v4/unget_game_gift', methods=['POST'])
# @sdk_api_request_check
# def v4_sdk_user_unget_gift():
#     return response_data(http_code=200, data=None)


# 推荐游戏列表
@gift_controller.route('/msa/v4/recommend_game_list', methods=['POST'])
@sdk_api_request_check
def v4_sdk_user_get_recommend_game_list():
    ucid = get_ucid_by_access_token(request.form['_token'])
    page = request.form['page'] if request.form.has_key('page') and request.form['page'] else 1
    count = request.form['count'] if request.form.has_key('count') and request.form['count'] else 10
    start_index = (int(page) - 1) * int(count)
    end_index = start_index + int(count)
    service_logger.info("用户：%s 获取推荐游戏列表，数据从%s到%s" % (ucid, start_index, end_index))
    from run import zhuayou_sdk_mysql_session
    find_game_count_sql = "select * from af_game_recom where status = 'normal' "
    game_count = zhuayou_sdk_mysql_session.execute(find_game_count_sql).scalar()
    find_game_info_sql = "select * from af_game_recom where status = 'normal' order by sort asc " \
                         "limit %s, %s" % (start_index, end_index)
    game_info_list = zhuayou_sdk_mysql_session.execute(find_game_info_sql).fetchall()
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
