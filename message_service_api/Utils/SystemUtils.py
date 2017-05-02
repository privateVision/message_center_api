# _*_ coding: utf-8 _*_
import time


# 获取当前时间戳
def get_current_timestamp():
    return int(time.time())


def convert_to_timestamp(date):
    time_array = time.strptime(date, "%Y-%m-%d %H:%M:%S")
    timestamp = int(time.mktime(time_array))
    return timestamp


def log_exception(request, exception_message=None):
    from MiddleWare import service_logger
    log_format = "%s - %s - %s" % (request.remote_addr, request.url, exception_message)
    service_logger.error(log_format)


class KafkaConsumeError(Exception):
    def __init__(self, message, status):
        super.__init__(message, status)
        self.message = message
        self.status = status
