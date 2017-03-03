# _*_ coding: utf-8 _*_
import base64
import hashlib
import json
import urlparse

from Crypto.Cipher import DES3

from MiddleWare import service_logger
from Controller.BaseController import response_exception
from run import app


def generate_checksum(request):
    sign = request.args.get("sign")
    if sign is None:
        return False, response_exception(http_code=403, code=403001, message="加密签名不能为空")
    if not check_cms_api_data_sign(request.form, sign):
        return False, response_exception(http_code=403, code=403002, message="数据签名校验失败")
    return True, True


def check_cms_api_data_sign(data, sign):
    gen_sign = get_md5_sign(data)
    service_logger.info("客户端数据：%s" % (data,))
    service_logger.info("客户端数据生成签名：%s" % (gen_sign,))
    if gen_sign == sign:
        return True
    return False


def get_md5_sign(data):
    m = hashlib.md5()
    data = json.dumps(data, sort_keys=True)
    str = "%s%s" % (data, app.config.get('MD5_SIGN_KEY'))
    service_logger.info("用于生成MD5的数据为：%s" % (str,))
    m.update(str)
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


def url_to_dict(url):
    service_logger.info("解密 sdk api request param is: %s" % (url,))
    url = "http://prefix.com/?%s" % (url,)
    query = urlparse.urlparse(url).query
    return dict([(k, v[0]) for k, v in urlparse.parse_qs(query).items()])


def sdk_api_gen_key(appid, data):
    find_prikey_sql = 'select priKey from procedures where pid = %s' % (appid,)
    from run import mysql_session
    app_info = mysql_session.execute(find_prikey_sql).first()
    if app_info:
        pri_key = app_info['priKey']
        m = hashlib.md5()
        m.update(pri_key)
        md5_key = m.hexdigest()
        sign_str = "%s%s" % (md5_key[0:16], md5_key[0:8])
        service_logger.info("运算生成加密key为：%s" % (sign_str,))
        return encrypt_des(data, sign_str)
    service_logger.error("根据appid未找到相关的应用信息pri_key")
    return False


def sdk_api_check_key(request):
    appid = request.form['appid']
    param = request.form['param']
    service_logger.info("sdk api request: appid - %s, params - %s " % (appid, param))
    find_prikey_sql = 'select priKey from procedures where pid = %s' % (appid,)
    from run import mysql_session
    app_info = mysql_session.execute(find_prikey_sql).first()
    if app_info:
        pri_key = app_info['priKey']
        m = hashlib.md5()
        m.update(pri_key)
        md5_key = m.hexdigest()
        sign_str = "%s%s" % (md5_key[0:16], md5_key[0:8])
        service_logger.info("运算生成解密key为：%s" % (sign_str,))
        return url_to_dict(decrypt_des(param, sign_str))
    service_logger.error("根据appid未找到相关的应用信息pri_key")
    return False
