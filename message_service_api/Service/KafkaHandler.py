# _*_ coding: utf-8 _*_
from Controller import service_logger


def MQConsumeHandler(message=None):
    service_logger.info(message)

