#! /bin/bash
##################################################
# Name: restart_app_server.sh
# Description: message service uwsgi server manager
##################################################

count=$(ps -ef|grep uwsgi|grep -v grep|wc -l)
if [ $count -gt 0 ];then
    echo 'stop main process ...'
    ps -ef|grep uwsgi|grep -v grep|awk '{print $2}'|xargs kill -9
    if [ 0 == $? ];then
        echo "stop main process success!"
        rm -f nohup.out
    else
        echo "stop main process failed!"
        exit
    fi
    echo 'starting main process ...'
    echo "当前启动地址： $1"
    nohup uwsgi --socket $1 --wsgi-file run.py --callable app --enable-threads --lazy-apps --evil-reload-on-as 1024 --evil-reload-on-rss 512 --listen 100 --processes 4 --threads 1 &
    if [ 0 == $? ];then
        echo "start main process success!"
    else
        echo "start main process failed"
    fi
else
    echo "当前启动地址： $1"
    nohup uwsgi --socket $1 --wsgi-file run.py --callable app --enable-threads --lazy-apps --evil-reload-on-as 1024 --evil-reload-on-rss 512 --listen 100 --processes 4 --threads 1 &
    if [ 0 == $? ];then
        echo "start main process success!"
    else
        echo "start main process failed"
    fi
fi


consume_count=$(ps -ef|grep consume|grep -v grep|wc -l)
if [ $consume_count -gt 0 ];then
    ps -ef|grep consume|grep -v grep|awk '{print $2}'|xargs kill -9
    nohup python consume.py &
    if [ 0 == $? ];then
        echo "kafka consume process start success!"
    else
        echo "kafka consume process start failed"
    fi
else
    echo "start kafka consume "
    nohup python consume.py &
    if [ 0 == $? ];then
        echo "kafka consume process start success!"
    else
        echo "kafka consume process start failed"
    fi
fi
