# _*_ coding: utf8 _*_
import json
import unittest

import requests
from mongoengine import Q, connect

"""
公告功能测试
"""


class NoticeFunctionTest(unittest.TestCase):
    def setUp(self):
        connect('users_message', host='localhost', port=27017)

    # def test_add_notice(self):
    #     from MongoModel.MessageModel import UsersMessage
    #     from MongoModel.UserMessageModel import UserMessage
    #     from MongoModel.UserReadMessageLogModel import UserReadMessageLog
    #     UsersMessage.objects(Q(type='notice') & Q(mysql_id=4)).delete()
    #     UserMessage.objects(Q(type='notice') & Q(mysql_id=4)).delete()
    #     UserReadMessageLog.objects(Q(type='notice') & Q(message_id=4)).delete()
    #     data = {
    #         "game":"[{\"apk_id\": \"all\"}]",
    #         "users_type":"255,3",
    #         "vip_user":"3",
    #         "specify_user":"",
    #         "id": "3",
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
    #     from Utils.EncryptUtils import get_md5_sign
    #     sign = get_md5_sign(data)
    #     r = requests.post("http://localhost:5000/msa/v4/notice?sign=%s" % (sign,), data=data)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text

    # def test_update_notice(self):
    #     data = {
    #         "game": "[{\"apk_id\": \"all\"}]",
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
    #     from Utils.EncryptUtils import get_md5_sign
    #     sign = get_md5_sign(data)
    #     r = requests.put("http://localhost:5000/v4/notice?sign=%s" % (sign,), data=data)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text

    # def test_set_notice_close(self):
    #     data = {
    #         "id": "4"
    #     }
    #     from Utils.EncryptUtils import get_md5_sign
    #     sign = get_md5_sign(data)
    #     r = requests.post("http://localhost:5000/v4/notice/close?sign=%s" % (sign,), data=data)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text

    # def test_set_notice_open(self):
    #     data = {
    #         "id": "4"
    #     }
    #     from Utils.EncryptUtils import get_md5_sign
    #     sign = get_md5_sign(data)
    #     r = requests.post("http://localhost:5000/v4/notice/open?sign=%s" % (sign,), data=data)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text

    def test_get_notice_list(self):
        body_data = {
            "_appid": 1525,
            "_device_id": "cgrzjjjyc6oso08kw0g480ws0",
            "_imei": "A9AE2C87-5246-4B86-85E7-2642A6209A7D",
            "_os": 1,
            "_rid": 1,
            "_sign": "72940fa88c88d4c87e763ccf17332e9d",
            "_sign_type": "md5",
            "_timestamp": 1495879256,
            "_token": "d3g20bbdkhwksw8okkwogoc40",
            "_type": "json"
        }
        r = requests.post('https://sdkv4.qcwan.com/msa/v4/notices', data=body_data)
        print r.text

    def tearDown(self):
        pass


def suit_update_notice_test():
    suite = unittest.TestSuite()
    suite.addTest(NoticeFunctionTest("test_update_notice"))
    return suite


if __name__ == '__main__':
    unittest.main(defaultTest='suit_update_notice_test')
