# _*_ coding: utf8 _*_
import json
import threading
import unittest

import requests
import time

from Utils.EncryptUtils import get_cms_md5_sign


class GiftTest(unittest.TestCase):

    # def test_get_gifts(self):
    #     body_data = {
    #         '_sign': 'b87430233acff183fdec47c8fb519add',
    #         'platform_id': 2,
    #         '_appid': 778,
    #         'page': 1,
    #         'count': 5,
    #         '_token': '4v42e1pwh5escckokkswskocs'
    #     }
    #     # sdkv4test.qcwanwan.com
    #     r = requests.post('http://sdkv4test.qcwanwan.com/msa/v4/gifts', data=body_data)
    #     print r.text
    #     self.assertEqual(r.status_code, 200)


    def test_lingqu_gifts(self):
        body_data = {
            '_sign': '4dd0e2d7f2740f1c437e7c20c1050459',
            'platform_id': 4,
            '_device_id': '2114234dsfvdsg',
            'gift_id': 134,
            '_appid': 778,
            '_token': '4v42e1pwh5escckokkswskocs'
        }
        # sdkv4test.qcwanwan.com
        r = requests.post('http://sdkv4test.qcwanwan.com/msa/v4/get_gift', data=body_data)
        print r.text


if __name__ == '__main__':
    unittest.main()
