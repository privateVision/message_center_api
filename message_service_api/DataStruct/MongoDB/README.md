## 消息服务中 MongoDB 数据结构设计


**1、集合名称：app\_vip\_rules**

```
用户 VIP 的等级规则
```
* 数据结构

```JSON
{
    "_id" : 1,         // vip等级
    "fee" : 2,         // 等级对应的消费金额
    "name" : "vip1"    // vip对应名称
}
```


**2、集合名称：message\_revocation**

```
用户消息撤回
```
* 数据结构

```JSON
{
    "_id" : "notice1",     // 主键ID（消息类型+消息ID）
    "type" : "notice",     // 消息类型
    "mysql_id" : 1,        // 消息对应Mysql存储表的主键ID
    "create_time" : ISODate("2017-03-07T15:30:41.428Z")   // 记录创建时间
}
```


**3、用户消息：user\_message**

```
用户相关的各种消息集合
```
* 数据结构

```JSON
{
    "_id" : "123notice3",          // 主键ID（用户ID+消息类型+消息ID）
    "ucid" : 123,                  // 用户ID
    "type" : "notice",             // 消息类型
    "mysql_id" : 3,                // 消息对应Mysql存储表的主键ID
    "closed" : 0,                  // 消息是否被关闭
    "is_read" : 0,                 // 消息是否已读
    "start_time" : 1134122221,     // 消息展示开始时间
    "end_time" : 1523113349,       // 消息展示结束时间   
    "create_time" : ISODate("2017-03-07T14:16:47.213Z"),   // 记录创建时间
    "is_time" : 1                  // 是否有时间限制
}
```


**3、用户已读消息日志：user\_read\_message\_log**

```
用户已读消息的日志记录
```
* 数据结构

```JSON
{
    "_id" : ObjectId("58af9b2bfe3a020a39af2c77"),         // 主键ID
    "type" : "notice",                                    // 消息类型
    "message_id" : 1,                                     // 消息对应Mysql存储表的主键ID
    "ucid" : 123,                                         // 用户ID
    "read_time" : ISODate("2017-02-24T10:32:11.316Z")     // 消息读取时间
}
```


**3、后台发送消息详情：users\_message**

```
运营后台发送的各种消息的详情记录
```
* 数据结构

```JSON
{
    "_id" : "notice3",                        // 主键ID（用户ID+消息类型+消息ID）
    "mysql_id" : 3,                           // 消息对应Mysql存储表的主键ID
    "type" : "notice",                        // 消息类型
    "closed" : 0,                             // 消息是否被关闭
    "title" : "测试公告4",
    "start_time" : 1134122221,                // 消息展示开始时间
    "end_time" : 1523113349,                  // 消息展示结束时间
    "show_times" : 0,                         // 展示次数
    "atype" : 2,                              // 公告类型
    "button_content" : "充值",
    "button_type" : "go_charge",
    "url_type" : "go_finish",
    "create_time" : 1314213324),
    "enter_status" : "",
    "content" : "公告的详细内容",
    "sortby" : 1,
    "button_url" : "zy://go_charge",
    "open_type" : 1,
    "img" : "http://img-url/1.jpg",
    "url" : "http://baidu.com",
    "users" : [                               // 指定发送的用户
        "123", 
        "253"
    ],
    "rtype" : [                               // 指定发送的用户类型
        "255", 
        "3"
    ],
    "app" : [                                 // 指定发送的游戏区服
        {
            "apk_id" : "all"
        }
    ],
    "vip" : [                                 // 指定发送的用户 VIP 等级    
        "3"
    ],
    "is_time" : 1,                            // 是否有显示时间限制
    "expire_at" : NumberLong(4523113349)      // 过期时间
}
```
	
	