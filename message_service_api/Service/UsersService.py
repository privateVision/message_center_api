# _*_ coding: utf-8 _*_

from mongoengine import Q
from MongoModel.MessageModel import UsersMessage


def get_game_and_area_and_user_type_and_vip_users(game=None, user_type=None, vip=None):
    from run import mysql_session
    game_users_list = []
    for game_info in game:
        for zone in game_info['zone_id_list']:
            find_users_in_game_area_sql = "select ucid from roleDatas where vid = %s and zoneName = '%s'"\
                                          % (game_info['apk_id'], zone)
            list = mysql_session.execute(find_users_in_game_area_sql)
            for ucid in list:
                game_users_list.append(ucid[0])
    get_user_type_users(user_type)
    return game_users_list


def get_user_type_users(user_type):
    from run import mysql_session
    users_list = []
    for type in user_type:
        find_users_by_user_type_sql = "select ucid from ucusers as u, retailers as r where u.rid = r.rid " \
                                      "and r.rtype = %s" % (type,)
        origin_list = mysql_session.execute(find_users_by_user_type_sql)
        for ucid in origin_list:
            users_list.append(ucid[0])
    return users_list


def get_vip_users(vip):
    pass


def getNoticeMessageDetailInfo(msg_id=None):
    return UsersMessage.objects(Q(type='notice') & Q(mysql_id=msg_id)).first()


def getUcidByAccessToken(access_token=None):
    from run import mysql_session
    find_ucid_sql = "select ucid from session where access_token = '%s'" % (access_token,)
    user_info = mysql_session.execute(find_ucid_sql).first()
    if user_info:
        if user_info['ucid']:
            return user_info['ucid']
    return False