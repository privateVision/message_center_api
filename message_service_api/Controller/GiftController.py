# _*_ coding: utf-8 _*_
import time
from flask import Blueprint
from flask import request

from Controller.BaseController import response_data
from MiddleWare import service_logger
from Service.UsersService import get_ucid_by_access_token, sdk_api_request_check

gift_controller = Blueprint('GiftController', __name__)


# SDK 根据游戏 id 获取未领取礼包列表
@gift_controller.route('/msa/v4/gifts', methods=['POST'])
# @sdk_api_request_check
def v4_sdk_get_gifts_list():
    # ucid = get_ucid_by_access_token(request.form['_token'])
    ucid = 11
    game_id = request.form['game_id']
    page = request.form['page'] if request.form.has_key('page') and request.form['page'] else 1
    count = request.form['count'] if request.form.has_key('count') and request.form['count'] else 10
    start_index = (int(page) - 1) * int(count)
    end_index = start_index + int(count)
    service_logger.info("用户：%s 获取礼包列表，数据从%s到%s" % (ucid, start_index, end_index))
    # 查询游戏信息
    from run import zhuayou_sdk_mysql_session
    find_game_info_sql = "select id, name, game_type from af_game where id= %s limit 1" % (game_id,)
    game_info = zhuayou_sdk_mysql_session.execute(find_game_info_sql).fetchone()
    game = {}
    if game_info:
        game['id'] = game_info['id']
        game['name'] = game_info['name']
        game['game_type'] = game_info['game_type']
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
            'game': gift['name'],
            'gift': gift['gift']
        }
        data_list.append(info)
    data = {
        "game": game,
        "today_count": today_gift_count,
        "total_count": unget_gifts_count,
        "data": data_list
    }
    return response_data(http_code=200, data=data)


# 领取礼包
@gift_controller.route('/msa/v4/get_gift', methods=['POST'])
@sdk_api_request_check
def v4_sdk_user_get_gift():
    return response_data(http_code=200, data=None)


# 查询游戏是否有未领取礼包
@gift_controller.route('/msa/v4/unget_game_gift', methods=['POST'])
@sdk_api_request_check
def v4_sdk_user_unget_gift():
    return response_data(http_code=200, data=None)


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
    return response_data(http_code=200, data=None)
