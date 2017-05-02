# _*_ coding: utf-8 _*_
import tornado
from flask_mongoengine import MongoEngine
from tornado import httpserver
from tornado import ioloop
from tornado import wsgi

from MiddleWare import create_app
import sys

reload(sys)
sys.setdefaultencoding('utf-8')

app, kafka_producer, mysql_session, mysql_cms_session = create_app()

# API 请求限流
# limiter = Limiter(app, key_func=get_remote_address)

db = MongoEngine()  # 建立MongoDB Engine
db.init_app(app)

SDK_PLATFORM_ID = app.config.get('SDK_PLATFORM_ID')


@app.errorhandler(429)
def ratelimit_handler(e):
    return "ratelimit exceeded, please try after for a while", 429


@app.errorhandler(404)
def page_not_found(error):
    return 'API Not Found', 404


@app.errorhandler(500)
def page_not_found(error):
    return 'Server Exception', 500

# if __name__ == '__main__':
#     host = app.config.get('HOST')
#     port = app.config.get('PORT')
#     debug = app.config.get('DEBUG')
#     app.run(host=host, port=port, debug=debug)

# container = wsgi.WSGIContainer(app)
# http_server = httpserver.HTTPServer(container)
# http_server.listen(8888)
# ioloop.IOLoop.current().start()

#  uwsgi 启动脚本： uwsgi --socket 127.0.0.1:5000 --wsgi-file run.py --callable app --enable-threads
#                       --lazy-apps --processes 4 --threads 2
