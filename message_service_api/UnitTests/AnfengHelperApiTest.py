# _*_ coding: utf8 _*_
import json
import unittest

import requests
from mongoengine import Q, connect

from Utils.EncryptUtils import get_cms_md5_sign

"""
安锋助手api单元测试
"""


class AnfengHelperApiTest(unittest.TestCase):
    def setUp(self):
        pass

    # def test_get_coupon(self):
    #     data = {
    #         "_token": '21a1bb65drk048ckcg8osccsk',
    #         "status": 1,
    #         "page": 1,
    #         "pagesize": 10,
    #         "_appid": 6,
    #         "_rid": 1,
    #         "_sign": "03b09812487467069d4fb5a74118d541"
    #     }
    #     # headers = {"Content-Type": "application/json"}
    #     # data_json = json.dumps(data)
    #     r = requests.post("http://sdkv4test.qcwanwan.com/msa/anfeng_helper/get_user_coupon", data=data)
    #     print r.text

    # def test_add_coupon(self):
    #     data = {
    #         "_token": '1vs54uvt3b8k884k0wogs0k88',
    #         "coupon_id": 1472,
    #         "_sign": "f18ad37f62becde929baccf3ed6db312"
    #     }
    #     # headers = {"Content-Type": "application/json"}
    #     # data_json = json.dumps(data)
    #     r = requests.post("http://localhost:5000/msa/anfeng_helper/coupon", data=data)
    #     print r.text

    # def test_get_all_gift(self):
    #     data = {
    #         "_sign": "7605aa3d5ee3768dc10b6bdb59ee6f00"
    #     }
    #     # headers = {"Content-Type": "application/json"}
    #     # data_json = json.dumps(data)
    #     r = requests.post("http://localhost:5000/msa/anfeng_helper/gifts", data=data)
    #     print r.text

    def test_tao_gift(self):
        data = {
            "_token": "19p4kqw0cgskkswgw44gcckwo",
            "_device_id": "asdfasgasgfgaf",
            "gift_id": 128,
            "_sign": "d73137f62a6a6958aa18add2cef8674a"
        }
        r = requests.post("http://sdkv4test.qcwanwan.com/msa/anfeng_helper/tao_gift", data=data)
        print r.text

    # def test_get_uses_gifts_list(self):
    #     data = {
    #         "ucid": 2993,
    #         "page": 1,
    #         "count": 10,
    #         "sign": "41ba43912bf98cbc752763ec584d6230"
    #     }
    #     headers = {"Content-Type": "application/json"}
    #     data_json = json.dumps(data)
    #     r = requests.post("http://localhost:5000/msa/anfeng_helper/get_user_gifts", data=data_json, headers=headers)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text
    #
    # def test_is_gift_get(self):
    #     data = {
    #         "ucid": 2993,
    #         "gift_id": 1,
    #         "sign": "41ba43912bf98cbc752763ec584d6230"
    #     }
    #     headers = {"Content-Type": "application/json"}
    #     data_json = json.dumps(data)
    #     r = requests.post("http://localhost:5000/msa/anfeng_helper/is_gift_get", data=data_json, headers=headers)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text

    # def test_gifts_real_time_count(self):
    #     data = {
    #         "gift_ids": '128|129',
    #         "_sign": "753628f844a137b8ea05da198b55bb29"
    #     }
    #     # sdkv4test.qcwanwan.com
    #     r = requests.post("http://sdkv4test.qcwanwan.com/msa/anfeng_helper/gifts_real_time_count", data=data)
    #     print r.text


    # def test_lingqu_coupon(self):
    #     data = {
    #         "_appid": 6,
    #         "_rid": 1,
    #         "_sign_type": "md5",
    #         "_timestamp": 1493349963,
    #         "_token": '',
    #         "_type": "json",
    #         "channel": 4,
    #         "coupon_id": 1460,
    #         "notify_url": "http://appi.anfeng.cn/api/coupon/notify",
    #         "order_id": 400057207,
    #         "ucid": 1573396,
    #         "_sign": "0787cebdd2755e07a18db401d8ea797b"
    #     }
    #     # sdkv4test.qcwanwan.com
    #     r = requests.post("http://localhost:5000/msa/anfeng_helper/coupon", data=data)
    #     print r.text

    def tearDown(self):
        pass


def suit_message_test():
    suite = unittest.TestSuite()
    suite.addTest(AnfengHelperApiTest("test_get_coupon"))
    return suite

if __name__ == '__main__':
    unittest.main(defaultTest='suit_anfeng_helper_test')
