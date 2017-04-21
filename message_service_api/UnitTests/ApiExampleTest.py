# _*_ coding: utf8 _*_
import hashlib
import unittest
import urllib

import requests


class ApiExampleTest(unittest.TestCase):

    # API 请求数据签名
    """
    签名规则：
        1、排序：将请求数据按 key 升序排列
        2. 拼接：将第1 步骤的结果组合成nkey1=value&key2=value (格式 key 和 value 值需要进行 urlencode 编码）
        3. 追加：将第2 步骤的结果末尾追加&signKey=appkey(appkey 由爪游分配)
        4. 加密：将第3 步骤的结果进行 md5 加密(32 位16 进制)，并将结果转换为小写字母，便得到 sign 参数
    """
    def api_post_data_sign_gen(self, post_data, sign_key):
        data_str = ''
        for key in sorted(post_data.keys()):
            k = urllib.quote_plus(key)
            v = urllib.quote_plus(str(post_data[key]))
            data_str += "%s=%s&" % (k, v)
        data_str += "signKey=%s" % (sign_key,)
        m = hashlib.md5()
        m.update(data_str)
        sign = m.hexdigest()
        return sign

    # 用户登录 API Example
    """
    请求参数：
        参数名  | 是否必填 |   类型  |   描述
        token  |    是   |  string | 用户识别码
        open_id|    是   |  string | 安峰网用户唯一标识
        app_id |    否   |  string | 商户应用 id,每应用惟一

    Response:
        {
            "code": 0,
            "msg": "",
            "data": "SUCESS"
        }
    """
    def test_user_login_auth(self):
        request_params = {
            'token': '3sxixcko4nc4kkcw44k0o8c0k',
            'open_id': 'ctmlmrm37jks4cs8848ok4804',
            'app_id': 778
        }
        request_data_sign = self.api_post_data_sign_gen(request_params, sign_key='84ee7ad1a1c0e67c02d7c79418e532a0')
        request_params['sign'] = request_data_sign
        response = requests.post('http://sdkv4.qcwan.com/api/tool/user/auth', data=request_params)
        print response.text

    # 订单查询 API Example
    """
    请求参数：
        参数名    | 是否必填 |   类型  |   描述
        app_id   |    是   |   int   | 商户应用 id
        open_id  |    是   |  string | 唯一识别id
        sn       |    是   |  string | 安锋网订单号
        sign     |    是   |  string | 厂商签名
        vorderid |    是   |  string | 厂家订单号

    Response:
        {
            "code": 1,
            "msg": "",
            "data": "1000"
        }
    """
    def test_query_order_list(self):
        request_params = {
            'app_id': 778,
            'open_id': 'ctmlmrm37jks4cs8848ok4804',
            'token': '3sxixcko4nc4kkcw44k0o8c0k',
            'sn': '170420143534155874389659',
            'vorderid': '1492670134367'
        }
        request_sign = self.api_post_data_sign_gen(request_params, sign_key='84ee7ad1a1c0e67c02d7c79418e532a0')
        request_params['sign'] = request_sign
        response = requests.post('http://sdkv4.qcwan.com/api/tool/info/order', data=request_params)
        print response.text

if __name__ == '__main__':
    unittest.main()
