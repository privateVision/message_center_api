# _*_ coding: utf-8 _*_
import json

from flask import Blueprint

from Controller import service_logger
from Controller.BaseController import response_ok, response_data

broadcast_controller = Blueprint('BroadcastController', __name__)


@broadcast_controller.route('/broadcast/<int:page>')
def show(page):
    pass