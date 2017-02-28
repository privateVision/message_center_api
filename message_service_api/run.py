# _*_ coding: utf-8 _*_
import threading

from flask import Flask
from flask_mongoengine import MongoEngine
from kafka import KafkaConsumer
from kafka import KafkaProducer
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker

from Blueprint.RegisterBlueprint import init_blueprint
from Service.KafkaHandler import kafka_consume_func
from config.config import config

import sys

reload(sys)
sys.setdefaultencoding('utf-8')


def create_app():
    app = Flask(__name__)
    app.config.from_object(config['development'])  # 加载配置文件

    app.config['MONGODB_SETTINGS'] = {
        'db': app.config.get('MONGODB_DATABASE_NAME'),
        'host': app.config.get('MONGODB_DATABASE_HOST'),
        'port': app.config.get('MONGODB_DATABASE_PORT'),
    }

    db = MongoEngine()  # 建立MongoDB Engine
    db.init_app(app)

    app.mysql_session = init_mysql_db(app)

    @app.before_request
    def before_request():
        pass

    @app.teardown_request
    def teardown_request(exception):
        pass
    return app


def init_mysql_db(app):
    app.config['SQLALCHEMY_DATABASE_URI'] = app.config.get('SQLALCHEMY_DATABASE_URI')
    mysql_engine = create_engine(app.config['SQLALCHEMY_DATABASE_URI'], encoding="utf-8", echo=True)
    mysql_session = sessionmaker(bind=mysql_engine)
    return mysql_session()


app = create_app()  # 创建应用
mysql_session = init_mysql_db(app)

kafka_producer = KafkaProducer(bootstrap_servers=app.config.get('KAFKA_URL'))
kafka_consumer = KafkaConsumer(bootstrap_servers=app.config.get('KAFKA_URL'))
kafka_consumer.subscribe([app.config.get('KAFKA_TOPIC')])
kafka_consumer_thread = threading.Thread(target=kafka_consume_func, args=(kafka_consumer,))
kafka_consumer_thread.setDaemon(True)
kafka_consumer_thread.start()

init_blueprint(app)     # 注册蓝图模块


if __name__ == '__main__':
    host = app.config.get('HOST')
    port = app.config.get('PORT')
    debug = app.config.get('DEBUG')
    app.run(host=host, port=port, debug=debug)