# _*_ coding: utf-8 _*_

from mongoengine import Q

from MongoModel.MessageModel import UsersMessage


def get_game_and_area_and_user_type_and_vip_users(game=None, user_type=None, vips=None):
    game_users_list = []
    for game_info in game:
        if game_info.has_key('zone_id_list'):
            for zone in game_info['zone_id_list']:
                find_users_in_game_area_sql = "select ucid from roleDatas where vid = %s and zoneName = '%s'"\
                                              % (game_info['apk_id'], zone)
                from run import mysql_session
                tmp_user_list = mysql_session.execute(find_users_in_game_area_sql)
                for ucid in tmp_user_list:
                    game_users_list.append(ucid[0])
    user_type_users_list = get_user_type_users(user_type)
    vip_users_list = get_vip_users(vips)
    uses_list = list(set(user_type_users_list).intersection(set(game_users_list)).intersection(set(vip_users_list)))
    return uses_list


def get_user_type_users(user_type):
    users_list = []
    for type in user_type:
        find_users_by_user_type_sql = "select ucid from ucusers as u, retailers as r where u.rid = r.rid " \
                                      "and r.rtype = %s" % (type,)
        from run import mysql_session
        origin_list = mysql_session.execute(find_users_by_user_type_sql)
        for ucid in origin_list:
            users_list.append(ucid[0])
    return users_list


def get_vip_users(vips):
    users_list = []
    for vip in vips:
        find_users_by_vip_sql = "select uid from ucusers_extend as u where u.vip = %s " % (vip,)
        from run import mysql_session
        origin_list = mysql_session.execute(find_users_by_vip_sql)
        for ucid in origin_list:
            users_list.append(ucid[0])
    return users_list


def get_notice_message_detail_info(msg_id=None):
    return UsersMessage.objects(Q(type='notice') & Q(mysql_id=msg_id)).first()


def get_broadcast_message_detail_info(msg_id=None):
    return UsersMessage.objects(Q(type='broadcast') & Q(mysql_id=msg_id)).first()


def get_ucid_by_access_token(access_token=None):
    find_ucid_sql = "select ucid from session where access_token = '%s'" % (access_token,)
    from run import mysql_session
    user_info = mysql_session.execute(find_ucid_sql).first()
    if user_info:
        if user_info['ucid']:
            return user_info['ucid']
    return False
