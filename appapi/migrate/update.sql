CREATE TABLE `ucusers_extend` (
  `uid` INT(10) UNSIGNED NOT NULL,
  `isbind` ENUM('1','0') CHARACTER SET latin1 DEFAULT '0' COMMENT '0 未绑定 1 绑定',
  `isfreeze` ENUM('0','1') CHARACTER SET latin1 DEFAULT '0' COMMENT '0 未冻结 1 已冻结',
  `newpass` CHAR(32) CHARACTER SET latin1 DEFAULT NULL COMMENT '用于客服登录新密码',
  `salt` CHAR(6) CHARACTER SET latin1 DEFAULT NULL COMMENT '加密key',
  `card_id` VARCHAR(18) DEFAULT NULL COMMENT '身份证号码',
  `is_reveal` TINYINT(1) DEFAULT '0' COMMENT '是否通过实名认证',
  `vip` TINYINT(2) DEFAULT '0' COMMENT 'vip等级',
  PRIMARY KEY (`uid`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

CREATE TABLE `yunpian_sms` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `yid` VARCHAR(64) NOT NULL,
  `mobile` VARCHAR(15) NOT NULL,
  `reply_time` DATETIME NOT NULL,
  `text` VARCHAR(255) NOT NULL,
  `extend` VARCHAR(16) NOT NULL,
  `base_extend` VARCHAR(16) NOT NULL,
  `created_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `yid` (`yid`),
  KEY `mobile` (`mobile`),
  KEY `text` (`text`)
) ENGINE=INNODB AUTO_INCREMENT=10880 DEFAULT CHARSET=utf8;