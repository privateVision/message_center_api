#! /bin/bash
##################################################
# Name: restart_app_server.sh
# Description: message service uwsgi server manager
##################################################

start_ports=$1
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
    for port in ${start_ports[*]};
    do
        echo "当前启动端口： $port"
        nohup uwsgi --buffer-size 32768 --socket 127.0.0.1:$port --wsgi-file run.py --callable app  --enable-threads --lazy-apps --evil-reload-on-as 1024 --evil-reload-on-rss 512 --listen 65535 --processes 2 --threads 1  &
        if [ 0 == $? ];then
            echo "start process $port success!"
        else
            echo "start process $port failed"
        fi
    done
    echo 'all process started!'
else
    echo 'starting main process ...'
    for port in ${start_ports[*]};
    do
        echo "当前启动端口： $port"
        nohup uwsgi --buffer-size 32768 --socket 127.0.0.1:$port --wsgi-file run.py --callable app  --enable-threads --lazy-apps --evil-reload-on-as 1024 --evil-reload-on-rss 512 --listen 65535 --processes 2 --threads 1  &
        if [ 0 == $? ];then
            echo "start process $port success!"
        else
            echo "start process $port failed"
        fi
    done
    echo 'all process started!'
fi


consume_count=$(ps -ef|grep consume|grep -v grep|wc -l)
if [ $consume_count -gt 0 ];then
    ps -ef|grep consume|grep -v grep|awk '{print $2}'|xargs kill -9
    nohup python consume.py > kafka_consume.log 2>&1 &
    if [ 0 == $? ];then
        echo "kafka consume process start success!"
    else
        echo "kafka consume process start failed"
    fi
else
    echo "start kafka consume "
    nohup python consume.py > kafka_consume.log 2>&1 &
    if [ 0 == $? ];then
        echo "kafka consume process start success!"
    else
        echo "kafka consume process start failed"
    fi
fi
