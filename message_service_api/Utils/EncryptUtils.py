# _*_ coding: utf-8 _*_
import base64
import hashlib
import json

from Crypto.Cipher import DES3

from Controller import service_logger
from Controller.BaseController import response_exception
from run import app


def generate_checksum(request):
    sign = request.args.get("sign")
    if sign is None:
        return False, response_exception(http_code=401, code=401001, message="加密签名不能为空")
    if not check_cms_api_data_sign(request.form, sign):
        return False, response_exception(http_code=401, code=401002, message="数据签名校验失败")
    return True, True


def check_cms_api_data_sign(data, sign):
    gen_sign = get_md5_sign(data)
    service_logger.info(gen_sign)
    if gen_sign == sign:
        return True
    return False


def get_md5_sign(data):
    m = hashlib.md5()
    data = json.dumps(data)
    m.update(data + app.config.get('MD5_SIGN_KEY'))
    return m.hexdigest()


def encrypt_des(data, des_key):
    des3 = DES3.new(des_key, DES3.MODE_ECB)
    s = DES3.block_size
    key = data + (s - len(data) % s) * chr(s - len(data) % s)
    result_str = des3.encrypt(key)
    return base64.b64encode(result_str)


def decrypt_des(data, des_key):
    data = base64.b64decode(data)
    des3 = DES3.new(des_key, DES3.MODE_ECB)
    str = des3.decrypt(data)
    result_str = str[0:-ord(str[-1])]
    return result_str
