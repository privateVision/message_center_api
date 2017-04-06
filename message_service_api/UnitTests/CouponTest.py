# _*_ coding: utf8 _*_
import json
import unittest

import requests
from mongoengine import Q, connect

from Utils.EncryptUtils import get_cms_md5_sign

"""
卡券功能测试
"""


class CouponFunctionTest(unittest.TestCase):
    def setUp(self):
        connect('users_message', host='localhost', port=27017)

    # def test_add_coupon(self):
    #     data = {
    #         "game": [{"apk_id": 783}],
    #         "users_type": "255,3",
    #         "vip_user": "3",
    #         "specify_user": "123,324",
    #         "id": 1,
    #         "title": "测试卡券",
    #         "stime": 123234533,
    #         "etime": 123432453,
    #         "is_first": 1,
    #         "info": "简单描述",
    #         "full": 1231223,
    #         "money": 41223,
    #         "method": "使用方法",
    #         "num": 11
    #     }
    #     headers = {"Content-Type": "application/json"}
    #     data_json = json.dumps(data)
    #     sign = get_cms_md5_sign(data_json)
    #     r = requests.post("http://dev.sdkapi.com/msa/v4/coupon?sign=%s" % (sign,), data=data_json, headers=headers)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text
    #
    # def test_update_coupon(self):
    #     data = {
    #         "game": [{"apk_id": 783}],
    #         "users_type": "255,3",
    #         "vip_user": "3",
    #         "specify_user": "123,324",
    #         "id": 1,
    #         "title": "测试卡券(更新)",
    #         "stime": 123234300,
    #         "etime": 123432333,
    #         "is_first": 1,
    #         "info": "简单描述（更新）",
    #         "full": 1231223,
    #         "money": 41223,
    #         "method": "使用方法（更新）",
    #         "num": 2
    #     }
    #     headers = {"Content-Type": "application/json"}
    #     data_json = json.dumps(data)
    #     sign = get_cms_md5_sign(data_json)
    #     r = requests.put("http://dev.sdkapi.com/msa/v4/coupon?sign=%s" % (sign,), data=data_json, headers=headers)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text
    #
    # def test_delete_coupon(self):
    #     data = {
    #         "id": 1
    #     }
    #     headers = {"Content-Type": "application/json"}
    #     data_json = json.dumps(data)
    #     sign = get_cms_md5_sign(data_json)
    #     r = requests.delete("http://dev.sdkapi.com/msa/v4/coupon?sign=%s" % (sign,), data=data_json, headers=headers)
    #     self.assertEqual(r.status_code, 200)
    #     print r.text

    def test_get_coupon_list(self):
        body_data = {
            '_sign': 'f1dece939a405839b62d22cd9166c17a',
            '_token': 'n9nfb13uwask8os0kwc4w0kw',
            '_appid': 778,
            'page': 1,
            'count': 10
        }
        r = requests.post('http://sdkv4test.qcwanwan.com/msa/v4/coupons', data=body_data)
        print r.text

    # def test_get_notice(self):
    #     from Service.UsersService import get_user_notice_from_mysql
    #     list = get_user_notice_from_mysql(username='223', rtype='3', vip='4', appid='2', cur_zone='湖北3区')
    #     print len(list)
    #     for item in list:
    #         print "%s-%s" % (item['type'], item['mysql_id'])

    def tearDown(self):
        pass


def suite():
    suite = unittest.TestSuite()
    suite.addTest(CouponFunctionTest("test_get_coupon_list"))
    return suite


if __name__ == "__main__":
    unittest.main(defaultTest='suite')

