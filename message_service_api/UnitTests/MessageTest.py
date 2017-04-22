# _*_ coding: utf8 _*_
import json
import unittest

import requests
from mongoengine import Q, connect

from Utils.EncryptUtils import get_cms_md5_sign

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
    #         "game": [{"apk_id": 766, "zone_id_list":["S1帝国时代"]}],
    #         "users_type":"255,3",
    #         "vip_user":"3",
    #         "specify_user":"312,612",
    #         "id":2,
    #         "type": 2,
    #         "title":"测试消息",
    #         "description":"消息摘要",
    #         "content":"消息详细内容",
    #         "url":"http://baidu.com",
    #         "img":"http://img-url/1.jpg",
    #         "msg_type":"image_text",
    #         "send_time":1488426631,
    #         "create_time":223234533
    #     }
    #     headers = {"Content-Type": "application/json"}
    #     data_json = json.dumps(data)
    #     sign = get_cms_md5_sign(data_json)
    #     r = requests.post("http://dev.sdkapi.com/msa/v4/message?sign=%s" % (sign,), data=data_json, headers=headers)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text
    #
    # def test_delete_message(self):
    #     data = {
    #         "id": 2
    #     }
    #     headers = {"Content-Type": "application/json"}
    #     data_json = json.dumps(data)
    #     sign = get_cms_md5_sign(data_json)
    #     r = requests.delete("http://localhost:5000/v4/message?sign=%s" % (sign,), data=data_json, headers=headers)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text
    #
    # def test_get_message_list(self):
    #     body_data = {
    #         '_sign': '7004f02a9d4f466c79c9dace143541e7',
    #         '_token': 'a1bg7dhi95440sosoggk40ss8',
    #         '_appid': 778
    #     }
    #     r = requests.post('http://sdkv4test.qcwanwan.com/msa/v4/messages', data=body_data)
    #     print r.text

    def test_set_message_readed(self):
        body_data = {
            '_sign': '57a94a14cd75f480b506c35063f6e26a',
            '_token': '9jxmym7tin8kgcsg8c4w0scks',
            'type': 'message',
            'message_id': 6855,
            '_appid': 778
        }
        r = requests.post('http://sdkv4test.qcwanwan.com/msa/v4/message/read', data=body_data)
        print r.text

    def tearDown(self):
        pass


def suit_message_test():
    suite = unittest.TestSuite()
    suite.addTest(MessageFunctionTest("test_add_message"))
    return suite

if __name__ == '__main__':
    unittest.main(defaultTest='suit_message_test')
