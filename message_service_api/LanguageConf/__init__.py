import ConfigParser
import os

config = ConfigParser.ConfigParser()
path = os.path.split(os.path.realpath(__file__))[0] + '/message_tips.conf'
config.read(path)


def get_tips(section, key):
    return config.get(section, key)
