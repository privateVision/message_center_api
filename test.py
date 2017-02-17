import os,sys
import Crypto
from Crypto.Cipher import DES3
import base64
import urllib, urllib2
import json

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
		str = Http.__encrypt_postdata(data)
		request = urllib2.Request(BASEURL + uri, str)
		response = urllib2.urlopen(request)
		
		return response.read()

	@staticmethod
	def __encrypt_postdata(data):
		data = urllib.urlencode(data)
		return Crypt3DES.encrypt(data);

print "----------- api/app/initialize ---------"
result = Http.request('api/app/initialize', game_id = 1, device_code = '3447264d06ff60e9cc415229a0583a29', device_name = 'iphone 6plus', device_platform = 16, version = '1.0.0')
print result
print "------------------------------------"