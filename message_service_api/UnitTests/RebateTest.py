# _*_ coding: utf8 _*_
import json
import unittest

import requests
from mongoengine import Q, connect

"""
优惠券功能测试
"""


class RebateFunctionTest(unittest.TestCase):
    def setUp(self):
        connect('users_message', host='localhost', port=27017)

    def test_add_rebate(self):
        from MongoModel.MessageModel import UsersMessage
        from MongoModel.UserMessageModel import UserMessage
        from MongoModel.UserReadMessageLogModel import UserReadMessageLog
        UsersMessage.objects(Q(type='rebate')).delete()
        UserMessage.objects(Q(type='rebate')).delete()
        UserReadMessageLog.objects(Q(type='rebate')).delete()
        data = {
            "users_type":"255,3",
            "vip_user":"3,4,5",
            "specify_user":"123,452",
            "id":"1",
            "title":"测试优惠券",
            "content":"优惠券详细内容",
            "stime":"12313214",
            "etime":"14123423",
            "rules":"[{\"id\":1, \"recharge_id\": 1, \"full\": 200, \"dis\": 2, \"vip\": 3}]"
        }
        from Utils.EncryptUtils import get_md5_sign
        sign = get_md5_sign(data)
        r = requests.post("http://localhost/v4/rebate?sign=%s" % (sign,), data=data)
        self.assertEqual(r.status_code, 200)
        print r.text

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

    # def test_get_coupon_list(self):
    #     origin_param = 'access_token=fd9c69f1fd62f3070aafa5bc210f32ee&page=1&count=5'
    #     from Utils.EncryptUtils import sdk_api_gen_key
    #     param = sdk_api_gen_key(778, origin_param)
    #     print "加密后的参数为：%s" % (param,)
    #     if param:
    #         body_data = {
    #             'appid': 778,
    #             'param': param
    #         }
    #         r = requests.post('http://127.0.0.1/v4/coupons', data=body_data)
    #         print r.text
    #     else:
    #         print '加密失败'

    def tearDown(self):
        pass


def suite():
    suite = unittest.TestSuite()
    suite.addTest(RebateFunctionTest("test_add_rebate"))
    return suite


if __name__ == "__main__":
    unittest.main(defaultTest='suite')

