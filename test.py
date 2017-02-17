import os,sys
import Crypto
from Crypto.Cipher import DES3
import base64
import urllib, urllib2

DESKEY = '4c6e0a99384aff934c6e0a99'
BASEURL = 'http://192.168.1.209/sdk/index.php'

def encrypt3des(data):
	poststr = urllib.urlencode(data)
	des3 = DES3.new(DESKEY, DES3.MODE_ECB)
	str = des3.encrypt(poststr)
	return base64.standard_b64encode(str)

"""
print encrypt3des({
  "id":"2099865",
  "time":"1487210761003",
  "token":"0e29e0c3-bfef-46d8-84c5-0cc065cc324a",
})
"""
request = urllib2.Request('http://sdkapi.anfan.com/api/test', 'K+AmiLKQykh2d6wlaNc9rRhxVfi5ccjdo96VZu0Ke7iKND6v7Azxc/VDBlRFajFzi/XlOvy/C2G80CLoTkKDVjvMg92MctOiFnfZkj/UpoM=')
response = urllib2.urlopen(request)
print "----------------------------------"
print response.read()
print "----------------------------------"