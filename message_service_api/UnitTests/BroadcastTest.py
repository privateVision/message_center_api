# _*_ coding: utf8 _*_
import json
import unittest

import requests
import time
from mongoengine import Q, connect

"""
广播功能测试
"""


class BroadcastFunctionTest(unittest.TestCase):
    def setUp(self):
        connect('users_message', host='localhost', port=27017)

    # def test_add_broadcast(self):
    #     from MongoModel.MessageModel import UsersMessage
    #     from MongoModel.UserMessageModel import UserMessage
    #     from MongoModel.UserReadMessageLogModel import UserReadMessageLog
    #     UsersMessage.objects(Q(type='broadcast') & Q(mysql_id=1)).delete()
    #     UserMessage.objects(Q(type='broadcast') & Q(mysql_id=1)).delete()
    #     UserReadMessageLog.objects(Q(type='broadcast') & Q(message_id=1)).delete()
    #     data = {
    #         "game":"[{\"apk_id\": 760, \"zone_id_list\":[\"阿狸一区\"]}]",
    #         "users_type":"255,3,0",
    #         "vip_user":"3,4,5",
    #         "specify_user":"541,654",
    #         "id":"1",
    #         "title":"测试广播",
    #         "stime": "%s" % (int(time.time()),),
    #         "close_time":"3",
    #         "content":"广播内容",
    #         "create_time":"311231232"
    #     }
    #     from Utils.EncryptUtils import get_md5_sign
    #     sign = get_md5_sign(data)
    #     r = requests.post("http://dev.sdkapi.com/msa/v4/broadcast?sign=%s" % (sign,), data=data)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text

    # def test_update_broadcast(self):
    #     data = {
    #         "game": "[{\"apk_id\": 760, \"zone_id_list\":[\"阿狸一区\"]}]",
    #         "users_type": "255,3,0",
    #         "vip_user": "3,4,5",
    #         "specify_user": "541,654",
    #         "id": "1",
    #         "title": "测试广播（更新）",
    #         "stime": "1488424000",
    #         "close_time": "2",
    #         "content": "广播内容（更新）",
    #         "create_time": "311231232"
    #     }
    #     from Utils.EncryptUtils import get_md5_sign
    #     sign = get_md5_sign(data)
    #     r = requests.put("http://localhost:5000/v4/broadcast?sign=%s" % (sign,), data=data)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text

    # def test_delete_broadcast(self):
    #     data = {
    #         "id": "1"
    #     }
    #     from Utils.EncryptUtils import get_md5_sign
    #     sign = get_md5_sign(data)
    #     r = requests.delete("http://localhost/v4/broadcast?sign=%s" % (sign,), data=data)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text


    def test_get_broadcast_list(self):
        body_data = {
            'username': 'n88530190',
            '_sign': '9bf6db5d54faa4293e4d9eb35d8e41cc',
            '_type': 'json',
            '_timestamp': 1489566717,
            '_rid': 255,
            '_sign_type': 'md5',
            '_appid': 2,
            'rid': 255,
            'password': 123456
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
