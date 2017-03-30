#! /bin/bash
##################################################
# Name: restart_app_server.sh
# Description: message service uwsgi server manager
##################################################

count=$(ps -ef|grep uwsgi|grep -v grep|wc -l)
if [ 1 == $count ];then
    echo 'stop main process ...'
    ps -ef|grep uwsgi|grep -v grep|awk '{print $2}'|xargs kill -9
    if [ 0 == $? ];then
        echo "stop main process success!"
        rm -f nohup.out
    else
        echo "stop main process failed!"
        exit
    fi
    echo 'start process ...'
    echo "当前启动地址： $1"
    for v in ${start_host[@]}; do
        nohup uwsgi --socket $v --wsgi-file run.py --callable app --enable-threads &
        if [ 0 == $? ];then
            echo "start process "+ $v +" success!"
        else
            echo "start process "+ $v +" failed"
        fi
　　 done
    count=$(ps -ef|grep consume|grep -v grep|wc -l)
    if [ 1 == $count ];then
        ps -ef|grep consume|grep -v grep|awk '{print $2}'|xargs kill -9
        nohup python consume.py &
        if [ 0 == $? ];then
            echo "kafka consume process start success!"
        else
            echo "kafka consume process start failed"
        fi
    else
        echo 'kafka consume not start'
    fi
else
    echo "当前启动地址： $1"
    ps -ef|grep consume|grep -v grep|awk '{print $2}'|xargs kill -9
    for v in ${start_host[@]}; do
        nohup uwsgi --socket $v --wsgi-file run.py --callable app --enable-threads &
        if [ 0 == $? ];then
            echo "start process "+ $v +" success!"
        else
            echo "start process "+ $v +" failed"
        fi
　　 done
    nohup python consume.py &
    if [ 0 == $? ];then
        echo "kafka consume process start success!"
    else
        echo "kafka consume process start failed"
    fi
fi
