# _*_ coding: utf8 _*_
import json
import threading
import unittest

import requests
import time

from Utils.EncryptUtils import get_cms_md5_sign


class HeartBeatTest(unittest.TestCase):

    # def test_set_refresh_data(self):
    #     body_data = {
    #         'refresh_interval': 61
    #     }
    #     headers = {"Content-Type": "application/json"}
    #     data_json = json.dumps(body_data)
    #     sign = get_cms_md5_sign(data_json)
    #     r = requests.post("http://localhost:5000/msa/v4/refresh_heart_beat_data_interval?sign=%s" % (sign,),
    #                       data=data_json, headers=headers)
    #     print r.text

    # _appid = 2 & _device_id = 4l
    # 8l
    # eddgemucg8k0koskc0gc8 & _rid = 1 & _sign_type = md5 & _timestamp = 1493031607 & _token = 4
    # agr9ryp43c4og4gw8k8g00oo & _type = json & key = ebe89a4c54f35e593d86455aab4343a8


    def test_heart_beat_profiling(self):
        body_data = {
            '_sign': '7d1f3d97d52f3958fe5098638ff0bdce',
            '_device_id': '4l8leddgemucg8k0koskc0gc8',
            '_appid': 2,
            '_timestamp': 1493031607,
            'interval': 2000,
            '_token': 'coa120l2zu0ok08s08k0k484s'
        }
        r = requests.post('http://sdkv4test.qcwanwan.com/msa/v4/app/heartbeat', data=body_data)
        print r.text
        self.assertEqual(r.status_code, 200)


if __name__ == '__main__':
    unittest.main()
