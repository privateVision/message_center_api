ALTER TABLE `procedures_extend` CHANGE `third_appsecret` `third_appsecret` VARCHAR(1024) CHARSET utf8 COLLATE utf8_general_ci NULL, CHANGE `third_payprikey` `third_payprikey` VARCHAR(1024) CHARSET utf8 COLLATE utf8_general_ci NULL;

ALTER TABLE `anfanapi`.`procedures_extend`
CHANGE `enable` `enable` INT(11) NULL   COMMENT '使能寄存器：\n[0]是否在登陆时提示实名\n[1]是否在登陆时提示且强制实名\n[2]是否在支付时提示实名\n[3]是否在支付时提示且强制实名\n[4]是否绑定手机\n[5]是否强制绑定手机\n[6]是否使用安锋登陆\n[7]是否处于测试模式\n[8]是否开启安锋支付\n[9]是否禁用F币功能',
CHANGE `pay_method` `pay_method` INT(11) DEFAULT 1  NULL   COMMENT '支付方式寄存器：\n每4位代表一种支付，4位中每位表示该支付方式的支付类型\n[0~3]微信\n[4~7]支付宝\n[8~11]银联\n[12~15]mycard\n[16~19]现代支付微信';

ALTER TABLE `anfanapi`.`procedures_extend`   
  ADD COLUMN `sandbox_version` VARCHAR(20) NULL   COMMENT '指定要开启沙盒模式的版本号，格式>=1.0   >1.0   =1.0   <1.0   <=1.0   1.0' AFTER `enable`;
  
ALTER TABLE `anfanapi`.`procedures_extend`   
  CHANGE `sandbox_version` `test_version` VARCHAR(20) CHARSET utf8 COLLATE utf8_general_ci NULL   COMMENT '指定要开启测试模式的版本号，多个版本号之间竖线分隔\n1.0|1.1|1.2|1.7';