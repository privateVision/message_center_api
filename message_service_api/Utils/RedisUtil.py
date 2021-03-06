# _*_ coding: utf8 _*_
import json

from MiddleWare import redis_store


class RedisHandle(object):
    common_key_prefix = "msa_"

    @staticmethod
    def hincrby(key_name, field_name, incr_number=1):
        key = "%s%s" % (RedisHandle.common_key_prefix, key_name)
        return redis_store.hincrby(key, field_name, incr_number)

    @staticmethod
    def hdecrby(key_name, field_name, incr_number=-1):
        key = "%s%s" % (RedisHandle.common_key_prefix, key_name)
        if RedisHandle.exists(key_name):
            unread_count = int(redis_store.hget(key, field_name))
            if unread_count > 0:
                return redis_store.hincrby(key, field_name, incr_number)
            return redis_store.hset(key, field_name, 0)

    @staticmethod
    def set(key_name, field_value):
        key = "%s%s" % (RedisHandle.common_key_prefix, key_name)
        return redis_store.set(key, field_value)

    @staticmethod
    def get(key_name):
        key = "%s%s" % (RedisHandle.common_key_prefix, key_name)
        return redis_store.get(key)

    @staticmethod
    def hset(key_name, field_name, field_value):
        key = "%s%s" % (RedisHandle.common_key_prefix, key_name)
        return redis_store.hset(key, field_name, field_value)

    @staticmethod
    def exists(key_name):
        key = "%s%s" % (RedisHandle.common_key_prefix, key_name)
        return redis_store.exists(key)

    @staticmethod
    def hgetall(key_name):
        key = "%s%s" % (RedisHandle.common_key_prefix, key_name)
        return redis_store.hgetall(key)

    @staticmethod
    def hdel(key_name, field_name):
        key = "%s%s" % (RedisHandle.common_key_prefix, key_name)
        return redis_store.hdel(key, field_name)

    @staticmethod
    def clear_user_data_mark_in_redis(key_name, field_name):
        key = "%s%s" % (RedisHandle.common_key_prefix, key_name)
        return redis_store.hset(key, field_name, 0)

    @staticmethod
    def set_key_exipre(key_name, ttl):
        key = "%s%s" % (RedisHandle.common_key_prefix, key_name)
        redis_store.expire(key, ttl)

    @staticmethod
    def get_user_data_mark_in_redis(key_name, appid):
        key = "%s%s" % (RedisHandle.common_key_prefix, key_name)
        user_mark = {
            "broadcast": [],
            "message": 0,
            "gift_num": 0
        }
        import time
        from MiddleWare import service_logger
        stime = time.time()
        # 获取用户的广播数据
        from Service.UsersService import get_user_broadcast_list
        broadcast_data = get_user_broadcast_list(key_name)
        user_mark['broadcast'].extend(broadcast_data)

        # 获取用户的未读消息数
        from Service.UsersService import get_user_unread_message_count
        user_mark['message'] = get_user_unread_message_count(key_name)
        RedisHandle.hset(key_name, 'message', user_mark['message'])
        etime = time.time()
        service_logger.info("debug1-处理时间：%s" % (etime - stime,))
        # 获取用户未领取的礼包数
        from Service.UsersService import get_user_gift_count
        user_mark['gift_num'] = get_user_gift_count(key_name, appid)
        RedisHandle.hset(key_name, 'gift_num', user_mark['gift_num'])
        etime = time.time()
        service_logger.info("debug2-处理时间：%s" % (etime - stime,))
        # RedisHandle.set_key_exipre(key_name, 14400)

        # from Service.UsersService import get_user_gift_count
        # if RedisHandle.exists(key_name):
        #     redis_mark_data = RedisHandle.hgetall(key_name)
        #     if redis_mark_data.has_key('gift_num'):
        #         gift_num = int(redis_mark_data['gift_num'])
        #         if gift_num > 0:
        #             user_mark['gift_num'] = gift_num
        #         else:
        #             user_mark['gift_num'] = get_user_gift_count(key_name, appid)
        #             RedisHandle.hset(key_name, 'gift_num', user_mark['gift_num'])
        #             RedisHandle.set_key_exipre(key_name, 14400)
        #     else:
        #         user_mark['gift_num'] = get_user_gift_count(key_name, appid)
        #         RedisHandle.hset(key_name, 'gift_num', user_mark['gift_num'])
        #         RedisHandle.set_key_exipre(key_name, 14400)
        # else:
        #     user_mark['gift_num'] = get_user_gift_count(key_name, appid)
        #     RedisHandle.hset(key_name, 'gift_num', user_mark['gift_num'])
        #     RedisHandle.set_key_exipre(key_name, 14400)

        return user_mark

    @staticmethod
    def get_ucid_from_redis_by_token(token=None):
        get_token_key = 's_%s' % (token,)
        get_token_value = redis_store.hgetall(get_token_key)
        if get_token_value is None:
            return 0
        if get_token_value.has_key('ucid'):
            return int(get_token_value['ucid'])
        return 0

    @staticmethod
    def get_is_expired_from_redis_by_token(token=None):
        get_token_key = 's_%s' % (token,)
        get_token_value = redis_store.hgetall(get_token_key)
        if get_token_value is None:
            return None
        return get_token_value

    @staticmethod
    def get_user_is_freeze_from_redis_by_token(token=None):
        get_token_key = 's_%s' % (token,)
        get_token_value = redis_store.hgetall(get_token_key)
        if get_token_value is not None:
            if get_token_value.has_key('freeze'):
                return True, get_token_value['freeze']
            else:
                return True, None
        return None, None

    @staticmethod
    def get_ucuser_session_id_by_token(token=None):
        get_key = "ucuser_session_session_token_%s" % (token,)
        get_token_value = redis_store.get(get_key)
        if get_token_value is None:
            return None
        return get_token_value

    @staticmethod
    def get_ucuser_session_info_by_id(ucuser_session_key=None):
        get_token_value = redis_store.get(ucuser_session_key)
        if get_token_value is None:
            return None
        session_info = json.loads(get_token_value)
        return session_info
