
update `config` set value = '2025-7-1 13:00:00' where code = 'setup_date';
update `config` set value = '6e7e7a6555e6b832bdfa01f5de72fd35' where code = 'site_uniqid';

INSERT INTO `admin` (`admin_name`, `admin_password`, `admin_is_super`, `admin_gid`) VALUES('admin', 'e10adc3949ba59abbe56e057f20f883e', '1', '0');

INSERT INTO `member` (`member_id`,`member_name`,`member_password`,`member_nickname`,`member_email`,`member_addtime`,`member_logintime`,`member_old_logintime`) VALUES ('1', 'demo', 'e10adc3949ba59abbe56e057f20f883e', 'demo_123456', '', '1751437788', '1751437788', '1751437788');
INSERT INTO `store` (`store_id`,`store_name`,`grade_id`,`member_id`,`member_name`,`seller_name`,`store_state`,`store_addtime`) VALUES ('1', '演示站', '1', '1', 'demo', 'demo', '1', '1751437788');
INSERT INTO `storejoinin` (`member_id`,`member_name`,`seller_name`,`store_name`,`joinin_state`) VALUES ('1', 'demo', 'demo', '演示站', '40');
INSERT INTO `seller` (`seller_id`,`seller_name`,`member_id`,`sellergroup_id`,`store_id`,`is_admin`) VALUES ('1', 'demo', '1', '0', '1', '1');
INSERT INTO `storebindclass` (`storebindclass_id`, `store_id`, `commis_rate`, `class_1`, `class_2`, `class_3`, `storebindclass_state`) VALUES ('1', '1', '0', '0', '0', '0', '1');


update `goods` set is_platform_store = 1 where store_id = 1;
update `goodscommon` set is_platform_store = 1 where store_id = 1;
update `store` set is_platform_store = 1 where store_id = 1;
update `store` set bind_all_gc = 1 where store_id = 1;


update `goods` set store_name = '演示站';
update `goodscommon` set store_name = '演示站';
