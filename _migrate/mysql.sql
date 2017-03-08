/*
SQLyog Ultimate v12.09 (64 bit)
MySQL - 5.7.11-log : Database - anfanapi
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`anfanapi` /*!40100 DEFAULT CHARACTER SET utf8 */;

/*Table structure for table `AnfanApk` */

CREATE TABLE `AnfanApk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `aid` int(11) NOT NULL,
  `aname` varchar(20) NOT NULL,
  `asize` varchar(10) NOT NULL,
  `aversion` varchar(20) NOT NULL,
  `apkId` int(11) NOT NULL,
  `apkPackageName` varchar(200) DEFAULT NULL,
  `apkName` varchar(40) DEFAULT NULL,
  `apkVersionCode` varchar(20) DEFAULT NULL,
  `apkSize` int(11) DEFAULT NULL,
  `categoryName` varchar(20) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `aid` (`aid`),
  KEY `aname` (`aname`),
  KEY `apkId` (`apkId`),
  KEY `apkName` (`apkName`)
) ENGINE=InnoDB AUTO_INCREMENT=7744 DEFAULT CHARSET=utf8;

/*Table structure for table `Apk360` */

CREATE TABLE `Apk360` (
  `apkId` int(11) NOT NULL,
  `apkPackageName` varchar(200) DEFAULT NULL,
  `apkName` varchar(200) DEFAULT NULL,
  `apkIconUrl` varchar(200) DEFAULT NULL,
  `apkIncomeShare` int(11) DEFAULT NULL,
  `apkRating` float DEFAULT NULL,
  `apkDownloadUrl` varchar(200) DEFAULT NULL,
  `apkDescription` varchar(200) DEFAULT NULL,
  `apkDeveloper` varchar(200) DEFAULT NULL,
  `apkTag` varchar(200) DEFAULT NULL,
  `apkSelected` int(11) DEFAULT NULL,
  `apkFileName` varchar(20) DEFAULT NULL,
  `apkUpdateTime` bigint(20) DEFAULT NULL,
  `apkVersionCode` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`apkId`),
  KEY `apkId` (`apkId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Table structure for table `Apk360lm121374` */

