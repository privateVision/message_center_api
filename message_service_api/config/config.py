# _*_ coding:utf-8 _*_

class Config:
    MD5_SIGN_KEY = "868a4601f68867aff87950532857da34"

    @staticmethod
    def init_app(app):
        pass


class DevelopmentConfig(Config):
    HOST = 'localhost'
    PORT = 6000
    DEBUG = True

    CSRF_ENABLED = False
    SECRET_KEY = 'yuwqedbcaewfdsa813asjdfw'
    # ----- Kafka 配置 ------- #
    KAFKA_URL = "192.168.1.246:9092"
    KAFKA_TOPIC = "message-service"
    # ----- MongoDB 配置 ----- #
    MONGODB_DATABASE_HOST = 'localhost'
    MONGODB_DATABASE_PORT = 27017
    MONGODB_DATABASE_NAME = 'users_message'


class ProductionConfig(Config):
    HOST = 'localhost'
    PORT = 5000
    DEBUG = False

    CSRF_ENABLED = False
    SECRET_KEY = 'yuwqedbcaewfdsa813asjdfw'
    # ----- Kafka 配置 ------- #
    KAFKA_URL = "192.168.1.246:9092"
    KAFKA_TOPIC = "message-service"
    # ----- MongoDB 配置 ----- #
    MONGODB_DATABASE_HOST = 'localhost'
    MONGODB_DATABASE_PORT = 27017
    MONGODB_DATABASE_NAME = 'users_message'


config = {
    'development': DevelopmentConfig,
    'production': ProductionConfig,
    'default': DevelopmentConfig
}
