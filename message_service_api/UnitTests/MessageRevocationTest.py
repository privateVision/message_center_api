# _*_ coding: utf8 _*_
import json
import unittest

import requests
from mongoengine import Q, connect

from Utils.EncryptUtils import get_cms_md5_sign

"""
消息撤回功能测试
"""


class MessageRevocationFunctionTest(unittest.TestCase):
    def setUp(self):
        connect('users_message', host='localhost', port=27017)

    def test_message_revocation(self):
        data = {
            "type": "notice",
            "mysql_id": 1
        }
        headers = {"Content-Type": "application/json"}
        data_json = json.dumps(data)
        sign = get_cms_md5_sign(data_json)
        r = requests.post("http://localhost:5000/msa/v4/app/message_revocation?sign=%s" % (sign,),
                          data=data_json, headers=headers)
        self.assertEqual(r.status_code, 200)
        print r.text

    def tearDown(self):
        pass


def suit_message_revocation_test():
    suite = unittest.TestSuite()
    suite.addTest(MessageRevocationFunctionTest("test_message_revocation"))
    return suite


if __name__ == '__main__':
    unittest.main(defaultTest='suit_message_revocation_test')
