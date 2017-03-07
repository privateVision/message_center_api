# _*_ coding: utf8 _*_
import json
import threading
import unittest

import requests
import time


class HeartBeatTest(unittest.TestCase):

    def test_heart_beat_profiling(self):
        # origin_param = 'access_token=fd9c69f1fd62f3070aafa5bc210f32ee'
        # from Utils.EncryptUtils import sdk_api_gen_key
        # param = sdk_api_gen_key(778, origin_param)
        # print "加密后的参数为：%s" % (param,)
        # if param:
        #     body_data = {
        #         'appid': 778,
        #         'param': param
        #     }
        #     start_time = time.time()
        #     r = requests.post('http://localhost:5000/v4/app/heartbeat', data=body_data)
        #     self.assertEqual(r.status_code, 200)
        #     end_time = time.time()
        #     print end_time-start_time
        # else:
        #     print '加密失败'
        r = requests.get('http://localhost:5000/v4/app/heartbeat/253')
        print r.text
        self.assertEqual(r.status_code, 200)

    # def test_heart_beat_ack(self):
    #     origin_param = 'access_token=fd9c69f1fd62f3070aafa5bc210f32ee&type=notice'
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
