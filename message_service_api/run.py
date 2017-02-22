# _*_ coding: utf-8 _*_
from flask import Flask
from flask_mongoengine import MongoEngine
from kafka.client_async import KafkaClient

from Blueprint.RegisterBlueprint import init_blueprint
from config.config import config


def create_app():
    app = Flask(__name__)

    @app.before_request
    def before_request():
        pass

    @app.teardown_request
    def teardown_request(exception):
        pass
    return app


app = create_app()  # 创建应用
app.config.from_object(config['development'])   # 加载配置文件

app.config['MONGODB_SETTINGS'] = {
    'db': app.config.get('MONGODB_DATABASE_NAME'),
    'host': app.config.get('MONGODB_DATABASE_HOST'),
    'port': app.config.get('MONGODB_DATABASE_PORT'),
}

db = MongoEngine()  # 建立MongoDB Engine
db.init_app(app)

# client = KafkaClient(hosts=app.config.get('KAFKA_URL'))

init_blueprint(app)     # 注册蓝图模块

if __name__ == '__main__':
    host = app.config.get('HOST')
    port = app.config.get('PORT')
    debug = app.config.get('DEBUG')
    app.run(host=host, port=port, debug=debug)