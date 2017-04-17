import json

from flask import Blueprint

from Controller.BaseController import response_exception

error_controller = Blueprint('ErrorController', __name__)


@error_controller.app_errorhandler(404)
def not_found_error():
    return response_exception()


@error_controller.app_errorhandler(500)
def internal_error():
    return json.dumps('500')
