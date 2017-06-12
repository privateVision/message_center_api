#!/usr/bin/bash

HOST="106.75.153.1041"
USERNAME="root"
PASSWORD="An104Newan!@#.com()_feng.comapi1"

TTAR=$(date +%Y%m%d%H%M%S).tar.gz
tar -zcvf $TTAR app routes config

SCPCMD="scp $TTAR $USERNAME@$HOST:/data/sdkapi.com/appapi/"

expect -c "
    spawn $SCPCMD;
    expect {
        \"*yes/no*\" {send \"yes\r\"; exp_continue}
        \"password:\" {send \"$PASSWORD\r\"; exp_continue}
    }
"

SSHCMD="ssh $USERNAME@$HOST \"cd /data/sdkapi.com/appapi; tar -zxvf $TTAR; rm -rf $TTAR\""
expect -c "
    spawn $SSHCMD
    expect {
        \"*yes/no*\" {send \"yes\r\"; exp_continue}
        \"password:\" {send \"$PASSWORD\r\"; exp_continue}
    }
"

#\"root@\" {send\"df -h\r exit\r\"; exp_continue}
