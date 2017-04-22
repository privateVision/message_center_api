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
            "uid": 11421096,
            "status": 0,
            "page": 1,
            "pagesize": 5,
            "sign": "3629ad398f35468e18a46adf66493606"
        }
        headers = {"Content-Type": "application/json"}
        data_json = json.dumps(data)
        r = requests.post("http://localhost:5000/msa/anfeng_helper/get_user_coupon", data=data_json, headers=headers)
        self.assertEqual(r.status_code, 200)
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
