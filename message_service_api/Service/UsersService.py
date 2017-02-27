# _*_ coding: utf-8 _*_
from MongoModel.MessageModel import UsersMessage


def getGameAndAreaUsers(game=None, user_type=None, vip=None):
    from run import mysql_session
    print mysql_session.execute('select * from roleDatas where ucid = 1978914').first()
    return [123, 114, 231, 432]


def getNoticeMessageDetailInfo(msg_id=None):
    return UsersMessage.objects(mysql_id=msg_id)


def getUcidByAccessToken(access_token=None):
    from run import mysql_session
    find_ucid_sql = "select ucid from session where access_token = '%s'" % (access_token,)
    user_info = mysql_session.execute(find_ucid_sql).first()
    if user_info:
        if user_info['ucid']:
            return user_info['ucid']
    return False