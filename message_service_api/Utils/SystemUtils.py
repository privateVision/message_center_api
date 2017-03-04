# _*_ coding: utf-8 _*_
import time


# 获取当前时间戳
def get_current_timestamp():
    return int(time.time())


def log_exception(request, exception_message=None):
    from MiddleWare import service_logger
    log_format = "%s - %s - %s" % (request.remote_addr, request.url, exception_message)
    service_logger.error(log_format)
