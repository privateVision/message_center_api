# _*_ coding: utf8 _*_
import json
import unittest

import requests
from mongoengine import Q, connect

"""
消息功能测试
"""


class MessageFunctionTest(unittest.TestCase):
    def setUp(self):
        connect('users_message', host='localhost', port=27017)

    # def test_add_message(self):
    #     from MongoModel.MessageModel import UsersMessage
    #     from MongoModel.UserMessageModel import UserMessage
    #     from MongoModel.UserReadMessageLogModel import UserReadMessageLog
    #     UsersMessage.objects(Q(type='message')).delete()
    #     UserMessage.objects(Q(type='message')).delete()
    #     UserReadMessageLog.objects(Q(type='message')).delete()
    #     data = {
    #         "game": "[{\"apk_id\": 766, \"zone_id_list\":[\"S1帝国时代\"]}]",
    #         "users_type":"255,3",
    #         "vip_user":"3,4",
    #         "specify_user":"312,612",
    #         "id":"2",
    #         "type": "2",
    #         "title":"测试消息",
    #         "description":"消息摘要",
    #         "content":"消息详细内容",
    #         "url":"http://baidu.com",
    #         "img":"http://img-url/1.jpg",
    #         "msg_type":"image_text",
    #         "send_time":"1488426631",
    #         "create_time":"223234533"
    #     }
    #     from Utils.EncryptUtils import get_md5_sign
    #     sign = get_md5_sign(data)
    #     r = requests.post("http://dev.sdkapi.com/msa/v4/message?sign=%s" % (sign,), data=data)
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

    def test_get_message_list(self):
        body_data = {
            '_sign': 'd69bfcae81537a571bd84163696e1bb2',
            '_token': 'bb427a702d53dbb0cdd4f001fb301620',
            '_appid': 2
        }
        r = requests.post('http://dev.sdkapi.com/msa/v4/messages', data=body_data)
        print r.text

    def tearDown(self):
        pass


def suit_message_test():
    suite = unittest.TestSuite()
    suite.addTest(MessageFunctionTest("test_add_message"))
    return suite

if __name__ == '__main__':
    unittest.main(defaultTest='suit_message_test')
