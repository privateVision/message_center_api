## Message Service Deploy
 该服务采用 Flask 框架开发，该框架内置 Web Server，但是由于性能考虑，采用独立的 Web Server 来部署该应用。

 具体方案为 Nginx + uWSGI。

 * uWSGI 配置参考项目文件：uwsgi.ini

 * nginx 配置参考为：

 ```
 location / {
            include uwsgi_params;
            uwsgi_pass  127.0.0.1:5000;
            uwsgi_read_timeout 120;
        }
 ```