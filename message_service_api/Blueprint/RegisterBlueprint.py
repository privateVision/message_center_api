# _*_ coding: utf-8 _*_
from Controller.BroadcastController import broadcast_controller
from Controller.ErrorController import error_controller
from Controller.MessageController import message_controller
from Controller.NoticeController import notice_controller

# 注册蓝图的列表
blueprint_list = [error_controller, notice_controller, broadcast_controller, message_controller]


def init_blueprint(app):
    for blueprint in blueprint_list:
        app.register_blueprint(blueprint)