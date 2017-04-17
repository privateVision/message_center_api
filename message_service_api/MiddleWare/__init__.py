# _*_ coding: utf-8 _*_
import socket
import threading
from logging.handlers import TimedRotatingFileHandler, SMTPHandler

import wtforms_json
from flask import Flask
from flask_redis import FlaskRedis
from kafka import KafkaConsumer
from kafka import KafkaProducer
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker
from sqlalchemy.pool import NullPool

from Blueprint.RegisterBlueprint import init_blueprint
from config.config import config

import logging

# hdfs 待分析日志记录
hdfs_logger = logging.getLogger('hdfs_log_service')
hdfs_logger.setLevel(logging.DEBUG)
hdfs_fh = TimedRotatingFileHandler('./logs/message_service_hdfs.log',
                                   when="d",
                                   interval=1,
                                   backupCount=10)
hdfs_fh.suffix = "%Y%m%d.log"
hdfs_fh.setLevel(logging.DEBUG)
formatter = logging.Formatter('%(asctime)s - %(message)s')
hdfs_fh.setFormatter(formatter)
hdfs_logger.addHandler(hdfs_fh)

# 后台业务日志
service_logger = logging.getLogger('message_service')
service_logger.setLevel(logging.DEBUG)
fh = TimedRotatingFileHandler('./logs/message_service_api.log',
                              when="d",
                              interval=1,
                              backupCount=10)
fh.suffix = "%Y%m%d.log"
fh.setLevel(logging.DEBUG)
ch = logging.StreamHandler()
ch.setLevel(logging.INFO)
formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s - [%(filename)s:%(lineno)s]')
fh.setFormatter(formatter)
ch.setFormatter(formatter)
service_logger.addHandler(fh)
service_logger.addHandler(ch)


# 邮件和消息通知
ADMINS = ['14a1152bf3963d126735637d5e745ae5@mail.bearychat.com']
server_host_name = socket.getfqdn(socket.gethostname())
server_ip = socket.gethostbyname(server_host_name)
mail_handler = SMTPHandler('127.0.0.1', 'server-error@monitor.com', ADMINS,
                           "%s-%s-Service Exception Report" % (server_host_name, server_ip))
mail_handler.setFormatter(logging.Formatter('''
Message type:       %(levelname)s
Location:           %(pathname)s:%(lineno)d
Module:             %(module)s
Function:           %(funcName)s
Time:               %(asctime)s

Message:

%(message)s
'''))
mail_handler.setLevel(logging.ERROR)
service_logger.addHandler(mail_handler)


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
    mysql_engine = create_engine(app.config['SQLALCHEMY_DATABASE_URI'], encoding="utf-8", echo=False,
                                 pool_recycle=28800, poolclass=NullPool)
    mysql_session = sessionmaker(autocommit=False, bind=mysql_engine)

    mysql_cms_engine = create_engine(app.config['SQLALCHEMY_CMS_DATABASE_URI'], encoding="utf-8", echo=False,
                                     pool_recycle=28800, poolclass=NullPool)
    mysql_cms_session = sessionmaker(autocommit=False, bind=mysql_cms_engine)

    return app, kafka_producer, mysql_session(), mysql_cms_session()
