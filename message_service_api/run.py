# _*_ coding: utf-8 _*_
from flask_mongoengine import MongoEngine

from MiddleWare import create_app
import sys

reload(sys)
sys.setdefaultencoding('utf-8')

app, kafka_producer, mysql_session, mysql_cms_session = create_app()

db = MongoEngine()  # 建立MongoDB Engine
db.init_app(app)

SDK_PLATFORM_ID = app.config.get('SDK_PLATFORM_ID')


# 邮件和消息通知
import logging
from logging.handlers import SMTPHandler
ADMINS = ['14a1152bf3963d126735637d5e745ae5@mail.bearychat.com']
mail_handler = SMTPHandler('127.0.0.1', 'server-error@example.com', ADMINS, 'YourApplication Failed')
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
app.logger.addHandler(mail_handler)


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


#  uwsgi 启动脚本： uwsgi --socket 127.0.0.1:5000 --wsgi-file run.py --callable app --enable-threads
#                       --lazy-apps --processes 4 --threads 2
