# _*_ coding: utf-8 _*_
# from flask_limiter import Limiter
from flask_mongoengine import MongoEngine
# from tornado import netutil
# from tornado import process
# from tornado.httpserver import HTTPServer
# from tornado.ioloop import IOLoop
# from tornado.wsgi import WSGIContainer

from MiddleWare import create_app
import sys

reload(sys)
sys.setdefaultencoding('utf-8')

app, kafka_producer, mysql_session, mysql_cms_session, mysql_cms_read_session = create_app()

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

# if __name__ == '__main__':
#     container = WSGIContainer(app)
#     http_server = HTTPServer(container)
#     # process.fork_processes(0)
#     for port in range(5000, 5016, 1):
#         sockets = netutil.bind_sockets(port)
#         http_server.add_sockets(sockets)
#     IOLoop.current().start()
