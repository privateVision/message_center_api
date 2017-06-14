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
    #         "title":"223123",
    #         "description":"哎呀4.2",
    #         "content":"呵呵呵呵呵",
    #         "_sign": '8a5b4898853549be53cf2065a8d74ff7'
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


    # def test_delete_message_list(self):
    #     body_data = {
    #         '_sign': 'ed81ac86bc1fca205703c27d16d0d70e',
    #         '_token': '4da803965a27ce16aba27c597819b17c',
    #         'id_list': "[1497403898, 1497404654]",
    #         '_appid': 2
    #     }
    #     r = requests.delete('http://localhost:5000/msa/v4.2/messages', data=body_data)
    #     print r.text

    # def test_delete_all_message(self):
    #     body_data = {
    #         '_sign': 'ed81ac86bc1fca205703c27d16d0d70e',
    #         '_token': '4da803965a27ce16aba27c597819b17c',
    #         'delete_all': 1,
    #         '_appid': 2
    #     }
    #     r = requests.delete('http://localhost:5000/msa/v4.2/messages', data=body_data)
    #     print r.text

    # def test_set_message_list_readed(self):
    #     body_data = {
    #         '_sign': 'ed81ac86bc1fca205703c27d16d0d70e',
    #         '_token': '4da803965a27ce16aba27c597819b17c',
    #         'id_list': "[1497403898]",
    #         '_appid': 2
    #     }
    #     r = requests.post('http://localhost:5000/msa/v4.2/message/read', data=body_data)
    #     print r.text

    def test_set_all_message_readed(self):
        body_data = {
            '_sign': 'ed81ac86bc1fca205703c27d16d0d70e',
            '_token': '4da803965a27ce16aba27c597819b17c',
            'read_all': 1,
            '_appid': 2
        }
        r = requests.post('http://localhost:5000/msa/v4.2/message/read', data=body_data)
        print r.text

    def tearDown(self):
        pass


def suit_message_test():
    suite = unittest.TestSuite()
    suite.addTest(MessageFunctionTest("test_add_message"))
    return suite

if __name__ == '__main__':
    unittest.main(defaultTest='suit_message_test')
