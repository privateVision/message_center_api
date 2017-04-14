import ConfigParser
import os


def get_tips(section, key):
    config = ConfigParser.ConfigParser()
    path = os.path.split(os.path.realpath(__file__))[0] + '/message_tips.conf'
    config.read(path)
    return config.get(section, key)
