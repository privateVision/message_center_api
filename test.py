#coding:utf-8
import os,sys
from Crypto.Cipher import DES3
import base64
import urllib, urllib2
import json

APPID = 778
RID = 255
DESKEY = '4c6e0a99384aff934c6e0a99'
BASEURL = 'http://sdkapi.anfan.com/'

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
		print "\033[0mrequest:%s" % (uri)
		print "\033[0mrequest data:%s" % (json.dumps(data, indent=4, sort_keys=False, ensure_ascii=False))
		
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
				print "response data:%s" % (res)
				print "\033[1;31;40m返回值无法解析\033[0m"
			else:
				print "\033[0mresponse data:%s" % (json.dumps(data, indent=4, sort_keys=False, ensure_ascii=False))

				if data["code"] > 0:
					print "\033[1;31;40m%s(%d)\033[0m" % (data["msg"], data["code"])
				else:
					return data["data"]

		return None

	@staticmethod
	def __encrypt_param(data):
		data = urllib.urlencode(data)
		return Crypt3DES.encrypt(data);

print "------------ api/app/initialize ------------"
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

print "\n-----------api/account/loginToken-----------\n"
data = Http.request('api/account/loginToken', access_token = ACCESS_TOKEN, token = open('token').read());
if data == None:
	print "\033[1;31;40m自动登陆失败，程序退出 \033[0m"
	sys.exit(0)

open('token', 'w').write(data['token']);