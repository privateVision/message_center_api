#! /bin/bash
##################################################
# Name: restart_app_server.sh
# Description: message service uwsgi server manager
##################################################

start_ports=$1
count=$(ps -ef|grep uwsgi|grep -v grep|wc -l)
if [ $count -gt 0 ];then
    echo '停止各 uwsgi 进程 ...'
    ps -ef|grep uwsgi|grep -v grep|awk '{print $2}'|xargs kill -9
    if [ 0 == $? ];then
        echo "停止 uwsgi 进程【成功】!"
        rm -f nohup.out
    else
        echo "停止 uwsgi 进程【失败】!"
        exit
    fi
    echo '开始启动 uwsgi 各进程 ... '
    for port in ${start_ports[*]};
    do
        echo "当前启动端口： $port"
        nohup uwsgi --buffer-size 32768 --socket 127.0.0.1:$port --wsgi-file run.py --callable app  --enable-threads --lazy-apps --evil-reload-on-as 1024 --evil-reload-on-rss 512 --listen 65535 --processes 2 --threads 1 &
        if [ 0 == $? ];then
            echo "端口： $port 启动【成功】!"
        else
            echo "端口： $port 启动【失败】"
        fi
        sleep 0.1
    done
    echo 'all process started!'
else
    echo '开始启动 uwsgi 各进程 ... '
    for port in ${start_ports[*]};
    do
        echo "当前启动端口： $port"
        nohup uwsgi --buffer-size 32768 --socket 127.0.0.1:$port --wsgi-file run.py --callable app  --enable-threads --lazy-apps --evil-reload-on-as 1024 --evil-reload-on-rss 512 --listen 65535 --processes 2 --threads 1 &
        if [ 0 == $? ];then
            echo "端口： $port 启动【成功】!"
        else
            echo "端口： $port 启动【失败】"
        fi
        sleep 0.1
    done
    echo 'all process started!'
fi


consume_count=$(ps -ef|grep consume|grep -v grep|wc -l)
if [ $consume_count -gt 0 ];then
    ps -ef|grep consume|grep -v grep|awk '{print $2}'|xargs kill -9
    rm -f kafka_consume.log
    nohup python consume.py > kafka_consume.log 2>&1 &
    if [ 0 == $? ];then
        echo "kafka 消费进程启动【成功】!"
    else
        echo "kafka 消费进程启动【失败】!"
    fi
else
    nohup python consume.py > kafka_consume.log 2>&1 &
    if [ 0 == $? ];then
        echo "kafka 消费进程启动【成功】!"
    else
        echo "kafka 消费进程启动【失败】!"
    fi
fi