CREATE TABLE `Apk360lm121374` (
  `apkId` int(11) NOT NULL,
  `apkPackageName` varchar(200) DEFAULT NULL,
  `apkName` varchar(200) DEFAULT NULL,
  `apkIconUrl` varchar(200) DEFAULT NULL,
  `apkIncomeShare` int(11) DEFAULT NULL,
  `apkRating` float DEFAULT NULL,
  `apkDownloadUrl` varchar(200) DEFAULT NULL,
  `apkDescription` varchar(200) DEFAULT NULL,
  `apkDeveloper` varchar(200) DEFAULT NULL,
  `apkTag` varchar(200) DEFAULT NULL,
  `apkSelected` int(11) DEFAULT NULL,
  `apkFileName` varchar(20) DEFAULT NULL,
  `apkUpdateTime` bigint(20) DEFAULT NULL,
  `apkVersionCode` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`apkId`),
  KEY `apkId` (`apkId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `ApkList360` */

CREATE TABLE `ApkList360` (
  `apkId` int(11) NOT NULL,
  `apkPackageName` varchar(200) DEFAULT NULL,
  `apkName` varchar(40) DEFAULT NULL,
  `apkVersionCode` varchar(20) DEFAULT NULL,
  `apkSize` int(11) DEFAULT NULL,
  `categoryName` varchar(20) DEFAULT '',
  PRIMARY KEY (`apkId`),
  KEY `apkId` (`apkId`),
  KEY `apkName` (`apkName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `admingroups` */

CREATE TABLE `admingroups` (
  `aid` int(11) NOT NULL,
  `agid` int(11) NOT NULL,
  KEY `agid` (`agid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Table structure for table `admins` */

CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(32) NOT NULL,
  `auid` varchar(50) NOT NULL,
  PRIMARY KEY (`username`),
  KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=42 DEFAULT CHARSET=utf8;

/*Table structure for table `anfan_cps` */

CREATE TABLE `anfan_cps` (
  `cpsid` int(11) NOT NULL AUTO_INCREMENT,
  `cpsname` varchar(32) NOT NULL,
  `rid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cpsid`),
  KEY `cpsname` (`cpsname`),
  KEY `rid` (`rid`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

/*Table structure for table `anfan_cps_data` */

CREATE TABLE `anfan_cps_data` (
  `cpsdataid` int(11) NOT NULL AUTO_INCREMENT,
  `cpsid` int(11) NOT NULL,
  `cpsdate` datetime NOT NULL,
  `register` int(11) NOT NULL DEFAULT '0',
  `paycount` int(11) NOT NULL DEFAULT '0',
  `fee` float(18,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`cpsdataid`),
  KEY `cpsid` (`cpsid`),
  KEY `cpsdate` (`cpsdate`)
) ENGINE=MyISAM AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;

/*Table structure for table `anfan_gifts` */

CREATE TABLE `anfan_gifts` (
  `haoid` int(11) NOT NULL,
  `gameId` int(11) NOT NULL,
  `startTime` int(11) NOT NULL,
  `endTime` int(11) NOT NULL,
  `giftName` varchar(100) NOT NULL,
  `total` int(11) NOT NULL,
  `remain` int(11) NOT NULL,
  PRIMARY KEY (`haoid`),
  KEY `gameId` (`gameId`),
  KEY `startTime` (`startTime`),
  KEY `endTime` (`endTime`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Table structure for table `anfan_newserver` */

CREATE TABLE `anfan_newserver` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apkId` int(11) NOT NULL,
  `yunyingshang` varchar(100) NOT NULL,
  `starttime` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `apkId` (`apkId`),
  KEY `starttime` (`starttime`)
) ENGINE=MyISAM AUTO_INCREMENT=649 DEFAULT CHARSET=utf8;

/*Table structure for table `anfan_order_log` */

CREATE TABLE `anfan_order_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sn` varchar(36) NOT NULL,
  `logTime` datetime NOT NULL,
  `ucid` int(11) NOT NULL,
  `amt` float DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sn` (`sn`),
  KEY `logTime` (`logTime`)
) ENGINE=InnoDB AUTO_INCREMENT=6000 DEFAULT CHARSET=utf8;

/*Table structure for table `anfan_order_queue` */

CREATE TABLE `anfan_order_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sn` varchar(36) NOT NULL DEFAULT '',
  `amt` float(18,2) DEFAULT NULL,
  `notify_time` datetime DEFAULT NULL,
  `version` int(11) DEFAULT NULL,
  PRIMARY KEY (`sn`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1923 DEFAULT CHARSET=utf8;

/*Table structure for table `anfanpay_log` */

CREATE TABLE `anfanpay_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `sn` varchar(50) NOT NULL,
  `amount` decimal(18,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`id`),
  KEY `sn` (`sn`)
) ENGINE=MyISAM AUTO_INCREMENT=262962 DEFAULT CHARSET=utf8;

/*Table structure for table `anfanpay_queue` */

CREATE TABLE `anfanpay_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sn` varchar(36) NOT NULL DEFAULT '',
  `amt` float(18,2) DEFAULT NULL,
  `pm` varchar(20) DEFAULT NULL,
  `notify_time` datetime DEFAULT NULL,
  `notify_count` int(11) DEFAULT NULL,
  PRIMARY KEY (`sn`),
  KEY `id` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=111734 DEFAULT CHARSET=utf8;

/*Table structure for table `apk_Categorys` */

CREATE TABLE `apk_Categorys` (
  `catid` int(11) NOT NULL,
  `catname` varchar(100) DEFAULT NULL,
  `parentid` int(11) DEFAULT NULL,
  `listorder` int(11) DEFAULT NULL,
  PRIMARY KEY (`catid`),
  KEY `catid` (`catid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Table structure for table `authuris` */

CREATE TABLE `authuris` (
  `agid` int(11) NOT NULL,
  `uriid` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Table structure for table `autoResignAPK` */

CREATE TABLE `autoResignAPK` (
  `pid` int(11) NOT NULL,
  `rid` int(11) NOT NULL,
  `refreshTime` datetime NOT NULL,
  PRIMARY KEY (`pid`,`rid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `award_log` */

CREATE TABLE `award_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `createTime` datetime NOT NULL,
  `uid` varchar(50) NOT NULL,
  `op` varchar(20) NOT NULL,
  `des` varchar(1000) NOT NULL,
  `aname` varchar(100) DEFAULT '',
  `ucid` int(11) DEFAULT '0',
  `vcid` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `createTime` (`createTime`)
) ENGINE=MyISAM AUTO_INCREMENT=71368 DEFAULT CHARSET=utf8;

/*Table structure for table `callback_log` */

CREATE TABLE `callback_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `order_id` varchar(50) DEFAULT NULL,
  `postdata` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=825071 DEFAULT CHARSET=utf8;

/*Table structure for table `email_log` */

CREATE TABLE `email_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `authCode` varchar(20) NOT NULL,
  `sendTime` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `email` (`email`,`authCode`)
) ENGINE=InnoDB AUTO_INCREMENT=23951 DEFAULT CHARSET=utf8;

/*Table structure for table `email_queue` */

CREATE TABLE `email_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL DEFAULT '',
  `content` varchar(200) DEFAULT NULL,
  `authCode` varchar(10) DEFAULT NULL,
  `notify_time` datetime DEFAULT NULL,
  `notify_count` int(11) DEFAULT NULL,
  PRIMARY KEY (`email`),
  KEY `id` (`id`),
  KEY `notify_time` (`notify_time`),
  KEY `notify_count` (`notify_count`)
) ENGINE=InnoDB AUTO_INCREMENT=3516 DEFAULT CHARSET=utf8;

/*Table structure for table `failed_jobs` */

CREATE TABLE `failed_jobs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `connection` text COLLATE utf8_unicode_ci NOT NULL,
  `queue` text COLLATE utf8_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Table structure for table `forgetpwd_email_log` */

CREATE TABLE `forgetpwd_email_log` (
  `eid` int(11) NOT NULL AUTO_INCREMENT,
  `ucid` int(11) NOT NULL,
  `email` varchar(50) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `body` varchar(500) DEFAULT NULL,
  `createTime` datetime DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `sendTime` datetime DEFAULT NULL,
  `authCode` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`eid`),
  KEY `ucid` (`ucid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `gift_details` */

CREATE TABLE `gift_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `giftid` int(11) NOT NULL,
  `gift` varchar(50) NOT NULL,
  `uid` int(11) DEFAULT NULL,
  `sentTime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `giftid` (`giftid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `gifts` */

CREATE TABLE `gifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `name` varchar(20) DEFAULT NULL,
  `content` varchar(300) DEFAULT NULL,
  `total` int(11) DEFAULT NULL,
  `sent` int(11) DEFAULT NULL,
  `createTime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`,`name`,`createTime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `groups` */

CREATE TABLE `groups` (
  `agid` int(11) NOT NULL AUTO_INCREMENT,
  `groupname` varchar(20) NOT NULL,
  PRIMARY KEY (`agid`),
  KEY `agid` (`agid`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

/*Table structure for table `hcb_order_log` */

CREATE TABLE `hcb_order_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `resp_code` int(11) NOT NULL,
  `resp_msg` text,
  `dt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `hcb_order_queue` */

CREATE TABLE `hcb_order_queue` (
  `oid` int(11) NOT NULL,
  `ucid` int(11) NOT NULL,
  `fee` float(18,2) NOT NULL,
  `vcid` int(11) NOT NULL,
  `award` float(18,2) NOT NULL,
  `version` int(11) DEFAULT NULL,
  PRIMARY KEY (`oid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `heepaycard_log` */

CREATE TABLE `heepaycard_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `request` varchar(2000) DEFAULT NULL,
  `response` varchar(2000) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=10061 DEFAULT CHARSET=utf8;

/*Table structure for table `login_by_pro_ret_day` */

CREATE TABLE `login_by_pro_ret_day` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rid` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `dt` date NOT NULL,
  `newUser` int(11) NOT NULL,
  `loginUser` int(11) NOT NULL,
  `loginCount` int(11) NOT NULL,
  `amount` float(12,2) NOT NULL DEFAULT '0.00',
  `puserCount` int(11) NOT NULL,
  `d2` int(11) NOT NULL,
  `d3` int(11) NOT NULL,
  `d4` int(11) NOT NULL,
  `d5` int(11) NOT NULL,
  `d6` int(11) NOT NULL,
  `d7` int(11) NOT NULL,
  `d14` int(11) NOT NULL,
  `d30` int(11) NOT NULL,
  `dARPPU` float(12,2) DEFAULT '0.00',
  `dapu` float(12,2) DEFAULT '0.00',
  `dARPU` float(12,2) DEFAULT '0.00',
  PRIMARY KEY (`pid`,`rid`,`dt`),
  UNIQUE KEY `id` (`id`),
  KEY `pid` (`pid`),
  KEY `rid` (`rid`),
  KEY `dt` (`dt`)
) ENGINE=InnoDB AUTO_INCREMENT=9151626 DEFAULT CHARSET=utf8;

/*Table structure for table `login_log` */

CREATE TABLE `login_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ucid` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `loginDate` int(11) NOT NULL,
  `loginTime` int(11) NOT NULL,
  `loginIP` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`,`loginDate`),
  KEY `ucid` (`ucid`)
) ENGINE=MyISAM AUTO_INCREMENT=65084688 DEFAULT CHARSET=utf8;

/*Table structure for table `login_log_0707` */

CREATE TABLE `login_log_0707` (
  `id` int(11) NOT NULL DEFAULT '0',
  `ucid` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `loginDate` int(11) NOT NULL,
  `loginTime` int(11) NOT NULL,
  `loginIP` int(11) NOT NULL,
  KEY `IDX_login_log_0707_id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `login_log_161013` */

CREATE TABLE `login_log_161013` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ucid` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `loginDate` int(11) NOT NULL,
  `loginTime` int(11) NOT NULL,
  `loginIP` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `loginDate` (`loginDate`),
  KEY `pid` (`pid`,`loginDate`),
  KEY `ucid` (`ucid`)
) ENGINE=MyISAM AUTO_INCREMENT=260739066 DEFAULT CHARSET=utf8;

/*Table structure for table `max_fee_per_retailer` */

CREATE TABLE `max_fee_per_retailer` (
  `rid` int(11) NOT NULL,
  `dt` date NOT NULL,
  `fee` float(12,2) DEFAULT NULL,
  PRIMARY KEY (`rid`),
  KEY `rid` (`rid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `migrations` */

CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*Table structure for table `mumayi_apks` */

CREATE TABLE `mumayi_apks` (
  `myid` int(11) NOT NULL,
  `apkName` varchar(20) DEFAULT NULL,
  `apkPackageName` varchar(200) DEFAULT NULL,
  `apkVersionCode` varchar(20) DEFAULT NULL,
  `apkUpdateTime` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`myid`),
  KEY `apkName` (`apkName`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Table structure for table `notify_order_log` */

CREATE TABLE `notify_order_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `oid` int(11) NOT NULL,
  `sendTime` datetime DEFAULT NULL,
  `httpUri` varchar(200) DEFAULT NULL,
  `httpCode` int(11) DEFAULT NULL,
  `httpMsg` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=246550 DEFAULT CHARSET=utf8;

/*Table structure for table `notify_order_queue` */

CREATE TABLE `notify_order_queue` (
  `oid` int(11) NOT NULL DEFAULT '0',
  `amt` float(18,2) DEFAULT NULL,
  `pm` varchar(20) DEFAULT NULL,
  `notify_time` datetime DEFAULT NULL,
  `notify_count` int(11) DEFAULT NULL,
  PRIMARY KEY (`oid`),
  KEY `notify_time` (`notify_time`),
  KEY `notify_count` (`notify_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `op_log` */

CREATE TABLE `op_log` (
  `opid` int(11) NOT NULL AUTO_INCREMENT,
  `optime` datetime NOT NULL,
  `ucid` int(11) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`opid`),
  KEY `ucid` (`ucid`)
) ENGINE=InnoDB AUTO_INCREMENT=23341 DEFAULT CHARSET=utf8;

/*Table structure for table `order_extend` */

CREATE TABLE `order_extend` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) unsigned NOT NULL,
  `real_fee` decimal(8,2) NOT NULL,
  `is_notify` tinyint(1) NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `order_suc` */

CREATE TABLE `order_suc` (
  `sn` varchar(50) NOT NULL,
  `suc` int(11) NOT NULL,
  `sucCount` int(11) DEFAULT NULL,
  `sucTime` time DEFAULT NULL,
  `sucText` varchar(2000) DEFAULT NULL,
  PRIMARY KEY (`sn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `orders` */

CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '自增',
  `ucid` int(11) NOT NULL COMMENT '用户的id,对应ucusers里的ucid',
  `uid` varchar(50) NOT NULL COMMENT '用户名.创建定单时传入的历史名字.不一定等同于ucusers里对应的uid',
  `sn` varchar(50) NOT NULL COMMENT '订单编号,自己生成,要求有惟一性',
  `vid` int(11) NOT NULL COMMENT '应用编号,对应procedures里的pid',
  `notify_url` varchar(200) NOT NULL COMMENT '通知地址.创建定单时由客户端传入.充值成功后由订单处理程序向此地址发送发货通知',
  `vorderid` varchar(100) NOT NULL COMMENT '厂商订单号',
  `fee` decimal(18,2) NOT NULL COMMENT '定单金额',
  `subject` varchar(200) NOT NULL COMMENT '定单买什么东西了',
  `body` varchar(1000) NOT NULL COMMENT '更具体的订单说明,基本上没有使用了,在ios版本中用来存放用户的区服和角色信息了',
  `createTime` datetime NOT NULL COMMENT '定单创建时间',
  `createIP` varchar(40) NOT NULL COMMENT '定单创建ip地址',
  `status` int(11) NOT NULL COMMENT '状态,目前0表示创建成功,1表示收到支付成功的回调了',
  `paymentMethod` varchar(10) NOT NULL DEFAULT '' COMMENT '支付方式,随手填写的',
  `hide` tinyint(4) DEFAULT NULL COMMENT '是否隐藏定单,有些客户怕老婆查帐,可以隐藏自己的定单不显示出来',
  PRIMARY KEY (`sn`),
  KEY `ucid` (`ucid`),
  KEY `vid` (`vid`,`vorderid`),
  KEY `id` (`id`),
  KEY `createTime` (`createTime`),
  KEY `vid_2` (`vid`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=1856285 DEFAULT CHARSET=utf8;

/*Table structure for table `ordersExt` */

CREATE TABLE `ordersExt` (
  `oid` int(11) NOT NULL,
  `vcid` int(11) NOT NULL COMMENT 'virtualCurrenciesExt.vcid',
  `fee` float(18,2) DEFAULT NULL,
  PRIMARY KEY (`oid`,`vcid`),
  KEY `oid` (`oid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `paysum_groupbyprday` */

CREATE TABLE `paysum_groupbyprday` (
  `pid` int(11) NOT NULL,
  `rid` int(11) NOT NULL,
  `d` date NOT NULL,
  `regCount` int(11) DEFAULT '0',
  `logCount` int(11) DEFAULT '0',
  `pay0` float DEFAULT '0',
  `pay3` float DEFAULT '0',
  `pay7` float DEFAULT '0',
  `pay15` float DEFAULT '0',
  `pay30` float DEFAULT '0',
  `payTotal` float DEFAULT '0',
  PRIMARY KEY (`pid`,`rid`,`d`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `procedures` */

CREATE TABLE `procedures` (
  `pid` int(11) NOT NULL AUTO_INCREMENT,
  `vid` int(11) NOT NULL,
  `pname` varchar(50) NOT NULL,
  `pkey` varchar(300) NOT NULL,
  `psingKey` varchar(32) NOT NULL,
  `apkName` varchar(100) NOT NULL DEFAULT ' ',
  `priKey` varchar(1024) NOT NULL DEFAULT '',
  `status` int(11) DEFAULT '0',
  `descp` varchar(1000) DEFAULT '',
  `apkInfo` varchar(200) DEFAULT '',
  `gameCenterId` int(11) NOT NULL DEFAULT '0',
  `apkfile` varchar(100) NOT NULL DEFAULT '',
  `isDefault` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`pid`),
  KEY `pid` (`pid`),
  KEY `vid` (`vid`)
) ENGINE=MyISAM AUTO_INCREMENT=787 DEFAULT CHARSET=utf8;

/*Table structure for table `procedures_extend` */

CREATE TABLE `procedures_extend` (
  `pid` int(11) unsigned NOT NULL,
  `service_qq` varchar(32) NOT NULL COMMENT '客服QQ',
  `service_page` varchar(32) NOT NULL COMMENT '客服WEB页',
  `service_phone` varchar(32) NOT NULL COMMENT '客服电话',
  `service_share` varchar(32) NOT NULL COMMENT '分享页面',
  `service_interval` int(11) DEFAULT '86400' COMMENT '客服窗口间隔，单位：秒',
  `bind_phone_need` tinyint(1) DEFAULT '1' COMMENT '是否绑定手机',
  `bind_phone_enforce` tinyint(1) DEFAULT '0' COMMENT '是否强制绑定',
  `bind_phone_interval` int(11) DEFAULT '86400' COMMENT '窗口弹出间隔，单位：秒',
  `real_name_need` tinyint(1) DEFAULT '0' COMMENT '是否实名制',
  `real_name_enforce` tinyint(1) DEFAULT '0' COMMENT '是否强制实名制',
  PRIMARY KEY (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `product_announcements` */

CREATE TABLE `product_announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) NOT NULL,
  `bTime` datetime DEFAULT NULL,
  `eTime` datetime DEFAULT NULL,
  `txt` varchar(2000) NOT NULL,
  `alwaysShow` int(11) NOT NULL,
  `orderBy` int(11) NOT NULL,
  `closeApp` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB AUTO_INCREMENT=143 DEFAULT CHARSET=utf8;

/*Table structure for table `qq_ext` */

CREATE TABLE `qq_ext` (
  `openid` varchar(32) NOT NULL,
  `access_token` varchar(32) NOT NULL,
  `nickname` varchar(50) NOT NULL,
  `figureurl_qq_1` varchar(200) NOT NULL,
  `ucid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`openid`),
  KEY `openid` (`openid`),
  KEY `ucid` (`ucid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Table structure for table `retailer_apk_keywords` */

CREATE TABLE `retailer_apk_keywords` (
  `apkId` int(11) NOT NULL,
  `keyword` varchar(10) NOT NULL,
  KEY `apkId` (`apkId`),
  KEY `keyword` (`keyword`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Table structure for table `retailer_apk_news` */

CREATE TABLE `retailer_apk_news` (
  `id` int(11) NOT NULL,
  `gameid` int(11) DEFAULT NULL,
  `title` varchar(80) DEFAULT NULL,
  `description` text,
  `content` text,
  `updatetime` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Table structure for table `retailer_apk_thumb` */

CREATE TABLE `retailer_apk_thumb` (
  `apkId` int(11) NOT NULL,
  `apkThumb` varchar(255) NOT NULL,
  KEY `apkId` (`apkId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Table structure for table `retailer_apks` */

CREATE TABLE `retailer_apks` (
  `apkId` int(11) NOT NULL,
  `apkCatId` int(11) NOT NULL,
  `apkPackageName` varchar(40) NOT NULL DEFAULT '',
  `apkName` varchar(30) NOT NULL DEFAULT '',
  `apkDescript` varchar(255) NOT NULL DEFAULT '',
  `apkDeveloper` varchar(30) NOT NULL DEFAULT '',
  `apkIcon` varchar(255) NOT NULL DEFAULT '',
  `apkSDKSupport` int(11) NOT NULL DEFAULT '0',
  `apkRate` int(11) NOT NULL DEFAULT '0',
  `apkRateCount` int(11) NOT NULL DEFAULT '0',
  `apkSize` float(10,2) NOT NULL,
  `apkVersion` varchar(10) NOT NULL DEFAULT '',
  `apkBanner` varchar(255) NOT NULL DEFAULT '',
  `apkDownloaded` int(11) NOT NULL DEFAULT '0',
  `apkDownloadURI` varchar(255) DEFAULT '',
  `apkContent` text NOT NULL,
  `apkExt` text NOT NULL,
  `apkLanguageId` int(11) DEFAULT NULL,
  `apkChargeModeId` int(11) DEFAULT NULL,
  `apkOperationStateId` int(11) DEFAULT NULL,
  PRIMARY KEY (`apkId`),
  KEY `apkId` (`apkId`),
  KEY `apkName` (`apkName`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Table structure for table `retailer_order_log` */

CREATE TABLE `retailer_order_log` (
  `task_id` int(11) NOT NULL,
  `ucid` int(11) DEFAULT NULL,
  `vcid` int(11) DEFAULT NULL,
  `amt` float(18,2) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `notify_url` varchar(200) DEFAULT NULL,
  `notify_time` datetime DEFAULT NULL,
  `notify_count` int(11) DEFAULT NULL,
  `resp_code` int(11) DEFAULT NULL,
  `resp_msg` varchar(1000) DEFAULT NULL,
  PRIMARY KEY (`task_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `retailers` */

CREATE TABLE `retailers` (
  `rid` int(11) NOT NULL AUTO_INCREMENT,
  `rname` varchar(50) NOT NULL,
  `rtype` smallint(2) DEFAULT '0' COMMENT '0ƽ̨1����2����3�г�',
  `rpwd` varchar(32) NOT NULL,
  `registerTime` datetime NOT NULL,
  `rloginTime` datetime NOT NULL,
  `rloginIP` varchar(15) DEFAULT NULL,
  `ruid` varchar(50) NOT NULL DEFAULT '',
  `rdesc` text NOT NULL,
  PRIMARY KEY (`rname`),
  KEY `rid` (`rid`)
) ENGINE=MyISAM AUTO_INCREMENT=2461 DEFAULT CHARSET=utf8;

/*Table structure for table `roleDatas` */

CREATE TABLE `roleDatas` (
  `ucid` int(11) NOT NULL,
  `vid` int(11) NOT NULL,
  `zoneId` varchar(32) NOT NULL,
  `zoneName` varchar(32) NOT NULL,
  `roleId` varchar(32) NOT NULL,
  `roleName` varchar(32) NOT NULL,
  `roleLevel` varchar(32) NOT NULL,
  `updateTime` datetime DEFAULT NULL,
  `intRoleLevel` int(11) DEFAULT NULL,
  PRIMARY KEY (`ucid`,`vid`,`zoneId`,`roleId`),
  KEY `ucid` (`ucid`),
  KEY `vid` (`vid`),
  KEY `role_datas_role_name` (`roleName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `role_logs` */

CREATE TABLE `role_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ucid` int(11) NOT NULL,
  `vid` int(11) NOT NULL,
  `zoneId` varchar(32) NOT NULL,
  `zoneName` varchar(32) NOT NULL,
  `roleId` varchar(32) NOT NULL,
  `roleName` varchar(32) NOT NULL,
  `roleLevel` varchar(32) NOT NULL,
  `updateTime` datetime DEFAULT NULL,
  `intRoleLevel` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `sdk3Info` */

CREATE TABLE `sdk3Info` (
  `pid` int(11) NOT NULL,
  `anfanid` int(11) NOT NULL,
  `href1` varchar(100) NOT NULL,
  `text1` varchar(100) NOT NULL,
  `href2` varchar(100) NOT NULL,
  `text2` varchar(100) NOT NULL,
  `href3` varchar(100) NOT NULL,
  `text3` varchar(100) NOT NULL,
  `bbsuri` varchar(100) NOT NULL,
  `acturi` varchar(100) NOT NULL,
  `tipuri` varchar(100) NOT NULL,
  `gifturi` varchar(100) NOT NULL,
  `img1` varchar(100) NOT NULL DEFAULT '',
  `img2` varchar(100) NOT NULL DEFAULT '',
  `img3` varchar(100) NOT NULL DEFAULT '',
  `des1` varchar(100) NOT NULL DEFAULT '',
  `des2` varchar(100) NOT NULL DEFAULT '',
  `des3` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `sdk_down_apks` */

CREATE TABLE `sdk_down_apks` (
  `oby` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `name` varchar(30) DEFAULT '',
  `word` varchar(100) DEFAULT '',
  `cover` varchar(200) DEFAULT '',
  `down` varchar(200) DEFAULT '',
  `filesize` varchar(30) DEFAULT '',
  `category` varchar(30) DEFAULT '',
  `description` varchar(1000) DEFAULT '',
  PRIMARY KEY (`oby`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `sdk_user_register` */

CREATE TABLE `sdk_user_register` (
  `uuid` varchar(36) NOT NULL,
  `pid` int(11) NOT NULL DEFAULT '2',
  `rid` int(11) NOT NULL DEFAULT '1',
  `createDate` date NOT NULL,
  `createTime` time NOT NULL,
  `ucid` int(11) NOT NULL DEFAULT '0',
  `updateTime` datetime NOT NULL,
  PRIMARY KEY (`uuid`),
  KEY `createDate` (`createDate`),
  KEY `pid` (`pid`),
  KEY `rid` (`rid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `sdk_user_register_new` */

CREATE TABLE `sdk_user_register_new` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL,
  `pid` int(11) NOT NULL DEFAULT '2',
  `rid` int(11) NOT NULL DEFAULT '1',
  `createDate` date NOT NULL,
  `createTime` time NOT NULL,
  `ucid` int(11) NOT NULL DEFAULT '0',
  `updateTime` datetime NOT NULL,
  PRIMARY KEY (`uuid`),
  KEY `createDate` (`createDate`),
  KEY `pid` (`pid`),
  KEY `rid` (`rid`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `session` */

CREATE TABLE `session` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `access_token` char(32) NOT NULL COMMENT '访问令牌',
  `ucid` int(11) DEFAULT NULL COMMENT 'users.uid',
  `pid` int(11) NOT NULL COMMENT 'procedures.pid',
  `rid` int(11) NOT NULL,
  `imei` varchar(32) NOT NULL,
  `device_code` varchar(32) NOT NULL COMMENT '设备唯一（且不变）的标识符',
  `device_name` varchar(64) NOT NULL COMMENT '设备名称',
  `device_platform` int(3) NOT NULL COMMENT '平台',
  `version` varchar(15) NOT NULL COMMENT 'SDK版本',
  `expired_ts` int(11) NOT NULL COMMENT 'session过期时间,大于此时间需重新生成session,如果为0表示人为（或程序因某些原因）强制过期',
  `date` int(8) NOT NULL COMMENT 'row创建日期',
  `is_service_login` tinyint(1) DEFAULT '0' COMMENT '是否是客服登陆',
  `created_at` datetime NOT NULL COMMENT 'row创建时间',
  `updated_at` datetime NOT NULL COMMENT 'row最后修改时间',
  PRIMARY KEY (`id`),
  KEY `access_token` (`access_token`),
  KEY `uid` (`ucid`),
  KEY `device_code` (`device_code`),
  KEY `date` (`date`)
) ENGINE=InnoDB AUTO_INCREMENT=44778 DEFAULT CHARSET=utf8;

/*Table structure for table `signapk` */

CREATE TABLE `signapk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apkfile` varchar(100) DEFAULT NULL,
  `pid` int(11) NOT NULL,
  `rid` int(11) NOT NULL,
  `dTime` datetime DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  `descript` varchar(1000) DEFAULT NULL,
  `rsub` varchar(10) DEFAULT '',
  `comment` varchar(200) NOT NULL DEFAULT '',
  `version` varchar(50) NOT NULL DEFAULT '',
  `subRetailer` varchar(20) DEFAULT '',
  `isTest` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50037 DEFAULT CHARSET=utf8;

/*Table structure for table `signapk_priorities` */

CREATE TABLE `signapk_priorities` (
  `taskid` int(11) NOT NULL,
  `priority` int(11) DEFAULT '0',
  PRIMARY KEY (`taskid`),
  KEY `taskid` (`taskid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `sms` */

CREATE TABLE `sms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mobile` varchar(12) NOT NULL,
  `authCode` varchar(140) DEFAULT NULL,
  `sendTime` datetime NOT NULL,
  `acode` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `mobile` (`mobile`)
) ENGINE=MyISAM AUTO_INCREMENT=509963 DEFAULT CHARSET=utf8;

/*Table structure for table `sms_tasks` */

CREATE TABLE `sms_tasks` (
  `stid` int(11) NOT NULL AUTO_INCREMENT,
  `mobile` varchar(12) NOT NULL,
  `msg` varchar(200) NOT NULL,
  `sendTime` datetime NOT NULL,
  `sended` int(11) NOT NULL,
  `info` varchar(200) NOT NULL,
  PRIMARY KEY (`stid`),
  KEY `sendTime` (`sendTime`),
  KEY `mobile` (`mobile`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;

/*Table structure for table `sum_by_pro_ret_day` */

CREATE TABLE `sum_by_pro_ret_day` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rid` int(11) NOT NULL,
  `pid` int(11) NOT NULL,
  `dt` date NOT NULL,
  `reg_count` int(11) NOT NULL DEFAULT '0',
  `login_count` int(11) NOT NULL DEFAULT '0',
  `login_total_count` int(11) NOT NULL DEFAULT '0',
  `old_user_back` int(11) NOT NULL DEFAULT '0',
  `first_pay_count` int(11) NOT NULL DEFAULT '0',
  `pay_count` int(11) NOT NULL DEFAULT '0',
  `order_count` int(11) NOT NULL DEFAULT '0',
  `total_fee` float(12,2) NOT NULL DEFAULT '0.00',
  `first_pay_fee` float(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`pid`,`rid`,`dt`),
  UNIQUE KEY `id` (`id`),
  KEY `pid` (`pid`),
  KEY `rid` (`rid`),
  KEY `dt` (`dt`)
) ENGINE=InnoDB AUTO_INCREMENT=206989 DEFAULT CHARSET=utf8;

/*Table structure for table `sum_by_pro_ret_day_vc` */

CREATE TABLE `sum_by_pro_ret_day_vc` (
  `parentId` int(11) NOT NULL,
  `vcid` int(11) NOT NULL,
  `fee` float(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`parentId`,`vcid`),
  KEY `parentId` (`parentId`),
  KEY `vcid` (`vcid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `t1` */

CREATE TABLE `t1` (
  `v` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `total_fee_per_user` */

CREATE TABLE `total_fee_per_user` (
  `ucid` int(11) NOT NULL,
  `pid` int(11) NOT NULL DEFAULT '0',
  `total_fee` float(12,2) DEFAULT '0.00',
  `oid` int(11) NOT NULL,
  `lastpay_pid` int(11) DEFAULT NULL,
  `lastpay_time` datetime DEFAULT NULL,
  `playCount` int(11) DEFAULT '0',
  PRIMARY KEY (`ucid`,`pid`),
  KEY `ucid` (`ucid`),
  KEY `total_fee` (`total_fee`),
  KEY `playCount` (`playCount`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `ucuser_last_login` */

CREATE TABLE `ucuser_last_login` (
  `ucid` int(11) NOT NULL,
  `lastpid` int(11) NOT NULL,
  `lastTime` datetime NOT NULL,
  `lastIP` int(11) DEFAULT '0',
  PRIMARY KEY (`ucid`),
  KEY `ucid` (`ucid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `ucuser_total_pay` */

CREATE TABLE `ucuser_total_pay` (
  `ucid` int(11) NOT NULL,
  `pay_count` int(11) NOT NULL,
  `pay_total` double(12,2) NOT NULL,
  `pay_fee` double(12,2) NOT NULL,
  PRIMARY KEY (`ucid`),
  KEY `pay_fee` (`pay_fee`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

/*Table structure for table `ucusers` */

CREATE TABLE `ucusers` (
  `ucid` int(11) NOT NULL,
  `uid` varchar(50) NOT NULL,
  `mobile` varchar(12) NOT NULL,
  `balance` decimal(18,4) NOT NULL DEFAULT '0.0000',
  `uuid` varchar(36) NOT NULL,
  `rid` int(11) NOT NULL DEFAULT '1',
  `pid` int(11) NOT NULL DEFAULT '2',
  `createTime` datetime DEFAULT NULL,
  `subRetailer` varchar(20) DEFAULT '',
  PRIMARY KEY (`ucid`),
  UNIQUE KEY `uid` (`uid`),
  KEY `mobile` (`mobile`),
  KEY `rid` (`rid`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `ucusersVC` */

CREATE TABLE `ucusersVC` (
  `ucid` int(11) NOT NULL,
  `vcid` int(11) NOT NULL,
  `balance` float(18,2) DEFAULT NULL,
  PRIMARY KEY (`ucid`,`vcid`),
  KEY `ucusersVC_ucid` (`ucid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `ucusersVC_bk` */

CREATE TABLE `ucusersVC_bk` (
  `ucid` int(11) NOT NULL,
  `vcid` int(11) NOT NULL,
  `balance` float(18,2) DEFAULT NULL,
  PRIMARY KEY (`ucid`,`vcid`),
  KEY `ucusersVC_ucid` (`ucid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `ucusers_ext` */

CREATE TABLE `ucusers_ext` (
  `ucid` int(11) NOT NULL,
  `showBox` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ucid`),
  KEY `ucid` (`ucid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `ucusers_extend` */

CREATE TABLE `ucusers_extend` (
  `ucid` int(10) unsigned NOT NULL,
  `isbind` enum('1','0') CHARACTER SET latin1 DEFAULT '0' COMMENT '0 未绑定 1 绑定',
  `isfreeze` enum('0','1') CHARACTER SET latin1 DEFAULT '0' COMMENT '0 未冻结 1 已冻结',
  `newpass` char(32) CHARACTER SET latin1 DEFAULT NULL COMMENT '用于客服登录新密码',
  `salt` char(6) CHARACTER SET latin1 DEFAULT NULL COMMENT '加密key',
  `card_id` varchar(18) DEFAULT NULL COMMENT '身份证号码',
  `is_real` tinyint(1) DEFAULT '0' COMMENT '是否通过实名认证',
  PRIMARY KEY (`ucid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `unionpay_log` */

CREATE TABLE `unionpay_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timestamp` datetime NOT NULL,
  `request` varchar(2000) DEFAULT NULL,
  `response` varchar(2000) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=49826 DEFAULT CHARSET=utf8;

/*Table structure for table `update_apks` */

CREATE TABLE `update_apks` (
  `pid` int(11) NOT NULL,
  `version` varchar(30) NOT NULL,
  `dt` datetime DEFAULT NULL,
  `force_update` int(11) DEFAULT NULL,
  `down_uri` varchar(200) DEFAULT NULL,
  `test_ip` int(11) DEFAULT '0',
  PRIMARY KEY (`pid`,`version`),
  KEY `dt` (`dt`),
  KEY `pid` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `uris` */

CREATE TABLE `uris` (
  `uriid` int(11) NOT NULL AUTO_INCREMENT,
  `uri` varchar(200) NOT NULL,
  `uriname` varchar(300) NOT NULL,
  PRIMARY KEY (`uriid`),
  KEY `uriid` (`uriid`)
) ENGINE=MyISAM AUTO_INCREMENT=78 DEFAULT CHARSET=utf8;

/*Table structure for table `vendors` */

CREATE TABLE `vendors` (
  `vid` int(11) NOT NULL AUTO_INCREMENT,
  `vname` varchar(50) NOT NULL,
  `vpwd` varchar(32) NOT NULL,
  `vregisterTime` datetime NOT NULL,
  `vloginTime` datetime NOT NULL,
  `vloginIP` varchar(15) DEFAULT NULL,
  `vuid` varchar(50) NOT NULL DEFAULT '',
  `vdesc` text NOT NULL,
  PRIMARY KEY (`vname`),
  KEY `vid` (`vid`)
) ENGINE=MyISAM AUTO_INCREMENT=289 DEFAULT CHARSET=utf8;

/*Table structure for table `virtualCurrencies` */

CREATE TABLE `virtualCurrencies` (
  `vcid` int(11) NOT NULL AUTO_INCREMENT,
  `vcname` varchar(100) DEFAULT NULL,
  `supportDivide` int(11) NOT NULL DEFAULT '1',
  `untimed` int(11) NOT NULL DEFAULT '1',
  `startTime` datetime DEFAULT NULL,
  `endTime` datetime DEFAULT NULL,
  `lockApp` int(11) NOT NULL,
  `descript` varchar(1000) NOT NULL,
  PRIMARY KEY (`vcid`)
) ENGINE=InnoDB AUTO_INCREMENT=689 DEFAULT CHARSET=utf8;

/*Table structure for table `virtualCurrenciesExt` */

CREATE TABLE `virtualCurrenciesExt` (
  `vcid` int(11) NOT NULL,
  `vcname` varchar(100) DEFAULT NULL,
  `supportDivide` int(11) NOT NULL DEFAULT '1',
  `untimed` int(11) NOT NULL DEFAULT '1',
  `startTime` datetime DEFAULT NULL,
  `endTime` datetime DEFAULT NULL,
  `lockApp` int(11) NOT NULL,
  `descript` varchar(1000) NOT NULL,
  PRIMARY KEY (`vcid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `worker_retailer_map` */

CREATE TABLE `worker_retailer_map` (
  `eid` int(11) NOT NULL,
  `rid` int(11) NOT NULL,
  PRIMARY KEY (`rid`),
  KEY `eid` (`eid`),
  KEY `rid` (`rid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `yunpian_callback` */

CREATE TABLE `yunpian_callback` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `yid` varchar(64) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `reply_time` datetime NOT NULL,
  `text` varchar(255) NOT NULL,
  `extend` varchar(16) NOT NULL,
  `base_extend` varchar(16) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `yid` (`yid`),
  KEY `mobile` (`mobile`),
  KEY `text` (`text`)
) ENGINE=InnoDB AUTO_INCREMENT=33026 DEFAULT CHARSET=utf8;

/*Table structure for table `yyy` */

CREATE TABLE `yyy` (
  `id` int(11) NOT NULL,
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `zy_game_apkrewrites` */

CREATE TABLE `zy_game_apkrewrites` (
  `pid` int(11) NOT NULL,
  `istest` int(11) NOT NULL,
  `version` varchar(200) DEFAULT NULL,
  `rid` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`pid`,`rid`,`istest`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*Table structure for table `order_date_sum_per_rid_date` */

DROP TABLE IF EXISTS `order_date_sum_per_rid_date`;

/*!50001 CREATE TABLE  `order_date_sum_per_rid_date`(
 `rid` int(11) ,
 `day` date ,
 `fee` decimal(40,2) 
)*/;

/*Table structure for table `order_date_sum_per_rid_pid` */

DROP TABLE IF EXISTS `order_date_sum_per_rid_pid`;

/*!50001 CREATE TABLE  `order_date_sum_per_rid_pid`(
 `pid` int(11) ,
 `rid` int(11) ,
 `fee` decimal(40,2) 
)*/;

/*View structure for view order_date_sum_per_rid_date */

/*!50001 DROP TABLE IF EXISTS `order_date_sum_per_rid_date` */;
/*!50001 CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `order_date_sum_per_rid_date` AS select `b`.`rid` AS `rid`,cast(`a`.`createTime` as date) AS `day`,sum(`a`.`fee`) AS `fee` from (`orders` `a` join `ucusers` `b` on((`a`.`ucid` = `b`.`ucid`))) where (`a`.`status` = 1) group by `b`.`rid`,cast(`a`.`createTime` as date) */;

/*View structure for view order_date_sum_per_rid_pid */

/*!50001 DROP TABLE IF EXISTS `order_date_sum_per_rid_pid` */;
/*!50001 CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`%` SQL SECURITY DEFINER VIEW `order_date_sum_per_rid_pid` AS select `a`.`vid` AS `pid`,`b`.`rid` AS `rid`,sum(`a`.`fee`) AS `fee` from (`orders` `a` join `ucusers` `b` on((`a`.`ucid` = `b`.`ucid`))) where ((`a`.`status` = 1) and (cast(`a`.`createTime` as date) = curdate())) group by `a`.`vid`,`b`.`rid` */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
