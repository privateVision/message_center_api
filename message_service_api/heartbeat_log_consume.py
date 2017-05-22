# _*_ coding: utf8 _*_
from kafka import KafkaConsumer

from MiddleWare import local_service_logger
from run import app

if __name__ == '__main__':
    kafka_consumer = KafkaConsumer(bootstrap_servers=app.config.get('KAFKA_URL'),
                                   group_id='anfeng_message_service')
    kafka_consumer.subscribe(['message-service-heartbeat'])
    f = open('./logs/message_service_heartbeat.log', 'a')
    for msg in kafka_consumer:
        print msg.offset
        f.write(msg.value)
        f.write('\n')
        # local_service_logger.info(msg.value)
    f.close()
