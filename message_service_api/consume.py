# _*_ coding: utf8 _*_
from kafka import KafkaConsumer

from MiddleWare import service_logger
from run import app
from Service.KafkaHandler import consume_handler

if __name__ == '__main__':
    kafka_consumer = KafkaConsumer(bootstrap_servers=app.config.get('KAFKA_URL'),
                                   group_id='anfeng_message_service',
                                   auto_offset_reset='earliest',
                                   enable_auto_commit=False)
    kafka_consumer.subscribe([app.config.get('KAFKA_TOPIC')])
    for msg in kafka_consumer:
        try:
            consume_handler(msg)
            kafka_consumer.commit()  # 手动提交offset
        except Exception, err:
            service_logger.error("处理kafka消息异常：%s" % (err.message,))
