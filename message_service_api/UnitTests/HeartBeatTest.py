# _*_ coding: utf8 _*_
import json
import threading
import unittest

import requests
import time


class HeartBeatTest(unittest.TestCase):

    def test_heart_beat_profiling(self):
        body_data = {
            '_sign': 'd0c982b8a953bb657d74d4274b6361ec',
            '_appid': 778,
            'interval': 2000,
            '_token': '5zkefoi6sgg80gkokwsgwck4o'
        }
        r = requests.post('http://localhost:5000/msa/v4/app/heartbeat', data=body_data)
        print r.text
        self.assertEqual(r.status_code, 200)

    # def test_heart_beat_ack(self):
    #     origin_param = 'token=fd9c69f1fd62f3070aafa5bc210f32ee&type=notice'
    #     from Utils.EncryptUtils import sdk_api_gen_key
    #     param = sdk_api_gen_key(778, origin_param)
    #     print "加密后的参数为：%s" % (param,)
    #     if param:
    #         body_data = {
    #             'appid': 778,
    #             'param': param
    #         }
    #         r = requests.post('http://localhost:5000/v4/app/heartbeat/ack', data=body_data)
    #         self.assertEqual(r.status_code, 200)
    #         print r.text
    #     else:
    #         print '加密失败'


if __name__ == '__main__':
    unittest.main()
