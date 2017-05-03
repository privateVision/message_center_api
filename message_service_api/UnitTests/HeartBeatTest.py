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
            '_sign': 'a33e4b6089e6ed53d9d64fe70a1f023e',
            '_appid': 778,
            'interval': 2000,
            '_token': 'cwsv3fdnni0wwock4gg0gow4k'
        }
        # sdkv4test.qcwanwan.com
        r = requests.post('http://sdkv4test.qcwanwan.com/msa/v4/app/heartbeat', data=body_data)
        print r.text


if __name__ == '__main__':
    unittest.main()
