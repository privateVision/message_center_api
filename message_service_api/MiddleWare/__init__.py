# _*_ coding: utf-8 _*_

from flask import Flask
from flask_mongoengine import MongoEngine
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker

from Blueprint.RegisterBlueprint import init_blueprint
from config.config import config

import logging


service_logger = logging.getLogger('message_service')
service_logger.setLevel(logging.DEBUG)
fh = logging.FileHandler('./logs/message_service_api.log')
fh.setLevel(logging.DEBUG)
ch = logging.StreamHandler()
ch.setLevel(logging.DEBUG)
formatter = logging.Formatter('%(asctime)s - %(name)s - %(levelname)s - %(message)s')
fh.setFormatter(formatter)
ch.setFormatter(formatter)
service_logger.addHandler(fh)
service_logger.addHandler(ch)


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

    init_blueprint(app)  # 注册蓝图模块

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

