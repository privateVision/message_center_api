# _*_ coding: utf8 _*_
import json
import unittest

import requests
from mongoengine import Q, connect

from Utils.EncryptUtils import get_cms_md5_sign

"""
公告功能测试
"""


class NoticeFunctionTest(unittest.TestCase):
    def setUp(self):
        connect('users_message', host='localhost', port=27017)

    # def test_add_notice(self):
    #     data = {
    #         "game":[{"apk_id": "all"}],
    #         "users_type":"255,3",
    #         "vip_user":"3",
    #         "specify_user":"",
    #         "id": 3,
    #         "title":"测试公告4",
    #         "content": "公告的详细内容",
    #         "type":"2",
    #         "stime":"2134122221",
    #         "etime":"4523113349",
    #         "create_time":"2314213324",
    #         "img":"http://img-url/1.jpg",
    #         "url":"http://baidu.com",
    #         "open_type":"1",
    #         "url_type":"go_finish",
    #         "button_content":"充值",
    #         "button_url":"zy://go_charge",
    #         "button_type": "go_charge",
    #         "sortby": "1"
    #     }
    #     headers = {"Content-Type": "application/json"}
    #     data_json = json.dumps(data)
    #     sign = get_cms_md5_sign(data_json)
    #     r = requests.post("http://localhost:5000/msa/v4/notice?sign=%s" % (sign,), data=data_json, headers=headers)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text
    #
    # def test_update_notice(self):
    #     data = {
    #         "game": [{"apk_id": "all"}],
    #         "users_type": "255,3",
    #         "vip_user": "3",
    #         "specify_user": "123,253",
    #         "id": "4",
    #         "title": "测试公告",
    #         "content": "公告的详细内容",
    #         "type": "2",
    #         "stime": "12324110",
    #         "etime": "12434211",
    #         "create_time": "2314213324",
    #         "img": "http://img-url/1.jpg",
    #         "url": "http://haocong.com",
    #         "open_type": "1",
    #         "url_type": "go_finish",
    #         "button_content": "充值",
    #         "button_url": "zy://go_charge",
    #         "button_type": "go_charge",
    #         "sortby": "1"
    #     }
    #     headers = {"Content-Type": "application/json"}
    #     data_json = json.dumps(data)
    #     sign = get_cms_md5_sign(data_json)
    #     r = requests.put("http://localhost:5000/v4/notice?sign=%s" % (sign,), data=data_json, headers=headers)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text
    #
    # def test_set_notice_close(self):
    #     data = {
    #         "id": 4
    #     }
    #     headers = {"Content-Type": "application/json"}
    #     data_json = json.dumps(data)
    #     sign = get_cms_md5_sign(data_json)
    #     r = requests.post("http://localhost:5000/v4/notice/close?sign=%s" % (sign,), data=data_json, headers=headers)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text
    #
    # def test_set_notice_open(self):
    #     data = {
    #         "id": 4
    #     }
    #     headers = {"Content-Type": "application/json"}
    #     data_json = json.dumps(data)
    #     sign = get_cms_md5_sign(data_json)
    #     r = requests.post("http://localhost:5000/v4/notice/open?sign=%s" % (sign,), data=data_json, headers=headers)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text

    # def test_add_notice(self):
    #     data = {
    #         "sort_data": [{'id': 1, 'sortby': 1}, {'id': 2, 'sortby': 2}]
    #     }
    #     headers = {"Content-Type": "application/json"}
    #     data_json = json.dumps(data)
    #     sign = get_cms_md5_sign(data_json)
    #     r = requests.post("http://localhost:5000/msa/v4/notice/sort?sign=%s" % (sign,), data=data_json, headers=headers)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text

    def test_get_notice_list(self):
        body_data = {
            '_sign': '7004f02a9d4f466c79c9dace143541e7',
            '_token': 'a1bg7dhi95440sosoggk40ss8',
            '_appid': 778,
        }
        r = requests.post('http://sdkv4test.qcwanwan.com/msa/v4/notices', data=body_data)
        print r.text

    def tearDown(self):
        pass


def suit_update_notice_test():
    suite = unittest.TestSuite()
    suite.addTest(NoticeFunctionTest("test_update_notice"))
    return suite


if __name__ == '__main__':
    unittest.main(defaultTest='suit_update_notice_test')
