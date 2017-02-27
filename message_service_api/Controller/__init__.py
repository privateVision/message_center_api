# _*_ coding: utf-8 _*_
import logging


service_logger = logging.getLogger('message_service')
service_logger.setLevel(logging.DEBUG)
fh = logging.FileHandler('./logs/message_service_api.log')
fh.setLevel(logging.DEBUG)
ch = logging.StreamHandler()
ch.setLevel(logging.DEBUG)
formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
fh.setFormatter(formatter)
ch.setFormatter(formatter)
service_logger.addHandler(fh)
service_logger.addHandler(ch)
