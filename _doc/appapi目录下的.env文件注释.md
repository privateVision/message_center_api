## 常规设置
#### APP_ENV=local
指定系统需要载入的配置文件

#### APP_DEBUG=true
系统是否运行于DEBUG模式，在DEBUG模式下会有一些特殊的逻辑（比如充值只需要支付1分钱）

#### APP_LOCALE=zh-cn
国际化语言

#### APP_FALLBACK_LOCALE=zh-cn

#### APP_KEY=13d9c077e9cd1b9d8fec1d2a32dda274
用于加密cookie等加密 *无需更改*

#### APP_TIMEZONE=Asia/Shanghai
系统运行时区

#### 3DES_KEY=4c6e0a99384aff934c6e0a99
用于3des加密的默认KEY *无需更改*

#### ALARM_MAILS=sss60@qq.com
当系统产生错误时发出件通知，多个邮件地址使用竖线分隔

## 客服设置
#### AVATAR=http://api5.zhuayou.com/avatar.png
默认用户头像

#### SERVICE_SHARE=http://www.anfeng.cn/app
默认客服页面

#### SERVICE_PHONE=4000274365
默认客服电话

#### SERVICE_QQ=4000274365
默认客服QQ

#### KAFKA_SERVER=192.168.1.246:9092
kafka服务器地址，HOST:PORT

## SDK主库设置
#### DB_CONNECTION=anfanapi
#### DB_ANFANAPI_HOST=192.168.1.246
#### DB_ANFANAPI_PORT=3306
#### DB_ANFANAPI_DATABASE=anfanapi
#### DB_ANFANAPI_USERNAME=root
#### DB_ANFANAPI_PASSWORD=123456
#### DB_ANFANAPI_PREFIX=

## SDK附加库设置
#### DB_56GAMEBBS_HOST=192.168.1.246
#### DB_56GAMEBBS_PORT=3306
#### DB_56GAMEBBS_DATABASE=56gamebbs
#### DB_56GAMEBBS_USERNAME=root
#### DB_56GAMEBBS_PASSWORD=123456
#### DB_56GAMEBBS_PREFIX=pre_

## mongodb设置
#### MONGODB_HOST=192.168.1.246
#### MONGODB_PORT=27017
#### MONGODB_DATABASE=users_message
#### MONGODB_USERNAME=
#### MONGODB_PASSWORD=
#### MONGODB_PREFIX=sdk_appapi_

## email设置
#### MAIL_SMTP_HOST=smtp.exmail.qq.com
#### MAIL_SMTP_PORT=465
#### MAIL_ENCRYPT=ssl
#### MAIL_USERNAME=lixingxing@anfan.com
#### MAIL_PASSWORD=leeson8899C
#### MAIL_FROM_ADDRESS=lixingxing@anfan.com
#### MAIL_FROM_NAME=掌游

## redis设置
#### REDIS_CLUSTER=true
#### REDIS_HOST=192.168.1.246
#### REDIS_PORT=6379
#### REDIS_DATABASE=0
#### REDIS_PASSWORD

## 缓存及队列设置
#### CACHE_DRIVER=redis
#### QUEUE_DRIVER=redis

## 其它设置
#### APP_SELF_ID=2