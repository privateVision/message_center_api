# _*_ coding:utf-8 _*_
"""
示例配置文件
"""


class Config:
    MD5_SIGN_KEY = "868a4601f68867aff87950532857da34"
    ANFENG_HELPER_SIGN_KEY = "ebe89a4c54f35e593d86455aab4343a8"

    @staticmethod
    def init_app(app):
        pass


class DevelopmentConfig(Config):
    def __init__(self):
        pass

    # ----- 应用配置 ------- #
    HOST = 'localhost'    # 应用启动HOST
    PORT = 5000           # 应用启动端口
    DEBUG = True          # 应用是否以调试模式启动

    # ----- Kafka 配置 ------- #
    KAFKA_URL = "192.168.1.246:9092"      # kafka 地址
    KAFKA_TOPIC = "message-service"       # kafka 消息中心 Topic
    # ----- MongoDB 配置 ----- #
    MONGODB_DATABASE_HOST = 'localhost'   # mongodb 地址
    MONGODB_DATABASE_PORT = 27017         # mongodb 端口
    MONGODB_DATABASE_NAME = 'users_message'  # mongodb 库名
    # ----- Mysql 配置 ------ #
    SQLALCHEMY_DATABASE_URI = 'mysql://root:gelaoshi123,@192.168.1.241:4474/anfanapi?charset=utf8'   # Mysql 连接地址
    # ----- Redis 配置 ------ #
    REDIS_URL = "redis://:@localhost:6379/0"   # redis 连接地址


class ProductionConfig(Config):
    def __init__(self):
        pass

    # ----- 应用配置 ------- #
    HOST = 'localhost'
    PORT = 5000
    DEBUG = True

    # ----- Kafka 配置 ------- #
    KAFKA_URL = "192.168.1.246:9092"
    KAFKA_TOPIC = "message-service"
    # ----- MongoDB 配置 ----- #
    MONGODB_DATABASE_HOST = 'localhost'
    MONGODB_DATABASE_PORT = 27017
    MONGODB_DATABASE_NAME = 'users_message'
    # ----- Mysql 配置 ------ #
    SQLALCHEMY_DATABASE_URI = 'mysql://root:gelaoshi123,@192.168.1.241:4474/anfanapi?charset=utf8'
    # ----- Redis 配置 ------ #
    REDIS_URL = "redis://:@localhost:6379/0"


config = {
    'development': DevelopmentConfig,
    'production': ProductionConfig,
    'default': DevelopmentConfig
}
