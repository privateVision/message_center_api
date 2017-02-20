#coding:utf-8
import os,sys
from Crypto.Cipher import DES3
import base64
import urllib, urllib2
import json

APPID = 778
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
			data = response.read()
			print "response data:%s" % (data)
			data = json.loads(data)

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
data = Http.request('api/app/initialize', imei = '3447264d06ff60e9cc415229a0583a29', retailer = 255, device_code = '3447264d06ff60e9cc415229a0583a29', device_name = 'iphone 6plus', device_platform = 16, version = '1.0.0')
if data == None or not data.has_key('token'):
	print "\033[1;31;40m获取token失败，程序退出 \033[0m"
	sys.exit(0)
	
TOKEN = data['token']
print "\033[1;32;40mtoken:%s\033[0m" % (TOKEN)
