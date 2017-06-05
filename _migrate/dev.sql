/**20170602修改表字段类型【未上线】**/
ALTER TABLE `procedures_extend` CHANGE `third_appsecret` `third_appsecret` VARCHAR(1024) CHARSET utf8 COLLATE utf8_general_ci NULL, CHANGE `third_payprikey` `third_payprikey` VARCHAR(1024) CHARSET utf8 COLLATE utf8_general_ci NULL;

INSERT INTO `anfanapi`.`virtualCurrenciesExt`(`vcid`,`vcname`,`supportDivide`,`untimed`,`startTime`,`endTime`,`lockApp`,`descript`) VALUES ( '-12','华为','1','1',NULL,NULL,'0','华为平台支付');
INSERT INTO `anfanapi`.`virtualCurrenciesExt`(`vcid`,`vcname`,`supportDivide`,`untimed`,`startTime`,`endTime`,`lockApp`,`descript`) VALUES ( '-13','ViVo','1','1',NULL,NULL,'0','vivo平台支付');
INSERT INTO `anfanapi`.`virtualCurrenciesExt`(`vcid`,`vcname`,`supportDivide`,`untimed`,`startTime`,`endTime`,`lockApp`,`descript`) VALUES ( '-14','Lenovo','1','1',NULL,NULL,'0','联想平台支付');
INSERT INTO `anfanapi`.`virtualCurrenciesExt`(`vcid`,`vcname`,`supportDivide`,`untimed`,`startTime`,`endTime`,`lockApp`,`descript`) VALUES ( '-15','金立','1','1',NULL,NULL,'0','金立平台支付');
INSERT INTO `anfanapi`.`virtualCurrenciesExt`(`vcid`,`vcname`,`supportDivide`,`untimed`,`startTime`,`endTime`,`lockApp`,`descript`) VALUES ( '-16','Oppo','1','1',NULL,NULL,'0','Oppo平台支付');
INSERT INTO `anfanapi`.`virtualCurrenciesExt`(`vcid`,`vcname`,`supportDivide`,`untimed`,`startTime`,`endTime`,`lockApp`,`descript`) VALUES ( '-17','小米','1','1',NULL,NULL,'0','小米平台支付');
INSERT INTO `anfanapi`.`virtualCurrenciesExt`(`vcid`,`vcname`,`supportDivide`,`untimed`,`startTime`,`endTime`,`lockApp`,`descript`) VALUES ( '-18','乐视','1','1',NULL,NULL,'0','乐视平台支付');
