# 环境要求
+ os：linux、unix
+ php-version >= 5.6.4
+ mysql-version >= 5.5
+ nginx-version >= 1.4
+ redis-version >= 2.6
+ mongodb-version >= 3.0
+ kafka-version >= 0.9

# php 所需扩展
除了常规扩展，PHP还需安装以下扩展  

+ redis(>=3.1.1) [http://pecl.php.net/package/redis](http://pecl.php.net/package/redis "http://pecl.php.net/package/redis")
+ rdkafka(>=3.0.1) [http://pecl.php.net/package/rdkafka](http://pecl.php.net/package/rdkafka)
+ mongodb(>=1.2.5) [http://pecl.php.net/package/mongodb](http://pecl.php.net/package/mongodb)

# nginx 配置
nginx的root目录需要指向`appapi/public/`目录下

	server
	{
	    listen 80;
	    server_name sdkapi.anfan.com;
	    index index.html index.htm index.php;
	    root  /data/wwwroot/sdkapi/appapi/public;
	
	    if (!-e $request_filename) {
	        rewrite ^(.*)$ /index.php$1 last;
	    }
	
	    location ~ [^/]\.php(/|$)
	    {
	        try_files $uri =404;
	        fastcgi_pass  unix:/tmp/php-cgi.sock;
	        fastcgi_index index.php;
	        include fastcgi.conf;
	
	        set $real_script_name $fastcgi_script_name;
	
	        if ($fastcgi_script_name ~ "^(.+?\.php)(/.+)$") {
	            set $real_script_name $1;
	            set $path_info $2;
	        }
	
	        fastcgi_param SCRIPT_FILENAME $document_root$real_script_name;
	        fastcgi_param SCRIPT_NAME $real_script_name;
	        fastcgi_param PATH_INFO $path_info;
	    }
	
	    access_log /log/nginx_access.log mylog;
	}

# 目录权限
`appapi/stores` 是存储各类缓存、日志的目录，需要nginx用户有可写权限

# 配置文件修改
配置文件在`appapi/.env.example`需要将其重命名为`.env`，配置项的修改参见文档[appapi目录下的.env文件注释.md](appapi目录下的.env文件注释.md)

# 队列处理进程
## 启动
脚本在`appapi/listen-queue.sh`，该脚本会启动一个阻塞进程，要想让其后台运行要么使用`nohup`要么加`&`符号。随着业务越来越大，会需要更多的进程来处理队列数据，为了不影响主业务应该将队列处理进程单独部署在一台或多台服务器上。进程在运行过程中会有死掉的风险，必需要有软件进行守护，例如：`supervisor`，supervisor配置示例如下：

	[program:listen-queue-1]                                                                            
	command=/data/wwwroot/sdkapi/appapi/listen-queue.sh ; 启动命令
	directory=/data/wwwroot/sdkapi/appapi/              ; 在启动前先进入该目录
	autostart=true                                      ; 随supervisord一起启动
	autorestart=true                                    ; 进程异常退出重启
	startretries=3                                      ; 重启最大尝试次数
	redirect_stderr=true                                ; 将stderr重写向到stdout
	stderr_logfile=/log/listen-queue.err.log            ; 进程日志目录
	stdout_logfile=/log/listen-queue.out.log            ; 进程日志目录