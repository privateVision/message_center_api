# --sleep= 休眠时间，单位：秒
# --timeout= 任务处理超时时间，单位：秒
# --tries= 任务失败最大重试次数
# --daemon 守护进程（后台运行）
/usr/bin/php artisan queue:restart
/usr/bin/php artisan queue:work --sleep=1 --timeout=45 --tries=86400
# 重启进程
#/usr/bin/php artisan queue:restart
# 查看所有失败的任务
#/usr/bin/php artisan queue:failed
