#!/bin/bash
current_dir="$(cd "$(dirname "$0")" && pwd)"

/usr/bin/php $current_dir/artisan queue:restart

# --sleep= 休眠时间，单位：秒
# --timeout= 任务处理超时时间，单位：秒
# --tries= 任务失败最大重试次数
# --daemon 守护进程（后台运行）
/usr/bin/php $current_dir/artisan queue:work --daemon --sleep=1 --timeout=45 --tries=86400

# 查看所有失败的任务
#/usr/bin/php artisan queue:failed
