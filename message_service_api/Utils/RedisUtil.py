# _*_ coding: utf8 _*_
from MiddleWare import redis_store


class RedisHandle(object):
    common_key_prefix = "sdk_msg_service_"

    @staticmethod
    def hincrby(key_name, field_name, incr_number=1):
        key = "%s%s" % (RedisHandle.common_key_prefix, key_name)
        return redis_store.hincrby(key, field_name, incr_number)

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
            "notice": 0,
            "broadcast": 0,
            "message": 0,
            "coupon": 0,
            "rebate": 0,
            "is_freeze": 0
        }
        if redis_store.exists(key):
            redis_mark_data = redis_store.hgetall(key)
            if redis_mark_data.has_key('notice'):
                user_mark['notice'] = int(redis_mark_data['notice'])
            if redis_mark_data.has_key('broadcast'):
                user_mark['broadcast'] = int(redis_mark_data['broadcast'])
            if redis_mark_data.has_key('message'):
                user_mark['message'] = int(redis_mark_data['message'])
            if redis_mark_data.has_key('coupon'):
                user_mark['coupon'] = int(redis_mark_data['coupon'])
            if redis_mark_data.has_key('rebate'):
                user_mark['rebate'] = int(redis_mark_data['rebate'])
        return user_mark
