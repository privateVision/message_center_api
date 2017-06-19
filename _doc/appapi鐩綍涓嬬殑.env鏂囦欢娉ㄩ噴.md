# 常规设置

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
#### ALARM_MAILS=sss60@qq.com
当系统产生错误时发出邮件通知，多个邮件地址使用竖线分隔

# 客服设置

---

#### AVATAR=http://api5.zhuayou.com/avatar.png
默认用户头像
#### SERVICE_SHARE=http://www.anfeng.cn/app
默认客服页面
#### SERVICE_PHONE=4000274365
默认客服电话
#### SERVICE_QQ=4000274365
默认客服QQ

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

# 缓存及队列设置

---

#### CACHE_DRIVER=redis
缓存使用的DB
#### QUEUE_DRIVER=redis
队列使用的DB

# 其它设置

---

#### APP_SELF_ID=2
订单有两种，  
1：购买游戏道具，此时需要通知游戏端发货  
2：预储值，需要给用户增加ucusers.balance  
由于系统在设计的时候只考虑到第一种情况，于是需要把`预储值`也当成游戏充值，然后就需要分配一个procedures.pid用来标识这是第二种情况。因此需要在数据库添加一条记录用来标识它，然后将产生的pid配置在这里