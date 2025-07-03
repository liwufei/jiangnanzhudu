-- phpMyAdmin SQL Dump
-- version 5.0.4
-- https://www.phpmyadmin.net/
--
-- 主机： localhost
-- 生成日期： 2023-03-06 12:42:52
-- 服务器版本： 5.6.50-log
-- PHP 版本： 7.2.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库: `shopwind`
--

-- --------------------------------------------------------

--
-- 表的结构 `swd_acategory`
--
DROP TABLE IF EXISTS `swd_acategory`;
CREATE TABLE IF NOT EXISTS `swd_acategory` (
  `cate_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cate_name` varchar(100) NOT NULL DEFAULT '',
  `parent_id` int(10) unsigned DEFAULT '0',
  `store_id` int(10) DEFAULT '0',
  `sort_order` tinyint(3) unsigned DEFAULT '255',
  `if_show` int(1) DEFAULT '1',
  PRIMARY KEY (`cate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_address`
--
DROP TABLE IF EXISTS `swd_address`;
CREATE TABLE IF NOT EXISTS `swd_address` (
  `addr_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `consignee` varchar(60) NOT NULL DEFAULT '',
  `region_id` int(10) unsigned NOT NULL DEFAULT '0',
  `address` varchar(255) NOT NULL DEFAULT '',
  `phone_tel` varchar(60) DEFAULT '',
  `phone_mob` varchar(20) DEFAULT '',
  `latitude` varchar(30) DEFAULT '',
  `longitude` varchar(30) DEFAULT '',
  `defaddr` tinyint(3) DEFAULT '0',
  `label` varchar(10) DEFAULT '',
  PRIMARY KEY (`addr_id`),
  KEY `userid` (`userid`),
  KEY `region_id` (`region_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_appbuylog`
--
DROP TABLE IF EXISTS `swd_appbuylog`;
CREATE TABLE IF NOT EXISTS `swd_appbuylog` (
  `bid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `orderId` varchar(20) NOT NULL,
  `appid` varchar(20) NOT NULL,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `period` int(11) DEFAULT '0',
  `amount` decimal(10,2) DEFAULT '0',
  `status` tinyint(3) DEFAULT '0',
  `add_time` int(11) DEFAULT NULL,
  `pay_time` int(11) DEFAULT NULL,
  `end_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`bid`),
  KEY `bid` (`bid`),
  KEY `orderId` (`orderId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_appmarket`
--
DROP TABLE IF EXISTS `swd_appmarket`;
CREATE TABLE IF NOT EXISTS `swd_appmarket` (
  `aid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `appid` varchar(20) NOT NULL,
  `title` varchar(100) DEFAULT '',
  `summary` varchar(255) DEFAULT NULL,
  `category` int(11) DEFAULT '0',
  `description` text DEFAULT NULL,
  `logo` varchar(200) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT '0.00',
  `sales` int(11) DEFAULT '0',
  `views` int(11) DEFAULT '0',
  `status` tinyint(1) DEFAULT '0',
  `add_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`aid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_apprenewal`
--
DROP TABLE IF EXISTS `swd_apprenewal`;
CREATE TABLE IF NOT EXISTS `swd_apprenewal` (
  `rid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `appid` varchar(20) NOT NULL,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `add_time` int(11) unsigned DEFAULT NULL,
  `expired` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`rid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_article`
--
DROP TABLE IF EXISTS `swd_article`;
CREATE TABLE IF NOT EXISTS `swd_article` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) DEFAULT '',
  `cate_id` int(10) DEFAULT '0',
  `store_id` int(10) unsigned DEFAULT '0',
  `link` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sort_order` tinyint(3) unsigned DEFAULT '255',
  `if_show` tinyint(3) unsigned DEFAULT '1',
  `add_time` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cate_id` (`cate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_bank`
--
DROP TABLE IF EXISTS `swd_bank`;
CREATE TABLE IF NOT EXISTS `swd_bank` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `bank` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `account` varchar(50) NOT NULL,
  `area` varchar(100) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_bind`
--
DROP TABLE IF EXISTS `swd_bind`;
CREATE TABLE IF NOT EXISTS `swd_bind` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `unionid` varchar(255) NOT NULL,
  `openid` varchar(255) DEFAULT '',
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `code` varchar(50) DEFAULT '',
  `nickname` varchar(60) DEFAULT '',
  `enabled` int(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_brand`
--
DROP TABLE IF EXISTS `swd_brand`;
CREATE TABLE IF NOT EXISTS `swd_brand` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT '',
  `logo` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `cate_id` int(11) DEFAULT '0',
  `sort_order` tinyint(3) unsigned DEFAULT '255',
  `recommended` tinyint(3) unsigned DEFAULT '0',
  `store_id` int(10) unsigned DEFAULT '0',
  `if_show` tinyint(2) unsigned DEFAULT '1',
  `tag` varchar(100) DEFAULT '',
  `letter` varchar(10) DEFAULT '',
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_cart`
--
DROP TABLE IF EXISTS `swd_cart`;
CREATE TABLE IF NOT EXISTS `swd_cart` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `store_id` int(10) unsigned NOT NULL DEFAULT '0',
  `goods_id` int(10) unsigned NOT NULL DEFAULT '0',
  `goods_name` varchar(255) DEFAULT '',
  `spec_id` int(10) unsigned DEFAULT '0',
  `specification` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) unsigned DEFAULT '0.00',
  `quantity` int(10) unsigned DEFAULT '1',
  `goods_image` varchar(255) DEFAULT NULL,
  `selected` tinyint(1) unsigned DEFAULT '0',
  `product_id` varchar(32) DEFAULT '',
  `gtype` varchar(20) DEFAULT 'material',
  `invalid` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_cashcard`
--
DROP TABLE IF EXISTS `swd_cashcard`;
CREATE TABLE IF NOT EXISTS `swd_cashcard` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(30) DEFAULT NULL,
  `cardNo` varchar(30) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `money` decimal(10,2) DEFAULT NULL,
  `useId` int(11) unsigned DEFAULT '0',
  `printed` int(1) unsigned DEFAULT '0',
  `add_time` int(11) DEFAULT NULL,
  `active_time` int(11) DEFAULT NULL,
  `expire_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_category_goods`
--
DROP TABLE IF EXISTS `swd_category_goods`;
CREATE TABLE IF NOT EXISTS `swd_category_goods` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cate_id` int(10) unsigned DEFAULT '0',
  `goods_id` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `cate_id` (`cate_id`),
  KEY `goods_id` (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_category_store`
--
DROP TABLE IF EXISTS `swd_category_store`;
CREATE TABLE IF NOT EXISTS `swd_category_store` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cate_id` int(10) unsigned DEFAULT '0',
  `store_id` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `cate_id` (`cate_id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_cate_pvs`
--
DROP TABLE IF EXISTS `swd_cate_pvs`;
CREATE TABLE IF NOT EXISTS `swd_cate_pvs` (
  `cate_id` int(11) NOT NULL,
  `pvs` text DEFAULT '',
  PRIMARY KEY (`cate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_channel`
--
DROP TABLE IF EXISTS `swd_channel`;
CREATE TABLE IF NOT EXISTS `swd_channel` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cid` varchar(20) DEFAULT '',
  `title` varchar(50) DEFAULT '',
  `style` int(11) DEFAULT '1',
  `cate_id` int(11) DEFAULT '0',
  `status` int(11) DEFAULT '1',
  `add_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_cod`
--
DROP TABLE IF EXISTS `swd_cod`;
CREATE TABLE IF NOT EXISTS `swd_cod` (
  `store_id` int(10) NOT NULL,
  `regions` text,
  `status` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_collect`
--
DROP TABLE IF EXISTS `swd_collect`;
CREATE TABLE IF NOT EXISTS `swd_collect` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `type` varchar(10) DEFAULT 'goods',
  `item_id` int(10) unsigned DEFAULT '0',
  `keyword` varchar(60) DEFAULT NULL,
  `add_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `item_id` (`item_id`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_coupon`
--
DROP TABLE IF EXISTS `swd_coupon`;
CREATE TABLE IF NOT EXISTS `swd_coupon` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned DEFAULT '0',
  `name` varchar(100) DEFAULT '',
  `money` decimal(10,2) unsigned DEFAULT '0.00',
  `use_times` int(10) unsigned DEFAULT '1',
  `start_time` int(10) unsigned DEFAULT NULL,
  `end_time` int(10) unsigned DEFAULT NULL,
  `amount` decimal(10,2) unsigned DEFAULT '0.00',
  `available` int(11) DEFAULT '1',
  `image` varchar(255) DEFAULT NULL,
  `total` int(11) DEFAULT '0',
  `surplus` int(11) DEFAULT '0',
  `received` tinyint(1) DEFAULT '0' COMMENT '点击领取',
  `items` text DEFAULT '' COMMENT '指定商品可用',
  PRIMARY KEY (`id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_coupon_sn`
--
DROP TABLE IF EXISTS `swd_coupon_sn`;
CREATE TABLE IF NOT EXISTS `swd_coupon_sn` (
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `coupon_sn` varchar(20) NOT NULL DEFAULT '',
  `coupon_id` int(10) unsigned NOT NULL DEFAULT '0',
  `remain_times` int(10) unsigned DEFAULT '0',
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`coupon_sn`),
  KEY `coupon_id` (`coupon_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_delivery_template`
--
DROP TABLE IF EXISTS `swd_delivery_template`;
CREATE TABLE IF NOT EXISTS `swd_delivery_template` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(20) DEFAULT '',
  `store_id` int(10) DEFAULT '0',
  `basemoney` int(11) DEFAULT 0 COMMENT '同城起送金额',
  `type` varchar(10) DEFAULT 0 COMMENT '快递或同城',
  `label` varchar(10) DEFAULT '',
  `rules` text DEFAULT '',
  `enabled` int(1) DEFAULT '0',
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_delivery_timer`
--
DROP TABLE IF EXISTS `swd_delivery_timer`;
CREATE TABLE IF NOT EXISTS `swd_delivery_timer` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT '',
  `store_id` int(10) DEFAULT '0',
  `rules` text DEFAULT '',
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_deposit_account`
--
DROP TABLE IF EXISTS `swd_deposit_account`;
CREATE TABLE IF NOT EXISTS `swd_deposit_account` (
  `account_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `account` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT '',
  `money` decimal(10,2) DEFAULT '0',
  `frozen` decimal(10,2) DEFAULT '0',
  `bond` decimal(10,2) DEFAULT '0',
  `nodrawal` decimal(10,2) DEFAULT '0',
  `real_name` varchar(30) DEFAULT NULL,
  `pay_status` varchar(3) DEFAULT 'off',
  `add_time` int(11) DEFAULT NULL,
  `last_update` int(11) DEFAULT NULL,
  PRIMARY KEY (`account_id`),
  KEY `userid` (`userid`),
  KEY `account` (`account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_deposit_bond`
--
DROP TABLE IF EXISTS `swd_deposit_bond`;
CREATE TABLE IF NOT EXISTS `swd_deposit_bond` (
  `bond_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tradeNo` varchar(30) NOT NULL,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `amount` decimal(10,2) DEFAULT '0' COMMENT '收支金额',
  `balance` decimal(10,2) DEFAULT '0' COMMENT '账户余额',
  `flow` varchar(10) DEFAULT 'outlay' COMMENT '资金方向',
  `tradeType` varchar(20) DEFAULT 'PAY' COMMENT '收支类型',
  `name` varchar(100) DEFAULT '' COMMENT '名称',
  `remark` varchar(255) DEFAULT '' COMMENT '备注',
  PRIMARY KEY (`bond_id`),
  KEY `tradeNo` (`tradeNo`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_deposit_recharge`
--
DROP TABLE IF EXISTS `swd_deposit_recharge`;
CREATE TABLE IF NOT EXISTS `swd_deposit_recharge` (
  `recharge_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `orderId` varchar(30) NOT NULL,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `examine` varchar(100) DEFAULT '',
  `fundtype` varchar(10) DEFAULT 'money' COMMENT '资金类型(money|bond)',
  PRIMARY KEY (`recharge_id`),
  KEY `orderId` (`orderId`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_deposit_record`
--
DROP TABLE IF EXISTS `swd_deposit_record`;
CREATE TABLE IF NOT EXISTS `swd_deposit_record` (
  `record_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tradeNo` varchar(30) NOT NULL,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `amount` decimal(10,2) DEFAULT '0' COMMENT '收支金额',
  `balance` decimal(10,2) DEFAULT '0' COMMENT '账户余额',
  `flow` varchar(10) DEFAULT 'outlay' COMMENT '资金方向',
  `tradeType` varchar(20) DEFAULT 'PAY' COMMENT '收支类型',
  `fundtype` varchar(10) DEFAULT 'money' COMMENT '资金类型(money|bond)',
  `name` varchar(100) DEFAULT '' COMMENT '名称',
  `remark` varchar(255) DEFAULT '' COMMENT '备注',
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`record_id`),
  KEY `tradeNo` (`tradeNo`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_deposit_setting`
--
DROP TABLE IF EXISTS `swd_deposit_setting`;
CREATE TABLE IF NOT EXISTS `swd_deposit_setting` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `trade_rate` decimal(10,3) DEFAULT '0' COMMENT '交易手续费',
  `drawal_rate` decimal(10,3) DEFAULT '0' COMMENT '提现手续费',
  `transfer_rate` decimal(10,3) DEFAULT '0' COMMENT '转账手续费',
  `regive_rate` decimal(10,3) DEFAULT '0' COMMENT '充值赠送金额比率，即将废弃，调整为送具体金额',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_deposit_trade`
--
DROP TABLE IF EXISTS `swd_deposit_trade`;
CREATE TABLE IF NOT EXISTS `swd_deposit_trade` (
  `trade_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tradeNo` varchar(32) NOT NULL COMMENT '交易号',
  `outTradeNo` varchar(255) DEFAULT '' COMMENT '第三方平台的交易号',
  `payTradeNo` varchar(32) DEFAULT '' COMMENT '支付订单号',
  `bizOrderId` varchar(32) DEFAULT '' COMMENT '商户订单号',
  `bizIdentity` varchar(20) DEFAULT '' COMMENT '商户交易类型识别号',
  `buyer_id` int(11) NOT NULL COMMENT '交易买家',
  `seller_id` int(11) NOT NULL COMMENT '交易卖家',
  `amount` decimal(10,2) DEFAULT '0' COMMENT '交易金额',
  `status` varchar(30) DEFAULT '',
  `payment_code` varchar(20) COMMENT '支付方式代号',
  `pay_alter` int(11) DEFAULT '0' COMMENT '支付方式变更标记',
  `payType` varchar(20) DEFAULT NULL COMMENT '支付类型(担保即时)',
  `flow` varchar(10) DEFAULT 'outlay' COMMENT '资金流向',
  `payTerminal` varchar(10) DEFAULT '' COMMENT '支付终端',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '交易标题',
  `buyer_remark` varchar(255) DEFAULT '' COMMENT '买家备注',
  `seller_remark` varchar(255) DEFAULT '' COMMENT '卖家备注',
  `openid` varchar(255) DEFAULT '' COMMENT '第三方平台支付者用户标识',
  `add_time` int(11) DEFAULT NULL,
  `pay_time` int(11) DEFAULT NULL,
  `end_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`trade_id`),
  KEY `tradeNo` (`tradeNo`),
  KEY `bizOrderId` (`bizOrderId`),
  KEY `buyer_id` (`buyer_id`),
  KEY `seller_id` (`seller_id`),
  KEY `payTradeNo` (`payTradeNo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_deposit_withdraw`
--
DROP TABLE IF EXISTS `swd_deposit_withdraw`;
CREATE TABLE IF NOT EXISTS `swd_deposit_withdraw` (
  `draw_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `orderId` varchar(30) NOT NULL,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `drawtype` varchar(20) NOT NULL DEFAULT 'bank',
  `terminal` varchar(20) NOT NULL DEFAULT '',
  `account` varchar(255) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `bank` varchar(100) DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT '0' COMMENT '手续费',
  `qrcode` varchar(255) DEFAULT NULL COMMENT '收款二维码',
  PRIMARY KEY (`draw_id`),
  KEY `orderId` (`orderId`),
  KEY `userid` (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_distribute`
--
DROP TABLE IF EXISTS `swd_distribute`;
CREATE TABLE IF NOT EXISTS `swd_distribute` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `amount` decimal(10,2) DEFAULT '0',
  `layer1` decimal(10,2) DEFAULT '0',
  `layer2` decimal(10,2) DEFAULT '0',
  `layer3` decimal(10,2) DEFAULT '0',
  `goods` decimal(10,2) DEFAULT '0',
  `store` decimal(10,2) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_distribute_items`
--
DROP TABLE IF EXISTS `swd_distribute_items`;
CREATE TABLE IF NOT EXISTS `swd_distribute_items` (
  `diid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `item_id` int(11) NOT NULL,
  `type` varchar(20) DEFAULT '',
  `created` int(11) DEFAULT NULL ,
  PRIMARY KEY (`diid`),
  KEY `userid` (`userid`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_distribute_merchant`
--
DROP TABLE IF EXISTS `swd_distribute_merchant`;
CREATE TABLE IF NOT EXISTS `swd_distribute_merchant` (
  `dmid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `username` varchar(60) DEFAULT '',
  `parent_id` int(11) DEFAULT '0',
  `phone_mob` varchar(20) NOT NULL DEFAULT '',
  `name` varchar(100) NOT NULL DEFAULT '',
  `logo` varchar(255) DEFAULT NULL,
  `status` int(11) DEFAULT '0',
  `remark` varchar(100) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`dmid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_distribute_order`
--
DROP TABLE IF EXISTS `swd_distribute_order`;
CREATE TABLE IF NOT EXISTS `swd_distribute_order` (
  `doid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rid` int(11) NOT NULL COMMENT '订单商品表唯一键',
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `tradeNo` varchar(32) NOT NULL,
  `order_sn` varchar(20) DEFAULT '',
  `money` decimal(10,2) DEFAULT '0',
  `layer` int(11) DEFAULT '1',
  `ratio` decimal(10,2) DEFAULT '0',
  `type` varchar(20) DEFAULT '',
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`doid`),
  KEY `rid` (`rid`),
  KEY `userid` (`userid`),
  KEY `order_sn` (`order_sn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_distribute_setting`
--
DROP TABLE IF EXISTS `swd_distribute_setting`;
CREATE TABLE IF NOT EXISTS `swd_distribute_setting` (
  `dsid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(20) DEFAULT '',
  `item_id` int(11) DEFAULT '0',
  `ratio1` decimal(10,2) DEFAULT '0',
  `ratio2` decimal(10,2) DEFAULT '0',
  `ratio3` decimal(10,2) DEFAULT '0',
  `enabled` int(1) DEFAULT '1',
  PRIMARY KEY (`dsid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_friend`
--
DROP TABLE IF EXISTS `swd_friend`;
CREATE TABLE IF NOT EXISTS `swd_friend` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `friend_id` int(10) unsigned NOT NULL DEFAULT '0',
  `add_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `friend_id` (`friend_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_gcategory`
--
DROP TABLE IF EXISTS `swd_gcategory`;
CREATE TABLE IF NOT EXISTS `swd_gcategory` (
  `cate_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned DEFAULT '0',
  `cate_name` varchar(100) NOT NULL DEFAULT '',
  `parent_id` int(10) unsigned DEFAULT '0',
  `sort_order` tinyint(3) unsigned DEFAULT '255',
  `if_show` tinyint(3) unsigned DEFAULT '1',
  `image` varchar(255) DEFAULT NULL,
  `ad` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`cate_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_goods`
--
DROP TABLE IF EXISTS `swd_goods`;
CREATE TABLE IF NOT EXISTS `swd_goods` (
  `goods_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned NOT NULL DEFAULT '0',
  `type` varchar(10) DEFAULT 'material',
  `goods_name` varchar(255) NOT NULL DEFAULT '',
  `description` text DEFAULT NULL,
  `content` text DEFAULT NULL,
  `cate_id` int(10) unsigned DEFAULT '0',
  `cate_name` varchar(255) DEFAULT '',
  `brand` varchar(100)  DEFAULT '',
  `spec_qty` tinyint(4) unsigned DEFAULT '0',
  `spec_name_1` varchar(60) DEFAULT '',
  `spec_name_2` varchar(60) DEFAULT '',
  `if_show` tinyint(3) unsigned DEFAULT '1',
  `closed` tinyint(3) unsigned DEFAULT '0',
  `close_reason` varchar(255) DEFAULT NULL,
  `add_time` int(10) unsigned DEFAULT NULL,
  `last_update` int(10) unsigned DEFAULT NULL,
  `default_spec` int(11) unsigned DEFAULT '0',
  `default_image` varchar(255) DEFAULT NULL,
  `long_image` varchar(255) DEFAULT NULL,
  `video` varchar(255) DEFAULT NULL,
  `isnew` tinyint(4) unsigned DEFAULT '0',
  `recommended` tinyint(4) unsigned DEFAULT '0',
  `price` decimal(10,2) DEFAULT '0.00',
  `tags` varchar(102) DEFAULT '',
  PRIMARY KEY (`goods_id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_goods_image`
--
DROP TABLE IF EXISTS `swd_goods_image`;
CREATE TABLE IF NOT EXISTS `swd_goods_image` (
  `image_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) unsigned NOT NULL DEFAULT '0',
  `image_url` varchar(255) NOT NULL DEFAULT '',
  `thumbnail` varchar(255) DEFAULT '',
  `sort_order` tinyint(4) unsigned DEFAULT '0',
  `file_id` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`image_id`),
  KEY `goods_id` (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_goods_integral`
--
DROP TABLE IF EXISTS `swd_goods_integral`;
CREATE TABLE IF NOT EXISTS `swd_goods_integral` (
  `goods_id` int(10) NOT NULL,
  `max_exchange` int(11) DEFAULT '0',
  PRIMARY KEY (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_goods_prop`
--
DROP TABLE IF EXISTS `swd_goods_prop`;
CREATE TABLE IF NOT EXISTS `swd_goods_prop` (
  `pid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `ptype` varchar(20) DEFAULT 'select',
  `is_color` int(1) DEFAULT '0',
  `status` int(1) DEFAULT '1',
  `sort_order` int(11) DEFAULT '255',
  PRIMARY KEY (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_goods_prop_value`
--
DROP TABLE IF EXISTS `swd_goods_prop_value`;
CREATE TABLE IF NOT EXISTS `swd_goods_prop_value` (
  `vid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT '0',
  `pvalue` varchar(255) DEFAULT '',
  `color` varchar(255) DEFAULT '',
  `status` int(1) DEFAULT '1',
  `sort_order` int(11) DEFAULT '255',
  PRIMARY KEY (`vid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_goods_pvs`
--
DROP TABLE IF EXISTS `swd_goods_pvs`;
CREATE TABLE IF NOT EXISTS `swd_goods_pvs` (
  `goods_id` int(10) NOT NULL,
  `pvs` text DEFAULT '',
  PRIMARY KEY (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_goods_qa`
--
DROP TABLE IF EXISTS `swd_goods_qa`;
CREATE TABLE IF NOT EXISTS `swd_goods_qa` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `content` varchar(255) DEFAULT '',
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `store_id` int(10) unsigned DEFAULT '0',
  `email` varchar(60) DEFAULT '',
  `item_id` int(10) unsigned NOT NULL DEFAULT '0',
  `item_name` varchar(255) DEFAULT '',
  `reply_content` varchar(255) DEFAULT '',
  `add_time` int(10) unsigned DEFAULT NULL,
  `reply_time` int(10) unsigned DEFAULT NULL,
  `if_new` tinyint(3) unsigned DEFAULT '1',
  `type` varchar(10) DEFAULT 'goods',
  PRIMARY KEY (`id`),
  KEY `userid` (`userid`),
  KEY `goods_id` (`item_id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_goods_spec`
--
DROP TABLE IF EXISTS `swd_goods_spec`;
CREATE TABLE IF NOT EXISTS `swd_goods_spec` (
  `spec_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) unsigned NOT NULL DEFAULT '0',
  `spec_1` varchar(60) DEFAULT '',
  `spec_2` varchar(60) DEFAULT '',
  `price` decimal(10,2) DEFAULT '0.00',
  `mkprice` decimal(10,2) DEFAULT '0.00',
  `stock` int(11) DEFAULT '0',
  `sku` varchar(60) DEFAULT '',
  `weight` decimal(10,2) DEFAULT '0',
  `image` varchar(255) DEFAULT NULL,
  `sort_order` int(10) unsigned DEFAULT '255',
  PRIMARY KEY (`spec_id`),
  KEY `goods_id` (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_goods_statistics`
--
DROP TABLE IF EXISTS `swd_goods_statistics`;
CREATE TABLE IF NOT EXISTS `swd_goods_statistics` (
  `goods_id` int(10) unsigned NOT NULL DEFAULT '0',
  `views` int(10) unsigned DEFAULT '0',
  `collects` int(10) unsigned DEFAULT '0',
  `orders` int(10) unsigned DEFAULT '0',
  `sales` int(10) unsigned DEFAULT '0',
  `comments` int(11) unsigned DEFAULT '0',
  PRIMARY KEY (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_integral`
--
DROP TABLE IF EXISTS `swd_integral`;
CREATE TABLE IF NOT EXISTS `swd_integral` (
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `amount` decimal(10,2) DEFAULT '0',
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_integral_log`
--
DROP TABLE IF EXISTS `swd_integral_log`;
CREATE TABLE IF NOT EXISTS `swd_integral_log` (
  `log_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `order_id` int(10) NOT NULL DEFAULT '0',
  `order_sn` varchar(20) DEFAULT '',
  `changes` decimal(10,2) DEFAULT '0',
  `money` decimal(10,2) DEFAULT '0',
  `balance` decimal(10,2) DEFAULT '0',
  `type` varchar(50) DEFAULT '',
  `state` varchar(50) DEFAULT '',
  `flag` varchar(255) DEFAULT '',
  `add_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_integral_setting`
--
DROP TABLE IF EXISTS `swd_integral_setting`;
CREATE TABLE IF NOT EXISTS `swd_integral_setting` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rate` decimal(10,2) DEFAULT '0',
  `register` decimal(10,0) DEFAULT '0',
  `signin` decimal(10,0) DEFAULT '0',
  `openshop` decimal(10,0) DEFAULT '0',
  `buygoods` text DEFAULT NULL,
  `enabled` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_limitbuy`
--
DROP TABLE IF EXISTS `swd_limitbuy`;
CREATE TABLE IF NOT EXISTS `swd_limitbuy` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) NOT NULL,
  `title` varchar(50) NOT NULL DEFAULT '',
  `summary` varchar(255) DEFAULT '',
  `start_time` int(11) DEFAULT NULL,
  `end_time` int(11) DEFAULT NULL,
  `store_id` int(10) DEFAULT '0',
  `rules` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_mailbox`
--
DROP TABLE IF EXISTS `swd_mailbox`;
CREATE TABLE IF NOT EXISTS `swd_mailbox` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `from_id` int(10) unsigned NOT NULL DEFAULT '0',
  `to_id` int(10) unsigned NOT NULL DEFAULT '0',
  `title` varchar(100) DEFAULT '',
  `content` text DEFAULT NULL,
  `add_time` int(10) unsigned DEFAULT NULL,
  `last_update` int(10) unsigned DEFAULT NULL,
  `new` tinyint(3) unsigned DEFAULT '0',
  `parent_id` int(10) unsigned DEFAULT '0',
  `status` tinyint(3) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `from_id` (`from_id`),
  KEY `to_id` (`to_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_meal`
--
DROP TABLE IF EXISTS `swd_meal`;
CREATE TABLE IF NOT EXISTS `swd_meal` (
  `meal_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) NOT NULL,
  `title` varchar(255) DEFAULT '',
  `price` decimal(10,2) DEFAULT '0',
  `keyword` varchar(255) DEFAULT '',
  `description` text DEFAULT '',
  `status` int(1) DEFAULT '1',
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`meal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_meal_goods`
--
DROP TABLE IF EXISTS `swd_meal_goods`;
CREATE TABLE IF NOT EXISTS `swd_meal_goods` (
  `mg_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `meal_id` int(11) NOT NULL DEFAULT '0',
  `goods_id` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`mg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_order`
--
DROP TABLE IF EXISTS `swd_order`;
CREATE TABLE IF NOT EXISTS `swd_order` (
  `order_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_sn` varchar(20) NOT NULL DEFAULT '',
  `gtype` varchar(10) DEFAULT 'material',
  `otype` varchar(10) DEFAULT 'normal',
  `seller_id` int(10) unsigned NOT NULL DEFAULT '0',
  `seller_name` varchar(100) DEFAULT NULL,
  `buyer_id` int(10) unsigned NOT NULL DEFAULT '0',
  `buyer_name` varchar(100) DEFAULT NULL,
  `status` tinyint(3) unsigned DEFAULT '0',
  `add_time` int(10) unsigned DEFAULT NULL,
  `payment_name` varchar(100) DEFAULT NULL,
  `payment_code` varchar(20) DEFAULT '',
  `pay_time` int(10) unsigned DEFAULT NULL,
  `ship_time` int(10) unsigned DEFAULT NULL,
  `receive_time` int(10) unsigned DEFAULT NULL,
  `finished_time` int(10) unsigned DEFAULT NULL,
  `goods_amount` decimal(10,2) unsigned DEFAULT '0.00',
  `discount` decimal(10,2) unsigned DEFAULT '0.00',
  `order_amount` decimal(10,2) unsigned DEFAULT '0.00',
  `evaluation_status` tinyint(1) unsigned DEFAULT '0',
  `evaluation_time` int(10) unsigned DEFAULT NULL,
  `service_evaluation` decimal(10,2) DEFAULT '0.00',
  `shipped_evaluation` decimal(10,2) DEFAULT '0.00',
  `anonymous` tinyint(3) unsigned DEFAULT '0',
  `postscript` varchar(255) DEFAULT '',
  `pay_alter` tinyint(1) unsigned DEFAULT '0',
  `flag` int(1) DEFAULT '0',
  `memo` varchar(255) DEFAULT '',
  `dtype` varchar(20) DEFAULT '' COMMENT '物流&同城',
  `shipwx` tinyint(1) unsigned DEFAULT '0' COMMENT '发货信息推送至微信0=未推1=已推2=二推',
  PRIMARY KEY (`order_id`),
  KEY `order_sn` (`order_sn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_order_express`
--
DROP TABLE IF EXISTS `swd_order_express`;
CREATE TABLE IF NOT EXISTS `swd_order_express` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `code` varchar(20) DEFAULT '',
  `company` varchar(50) DEFAULT '',
  `number` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_order_extm`
--
DROP TABLE IF EXISTS `swd_order_extm`;
CREATE TABLE IF NOT EXISTS `swd_order_extm` (
  `order_id` int(10) unsigned NOT NULL DEFAULT '0',
  `consignee` varchar(60) NOT NULL DEFAULT '',
  `region_id` int(10) unsigned NOT NULL DEFAULT '0',
  `address` varchar(255) NOT NULL DEFAULT '',
  `phone_tel` varchar(60) DEFAULT '',
  `phone_mob` varchar(20) DEFAULT '',
  `freight` decimal(10,2) DEFAULT '0.00' COMMENT '平台运费',
  `latitude` varchar(20) DEFAULT '' COMMENT '纬度',
  `longitude` varchar(20) DEFAULT '' COMMENT '经度',
  `distance` decimal(10,2) DEFAULT '0.00' COMMENT '配送距离(米)',
  `deliverTime` varchar(100) DEFAULT '' COMMENT '期望送货时间',
  `deliverFee` decimal(10,2) DEFAULT '0.00' COMMENT '第三方平台配送费',
  `deliverFine` decimal(10,2) DEFAULT '0.00' COMMENT '商户取消配送的罚息',
  `deliveryName` varchar(100) DEFAULT '' COMMENT '配送名称',
  `deliveryCode` varchar(20) DEFAULT '' COMMENT '配送插件代码',
  PRIMARY KEY (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_order_goods`
--
DROP TABLE IF EXISTS `swd_order_goods`;
CREATE TABLE IF NOT EXISTS `swd_order_goods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL DEFAULT '0',
  `goods_id` int(10) unsigned NOT NULL DEFAULT '0',
  `goods_name` varchar(255) DEFAULT '',
  `spec_id` int(10) unsigned DEFAULT '0',
  `specification` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) unsigned DEFAULT '0.00',
  `quantity` int(10) unsigned DEFAULT '1',
  `goods_image` varchar(255) DEFAULT NULL,
  `evaluation` tinyint(1) unsigned DEFAULT '0',
  `comment` varchar(255) DEFAULT '',
  `images` text DEFAULT '',
  `is_valid` tinyint(1) unsigned DEFAULT '1',
  `reply_comment` varchar(255) DEFAULT NULL,
  `reply_time` int(11) DEFAULT NULL,
  `inviteType` varchar(20) DEFAULT '',
  `inviteRatio` varchar(255) DEFAULT '',
  `inviteUid` int(11) DEFAULT '0',
  `status` varchar(50) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`,`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_order_log`
--
DROP TABLE IF EXISTS `swd_order_log`;
CREATE TABLE IF NOT EXISTS `swd_order_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL DEFAULT '0',
  `operator` varchar(60) DEFAULT '',
  `status` varchar(60) DEFAULT '',
  `remark` varchar(255) DEFAULT NULL,
  `created` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_plugin`
--
DROP TABLE IF EXISTS `swd_plugin`;
CREATE TABLE IF NOT EXISTS `swd_plugin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `instance` varchar(20) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `subname` varchar(50) DEFAULT NULL,
  `summary` varchar(100) DEFAULT NULL,
  `config` text NOT NULL,
  `enabled` int(11) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_promote_item`
--
DROP TABLE IF EXISTS `swd_promote_item`;
CREATE TABLE IF NOT EXISTS `swd_promote_item` (
  `piid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) NOT NULL,
  `appid` varchar(20) NOT NULL,
  `store_id` int(10) DEFAULT '0',
  `config` text DEFAULT '',
  `status` int(1) DEFAULT '1',
  `add_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`piid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_promote_setting`
--
DROP TABLE IF EXISTS `swd_promote_setting`;
CREATE TABLE IF NOT EXISTS `swd_promote_setting` (
  `psid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `appid` varchar(20) NOT NULL,
  `store_id` int(10) DEFAULT '0',
  `rules` text DEFAULT '',
  `status` tinyint(1) DEFAULT '0',
  `add_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`psid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_recommend`
--
DROP TABLE IF EXISTS `swd_recommend`;
CREATE TABLE IF NOT EXISTS `swd_recommend` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `store_id` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_recommend_goods`
--
DROP TABLE IF EXISTS `swd_recommend_goods`;
CREATE TABLE IF NOT EXISTS `swd_recommend_goods` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) unsigned NOT NULL DEFAULT '0',
  `recid` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `goods_id` (`goods_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_refund`
--
DROP TABLE IF EXISTS `swd_refund`;
CREATE TABLE IF NOT EXISTS `swd_refund` (
  `refund_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tradeNo` varchar(30) NOT NULL,
  `refund_sn` varchar(30) NOT NULL,
  `title` varchar(255) DEFAULT '',
  `refund_reason` varchar(50) DEFAULT '',
  `refund_desc` varchar(255) DEFAULT '',
  `total_fee` decimal(10,2) DEFAULT '0',
  `goods_fee` decimal(10,2) DEFAULT '0',
  `freight` decimal(10,2) DEFAULT '0',
  `refund_total_fee` decimal(10,2) DEFAULT '0',
  `refund_goods_fee` decimal(10,2) DEFAULT '0',
  `refund_freight` decimal(10,2) DEFAULT '0',
  `buyer_id` int(10) NOT NULL,
  `seller_id` int(10) NOT NULL,
  `status` varchar(100) DEFAULT '',
  `shipped` int(11) DEFAULT '0',
  `intervene` int(1) DEFAULT '0',
  `created` int(11) DEFAULT NULL,
  `finished` int(11) DEFAULT NULL,
  PRIMARY KEY (`refund_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_refund_message`
--
DROP TABLE IF EXISTS `swd_refund_message`;
CREATE TABLE IF NOT EXISTS `swd_refund_message` (
  `rm_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `owner_role` varchar(10) DEFAULT '',
  `refund_id` int(11) NOT NULL,
  `content` varchar(255) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created` int(11) DEFAULT NULL,
  PRIMARY KEY (`rm_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_region`
--
DROP TABLE IF EXISTS `swd_region`;
CREATE TABLE IF NOT EXISTS `swd_region` (
  `region_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL DEFAULT '',
  `parent_id` int(10) unsigned DEFAULT '0',
  `letter` varchar(10) NOT NULL DEFAULT '',
  `sort_order` tinyint(3) unsigned DEFAULT '255',
  `if_show` int(1) DEFAULT '1',
  PRIMARY KEY (`region_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_recharge_setting`
--
DROP TABLE IF EXISTS `swd_recharge_setting`;
CREATE TABLE IF NOT EXISTS `swd_recharge_setting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `money` decimal(10,2) unsigned NOT NULL COMMENT '充值金额',
  `reward` decimal(10,2) DEFAULT '0' COMMENT '赠送金额',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_report`
--
DROP TABLE IF EXISTS `swd_report`;
CREATE TABLE IF NOT EXISTS `swd_report` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '举报人ID',
  `store_id` int(10) DEFAULT NULL COMMENT '被举报店铺ID',
  `goods_id` int(10) DEFAULT NULL COMMENT '被举报商品ID',
  `content` varchar(255) DEFAULT NULL COMMENT '举报内容',
  `images` text DEFAULT NULL,
  `add_time` int(10) DEFAULT NULL COMMENT '添加时间',
  `status` int(3) DEFAULT NULL COMMENT '状态',
  `examine` varchar(20) DEFAULT NULL COMMENT '审核员',
  `verify` varchar(255) DEFAULT NULL COMMENT '审核说明',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_scategory`
--
DROP TABLE IF EXISTS `swd_scategory`;
CREATE TABLE IF NOT EXISTS `swd_scategory` (
  `cate_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cate_name` varchar(100) NOT NULL DEFAULT '',
  `parent_id` int(10) unsigned DEFAULT '0',
  `sort_order` tinyint(3) unsigned DEFAULT '255',
  `if_show` int(1) DEFAULT '1',
  PRIMARY KEY (`cate_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_sgrade`
--
DROP TABLE IF EXISTS `swd_sgrade`;
CREATE TABLE IF NOT EXISTS `swd_sgrade` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL DEFAULT '',
  `goods_limit` int(10) unsigned DEFAULT '0',
  `space_limit` int(10) unsigned DEFAULT '0',
  `charge` decimal(10,2) DEFAULT '0',
  `need_confirm` tinyint(3) unsigned DEFAULT '0',
  `description` varchar(255) DEFAULT '',
  `sort_order` tinyint(4) unsigned DEFAULT '255',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_sms`
--
DROP TABLE IF EXISTS `swd_sms`;
CREATE TABLE IF NOT EXISTS `swd_sms` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `num` int(10) unsigned DEFAULT '0',
  `functions` varchar(255) DEFAULT NULL,
  `state` tinyint(3) unsigned DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_sms_log`
--
DROP TABLE IF EXISTS `swd_sms_log`;
CREATE TABLE IF NOT EXISTS `swd_sms_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `receiver` varchar(20) NOT NULL DEFAULT '',
  `verifycode` int(10) unsigned DEFAULT NULL,
  `codekey` varchar(32) DEFAULT NULL,
  `content` text,
  `quantity` int(10) DEFAULT '0',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(3) DEFAULT '0',
  `message` varchar(100) DEFAULT NULL,
  `add_time` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_sms_template`
--
DROP TABLE IF EXISTS `swd_sms_template`;
CREATE TABLE IF NOT EXISTS `swd_sms_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `scene` varchar(50) NOT NULL,
  `signName` varchar(50) NOT NULL,
  `templateId` varchar(40) NOT NULL,
  `content` varchar(255) NOT NULL DEFAULT '',
  `add_time` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_store`
--
DROP TABLE IF EXISTS `swd_store`;
CREATE TABLE IF NOT EXISTS `swd_store` (
  `store_id` int(10) unsigned NOT NULL DEFAULT '0',
  `store_name` varchar(100) NOT NULL DEFAULT '',
  `owner` varchar(60) DEFAULT '' COMMENT '主体',
  `contacter` varchar(60) DEFAULT '' COMMENT '联系人',
  `identity_card` varchar(60) DEFAULT '',
  `region_id` int(10) unsigned DEFAULT NULL,
  `address` varchar(255) DEFAULT '',
  `tel` varchar(20) DEFAULT '' COMMENT '门店电话',
  `phone` varchar(20) DEFAULT '' COMMENT '联系人电话',
  `qq` varchar(60) DEFAULT '',
  `sgrade` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `stype` VARCHAR(20) NOT NULL DEFAULT 'personal',
  `brand`  VARCHAR(20) NOT NULL DEFAULT '',
  `apply_remark` varchar(255) DEFAULT '',
  `credit_value` decimal(10,2) unsigned DEFAULT '0.00',
  `praise_rate` decimal(10,2) unsigned DEFAULT '0.00',
  `state` tinyint(3) unsigned DEFAULT '0',
  `close_reason` varchar(255) DEFAULT '',
  `add_time` int(10) unsigned DEFAULT NULL,
  `end_time` int(10) unsigned DEFAULT '0',
  `certification` varchar(255) DEFAULT NULL,
  `sort_order` int(10) unsigned DEFAULT '255',
  `recommended` tinyint(4) DEFAULT '0',
  `theme` varchar(60) DEFAULT '',
  `banner` varchar(255) DEFAULT NULL,
  `pcbanner` varchar(255) DEFAULT NULL,
  `store_logo` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `identity_front` varchar(255) DEFAULT '',
  `identity_back` varchar(255) DEFAULT '',
  `business_license` varchar(255) DEFAULT '',
  `swiper` text DEFAULT '',
  `longitude` varchar(20) DEFAULT '',
  `latitude` varchar(20) DEFAULT '',
  `bustime` varchar(30) DEFAULT '' COMMENT '营业时间',
  `deliveryCode` varchar(20) DEFAULT '' COMMENT '同城配送插件代码',
  `radius` int(11) unsigned DEFAULT '0',
  PRIMARY KEY (`store_id`),
  KEY `store_name` (`store_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_teambuy`
--
DROP TABLE IF EXISTS `swd_teambuy`;
CREATE TABLE IF NOT EXISTS `swd_teambuy` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) NOT NULL,
  `title` varchar(50) NOT NULL DEFAULT '',
  `status` tinyint(3) NOT NULL DEFAULT '1',
  `store_id` int(10) DEFAULT '0',
  `people` int(10) unsigned NOT NULL DEFAULT '2',
  `specs` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_teambuy_log`
--
DROP TABLE IF EXISTS `swd_teambuy_log`;
CREATE TABLE IF NOT EXISTS `swd_teambuy_log` (
  `logid` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `tbid` int(10) unsigned DEFAULT '0',
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `order_id` int(10) unsigned NOT NULL DEFAULT '0',
  `goods_id` int(10) unsigned NOT NULL DEFAULT '0',
  `teamid` varchar(32) NOT NULL DEFAULT '',
  `leader` tinyint(3) unsigned DEFAULT '0',
  `people` int(10) unsigned NOT NULL DEFAULT '2',
  `status` tinyint(3) unsigned DEFAULT '0',
  `created` int(11) unsigned NOT NULL DEFAULT '0',
  `expired` int(11) unsigned NOT NULL DEFAULT '0',
  `pay_time` int(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`logid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_uploaded_file`
--
DROP TABLE IF EXISTS `swd_uploaded_file`;
CREATE TABLE IF NOT EXISTS `swd_uploaded_file` (
  `file_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned DEFAULT '0',
  `file_type` varchar(60) DEFAULT '',
  `file_size` int(10) unsigned DEFAULT '0',
  `file_name` varchar(255) DEFAULT '',
  `file_path` varchar(255) NOT NULL DEFAULT '',
  `add_time` int(10) unsigned DEFAULT NULL,
  `belong` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `item_id` int(10) unsigned DEFAULT '0',
  PRIMARY KEY (`file_id`),
  KEY `store_id` (`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_user`
--
DROP TABLE IF EXISTS `swd_user`;
CREATE TABLE IF NOT EXISTS `swd_user` (
  `userid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(60) NOT NULL DEFAULT '',
  `nickname` varchar(60) NOT NULL DEFAULT '',
  `email` varchar(60) DEFAULT '',
  `password` varchar(255) DEFAULT '',
  `password_reset_token` varchar(255) DEFAULT '',
  `real_name` varchar(60) DEFAULT NULL,
  `gender` tinyint(3) unsigned DEFAULT '0',
  `birthday` varchar(50) NOT NULL DEFAULT '',
  `phone_tel` varchar(60) NOT NULL DEFAULT '',
  `phone_mob` varchar(20) NOT NULL DEFAULT '',
  `qq` varchar(60) NOT NULL DEFAULT '',
  `create_time` int(10) unsigned DEFAULT NULL,
  `update_time` int(10) unsigned DEFAULT NULL,
  `last_login` int(10) unsigned DEFAULT NULL,
  `last_ip` varchar(15) DEFAULT NULL,
  `logins` int(10) unsigned DEFAULT '0',
  `ugrade` tinyint(3) unsigned DEFAULT '1',
  `regtype` varchar(60) NOT NULL DEFAULT '' COMMENT '注册渠道',
  `portrait` varchar(255) DEFAULT NULL,
  `activation` varchar(60) DEFAULT NULL,
  `locked` int(1) DEFAULT '0',
  `imforbid` int(1) DEFAULT '0',
  `auth_key` varchar(255) DEFAULT '',
  PRIMARY KEY (`userid`),
  KEY `username` (`username`),
  KEY `phone_mob` (`phone_mob`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_user_enter`
--
DROP TABLE IF EXISTS `swd_user_enter`;
CREATE TABLE IF NOT EXISTS `swd_user_enter` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `username` varchar(50) DEFAULT NULL,
  `scene` varchar(20) DEFAULT '',
  `ip` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `add_time` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_user_priv`
--
DROP TABLE IF EXISTS `swd_user_priv`;
CREATE TABLE IF NOT EXISTS `swd_user_priv` (
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `store_id` int(10) unsigned DEFAULT '0',
  `privs` text DEFAULT '',
  PRIMARY KEY (`userid`,`store_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_user_token`
--
DROP TABLE IF EXISTS `swd_user_token`;
CREATE TABLE IF NOT EXISTS `swd_user_token` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `token` varchar(100) NOT NULL DEFAULT '',
  `expire_time` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_webim`
--
DROP TABLE IF EXISTS `swd_webim`;
CREATE TABLE IF NOT EXISTS `swd_webim` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `toid` int(11) NOT NULL,
  `fromid` int(11) NOT NULL,
  `groupid` varchar(255) NOT NULL,
  `store_id` int(10) DEFAULT NULL,
  `store_name` varchar(100) DEFAULT NULL,
  `content` text CHARACTER SET utf8mb4 NOT NULL,
  `unread` int(11) NOT NULL DEFAULT '0',
  `created` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_weixin_menu`
--
DROP TABLE IF EXISTS `swd_weixin_menu`;
CREATE TABLE IF NOT EXISTS `swd_weixin_menu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `parent_id` int(10) DEFAULT '0',
  `name` varchar(255) DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  `add_time` int(10) DEFAULT NULL,
  `sort_order` tinyint(3) unsigned DEFAULT '255',
  `url` varchar(255) DEFAULT NULL,
  `reply_id` int(10) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_weixin_reply`
--
DROP TABLE IF EXISTS `swd_weixin_reply`;
CREATE TABLE IF NOT EXISTS `swd_weixin_reply` (
  `reply_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `userid` int(10) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(1) unsigned DEFAULT '0' COMMENT '回复类型0文字1图文',
  `action` varchar(20) DEFAULT NULL COMMENT '消息类型：关注、文本、关键字',
  `title` varchar(255) DEFAULT NULL,
  `link` varchar(50) DEFAULT NULL,
  `image` varchar(100) DEFAULT NULL,
  `keywords` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `add_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`reply_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- 表的结构 `swd_weixin_setting`
--
DROP TABLE IF EXISTS `swd_weixin_setting`;
CREATE TABLE IF NOT EXISTS `swd_weixin_setting` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `token` varchar(255) DEFAULT '',
  `appid` varchar(255) DEFAULT NULL,
  `appsecret` varchar(255) DEFAULT NULL,
  `codeurl` varchar(255) DEFAULT NULL,
  `autoreg` int(1) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
