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
        data_str += "sign_key=%s" % (sign_key,)
        m = hashlib.md5()
        m.update(data_str)
        sign = m.hexdigest()
        return sign

    # 用户登录状态检测 API Example
    """
    请求方式：POST
    请求参数：
        参数名  | 是否必填 |   类型  |   描述
        token  |    是   |  string | 登录返回token
        open_id|    是   |  string | 支付的发起人,用户在SDK 的数字标记
        app_id |    否   |  string | 商户应用AF_APPID

    Response:
        {
            "code": 1,
            "msg": "用户信息",
            "data": true
        }
    """
    # def test_user_login_auth(self):
    #     api_http_url = 'http://sdkv4.qcwan.com/api/tool/user/auth'
    #     request_params = {
    #         'token': '3sxixcko4nc4kkcw44k0o8c0k',
    #         'open_id': 'ctmlmrm37jks4cs8848ok4804',
    #         'app_id': 778
    #     }
    #     request_data_sign = self.api_post_data_sign_gen(request_params, sign_key='84ee7ad1a1c0e67c02d7c79418e532a0')
    #     request_params['sign'] = request_data_sign
    #     response = requests.post(api_http_url, data=request_params)
    #     print response.text

    # 订单查询 API Example
    """
    请求方式：POST
    请求参数：
        参数名    | 是否必填 |   类型  |   描述
        app_id   |    是   |   int   | 商户应用AF_APPID
        open_id  |    是   |  string | 支付的发起人,用户在SDK 的数字标记
        sn       |    是   |  string | SDK 订单号
        sign     |    是   |  string | sdk 验证签名
        vorder_id|    是   |  string | 厂家订单号

    Response:
        {
            "code": 1,
            "msg": "订单待处理",
            "data": {
                "open_id":"b7qvattt01wkwc0g080cw0k0k",
                "vorder_id":"1492768478794",
                "sn":"170421175437312464423937",
                "app_id":null,
                "fee":"1.00",
                "body":"为刀塔传奇充值10水晶",
                "create_time":{
                    "date":"2017-04-21 17:54:37.000000",
                    "timezone_type":3,
                    "timezone":"Asia/Shanghai"
                }
            }
        }
    """
    def test_query_order_list(self):
        api_http_url = 'http://sdkv4.qcwan.com/api/tool/info/order'
        request_params = {
            'app_id': 778,
            'open_id': 'b7qvattt01wkwc0g080cw0k0k',
            'sn': '170421175437312464423937',
            'vorder_id': '1492768478794'
        }
        request_sign = self.api_post_data_sign_gen(request_params, sign_key='84ee7ad1a1c0e67c02d7c79418e532a0')
        request_params['sign'] = request_sign
        response = requests.post(api_http_url, data=request_params)
        print response.text


    # 接收发货通知
    # @app_controller.route('/msa/v4/notify_test', methods=['POST'])
    # def v4_notify_test():
    #     sign_key = "84ee7ad1a1c0e67c02d7c79418e532a0"
    #     data_str = ""
    #     request_data = request.form.copy()
    #     del request_data['sign']
    #     for key in sorted(request_data.keys()):
    #         k = urllib.quote_plus(key)
    #         v = urllib.quote_plus(str(request_data[key]))
    #         data_str += "%s=%s&" % (k, v)
    #     data_str += "sign_key=%s" % (sign_key,)
    #     m = hashlib.md5()
    #     m.update(data_str)
    #     gen_sign = m.hexdigest()
    #     print gen_sign
    #     print request.form['sign']
    #     if gen_sign == request.form['sign']:
    #         return response_ok()
    #     else:
    #         return response_exception()

if __name__ == '__main__':
    unittest.main()
