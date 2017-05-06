# _*_ coding: utf8 _*_
from kafka import KafkaConsumer

from run import app

if __name__ == '__main__':
    kafka_consumer = KafkaConsumer(bootstrap_servers=app.config.get('KAFKA_URL'),
                                   group_id='anfeng_message_service')
    kafka_consumer.subscribe(['message-service-log'])
    f = open('./logs/message_service_api.log', 'a')
    for msg in kafka_consumer:
        print msg.offset
        f.write(msg.value)
        f.write('\n')
        f.flush()
        # local_service_logger.info(msg.value)
    f.close()
