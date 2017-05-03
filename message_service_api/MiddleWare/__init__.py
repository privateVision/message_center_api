# _*_ coding: utf-8 _*_
import socket
import threading
from logging.handlers import TimedRotatingFileHandler, SMTPHandler

import wtforms_json
from flask import Flask
from flask_redis import FlaskRedis
from kafka import KafkaConsumer
from kafka import KafkaProducer
from kafka_logger.handler import KafkaLoggingHandler
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker
from sqlalchemy.pool import NullPool, SingletonThreadPool

from Blueprint.RegisterBlueprint import init_blueprint
from config.config import config

import logging

# hdfs 待分析日志记录 (输出到本地文件)
hdfs_logger = logging.getLogger('message_service_heartbeat')
hdfs_logger.setLevel(logging.INFO)
hdfs_fh = TimedRotatingFileHandler('./logs/message_service_heartbeat.log',
                                   when="d",
                                   interval=1,
                                   backupCount=10)
hdfs_fh.suffix = "%Y%m%d.log"
hdfs_fh.setLevel(logging.DEBUG)
local_hdfs_logger_formatter = logging.Formatter('%(asctime)s - %(message)s')
hdfs_fh.setFormatter(local_hdfs_logger_formatter)
hdfs_logger.addHandler(hdfs_fh)

# 后台业务日志 (输出到本地文件)
service_logger = logging.getLogger('message_service')
service_logger.setLevel(logging.INFO)
fh = TimedRotatingFileHandler('./logs/message_service_api.log',
                              when="d",
                              interval=1,
                              backupCount=10)
fh.suffix = "%Y%m%d.log"
fh.setLevel(logging.DEBUG)
ch = logging.StreamHandler()
ch.setLevel(logging.INFO)
local_service_logger_formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s '
                                                   '- %(message)s - [%(filename)s:%(lineno)s]')
fh.setFormatter(local_service_logger_formatter)
ch.setFormatter(local_service_logger_formatter)
service_logger.addHandler(fh)
service_logger.addHandler(ch)

# Kafka Broker
kafka_server = "10.13.92.24:9092"

# 心跳日志输出到 kafka
# hdfs_logger = logging.getLogger('message_service_heartbeat')
# hdfs_logger.setLevel(logging.DEBUG)
# heartbeat_logger_producer = KafkaProducer(bootstrap_servers=kafka_server)
# kafka_heartbeat_handler = KafkaLoggingHandler(heartbeat_logger_producer, 'message-service-heartbeat')
# heartbeat_logger_formatter = logging.Formatter('%(asctime)s - %(message)s')
# kafka_heartbeat_handler.setFormatter(heartbeat_logger_formatter)
# hdfs_logger.addHandler(kafka_heartbeat_handler)


# 后台业务日志输出到 kafka
# service_logger = logging.getLogger('message_service')
# service_logger.setLevel(logging.INFO)
# message_service_logger_producer = KafkaProducer(bootstrap_servers=kafka_server)
# kafka_handler = KafkaLoggingHandler(message_service_logger_producer, 'message-service-log')
# message_service_logger_formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s -'
#                                                      ' %(message)s - [%(filename)s:%(lineno)s]')
# kafka_handler.setFormatter(message_service_logger_formatter)
# service_logger.addHandler(kafka_handler)


redis_store = FlaskRedis()
wtforms_json.init()


def create_app():
    app = Flask(__name__)
    app.config.from_object(config['development'])  # 加载配置文件

    app.config['MONGODB_SETTINGS'] = {
        'db': app.config.get('MONGODB_DATABASE_NAME'),
        'host': app.config.get('MONGODB_DATABASE_HOST'),
        'port': app.config.get('MONGODB_DATABASE_PORT'),
    }

    init_blueprint(app)  # 注册蓝图模块

    redis_store.init_app(app)

    kafka_producer = KafkaProducer(bootstrap_servers=app.config.get('KAFKA_URL'))
    # kafka_consumer = KafkaConsumer(bootstrap_servers=app.config.get('KAFKA_URL'), group_id='dev_anfeng_message_service')
    # kafka_consumer.subscribe([app.config.get('KAFKA_TOPIC')])
    # from Service.KafkaHandler import kafka_consume_func
    # kafka_consumer_thread = threading.Thread(target=kafka_consume_func, args=(kafka_consumer,))
    # kafka_consumer_thread.setDaemon(True)
    # kafka_consumer_thread.start()

    app.config['SQLALCHEMY_DATABASE_URI'] = app.config.get('SQLALCHEMY_DATABASE_URI')
    mysql_engine = create_engine(app.config['SQLALCHEMY_DATABASE_URI'], encoding="utf-8", echo=True,
                                 pool_recycle=28800, poolclass=SingletonThreadPool)
    mysql_session = sessionmaker(autocommit=False, bind=mysql_engine)

    mysql_cms_engine = create_engine(app.config['SQLALCHEMY_CMS_DATABASE_URI'], encoding="utf-8", echo=True,
                                     pool_recycle=28800, poolclass=SingletonThreadPool)
    mysql_cms_session = sessionmaker(autocommit=False, bind=mysql_cms_engine)

    return app, kafka_producer, mysql_session(), mysql_cms_session()
