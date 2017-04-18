# _*_ coding: utf8 _*_
from kafka import KafkaConsumer

from MiddleWare import service_logger
from run import app
from Service.KafkaHandler import consume_handler

if __name__ == '__main__':
    kafka_consumer = KafkaConsumer(bootstrap_servers=app.config.get('KAFKA_URL'), group_id='anfeng_message_service')
    kafka_consumer.subscribe([app.config.get('KAFKA_TOPIC')])
    for msg in kafka_consumer:
        try:
            consume_handler(msg)
        except Exception, err:
            service_logger.error("处理kafka消息异常：%s" % (err.message,))