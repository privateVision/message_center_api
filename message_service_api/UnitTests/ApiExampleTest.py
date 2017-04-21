# _*_ coding: utf8 _*_
import hashlib
import json
import threading
import unittest
import urllib

import requests
import time


class ApiExampleTest(unittest.TestCase):

    # API 请求数据签名
    """
    签名规则：
        1、排序：将请求数据按 key 升序排列
        2. 拼接：将第1 步骤的结果组合成key1=value&key2=value 格式
        （key 和value 值需要进行urlencode 编码）
        3. 追加：将第2 步骤的结果末尾追加&signKey=appkey(appkey 由爪
        游分配)
        4. 加密：将第3 步骤的结果进行 md5 加密(32 位16 进制)，并将结果
        转换为小写字母，便得到 sign 参数
    """
    def api_post_data_sign_gen(self, post_data):
        data_str = ''
        for key in sorted(post_data.keys()):
            k = urllib.quote_plus(key)
            v = urllib.quote_plus(str(post_data[key]))
            data_str += "%s=%s&" % (k, v)
        m = hashlib.md5()
        m.update(data_str)
        sign = m.hexdigest()
        return sign

    # 用户登录 API Example
    """
    请求参数：
        参数名  | 是否必填 |   类型  |   描述
        token  |    是   |  string | 用户识别码
        openid |    是   |  string | 安峰网用户唯一标识
        appid  |    否   |  string | 商户应用 id,每应用惟一
    """
    def test_user_login_auth(self):
        request_params = {
            'appid': 778,
            'openid': '38bgko9xcpusoskkookgwccks',
            'token': '3sxixcko4nc4kkcw44k0o8c0k',
            'signKey': """
                        -----BEGIN PUBLIC KEY-----
                        MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCnzryn9n2tKl85aqZ+BPGLgUZO
                        p6smovbmlwBAvTEjztl0Pmer+4SYqsSbd7v9p9U1kMWRkARyo0ESyfftZuXGNx7u
                        lJbs7X2CA7gEc0MxzQN7WlvLQEVkCoRWbXsjmBIdc8A6EooWNY6SOvJdzLg7f1RC
                        M2JeXE30kPyxTiJNPQIDAQAB
                        -----END PUBLIC KEY-----
                        """
        }
        request_data_sign = self.api_post_data_sign_gen(request_params)
        post_body_data = {
            'token': '3sxixcko4nc4kkcw44k0o8c0k',
            'openid': '38bgko9xcpusoskkookgwccks',
            'appid': 778,
            'sign': request_data_sign
        }
        response = requests.post('http://sdkv4test.qcwanwan.com/api/tool/user/auth', data=post_body_data)
        print response.text

    # 订单查询 API Example
    """
    请求参数：
        参数名    | 是否必填 |   类型  |   描述
        appid    |    是   |   int   | 商户应用 id
        openid   |    是   |  string | 唯一识别id
        sn       |    是   |  string | 安锋网订单号
        sign     |    是   |  string | 厂商签名
        vorderid |    是   |  string | 厂家订单号
    """
    # def test_query_order_list(self):
    #     request_params = {
    #         'appid': 778,
    #         'openid': '38bgko9xcpusoskkookgwccks',
    #         'token': '3sxixcko4nc4kkcw44k0o8c0k',
    #         'signKey': 'asdfhqufkfklasfoiyewqirh'
    #     }
    #     request_sign = self.api_post_data_sign_gen(request_params)
    #     post_body_data = {
    #         'token': '3sxixcko4nc4kkcw44k0o8c0k',
    #         'openid': '38bgko9xcpusoskkookgwccks',
    #         'appid': '778',
    #         'sign': request_sign
    #     }
    #     response = requests.post('http://sdkv4test.qcwanwan.com/api/tool/info/order', data=post_body_data)
    #     print response.text

if __name__ == '__main__':
    unittest.main()
