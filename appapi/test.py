#coding:utf8
import os,sys
from Crypto.Cipher import DES3
import md5
import base64
import urllib, urllib2
import json
import random
import string

APPID = 778
RID = 255
DESKEY = '4c6e0a99384aff934c6e0a99'
BASEURL = 'http://sdkapi.anfan.com/'
#BASEURL = 'http://uapitest.qcwan.com/'

class Crypt3DES:
	@staticmethod
	def __pkcs5_pad(str):
		s = DES3.block_size
		return str + (s - len(str) % s) * chr(s - len(str) % s)

	@staticmethod
	def __pkcs5_unpad(str):
		return str[0:-ord(str[-1])]

	@staticmethod
	def encrypt(data):
		des3 = DES3.new(DESKEY, DES3.MODE_ECB)
		str = des3.encrypt(Crypt3DES.__pkcs5_pad(data))
		return base64.b64encode(str)
	
	@staticmethod
	def decrypt(data):
		data = base64.b64decode(data)
		des3 = DES3.new(DESKEY, DES3.MODE_ECB)
		str = des3.decrypt(data);
		return Crypt3DES.__pkcs5_unpad(str);

class Http:
	@staticmethod
	def request(uri, **data):
		print "\033[0murl:%s\n" % (BASEURL + uri)
		print "\033[0mrequest:%s\n" % (json.dumps(data, indent=4, sort_keys=False, ensure_ascii=False))
		
		postdata = {
			"param": Http.__encrypt_param(data), 
			"appid": APPID
		}
		
		try:
			request = urllib2.Request(BASEURL + uri, urllib.urlencode(postdata))
			response = urllib2.urlopen(request)
		except Exception, e:
			print "\033[1;31;40m%d,%s\033[0m" % (e.code, e.msg)
		else:
			res = response.read()
			#print "response data:%s" % (data)
			try:
				data = json.loads(res)
			except Exception, e:
				print "response:%s" % (res)
				print "\033[1;31;40m返回值无法解析\033[0m"
			else:
				print "\033[0mresponse:%s" % (json.dumps(data, indent=4, sort_keys=False, ensure_ascii=False))

				if data["code"] != 1:
					print "\033[1;31;40m%s(%d)\033[0m" % (data["msg"], data["code"])
				else:
					return data["data"]

		return None

	@staticmethod
	def __encrypt_param(data):
		data = urllib.urlencode(data)
		return Crypt3DES.encrypt(data);

####################################################################################################################### 初始化
print "\n------------ api/app/initialize ------------\n"
data = Http.request('api/app/initialize', 
	imei = '3447264d06ff60e9cc415229a0583a29', 
	rid = RID, 
	device_code = '3447264d06ff60e9cc415229a0583a29', 
	device_name = 'iphone 6plus', 
	device_platform = 16, 
	version = '1.0.0', 
	app_version = '1.1.0'
)
if data == None:
	print "\033[1;31;40m获取token失败，程序退出 \033[0m"
	sys.exit(0)

ACCESS_TOKEN = data['access_token']
####################################################################################################################### 自动登陆
"""
print "\n-----------api/account/login_token-----------\n"
data = Http.request('api/account/login_token', access_token = ACCESS_TOKEN, token = open('.token').read());
if data == None:
	print "\033[1;31;40m自动登陆失败，程序退出 \033[0m"
	sys.exit(0)

open('.token', 'w').write(data['token']);
"""
####################################################################################################################### 生成用户名
"""
print "\n---------- api/account/username ----------\n"
data = Http.request('api/account/username', access_token = ACCESS_TOKEN)

print "\n---------- api/account/register ----------\n"
if data != None:
	Http.request('api/account/register', access_token = ACCESS_TOKEN, username = data['username'], password = 123456)
"""
####################################################################################################################### 用户名或手机号码登陆
"""
print "\n-------------- api/account/login --------------\n"
Http.request('api/account/login', access_token = ACCESS_TOKEN, username = 'a81922755', password = 123456);
"""
####################################################################################################################### 云片偷偷发短信回调

print "\n--------------- yunpian/callback ---------------\n"
data = {
	"base_extend": "8888",
	"extend": "01",
	"id": "2a70c6bb4f2845da816ea1bfe5732747",
	"mobile": "123" + str(random.random())[2:10],
	"reply_time": "2014-03-17 22:55:21",
	"text": ACCESS_TOKEN,
}

s = ''
key = ['base_extend', 'extend', 'id', 'mobile', 'reply_time', 'text']
for k in ['base_extend', 'extend', 'id', 'mobile', 'reply_time', 'text']:
	s = s + data[k] + ','
s = s + '0dbc5a50c034a8396b50f3a80609497d'
m = md5.new()
m.update(s)
data['_sign'] = m.hexdigest()
req = urllib2.Request(BASEURL + 'yunpian/callback', urllib.urlencode({"sms_reply": json.dumps(data)}))
print urllib2.urlopen(req).read()

print "\n------------ api/account/login_phone ------------\n"
Http.request('api/account/login_phone', access_token = ACCESS_TOKEN)

####################################################################################################################### 创建订单

print "\n-------------- api/pay/order/new ---------------\n"
data = Http.request('api/pay/order/new', access_token = ACCESS_TOKEN, fee = 10, body = '10 Gold', subject = '10 Gold', notify_url = 'http://www.baidu.com/', vorderid = str(random.random())[2:])
if data == None:
	sys.exit(0)

ORDER_ID = data['order_id'];

####################################################################################################################### 创建平台币订单
"""
print "\n-------------- api/pay/order/anfeng/new ---------------\n"
data = Http.request('api/pay/order/anfeng/new', access_token = ACCESS_TOKEN, fee = 10, body = '10 Gold', subject = '10 Gold')
if data == None:
	sys.exit(0)

ORDER_ID = data['order_id'];
"""
####################################################################################################################### 微信支付

print "\n------------ api/pay/nowpay/wechat -------------\n"
Http.request('api/pay/nowpay/wechat', access_token = ACCESS_TOKEN, order_id = ORDER_ID, balance = 0);

####################################################################################################################### 支付宝支付
"""
print "\n------------ api/pay/nowpay/alipay -------------\n"
Http.request('api/pay/nowpay/alipay', access_token = ACCESS_TOKEN, order_id = ORDER_ID, balance = 1);
"""
####################################################################################################################### 银联支付
"""
print "\n----------- api/pay/nowpay/unionpay ------------\n"
Http.request('api/pay/nowpay/unionpay', access_token = ACCESS_TOKEN, order_id = ORDER_ID, balance = 1);
"""
####################################################################################################################### 用户退出

print "\n-------------- api/account/logout --------------\n"
Http.request('api/account/logout', access_token = ACCESS_TOKEN);

#######################################################################################################################
