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
    #         "_token": '5geewyhxot8ggsgkscww4k0c8',
    #         "status": 0,
    #         "page": 1,
    #         "pagesize": 5,
    #         "_sign": "318072f312a4b2085e115794e52d4ebd"
    #     }
    #     # headers = {"Content-Type": "application/json"}
    #     # data_json = json.dumps(data)
    #     r = requests.post("http://localhost:5000/msa/anfeng_helper/get_user_coupon", data=data)
    #     print r.text

    def test_add_coupon(self):
        data = {
            "_token": '1vs54uvt3b8k884k0wogs0k88',
            "coupon_id": 1472,
            "_sign": "f18ad37f62becde929baccf3ed6db312"
        }
        # headers = {"Content-Type": "application/json"}
        # data_json = json.dumps(data)
        r = requests.post("http://localhost:5000/msa/anfeng_helper/coupon", data=data)
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

    def tearDown(self):
        pass


def suit_message_test():
    suite = unittest.TestSuite()
    suite.addTest(AnfengHelperApiTest("test_get_coupon"))
    return suite

if __name__ == '__main__':
    unittest.main(defaultTest='suit_anfeng_helper_test')
