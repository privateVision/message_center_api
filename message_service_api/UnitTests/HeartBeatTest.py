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
    #         'refresh_interval': 60
    #     }
    #     headers = {"Content-Type": "application/json"}
    #     data_json = json.dumps(body_data)
    #     sign = get_cms_md5_sign(data_json)
    #     r = requests.post("http://localhost:5000/msa/v4/refresh_heart_beat_data_interval?sign=%s" % (sign,),
    #                       data=data_json, headers=headers)
    #     print r.text

    def test_heart_beat_profiling(self):
        body_data = {
            '_sign': '74b9db5b7e639163e2ecc728591f1680',
            '_appid': 778,
            'interval': 2000,
            # 'num': 5,
            '_token': 'bc218n1t9b4k408c0g0s8o004'
        }
        # sdkv4test.qcwanwan.coms
        r = requests.post('http://127.0.0.1:8888/msa/v4/app/heartbeat', data=body_data)
        print r.texts


if __name__ == '__main__':
    unittest.main()
