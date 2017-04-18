# _*_ coding: utf-8 _*_

from flask import jsonify

from LanguageConf import get_tips


def response_ok(http_code=200, code=1, message=get_tips('common', 'success')):
    response = {
        "code": code,
        "msg": message,
        "data": None
    }
    return jsonify(response), http_code


def response_data(http_code=200, code=1, message=get_tips('common', 'success'), data=None):
    response = {
        "code": code,
        "msg": message,
        "data": data
    }
    return jsonify(response), http_code


def response_exception(http_code=200, code=0, message=get_tips('common', 'client_request_error')):
    response = {
        "code": code,
        "msg": message
    }
    return jsonify(response), http_code
