# 系统设置

--- 

#### APP_ENV=local
指定系统需要载入的配置文件

#### APP_DEBUG=true
系统是否运行于DEBUG模式，在DEBUG模式下会有一些特殊的逻辑（比如充值只需要支付1分钱）

#### APP_LOCALE=zh-cn
国际化语言

#### APP_FALLBACK_LOCALE=zh-cn
国际化备用语言

#### APP_KEY=13d9c077e9cd1b9d8fec1d2a32dda274
用于加密cookie等 *无需更改*

#### APP_TIMEZONE=Asia/Shanghai
系统运行时区

#### 3DES_KEY=4c6e0a99384aff934c6e0a99
用于3des加密的默认KEY *无需更改*

#### alarm_emails=sss60@qq.com
当系统产生错误时发出邮件通知，多个邮件地址使用竖线分隔

#### log_path=/log/
日志存放目录，需以`/`结尾，日志每天产生一个新文件，如：20170411.log

#### log_level=0
日志有0~4共4个等级，分别对应：debug,info,warning,error

# 常规设置

---

#### default_avatar=http://api5.zhuayou.com/avatar.png
用户默认头像

#### service_share=http://www.anfeng.cn/app
玩家分享页面

#### service_page=http://m.anfeng.cn/service.html
默认客服页面

#### service_phone=4000274365
默认客服电话

#### service_qq=4000274365
默认客服QQ

#### default_heartbeat_interval=2000
默认客户端心跳间隔（毫秒）

#### logout_img=http://appicdn.anfeng.cn/app/upload/appforsdk.jpg
在客户端退出时展示的默认图片

#### logout_redirect=http://www.anfeng.cn/
在客户端退出时展示图片，点击图片时跳转的URL地址

#### protocol_title=安锋用户协议
用户协议标题

#### protocol_url=http://192.168.1.209/protocol.html
用户协议URL

#### oauth_url_qq=http://passtest.anfeng.cn/oauth/login/qq
QQ登陆URL

#### oauth_url_weixin=http://passtest.anfeng.cn/oauth/login/weixin
微信登陆URL

#### oauth_url_weibo=http://passtest.anfeng.cn/oauth/login/weibo
微博登陆URL

#### reset_password_url=http://passtest.anfeng.cn/reset_password.html
用户如果找客服要申请修改密码时，客服将该页面URL发给用户，用户自行修改


# kafka配置

---

#### KAFKA_SERVER=192.168.1.246:9092
kafka服务器地址，多个服务器使用逗号分隔：`host1:port,host2:port`

# SDK主库设置

---

#### DB_ANFANAPI_HOST=192.168.1.246
host
#### DB_ANFANAPI_PORT=3306
port
#### DB_ANFANAPI_DATABASE=anfanapi
dbname
#### DB_ANFANAPI_USERNAME=root
username
#### DB_ANFANAPI_PASSWORD=123456
password
#### DB_ANFANAPI_PREFIX=
prefix

# SDK附加库设置

---

这个库就是ucenter库，目前只用到表`pre_ucenter_members`
#### DB_56GAMEBBS_HOST=192.168.1.246
host
#### DB_56GAMEBBS_PORT=3306
port
#### DB_56GAMEBBS_DATABASE=56gamebbs
dbname
#### DB_56GAMEBBS_USERNAME=root
username
#### DB_56GAMEBBS_PASSWORD=123456
password
#### DB_56GAMEBBS_PREFIX=pre_
prefix

# mongodb设置

---

#### MONGODB_HOST=192.168.1.246
host
#### MONGODB_PORT=27017
port
#### MONGODB_DATABASE=users_message
dbname
#### MONGODB_USERNAME=
username
#### MONGODB_PASSWORD=
password

# 专门用于记录日志的Mongodb

---

#### MONGODB_LOG_HOST=192.168.1.246
host
#### MONGODB_LOG_PORT=27017
port
#### MONGODB_LOG_DATABASE=users_message
dbname
#### MONGODB_LOG_USERNAME=
username
#### MONGODB_LOG_PASSWORD=
password

# email设置

---

#### MAIL_SMTP_HOST=smtp.exmail.qq.com
smtp host
#### MAIL_SMTP_PORT=465
smtp port
#### MAIL_ENCRYPT=ssl
encrypt
#### MAIL_USERNAME=lixingxing@anfan.com
username
#### MAIL_PASSWORD=leeson8899C
password
#### MAIL_FROM_ADDRESS=lixingxing@anfan.com
email address
#### MAIL_FROM_NAME=掌游
email name

# redis设置

---

#### REDIS_CLUSTER=true
是否是集群
#### REDIS_HOST=192.168.1.246
host
#### REDIS_PORT=6379
prot
#### REDIS_DATABASE=0
database
#### REDIS_PASSWORD
password