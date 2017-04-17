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
    def get_user_data_mark_in_redis(key_name):
        key = "%s%s" % (RedisHandle.common_key_prefix, key_name)
        user_mark = {
            "broadcast": [],
            "message": 0
        }
        from Service.UsersService import get_user_broadcast_list
        # 获取用户的广播数据
        broadcast_data = get_user_broadcast_list(key_name)
        user_mark['broadcast'].extend(broadcast_data)
        # 获取用户的未读消息数
        from Service.UsersService import get_user_unread_message_count
        if redis_store.exists(key):
            redis_mark_data = redis_store.hgetall(key)
            if redis_mark_data.has_key('message'):
                user_mark['message'] = int(redis_mark_data['message'])
                if user_mark['message'] <= 0:
                    user_mark['message'] = get_user_unread_message_count(key_name)
                    RedisHandle.hset(key_name, 'message', user_mark['message'])
        else:  # 不存在缓存数据
            user_mark['message'] = get_user_unread_message_count(key_name)
            RedisHandle.hset(key_name, 'message', user_mark['message'])
        return user_mark

    @staticmethod
    def get_ucid_from_redis_by_token(token=None):
        get_token_key = 'session_token_%s' % (token,)
        get_token_value = redis_store.get(get_token_key)
        if get_token_value is None:
            return None
        user_info_str = redis_store.get(get_token_value)
        if user_info_str is None:
            return None
        user_info = json.loads(user_info_str)
        return user_info['ucid']

    @staticmethod
    def get_expired_ts_from_redis_by_token(token=None):
        get_token_key = 'session_token_%s' % (token,)
        get_token_value = redis_store.get(get_token_key)
        if get_token_value is None:
            return None
        user_info_str = redis_store.get(get_token_value)
        if user_info_str is None:
            return None
        user_info = json.loads(user_info_str)
        return user_info['expired_ts']

    @staticmethod
    def get_user_is_freeze_from_redis_by_token(token=None):
        get_token_key = 'session_token_%s' % (token,)
        get_token_value = redis_store.get(get_token_key)
        if get_token_value is None:
            return None
        user_info_str = redis_store.get(get_token_value)
        if user_info_str is None:
            return None
        user_info = json.loads(user_info_str)
        return user_info['freeze']
