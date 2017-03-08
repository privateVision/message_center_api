## 消息服务中 Redis 数据结构设计

1、用户心跳标记数据

* 存储类型: Hash
* Field：

>	notice ( int - 用户未读公告数量)
>
>	broadcast ( int - 用户未读广播数量)
>
>	message ( int - 用户未读消息数量)

>	coupon ( int - 用户未读卡券数量)

>	rebate ( int - 用户未读优惠券数量)
>
>	is_freeze ( int - 用户账号是否被冻结)

