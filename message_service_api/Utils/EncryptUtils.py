# _*_ coding: utf-8 _*_
import base64
import hashlib
import json
import urllib
import urlparse

import time
from Crypto.Cipher import DES3

from MiddleWare import service_logger
from Controller.BaseController import response_exception
from Utils.RedisUtil import RedisHandle
from run import app


def generate_checksum(request):
    sign = request.args.get("sign")
    from Utils.SystemUtils import log_exception
    if sign is None:
        log_exception(request, "加密签名不能为空")
        return False, response_exception(http_code=200, code=0, message="加密签名不能为空")
    if not check_cms_api_data_sign(request.get_data(), sign):
        log_exception(request, "数据签名校验失败")
        return False, response_exception(http_code=200, code=0, message="数据签名校验失败")
    return True, True


def check_cms_api_data_sign(data, sign):
    m = hashlib.md5()
    str = "%s%s" % (data, app.config.get('MD5_SIGN_KEY'))
    m.update(str)
    gen_sign = m.hexdigest()
    service_logger.info("生成md5的数据：%s" % (str,))
    service_logger.info("客户端数据生成签名：%s" % (gen_sign,))
    if gen_sign == sign:
        return True
    return False


def get_cms_md5_sign(data):
    m = hashlib.md5()
    str = "%s%s" % (data, app.config.get('MD5_SIGN_KEY'))
    service_logger.info("用于生成MD5的数据为：%s" % (str,))
    m.update(str)
    return m.hexdigest()


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
    try:
        app_info = mysql_session.execute(find_prikey_sql).first()
        if app_info:
            pri_key = app_info['priKey']
            m = hashlib.md5()
            m.update(pri_key)
            sign_str = m.hexdigest()
            # sign_str = "%s%s" % (md5_key[0:16], md5_key[0:8])
            service_logger.info("运算生成加密key为：%s" % (sign_str,))
            return encrypt_des(data, sign_str)
        service_logger.error("根据appid未找到相关的应用信息pri_key")
        return False
    except Exception, err:
        service_logger.error(err.message)
        mysql_session.rollback()
    finally:
        mysql_session.close()
    return False


def sdk_api_params_check(request):
    if not request.form.has_key('_appid') or not request.form.has_key('_sign') or not request.form.has_key('_token'):
        return False
    return True


def sdk_api_check_sign(request):
    data_str = ""
    request_data = request.form.copy()
    del request_data['_sign']
    for key in sorted(request_data.keys()):
        k = urllib.quote_plus(key)
        v = urllib.quote_plus(str(request_data[key]))
        data_str += "%s=%s&" % (k, v)
    appid = request.form['_appid']
    procedure_key = "procedure_%s" % (appid,)
    service_logger.info("sdk api request: appid - %s" % (appid,))
    pri_key = ''
    if RedisHandle.exists(procedure_key):
        pri_key = RedisHandle.get(procedure_key)
    else:
        from run import mysql_session
        find_prikey_sql = 'select priKey from procedures where pid = %s' % (appid,)
        try:
            app_info = mysql_session.execute(find_prikey_sql).first()
            if app_info:
                pri_key = app_info['priKey']
                RedisHandle.set(procedure_key, pri_key)
            else:
                service_logger.error("根据appid未找到相关的应用信息pri_key")
                return False
        except Exception, err:
            service_logger.error(err.message)
            mysql_session.rollback()
        finally:
            mysql_session.close()
    m = hashlib.md5()
    m.update(pri_key)
    pri_key = m.hexdigest()
    m = hashlib.md5()
    m.update(data_str + "key=" + pri_key)
    service_logger.info(data_str + "key=" + pri_key)
    md5_sign = m.hexdigest()
    service_logger.info("客户端sign为：%s" % (request.form['_sign'],))
    service_logger.info("服务器运算生成sign为：%s" % (md5_sign,))
    if md5_sign == request.form['_sign']:
        return True
    service_logger.info("服务端与客户端签名不匹配")
    return False


# 安锋助手的后台请求校验函数
def anfeng_helper_api_check_sign(request):
    data_str = ""
    request_data = request.form.copy()
    del request_data['_sign']
    for key in sorted(request_data.keys()):
        k = urllib.quote_plus(key)
        v = urllib.quote_plus(str(request_data[key]))
        data_str += "%s=%s&" % (k, v)
    anfeng_helper_sign_key = app.config.get('ANFENG_HELPER_SIGN_KEY')
    m = hashlib.md5()
    enc_str = "%skey=%s" % (data_str, anfeng_helper_sign_key)
    service_logger.info(enc_str)
    m.update(enc_str)
    gen_sign = m.hexdigest()
    service_logger.info("客户端sign为：%s" % (request.form['_sign'],))
    service_logger.info("服务器运算生成sign为：%s" % (gen_sign,))
    if gen_sign == request.form['_sign']:
        return True
    return False
