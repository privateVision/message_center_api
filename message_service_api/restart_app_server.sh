#! /bin/bash
##################################################
# Name: restart_app_server.sh
# Description: message service uwsgi server manager
##################################################

count=$(ps -ef|grep uwsgi|grep -v grep|wc -l)
if [ 1 == $count ];then
    echo 'stop process ...'
    ps -ef|grep uwsgi|grep -v grep|awk '{print $2}'|xargs kill -9
    if [ 0 == $? ];then
        echo "stop process success!"
        rm -f nohup.out
    else
        echo "stop process failed!"
        exit
    fi
    ps -ef|grep consume|grep -v grep|awk '{print $2}'|xargs kill -9
    if [ 0 == $? ];then
        echo "stop kafka consume process success!"
        rm -f nohup.out
    else
        echo "stop kafka consume process failed!"
        exit
    fi
    echo 'start process ...'
    echo "当前启动地址： $1"
    nohup uwsgi --socket $1 --wsgi-file run.py --callable app --enable-threads &
    if [ 0 == $? ];then
        echo "start process success!"
    else
        echo "start process failed"
    fi
    nohup python consume &
    if [ 0 == $? ];then
        echo "kafka consume process start success!"
    else
        echo "kafka consume process start failed"
    fi
else
    echo "当前启动地址： $1"
    ps -ef|grep consume|grep -v grep|awk '{print $2}'|xargs kill -9
    nohup uwsgi --socket $1 --wsgi-file run.py --callable app --enable-threads &
    if [ 0 == $? ];then
        echo "start process success!"
    else
        echo "start process failed"
    fi
    nohup python consume &
    if [ 0 == $? ];then
        echo "kafka consume process start success!"
    else
        echo "kafka consume process start failed"
    fi
fi
