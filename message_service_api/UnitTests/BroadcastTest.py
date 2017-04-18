# _*_ coding: utf8 _*_
import json
import unittest

import requests
import time
from mongoengine import Q, connect

from Utils.EncryptUtils import get_cms_md5_sign

"""
广播功能测试
"""


class BroadcastFunctionTest(unittest.TestCase):
    def setUp(self):
        connect('users_message', host='localhost', port=27017)

    def test_add_broadcast(self):
        data = {
            "game": [{"apk_id": "760", "zone_id_list": ["阿狸一区"]}],
            "users_type": "2",
            "vip_user": "0",
            "specify_user": "",
            "id": 1,
            "title": "测试广播",
            "stime": "%s" % (int(time.time()),),
            "close_time": 3,
            "content": "广播内容",
            "create_time": 1490846400
        }
        headers = {"Content-Type": "application/json"}
        data_json = json.dumps(data)
        sign = get_cms_md5_sign(data_json)
        r = requests.post("http://dev.sdkapi.com/msa/v4/add_broadcast?sign=%s" % (sign,), data=data_json,
                          headers=headers)
        self.assertEqual(r.status_code, 200)
        print r.text

    def test_update_broadcast(self):
        data = {
            "game": [{"apk_id": 760, "zone_id_list": ["阿狸一区"]}],
            "users_type": "255,3,0",
            "vip_user": "3",
            "specify_user": "541,654",
            "id": 1,
            "title": "测试广播（更新）",
            "stime": 1488424000,
            "close_time": 2,
            "content": "广播内容（更新）",
            "create_time": 311231232
        }
        headers = {"Content-Type": "application/json"}
        data_json = json.dumps(data)
        sign = get_cms_md5_sign(data_json)
        r = requests.put("http://dev.sdkapi.com/msa/v4/broadcast?sign=%s" % (sign,), data=data_json, headers=headers)
        self.assertEqual(r.status_code, 200)
        print r.text

    def test_delete_broadcast(self):
        data = {
            "id": 1
        }
        headers = {"Content-Type": "application/json"}
        data_json = json.dumps(data)
        sign = get_cms_md5_sign(data_json)
        r = requests.delete("http://dev.sdkapi.com/msa/v4/broadcast?sign=%s" % (sign,), data=data_json, headers=headers)
        self.assertEqual(r.status_code, 200)
        print r.text

    def test_get_broadcast_list(self):
        body_data = {
            '_sign': 'd69bfcae81537a571bd84163696e1bb2',
            '_token': 'bb427a702d53dbb0cdd4f001fb301620',
            '_appid': 2,
        }
        r = requests.post('http://dev.sdkapi.com/msa/v4/broadcasts', data=body_data)
        print r.text

    def tearDown(self):
        pass


def suit_broadcast_test():
    suite = unittest.TestSuite()
    suite.addTest(BroadcastFunctionTest("test_update_broadcast"))
    return suite


if __name__ == '__main__':
    unittest.main(defaultTest='suit_broadcast_test')
