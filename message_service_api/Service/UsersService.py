# _*_ coding: utf-8 _*_
import json

from mongoengine import Q

from MongoModel.MessageModel import UsersMessage


def get_game_and_area_users(game=None, user_type=None, vip=None):
    from run import mysql_session
    users_list = []
    for game_info in game:
        for zone in game_info['zone_id_list']:
            find_users_in_game_area_sql = "select ucid from roleDatas where vid = %s and zoneName = '%s'"\
                                          % (game_info['apk_id'], zone)
            list = mysql_session.execute(find_users_in_game_area_sql)
            for ucid in list:
                users_list.append(ucid[0])
    return users_list


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