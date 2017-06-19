# _*_ coding: utf8 _*_
from kafka import KafkaConsumer
# from pykafka import KafkaClient
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
    # client = KafkaClient(hosts=app.config.get('KAFKA_URL'))
    # topic = client.topics[app.config.get('KAFKA_TOPIC')]
    # balanced_consumer = topic.get_balanced_consumer(consumer_group='anfeng_message_service',
    #                                                 auto_commit_enable=True,
    #                                                 zookeeper_connect='10.13.92.24:2181')
    # for message in balanced_consumer:
    #     if message is not None:
    #         service_logger.info("%s-%s" % (message.offset, message.value))
    #         try:
    #             consume_handler(message)
    #             balanced_consumer.commit_offsets()
    #         except Exception, err:
    #             service_logger.error("处理kafka消息异常：%s" % (err.message,))
