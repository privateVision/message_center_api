# _*_ coding: utf8 _*_
import json
import unittest
from urllib import urlencode

import requests
from mongoengine import Q, connect

"""
消息功能测试
"""


class MessageFunctionTest(unittest.TestCase):
    def setUp(self):
        connect('users_message', host='localhost', port=27017)

    # def test_add_message(self):
    #     data = {
    #         "ucid": 10010,
    #         "title":"测试消息",
    #         "description":"消息摘要",
    #         "content":"消息详细内容",
    #         "_sign": '7f9cbdef11241b2b121df9e736b30392'
    #     }
    #     r = requests.post("http://localhost:5000/msa/v4.2/account_message", data=data)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text

    # def test_delete_message(self):
    #     data = {
    #         "id": "2"
    #     }
    #     from Utils.EncryptUtils import get_md5_sign
    #     sign = get_md5_sign(data)
    #     r = requests.delete("http://localhost:5000/v4/message?sign=%s" % (sign,), data=data)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text

    # def test_get_message_list(self):
    #     body_data = {
    #         '_sign': '141aa24d6419b7ba2850bdcab13e95da',
    #         '_token': 'c31fc6b6b2f7b993713c124f8104209d',
    #         '_appid': 2
    #     }
    #     r = requests.post('http://localhost:5000/msa/v4/messages', data=body_data)
    #     print r.text

    # def test_set_message_readed(self):
    #     body_data = {
    #         '_sign': 'ed81ac86bc1fca205703c27d16d0d70e',
    #         '_token': 'bb427a702d53dbb0cdd4f001fb301620',
    #         'type': 'message',
    #         'message_id': 2,
    #         '_appid': 2
    #     }
    #     r = requests.post('http://dev.sdkapi.com/msa/v4/message/read', data=body_data)
    #     print r.text


    def test_set_message_readed(self):
        body_data = {
            '_sign': 'ed81ac86bc1fca205703c27d16d0d70e',
            '_token': 'bb427a702d53dbb0cdd4f001fb301620',
            'id_list': "[1, 2, 3]",
            '_appid': 2
        }
        r = requests.delete('http://localhost:5000/msa/v4.2/messages', data=body_data)
        print r.text

    def tearDown(self):
        pass


def suit_message_test():
    suite = unittest.TestSuite()
    suite.addTest(MessageFunctionTest("test_add_message"))
    return suite

if __name__ == '__main__':
    unittest.main(defaultTest='suit_message_test')
