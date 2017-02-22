# _*_ coding: utf-8 _*_

from flask import jsonify


def response_ok(http_code=200, code=0, message="success"):
    response = {
        "code": code,
        "message": message
    }
    return jsonify(response), http_code


def response_data(http_code=200, code=0, message="success", data=None):
    response = {
        "code": code,
        "message": message,
        "data": data
    }
    return jsonify(response), http_code


def response_exception(http_code=400, code=400, message="客户端请求错误"):
    response = {
        "code": code,
        "message": message
    }
    return jsonify(response), http_code
