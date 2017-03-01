# _*_ coding: utf-8 _*_
import json
import threading

from flask_socketio import SocketIO, emit
from kafka import KafkaConsumer
from kafka import KafkaProducer

from MiddleWare import create_app, init_mysql_db
import sys
from Service.KafkaHandler import kafka_consume_func

reload(sys)
sys.setdefaultencoding('utf-8')

app = create_app()
socketio = SocketIO(app)
mysql_session = init_mysql_db(app)

kafka_producer = KafkaProducer(bootstrap_servers=app.config.get('KAFKA_URL'))
kafka_consumer = KafkaConsumer(bootstrap_servers=app.config.get('KAFKA_URL'))
kafka_consumer.subscribe([app.config.get('KAFKA_TOPIC')])
kafka_consumer_thread = threading.Thread(target=kafka_consume_func, args=(kafka_consumer,))
kafka_consumer_thread.setDaemon(True)
kafka_consumer_thread.start()


@socketio.on('message')
def handle_message(message):
    print('received message: ' + json.dumps(message))


@app.errorhandler(404)
def page_not_found(error):
    return 'API Not Found', 404


@app.errorhandler(500)
def page_not_found(error):
    return 'Server Exception', 500

if __name__ == '__main__':
    host = app.config.get('HOST')
    port = app.config.get('PORT')
    debug = app.config.get('DEBUG')
    # app.run(host=host, port=port, debug=debug)
    socketio.run(app, port=6666)
