# _*_ coding:utf8 _*_
from fabric.api import local
from fabric.api import lcd
from fabric.api import put
from fabric.api import env
from fabric.api import run
from fabric.context_managers import cd, settings

env.hosts = [
    'root@192.168.1.246:22'
]
env.passwords = {
    'root@192.168.1.246:22': '123456'
}
local_dir = "../message_service_api"
local_app_name = "message_service_api"
remote_dir = "/tmp/"


def app_pack():
    with lcd(local_dir):
        local('rm -f %s.tar.gz' % (local_app_name,))
        local('tar -czvf %s.tar.gz %s' % (local_app_name, local_dir))  # 打包
        put('%s.tar.gz' % (local_app_name,), remote_dir)  # 上传到远程服务器目录


def app_start():
    with cd(remote_dir):
        run('tar -zxvf %s.tar.gz' % (local_app_name,))
        with cd(local_app_name):
            run('./self_start.sh')
        run('ps -ef|grep uwsgi|grep -v grep|wc -l')
        run('ps -ef|grep consume|grep -v grep|wc -l')


def deploy_message_service_app():
    app_pack()
    app_start()
