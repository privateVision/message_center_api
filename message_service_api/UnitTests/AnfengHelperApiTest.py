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

    def test_get_coupon(self):
        data = {
            "ucid": 2993,
            "sign": "hyqenchweqewwe"
        }
        headers = {"Content-Type": "application/json"}
        data_json = json.dumps(data)
        r = requests.post("http://localhost:5000/msa/anfeng_helper/get_user_coupon", data=data_json, headers=headers)
        self.assertEqual(r.status_code, 200)
        print r.text

    def tearDown(self):
        pass


def suit_message_test():
    suite = unittest.TestSuite()
    suite.addTest(AnfengHelperApiTest("test_get_coupon"))
    return suite

if __name__ == '__main__':
    unittest.main(defaultTest='suit_anfeng_helper_test')
