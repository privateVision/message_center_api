# _*_ coding: utf8 _*_
import json
import unittest

import requests
from mongoengine import Q, connect

"""
卡券功能测试
"""


class CouponFunctionTest(unittest.TestCase):
    def setUp(self):
        connect('users_message', host='localhost', port=27017)

    # def test_add_coupon(self):
    #     from MongoModel.MessageModel import UsersMessage
    #     from MongoModel.UserMessageModel import UserMessage
    #     from MongoModel.UserReadMessageLogModel import UserReadMessageLog
    #     UsersMessage.objects(Q(type='coupon')).delete()
    #     UserMessage.objects(Q(type='coupon')).delete()
    #     UserReadMessageLog.objects(Q(type='coupon')).delete()
    #     data = {
    #         "game": "[{\"apk_id\": 783}]",
    #         "users_type": "255,3",
    #         "vip_user": "3,4",
    #         "specify_user": "123,324",
    #         "id": "1",
    #         "title": "测试卡券",
    #         "stime": "123234533",
    #         "etime": "123432453",
    #         "is_first": "1",
    #         "info": "简单描述",
    #         "full": "1231223",
    #         "money": "41223",
    #         "method": "使用方法",
    #         "num": "11"
    #     }
    #     from Utils.EncryptUtils import get_md5_sign
    #     sign = get_md5_sign(data)
    #     r = requests.post("http://localhost:5000/v4/coupon?sign=%s" % (sign,), data=data)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text

    # def test_update_coupon(self):
    #     data = {
    #         "game": "[{\"apk_id\": 783}]",
    #         "users_type": "255,3",
    #         "vip_user": "3,4",
    #         "specify_user": "123,324",
    #         "id": "1",
    #         "title": "测试卡券(更新)",
    #         "stime": "123234300",
    #         "etime": "123432333",
    #         "is_first": "1",
    #         "info": "简单描述（更新）",
    #         "full": "1231223",
    #         "money": "41223",
    #         "method": "使用方法（更新）",
    #         "num": "2"
    #     }
    #     from Utils.EncryptUtils import get_md5_sign
    #     sign = get_md5_sign(data)
    #     r = requests.put("http://localhost:5000/v4/coupon?sign=%s" % (sign,), data=data)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text

    # def test_delete_coupon(self):
    #     data = {
    #         "id": "1"
    #     }
    #     from Utils.EncryptUtils import get_md5_sign
    #     sign = get_md5_sign(data)
    #     r = requests.delete("http://localhost:5000/v4/coupon?sign=%s" % (sign,), data=data)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text

    def test_get_coupon_list(self):
        body_data = {
            '_sign': '141aa24d6419b7ba2850bdcab13e95da',
            '_token': 'c31fc6b6b2f7b993713c124f8104209d',
            '_appid': 2
        }
        r = requests.post('http://localhost:5000/msa/v4/coupons', data=body_data)
        print r.text

    def tearDown(self):
        pass


def suite():
    suite = unittest.TestSuite()
    suite.addTest(CouponFunctionTest("test_get_coupon_list"))
    return suite


if __name__ == "__main__":
    unittest.main(defaultTest='suite')

