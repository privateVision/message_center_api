# _*_ coding: utf-8 _*_
import threading

from kafka import KafkaConsumer
from kafka import KafkaProducer

from MiddleWare import create_app, init_mysql_db
import sys

from Service.KafkaHandler import kafka_consume_func

reload(sys)
sys.setdefaultencoding('utf-8')

app = create_app()
mysql_session = init_mysql_db(app)

kafka_producer = KafkaProducer(bootstrap_servers=app.config.get('KAFKA_URL'))
kafka_consumer = KafkaConsumer(bootstrap_servers=app.config.get('KAFKA_URL'))
kafka_consumer.subscribe([app.config.get('KAFKA_TOPIC')])
kafka_consumer_thread = threading.Thread(target=kafka_consume_func, args=(kafka_consumer,))
kafka_consumer_thread.setDaemon(True)
kafka_consumer_thread.start()

if __name__ == '__main__':
    host = app.config.get('HOST')
    port = app.config.get('PORT')
    debug = app.config.get('DEBUG')
    app.run(host=host, port=port, debug=debug)