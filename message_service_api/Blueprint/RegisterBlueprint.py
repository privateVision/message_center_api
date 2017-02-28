# _*_ coding: utf-8 _*_


def init_blueprint(app):
    from Controller.AppController import app_controller
    from Controller.BroadcastController import broadcast_controller
    from Controller.CouponController import coupon_controller
    from Controller.ErrorController import error_controller
    from Controller.MessageController import message_controller
    from Controller.NoticeController import notice_controller
    from Controller.RebateController import rebate_controller
    # 注册蓝图的列表
    blueprint_list = [error_controller, notice_controller, broadcast_controller,
                      message_controller, app_controller, coupon_controller,
                      rebate_controller]
    for blueprint in blueprint_list:
        app.register_blueprint(blueprint)
