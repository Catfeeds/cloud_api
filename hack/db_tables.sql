-- MySQL dump 10.13  Distrib 5.7.17, for macos10.12 (x86_64)
--
-- Host: rds45hf2v05567xa3knwo.mysql.rds.aliyuncs.com    Database: strongberry_pms
-- ------------------------------------------------------
-- Server version	5.6.34

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;

--
-- GTID state at the beginning of the backup 
--

SET @@GLOBAL.GTID_PURGED='765ad3fc-7e2b-11e8-9441-7cd30ae41340:1-322846,
e50dbff2-e146-11e5-8a85-9c37f40104fa:1-4055981,
f1773111-e146-11e5-8a85-9c37f401986a:1-10061702';

--
-- Table structure for table `boss_activity`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_activity` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '活动的名称',
  `type` enum('ATTRACT','DISCOUNT','NORMAL') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'NORMAL' COMMENT '活动的类型',
  `description` text COLLATE utf8_unicode_ci NOT NULL COMMENT '活动的介绍',
  `coupon_info` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '优惠券的信息, json格式的字符串',
  `qrcode_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '活动头图海报链接',
  `start_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '开始时间',
  `end_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '结束时间',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `activity_type` enum('TRNTABLE','SCRATCH','NORMAL','LOWER','OLDBELTNEW','CHECKIN') COLLATE utf8_unicode_ci  DEFAULT 'NORMAL' COMMENT '新活动的类型',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  `limit` varchar(255) COLLATE utf8_unicode_ci COMMENT '限制条件',
  `employee_id` int(8)  DEFAULT 0 COMMENT '员工id',
  `one_prize` int(8)  DEFAULT 0,
  `one_count` int(8)  DEFAULT 0,
  `two_prize` int(8)  DEFAULT 0,
  `two_count` int(8)  DEFAULT 0,
  `three_prize` int(8)  DEFAULT 0,
  `three_count` int(8)  DEFAULT 0,
  `share_img` varchar(255) COLLATE utf8_unicode_ci,
  `share_title` varchar(255) COLLATE utf8_unicode_ci,
  `share_des` varchar(255) COLLATE utf8_unicode_ci,
  `prize_id` int(10) DEFAULT nll,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=120 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_activity_coupontype`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_activity_coupontype` (
  `activity_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公寓id',
  `coupon_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '优惠券类型的id',
  `count` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '赠送券的数量',
  `min` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '吸粉活动时的最低细粉量',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_activity_prize`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_activity_prize` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `prize` varchar(255) NOT NULL,
  `count` varchar(255) NOT NULL,
  `grant` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP COMMENT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8;

--
-- Table structure for table `boss_activity_record`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_activity_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `activity_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '活动的id',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '被助力的用户id',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '扫码时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间,无用',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1824 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_api`
--

CREATE TABLE `boss_api` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `apikey` varchar(255) NOT NULL DEFAULT '',
  `apisecret` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  `note` text NOT NULL COMMENT '备注说明',
  PRIMARY KEY (`id`),
  UNIQUE KEY `apikey_UNIQUE` (`apikey`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;


--
-- Table structure for table `boss_bill`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_bill` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sequence_number` char(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '流水号',
  `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公寓的id',
  `employee_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '员工的id',
  `resident_id` int(10) unsigned DEFAULT '0' COMMENT '住户id',
  `customer_id` int(11) DEFAULT NULL,
  `uxid` int(10) unsigned DEFAULT '0' COMMENT '客户id',
  `room_id` int(10) unsigned DEFAULT '0' COMMENT '房间id',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '支付金额',
  `type` enum('INPUT','OUTPUT') NOT NULL DEFAULT 'INPUT' COMMENT '收入/支出',
  `pay_type` enum('JSAPI','BANK','ALIPAY','DEPOSIT') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '支付方式,微信支付,刷卡,支付宝,押金抵扣',
  `confirm` int(11) DEFAULT '0' COMMENT '是否 确认 0/1 （需要status？）',
  `pay_date` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '支付截止日或者实际支付时间',
  `remark` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT '' COMMENT '备注',
  `created_at` datetime DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `deleted_at` datetime DEFAULT NULL,
  `data` varchar(1000) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT '' COMMENT '数据',
  `updated_at` datetime DEFAULT NULL,
  `confirm_date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('UNDEAL','DONE') DEFAULT 'UNDEAL',
  `out_trade_no` int(11) DEFAULT NULL,
  `store_pay_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11817 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_building`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_building` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '楼栋id',
  `store_id` int(11) NOT NULL COMMENT '所属门店的id',
  `name` varchar(255) NOT NULL COMMENT '楼栋名称',
  `layer_total` int(11) DEFAULT NULL COMMENT '总层高',
  `layer_room_number` int(11) DEFAULT NULL COMMENT '每层的房间数目',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_checkout_record`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_checkout_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公寓的id',
  `room_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房间的id',
  `resident_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '住户的id',
  `employee_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '办理该业务的员工id',
  `pay_or_not` tinyint(4) unsigned NOT NULL DEFAULT '0' COMMENT '住户是否主动支付账单',
  `status` enum('APPLIED','UNPAID','PENDING','BY_MANAGER','MANAGER_APPROVED','PRINCIPAL_APPROVED','COMPLETED') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'UNPAID' COMMENT '该退房账单的状态',
  `type` enum('NORMAL_REFUND','UNDER_CONTRACT') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'NORMAL_REFUND' COMMENT '住户是否是违约退房',
  `rent_deposit_deduction` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '住宿押金抵扣金额',
  `other_deposit_deduction` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '其他押金抵扣金额',
  `deposit_trans` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '押金转物业服务费金额',
  `debt` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '欠款',
  `refund` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '退还金额',
  `bank` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '退款银行',
  `account` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '开户人姓名',
  `bank_card_number` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '银行卡号',
  `employee_remark` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '店员备注',
  `manager_remark` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '店长备注',
  `principal_remark` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '运营经理备注',
  `bank_sequence` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `accountant_remark` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '财务备注',
  `bank_card_img` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '银行卡照片路径',
  `data` varchar(1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '存储住户退房过程中的一些信息',
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '退房时间',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=148 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_community`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_community` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '分布式小区管理community',
  `store_id` int(11) DEFAULT NULL COMMENT '门店id',
  `name` varchar(255) NOT NULL COMMENT '小区名称',
  `province` varchar(255) DEFAULT NULL COMMENT '省份',
  `city` varchar(255) DEFAULT NULL COMMENT '城市',
  `district` varchar(255) DEFAULT NULL COMMENT '区',
  `city_id` int(11) DEFAULT NULL,
  `address` text COMMENT '详细地址',
  `environment_id` int(11) DEFAULT NULL,
  `shop` text COMMENT '商场',
  `relax` text COMMENT '休闲',
  `history` text COMMENT '医院',
  `bus` text COMMENT '交通',
  `images` text,
  `describe` text COMMENT '描述',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_contract`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_contract` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `city_id` int(10) NOT NULL DEFAULT '0' COMMENT '城市id',
  `store_id` int(10) NOT NULL DEFAULT '0' COMMENT '公寓id',
  `room_id` int(10) NOT NULL DEFAULT '0' COMMENT '房间id',
  `resident_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '住户id',
  `employee_id` int(11) DEFAULT '0',
  `type` enum('FDD','NORMAL') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'NORMAL' COMMENT '该公寓签合同类型, FDD:法大大合同, NORMAL:纸质合同',
  `contract_id` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '合同id,与法大大系统对应',
  `fdd_customer_id` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '客户在法大大系统中注册后获取的id',
  `doc_title` char(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '合同标题',
  `download_url` text COLLATE utf8_unicode_ci COMMENT '合同下载链接',
  `view_url` text COLLATE utf8_unicode_ci COMMENT '合同预览链接',
  `status` enum('GENERATED','SIGNED_A','SIGNED_B','SIGNING','ARCHIVED') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'GENERATED' COMMENT '合同的状态',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `customer_id` int(11) DEFAULT NULL,
  `uxid` int(11) DEFAULT NULL COMMENT '客户的id',
  `sign_type` enum('NEW','RENEW','CHANGE') CHARACTER SET utf8 DEFAULT NULL COMMENT '新签，续新，换房',
  `serial_number` char(32) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '法大大系统需要的合同编号（未使用）',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2142 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_contract_template`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_contract_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '合同模板id',
  `company_id` int(11) DEFAULT NULL COMMENT '公司id',
  `store_id` int(11) NOT NULL COMMENT '门店id',
  `room_type_id` int(11) DEFAULT NULL COMMENT '导数据用之后删除',
  `rent_type` enum('LONG','SHORT','RESERVE') DEFAULT 'LONG' COMMENT '长租 短租 预定',
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL COMMENT '模板的链接地址',
  `fdd_tpl_id` varchar(32) DEFAULT NULL COMMENT 'fdd上传id',
  `contract_tpl_path` varchar(255) DEFAULT NULL COMMENT 'fdd路径',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_coupon`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_coupon` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'customers表中的id',
  `resident_id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '使用了该券的订单id',
  `activity_id` int(10) NOT NULL,
  `company_id` int(8) DEFAULT null,
  `store_id` varcher(255) DEFAULT null,
  `coupon_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'coupons表中的id',
  `status` enum('ASSIGNED','UNUSED','ROLLBACKING','OCCUPIED','USED','EXPIRED','INACTIVE') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'UNUSED' COMMENT '优惠券状态 ASSIGNED:已分配,但是未审核,不对用户显示, 用于吸粉活动的优惠券派发 UNUSED:未使用, ROLLBACKING:由于订单取消等原因,优惠券回滚到未使用状态中, OCCUPIED:被占用,支付时已使用,订单未完成, USED:已经使用, EXPIRED:已经过期, 未激活（未到开始使用日期）',
  `deadline` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '过期时间',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '领取时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6534 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_coupon_type`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_coupon_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('CASH','DISCOUNT','REMIT') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'CASH' COMMENT '优惠券类型 CASH:代金券 DISCOUNT:打折券 REMIT:减免券',
  `limit` enum('ROOM','MANAGEMENT') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ROOM' COMMENT '使用限制 ROOM:房租, UTILITY:水电 MANAGEMENT:服务管理费 NONE:所有都可以',
  `valid_time` mediumint(9) NOT NULL DEFAULT '0' COMMENT '优惠券的有效时长,单位(天)',
  `deadline` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '使用截止日, deadline 和 valid_time 二者必须设置一个',
  `status` enum('GENERATED','ACTIVATED','FINISHED','ABANDONED') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'GENERATED' COMMENT 'GENERATED:生成, ACTIVATED:已经激活(有领取过即为激活), FINISHED:结束了, ABANDONED:已经删除了,暂时有这么个状态吧',
  `name` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '优惠券的名称',
  `description` text COLLATE utf8_unicode_ci NOT NULL COMMENT '优惠券的简要描述',
  `discount` mediumint(9) NOT NULL DEFAULT '0' COMMENT '代金券的面额或者折扣券的折扣, 如果是折扣券, 则要求是100以内的整数',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_customer`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_customer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uxid` int(10) unsigned DEFAULT NULL COMMENT '用户id',
  `company_id` int(11) NOT NULL,
  `name` char(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '姓名',
  `phone` char(11) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '手机',
  `openid` char(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '微信的唯一id',
  `subscribe` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否关注',
  `nickname` varchar(64) NOT NULL DEFAULT '' COMMENT '微信昵称',
  `gender` enum('M','W','N') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'N' COMMENT '微信性别',
  `province` char(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '省份',
  `city` char(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '城市',
  `avatar` varchar(256) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '头像',
  `country` char(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '国家',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  `unionid` char(32) CHARACTER SET utf8 DEFAULT NULL COMMENT '微信unionid',
  PRIMARY KEY (`id`),
  UNIQUE KEY `wechat_openid` (`openid`)
) ENGINE=InnoDB AUTO_INCREMENT=13069 DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_device`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_device` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公寓的id',
  `room_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房型的id',
  `room_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房间的id',
  `year` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '选择的年',
  `month` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '选择的月份',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '支付的金额',
  `status` enum('PENDING','CONFIRM','COMPLATE') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'PENDING' COMMENT '状态',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=172 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

-- ----------------------------
-- Table structure for boss_draw
-- ----------------------------
CREATE TABLE `boss_draw` (
  `id` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `activity_id` int(11) NOT NULL DEFAULT 0 COMMENT '活动id',
  `customer_id` int(11) NOT NULL DEFAULT 0 COMMENT '用户id',
  `draw_time` datetime NOT NULL  DEFAULT '0000-00-00 00:00:00' COMMENT '抽奖时间',
  `is_draw` tinyint(11) NOT NULL DEFAULT 0 COMMENT '是否中奖',
  `prize_id` int(11) NOT NULL DEFAULT 0 COMMENT '奖品id',
  `prize_name` varchar(255) NOT NULL DEFAULT '' COMMENT '奖品名称',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=134 DEFAULT CHARSET=utf8;

--
-- Table structure for table `boss_employee`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_employee` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL COMMENT '姓名',
  `phone` varchar(13) DEFAULT NULL COMMENT '手机号',
  `base_position` enum('PRINCIPAL','MANAGER','EMPLOYEE','ADMIN_USERS') DEFAULT 'EMPLOYEE' COMMENT '定位',
  `bxid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公司用户的id',
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所属公司',
  `position_id` int(11) DEFAULT NULL COMMENT '职位id',
  `store_id` int(11) NOT NULL COMMENT '当前操作的门店id',
  `store_ids` varchar(255) DEFAULT NULL COMMENT '可操作的门店（存入json格式）',
  `store_names` text COMMENT '门店名称',
  `nickname` varchar(255) DEFAULT NULL COMMENT '昵称',
  `gender` enum('N','W','M','MAN','WOMAN','UNKNOW') NOT NULL DEFAULT 'UNKNOW' COMMENT '性别',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像',
  `province` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `openid` varchar(255) DEFAULT NULL COMMENT '员工的网站应用登录openid',
  `unionid` varchar(255) DEFAULT NULL COMMENT '微信unionid',
  `mini_openid` varchar(255) DEFAULT NULL COMMENT '小程序openid',
  `employee_mp_openid` varchar(255) DEFAULT NULL COMMENT '员工公众号openid',
  `session_key` varchar(255) DEFAULT NULL,
  `hiredate` date NOT NULL DEFAULT '0000-00-00' COMMENT '入职时间',
  `status` enum('N','Y','ENABLE','DISABLE') NOT NULL DEFAULT 'ENABLE' COMMENT '员工的状态',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `type` enum('EMPLOYEE','ADMIN') DEFAULT 'EMPLOYEE' COMMENT '管理员 员工',
  `position` enum('MANAGER','EMPLOYEE','PRINCIPAL') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'EMPLOYEE' COMMENT '职位',
  `email` char(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '邮箱',
  `password` char(60) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '密码',
  `token` char(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '登录的token',
  `login_ip` char(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '登录的ip地址',
  `login_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '登录的时间',
  `login_count` smallint(6) NOT NULL DEFAULT '0' COMMENT '登录的次数',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=177 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_fdd_transaction`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_fdd_transaction` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'fdd_contract表中的主键id',
  `transaction_id` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '交易号, 签署时生成',
  `role` enum('A','B') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'B' COMMENT '签署人的角色, A甲方, B乙方',
  `status` enum('INITIATED','SUCCEED','FAILED') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'INITIATED' COMMENT '交易/本次签署的状态',
  `remark` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '备注,不时之需',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3384 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_help_record`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_help_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `activity_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '活动的id',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '被助力的用户id',
  `helper_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '助力者的用户id',
  `remark` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '扫码关注后的留言',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '扫码时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间,无用',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1111 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_house`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_house` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '房屋id',
  `store_id` int(11) NOT NULL COMMENT '门店id',
  `community_id` int(11) NOT NULL DEFAULT '0' COMMENT '小区ID',
  `building_id` int(10) NOT NULL DEFAULT '0' COMMENT '楼栋ID',
  `building_name` varchar(128) NOT NULL COMMENT '楼栋号',
  `is_own` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否自持',
  `unit` int(11) NOT NULL COMMENT '单元号',
  `layer` int(11) NOT NULL DEFAULT '0' COMMENT '所在楼层',
  `layer_total` int(11) NOT NULL COMMENT '总楼层',
  `number` varchar(32) NOT NULL COMMENT '房号',
  `room_number` tinyint(4) NOT NULL DEFAULT '0' COMMENT '几室',
  `hall_number` tinyint(4) NOT NULL DEFAULT '0' COMMENT '几厅',
  `kitchen_count` tinyint(4) NOT NULL DEFAULT '0' COMMENT '几厨',
  `toilet_number` tinyint(4) NOT NULL DEFAULT '0' COMMENT '几卫',
  `people_count` smallint(5) NOT NULL,
  `status` enum('REPAIR','RENT','BLANK') NOT NULL DEFAULT 'BLANK' COMMENT '房间状态:空 出租 维修',
  `area` decimal(10,0) NOT NULL COMMENT '面积',
  `address` varchar(255) NOT NULL DEFAULT '' COMMENT '地址',
  `images` text NOT NULL COMMENT '房屋图片',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime NOT NULL,
  `rent_type` enum('FULL','HALF') NOT NULL COMMENT '出租类型 （整租，合租）',
  `smart_device_id` int(11) NOT NULL COMMENT '智能设备id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_lock_password`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_lock_password` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `smart_device_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '智能设备id',
  `pwd_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '密码的索引或pwdId',
  `password` char(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '加密后的密码',
  `type` enum('TENANT','BUTLER','ONCE') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'TENANT' COMMENT '密码类型 TENANT:房客密码 BUTLER:管家密码 ONCE:临时密码',
  `created_at` datetime NOT NULL DEFAULT '1970-01-01 01:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '1970-01-01 01:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=106 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_message_types`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_message_types` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` smallint(4) DEFAULT '0' COMMENT '0:初始值,1:停电通知,2:停水通知',
  `title` char(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '模板的标题',
  `template_id` char(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '模板消息的模板id',
  `content` varchar(1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '消息模板中的详细内容',
  `in_use` tinyint(4) NOT NULL DEFAULT '0' COMMENT '该模板是否使用',
  `created_at` datetime NOT NULL DEFAULT '2017-01-01 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '2017-01-01 00:00:00' COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `type` (`type`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_messages`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `message_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '消息模板的id',
  `apartment_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公寓id',
  `employee_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '员工id,标识是谁发送的通知',
  `content` varchar(1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '该通知的具体内容',
  `receiver` varchar(1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '通知范围',
  `status` enum('SUBMITTED','FAILED','SUCCEED') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'SUBMITTED' COMMENT '该通知的执行结果',
  `created_at` datetime NOT NULL DEFAULT '2017-01-01 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '2017-01-01 00:00:00' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_meter_reading`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_meter_reading` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房间id',
  `type` enum('COLD_WATER_METER','HOT_WATER_METER','ELECTRIC_METER') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'COLD_WATER_METER' COMMENT '表计类型：冷水表，热水表，电表',
  `reading` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '最新读数',
  `created_at` datetime NOT NULL DEFAULT '2017-01-01 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '2017-01-01 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44689 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_meter_reading_transfer`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_meter_reading_transfer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公寓id',
  `building_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '楼的id',
  `serial_number` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '设备编号',
  `room_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '楼的id',
  `resident_id` int(10) NOT NULL DEFAULT '0' COMMENT '租户ID',
  `year` int(8) NOT NULL DEFAULT '2018' COMMENT '年份',
  `month` int(4) NOT NULL DEFAULT '0' COMMENT '月份',
  `type` enum('COLD_WATER_METER','HOT_WATER_METER','ELECTRIC_METER') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'COLD_WATER_METER' COMMENT '表计类型：冷水表，热水表，电表',
  `last_reading` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '上一次的读数',
  `last_time` datetime NOT NULL DEFAULT '2017-01-01 00:00:00' COMMENT '上次抄表时间',
  `this_reading` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '最新读数',
  `this_time` datetime NOT NULL DEFAULT '2017-01-01 00:00:00' COMMENT '本次抄表时间',
  `weight` tinyint(4) NOT NULL DEFAULT '100' COMMENT '计算金额的比例',
  `status` enum('NORMAL','NEW_RNET','CHANGE_NEW','CHANGE_OLD') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'NORMAL' COMMENT '状态：NORMAL正常，CHANGE_NEW换表(新),NEW_RNET新入住,CHANGE_OLD换表(旧),',
  `confirmed` tinyint(4) NOT NULL DEFAULT '0' COMMENT '数据是否已经确认',
  `created_at` datetime NOT NULL DEFAULT '2017-01-01 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '2017-01-01 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`resident_id`,`year`,`month`,`type`,`room_id`,`serial_number`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4393 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_opendoor_records`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_opendoor_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '设备id',
  `room_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房间id',
  `type` enum('0','1','2','3') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT '密码类型',
  `pwd_id` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '密码id',
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '开锁时间',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=234899 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_operations`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_operations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bxid` int(10) unsigned DEFAULT '0' COMMENT '公司用户id',
  `company_id` int(10) unsigned DEFAULT NULL COMMENT '所属公司',
  `employee_id` int(10) unsigned DEFAULT NULL COMMENT '员工id',
  `name` varchar(255) DEFAULT NULL COMMENT '员工姓名',
  `url` varchar(255) DEFAULT NULL COMMENT '访问的url',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=223801 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_order`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `number` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '订单号',
  `sequence_number` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '流水号',
  `new_number` char(32) COLLATE utf8_unicode_ci DEFAULT NULL,
  `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公寓的id',
  `bill_id` int(11) DEFAULT NULL,
  `room_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房型id',
  `room_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房间id',
  `employee_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '员工的id',
  `resident_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '住户id',
  `customer_id` int(11) NOT NULL,
  `uxid` int(10) unsigned DEFAULT '0' COMMENT '客户id',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '支付金额',
  `paid` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实付金额',
  `pay_type` enum('JSAPI','BANK','ALIPAY','DEPOSIT') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'BANK' COMMENT '支付方式,微信支付,刷卡,支付宝,押金抵扣',
  `type` enum('ROOM','DEIVCE','UTILITY','REFUND','DEPOSIT_R','DEPOSIT_O','MANAGEMENT','OTHER','RESERVE','CLEAN','WATER','ELECTRICITY','COMPENSATION','REPAIR','HOT_WATER','OVERDUE') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ROOM' COMMENT '订单类型 房间 设备  水电费 退房 预订 清洁费 水费 电费 赔偿费 维修费 热水水费 滞纳金',
  `year` int(11) NOT NULL DEFAULT '0' COMMENT '年份',
  `month` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '几个月',
  `other_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '水电费或者设备的id',
  `status` enum('PENDING','CONFIRM','COMPLATE','REFUND','EXPIRE','AUDITED','GENERATE','CLOSE') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'PENDING' COMMENT '订单状态 查看 Ordermodel',
  `deal` enum('UNDONE','DONE') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'UNDONE' COMMENT '是否处理',
  `pay_date` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '支付截止日或者实际支付时间',
  `remark` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  `discount_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '优惠金额',
  `pay_status` enum('PAYMENT','SERVER','RENEWALS') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'PAYMENT' COMMENT '是否首次支付',
  `data` varchar(1000) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '数据',
  `old_number` char(255) CHARACTER SET utf8 DEFAULT NULL,
  `out_trade_no` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT '微信支付的订单号',
  `is_notify` tinyint(4) DEFAULT '0',
  `store_pay_id` int(11) DEFAULT NULL,
  `transfer_id_s` int(10) DEFAULT '0' COMMENT '初始水电读数ID',
  `transfer_id_e` int(10) DEFAULT '0' COMMENT '截止水电读数ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47785 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
--
-- Table structure for table `boss_owner`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_owner` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `house_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房间的id',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '客户的id',
  `employee_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '员工的id',
  `name` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '姓名',
  `phone` char(11) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `card_type` enum('P','F','E','C','B','A','6','2','1','0','OTHER') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT '证件类型',
  `card_number` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '证件号码',
  `delivery_date` date NOT NULL DEFAULT '1970-01-01' COMMENT '交付日期, 即合同开始日期',
  `start_date` date NOT NULL DEFAULT '1970-01-01' COMMENT '即合同开始日期',
  `end_date` date NOT NULL DEFAULT '1970-01-01' COMMENT '交付日期, 即合同开始日期',
  `status` enum('NORMAL','RESCISSION','INVALID') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'NORMAL' COMMENT '理解为小业主的状态,正常,解约,无效',
  `contract_years` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '合同日期',
  `emergency_name` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '紧急联系人的姓名',
  `emergency_phone` char(11) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '紧急联系人电话',
  `minimum_rent` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '保底租金',
  `bank_name` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '收款账户开户行',
  `account` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '开户人姓名',
  `bank_card_number` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '银行卡号',
  `own_account` tinyint(4) NOT NULL DEFAULT '0' COMMENT '银行账户是否是业主自己的',
  `address` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '通讯地址',
  `agent_info` varchar(1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '代理人信息',
  `id_card_urls` varchar(1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '证件照片url',
  `bank_card_urls` varchar(1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '银行卡照片url',
  `contract_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '合同文件url',
  `rent_increase_rate` varchar(1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '每年租金递增比例, 单位是百分比',
  `no_rent_days` varchar(1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '免租期期限, 单位是天',
  `created_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_owner_deduction`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_owner_deduction` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `house_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房间的id',
  `earnings_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '业主收益记录的id',
  `type` enum('REPAIR','OTHER') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'REPAIR' COMMENT '抵扣项目, REPAIR:维修, OTHER:预留字段, 其他',
  `remark` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '需要抵扣的金额',
  `created_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_owner_earning`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_owner_earning` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sequence_number` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '支付流水号',
  `apartment_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公寓的id',
  `house_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房间的id',
  `owner_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '客户微信记录的id',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '客户的id',
  `employee_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '操作员工的id',
  `year` smallint(10) NOT NULL DEFAULT '0' COMMENT '是哪一年的收益',
  `season` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是第几季度的收益',
  `start_date` date NOT NULL DEFAULT '1970-01-01' COMMENT '收益起算日',
  `end_date` date NOT NULL DEFAULT '1970-01-01' COMMENT '收益截止日',
  `pay_date` date NOT NULL DEFAULT '1970-01-01' COMMENT '实际打款日期',
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '账单期总金额',
  `deduction` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '抵扣金额,打款时确定',
  `earnings` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际收益,即打款金额',
  `receipt_path` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '打款凭证路径',
  `status` enum('GENERATED','PAID','CANCELLED') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'GENERATED' COMMENT '已生成, 已打款, 取消',
  `pay_way` enum('TRANSFER','OTHER') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'TRANSFER' COMMENT '支付方式',
  `created_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_owner_house`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_owner_house` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `number` char(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '门牌号',
  `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公寓id',
  `building_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '楼的id',
  `is_own` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否自持',
  `layer` smallint(6) unsigned NOT NULL DEFAULT '0' COMMENT '楼层',
  `area` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '面积',
  `room_count` tinyint(4) NOT NULL DEFAULT '0' COMMENT '室的数量',
  `hall_count` tinyint(4) NOT NULL DEFAULT '0' COMMENT '厅的数量',
  `kitchen_count` tinyint(4) NOT NULL DEFAULT '0' COMMENT '厨房的数量',
  `bathroom_count` tinyint(4) NOT NULL DEFAULT '0' COMMENT '卫的数目',
  `people_count` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '房间人数',
  `status` enum('BLANK','RENT','REPAIR') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'BLANK' COMMENT '房间状态:空 出租 维修',
  `address` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '房间地址',
  `created_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '1970-01-01 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1283 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_position`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_position` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '职位',
  `company_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL COMMENT '职位名称',
  `pc_privilege_ids` varchar(1024) DEFAULT NULL COMMENT 'pc端权限ids',
  `mini_privilege_ids` varchar(1024) DEFAULT NULL COMMENT 'mini端权限ids',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_privilege`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_privilege` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) DEFAULT NULL COMMENT '上一级权限id',
  `name` varchar(255) DEFAULT NULL COMMENT '权限名称',
  `url` varchar(255) DEFAULT NULL COMMENT '权限对应的url',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=126 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_provides`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_provides` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `room_type_id` int(11) DEFAULT NULL COMMENT '房型id',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_reserve_order`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_reserve_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '接待人',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '客户的id',
  `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公寓id',
  `room_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房型id',
  `name` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '客户姓名',
  `phone` char(11) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '客户电话',
  `visit_by` enum('PHONE','VISIT','WEB','WECHAT') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'WECHAT' COMMENT '看房或者预约的途径. PHONE:电话咨询, VISIT:现场看房, WEB:官网预约, WECHAT:订房系统预约',
  `guest_type` enum('A','B','C','D') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'A' COMMENT '客户类型,用 于评价客户的质量',
  `check_in_time` char(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '入住时间',
  `people_count` smallint(10) NOT NULL DEFAULT '0' COMMENT '入住人数',
  `status` enum('BEGIN','WAIT','INVALID','END') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'BEGIN',
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '预约时间',
  `work_address` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '工作地点',
  `require` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '需求',
  `info_source` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT '信息来源',
  `source` smallint(4) DEFAULT '0' COMMENT '0:初始值,1:58同城,2:豆瓣,3:租房网,4:嗨住,5:zuber,6:中介,7:路过,8:老带新,9:朋友介绍,10:微信,11:同行转介,12:闲鱼,13:蘑菇租房,14:微博,15:其它',
  `remark` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `room_id` int(10) DEFAULT '1' COMMENT '房间ID',
  `visit_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '看房时间',
  `reason` varchar(255) COLLATE utf8_unicode_ci DEFAULT ''' ''' COMMENT '未能入住原因',
  `Intention` enum('N','Y') COLLATE utf8_unicode_ci DEFAULT 'Y' COMMENT '是否有意向入住',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1902 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_resident`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_resident` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `room_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房间的id',
  `customer_id` int(11) unsigned DEFAULT '0',
  `uxid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '客户的id',
  `employee_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '员工的id',
  `discount_id` int(10) NOT NULL DEFAULT '0' COMMENT '对应折扣的记录id',
  `name` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '姓名',
  `phone` char(11) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `pay_frequency` tinyint(4) NOT NULL DEFAULT '1' COMMENT '付款形式(月付1, 季付3, 半年付6, 年付12)',
  `card_type` enum('IDCARD','P','F','E','C','B','A','6','2','1','0','OTHER') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'IDCARD' COMMENT '证件类型',
  `card_number` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '证件号码',
  `alternative` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '紧急联系人的姓名',
  `alter_phone` char(11) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '紧急联系人电话',
  `people_count` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '人数',
  `begin_time` datetime DEFAULT NULL COMMENT '租房开始',
  `end_time` datetime DEFAULT NULL COMMENT '租房结束',
  `refund_time` datetime DEFAULT NULL COMMENT '退租时间',
  `card_one` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '证件正面',
  `card_two` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '证件反面',
  `card_three` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '手持证件',
  `real_rent_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际租金',
  `real_property_costs` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实际物业费',
  `water_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '冷水价格',
  `hot_water_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '热水价格',
  `electricity_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '电价格',
  `discount_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '优惠金额',
  `status` enum('NOT_PAY','PRE_RESERVE','PRE_CHECKIN','PRE_CHANGE','PRE_RENEW','RESERVE','NORMAL','NORMAL_REFUND','UNDER_CONTRACT','INVALID','CHECKOUT','RENEWAL') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'NOT_PAY' COMMENT '办理入住未支付 办理预订未支付 预订转入住未支付 换房未支付 续约未支付 预订 正常 正常退房 违约退房 无效 已退房',
  `address` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '通讯地址',
  `special_term` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '合同中的特殊说明',
  `remark` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  `book_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '定金金额',
  `book_time` datetime DEFAULT NULL COMMENT '预订日期',
  `name_two` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '姓名二',
  `phone_two` char(11) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '手机号二',
  `card_number_two` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '证件号码二',
  `card_type_two` enum('IDCARD','P','F','E','C','B','A','6','2','1','0','OTHER') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'IDCARD' COMMENT '随住人证件类型',
  `contract_time` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '合同日期',
  `rent_type` enum('SHORT','LONG') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'LONG' COMMENT '用户是长租还是短租',
  `first_pay_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '首次支付',
  `deposit_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '押金',
  `deposit_month` smallint(5) unsigned NOT NULL DEFAULT '2' COMMENT '押金月份',
  `tmp_deposit` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '临时押金',
  `data` varchar(1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '存储住户的一些不常用的信息',
  `property_price` decimal(10,2) DEFAULT NULL COMMENT '未打折前',
  `rent_price` decimal(10,2) DEFAULT NULL COMMENT '未打折前',
  `type` enum('FIRST','RENEWAL') COLLATE utf8_unicode_ci DEFAULT 'FIRST',
  `check_images` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '入住时房间实拍',
  `old_phone` char(11) COLLATE utf8_unicode_ci  DEFAULT NULL COMMENT '老用户手机号',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3196 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_room_dot`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_room_dot` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '分布式房间表',
  `store_id` int(11) DEFAULT NULL COMMENT '门店id',
  `community_id` int(11) DEFAULT NULL COMMENT '小区id',
  `house_id` int(11) DEFAULT NULL COMMENT '房屋id',
  `sort` int(11) DEFAULT NULL COMMENT '排序',
  `number` varchar(32) DEFAULT NULL COMMENT '房间号',
  `area` decimal(10,2) DEFAULT NULL COMMENT '房间面积',
  `toward` enum('E','W','S','N','EW','SN') DEFAULT NULL COMMENT '朝向（东西南北）',
  `feature` enum('M','S','MT') DEFAULT NULL COMMENT '房间特色（M：主卧，S：次卧，MT:主卧独卫）',
  `provides` text COMMENT '房间配套',
  `contract_template_short_id` int(11) DEFAULT NULL COMMENT '短租模板',
  `contract_template_reserve_id` int(11) DEFAULT NULL COMMENT '预定模板',
  `contract_template_long_id` int(11) DEFAULT NULL COMMENT '长租模板',
  `keeper` varchar(255) DEFAULT NULL COMMENT '管家',
  `status` enum('BLANK','RESERVE','RENT','ARREARS','REFUND','OCCUPIED','OTHER','REPAIR') DEFAULT NULL COMMENT '''房间状态:空 预订 正常出租 欠费出租 退房 占用 其他 维修''',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `contract_min_time` int(11) DEFAULT NULL COMMENT '合同最少签约期限（月份计）',
  `contract_max_time` int(11) DEFAULT NULL COMMENT '合同最多签约期限（月份计）',
  `pay_frequency_allow` varchar(255) DEFAULT NULL COMMENT '允许的付款周期（月份 json格式）{1,2,3,6,12,24}',
  `deposit_type` enum('FREE') DEFAULT NULL COMMENT '押金信息（免押金）',
  `is_alone` enum('Y','N') DEFAULT NULL COMMENT '是否独租',
  `house_rent_type` enum('FULL','HALF') DEFAULT NULL COMMENT '房屋出租类型（整租，合租）',
  `rent_type` enum('LONG','SHORT') DEFAULT NULL COMMENT '长租还是短租',
  `hourse_smart_device_id` int(11) DEFAULT NULL COMMENT '房屋公共智能设备（仅用于房屋有合租的情况）',
  `smart_device_id` int(11) DEFAULT NULL COMMENT '房间智能设备',
  `resident_id` int(11) DEFAULT NULL COMMENT '当前住户id',
  `people_count` int(11) DEFAULT NULL COMMENT '房间住户人数',
  `begin_time` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '租房开始',
  `end_time` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '租房结束',
  `arrears` varchar(255) DEFAULT NULL COMMENT '欠费信息',
  `rent_price` decimal(10,2) DEFAULT NULL COMMENT '租金',
  `property_price` decimal(10,2) DEFAULT NULL COMMENT '物业费',
  `cold_water_price` decimal(10,2) DEFAULT NULL COMMENT '冷水价',
  `hot_water_price` decimal(10,2) DEFAULT NULL COMMENT '热水价',
  `electricity_price` decimal(10,2) DEFAULT NULL COMMENT '电价',
  `images` text COMMENT '房间图片',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_room_type`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_room_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '房型id',
  `store_id` int(11) NOT NULL,
  `building_id` int(10) DEFAULT NULL COMMENT '楼栋id',
  `name` varchar(255) NOT NULL COMMENT '房型名称',
  `feature` text COMMENT '房型特点',
  `area` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '面积',
  `room_number` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '几个房室',
  `hall_number` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '几个大厅',
  `toilet_number` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '几个卫生间',
  `toward` enum('E','W','S','N','EW','SN') DEFAULT NULL COMMENT '东西南北',
  `provides` text COMMENT '房型配套(json)',
  `images` text COMMENT '房型图片',
  `is_show` enum('Y','N') NOT NULL DEFAULT 'Y' COMMENT '是否展示',
  `description` text NOT NULL COMMENT '描述',
  `fdd_tpl_id` varchar(1024) NOT NULL DEFAULT '' COMMENT '法大大合同的合同模板id',
  `contract_tpl_path` varchar(1024) NOT NULL DEFAULT '' COMMENT '合同模板在本地的路径及相对路径',
  `display` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否展示',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=91 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_room_union`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_room_union` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '集中式房间id',
  `store_id` int(11) unsigned DEFAULT NULL COMMENT '门店id',
  `building_id` int(11) DEFAULT NULL COMMENT '楼栋id',
  `building_name` varchar(128) DEFAULT NULL COMMENT '楼栋名称',
  `room_type_id` int(11) unsigned DEFAULT NULL COMMENT '房型id',
  `layer_total` int(11) DEFAULT NULL COMMENT '总层高',
  `layer` int(11) DEFAULT NULL COMMENT '楼层',
  `provides` text,
  `number` varchar(32) DEFAULT NULL COMMENT '门牌号',
  `contract_template_short_id` int(11) DEFAULT NULL COMMENT '短租模板',
  `contract_template_long_id` int(11) DEFAULT NULL COMMENT '长租模板',
  `contract_template_reserve_id` int(11) DEFAULT NULL COMMENT '预定合同模板',
  `rent_price` decimal(10,2) DEFAULT NULL COMMENT '租金价格',
  `property_price` decimal(10,2) DEFAULT NULL COMMENT '物业费',
  `status` enum('BLANK','RESERVE','RENT','ARREARS','REFUND','OCCUPIED','OTHER','REPAIR') NOT NULL DEFAULT 'BLANK' COMMENT '''房间状态:空 预订 正常出租 欠费出租 退房 占用 其他 维修''',
  `keeper` varchar(255) DEFAULT NULL COMMENT '管家',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `deposit_type` enum('FREE') DEFAULT NULL COMMENT '押金信息（免押金）',
  `contract_min_time` int(11) DEFAULT NULL COMMENT '合同最少签约期限（月份计）',
  `contract_max_time` int(11) DEFAULT NULL COMMENT '合同最多签约期限（月份计）',
  `pay_frequency_allow` varchar(255) DEFAULT NULL COMMENT '允许的付款周期（月份 json格式）{1,2,3,6,12,24}',
  `is_alone` enum('Y','N') NOT NULL COMMENT '是否独租（还是有同住人）',
  `house_rent_type` enum('FULL','HALF') DEFAULT NULL COMMENT '房屋出租类型（整租，合租）',
  `rent_type` enum('LONG','SHORT') DEFAULT NULL COMMENT '长租还是短租',
  `hourse_smart_device_id` int(10) unsigned DEFAULT NULL COMMENT '房屋公共智能设备（仅用于房屋有合租的情况）',
  `smart_device_id` int(11) DEFAULT NULL COMMENT '房间智能设备',
  `resident_id` int(11) DEFAULT NULL COMMENT '当前住户id',
  `people_count` int(11) DEFAULT NULL COMMENT '居住人数',
  `begin_time` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '租房开始',
  `end_time` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '租房截止',
  `arrears` varchar(255) DEFAULT NULL COMMENT '欠费信息',
  `area` decimal(10,2) DEFAULT NULL COMMENT '房间面积',
  `cold_water_price` decimal(10,2) DEFAULT NULL COMMENT '展示的冷水价格',
  `hot_water_price` decimal(10,2) DEFAULT NULL COMMENT '展示的热水价格',
  `electricity_price` decimal(10,2) DEFAULT NULL COMMENT '展示的电费',
  `device_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2174 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_service_order`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_service_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `number` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '订单号',
  `sequence_number` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '流水号',
  `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公寓的id',
  `room_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房间id',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '住户id',
  `employee_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '员工id',
  `service_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '服务类型ID',
  `type` enum('CLEAN','REPAIR') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'CLEAN' COMMENT '服务订单的类型. CLEAN:清洁, REPAIR:维修',
  `name` char(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '客户姓名',
  `phone` char(11) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '电话号码',
  `addr_from` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '地址 始点',
  `addr_to` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '地址 目的地',
  `estimate_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '预估费用',
  `pay_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '支付费用',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '服务费用',
  `status` enum('SUBMITTED','PENDING','PAID','SERVING','COMPLETED','CANCELED','WAITING','CONFIRMED','EXPIRED') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'SUBMITTED' COMMENT '订单状态. 已提交, 待支付, 已支付, 处理中, 完成, 取消',
  `deal` enum('UNDONE','SDOING','SDONE','PDONE') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'UNDONE' COMMENT '是否处理',
  `time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '预约时间',
  `remark` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '客户备注',
  `paths` varchar(1000) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '维修上传的图片',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `order_id` int(10) NOT NULL DEFAULT '0' COMMENT '账单ID（关联账单表）',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=112 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_service_type`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_service_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '服务名称',
  `feature` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '服务特点',
  `description` text COLLATE utf8_unicode_ci NOT NULL COMMENT '简介',
  `image_url` char(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '图片地址',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_shop_address`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_shop_address` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '客户id',
  `name` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '收货人',
  `phone` char(11) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '电话',
  `apartment` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '公寓',
  `building` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '楼',
  `room_number` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '房间号',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `uxid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '客户id',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_shop_cart`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_shop_cart` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '客户id',
  `goods_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `quantity` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '数量',
  `uxid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '客户id',
  `updated_at` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=113 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_shop_category`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_shop_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` char(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `is_show` enum('Y','N') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Y' COMMENT '是否显示',
  `sort` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_shop_goods`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_shop_goods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类id',
  `name` char(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '商品名称',
  `market_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '市场价格',
  `shop_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '本店价格',
  `description` text COLLATE utf8_unicode_ci NOT NULL COMMENT '商品简要描述',
  `detail` text COLLATE utf8_unicode_ci NOT NULL COMMENT '商品详情',
  `quantity` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '商品数量',
  `sale_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '已卖出数量',
  `goods_thumb` char(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '商品缩略图',
  `on_sale` enum('Y','N') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Y' COMMENT '是否上架',
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `original_link` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '原始地址',
  `deleted_at` datetime DEFAULT NULL,
  `goods_carousel` varchar(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '商品轮播图',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_shop_image`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_shop_image` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `goods_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `url` char(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '地址',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_shop_order`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_shop_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `number` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '订单号',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '客户id',
  `address_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '地址的id',
  `status` enum('PENDING','PAYMENT','DELIVERED','COMPLETE','CANCEL','EXPIRE') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'PENDING' COMMENT '订单状态',
  `remark` char(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '用户备注',
  `goods_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品总金额',
  `pay_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '支付金额',
  `goods_quantity` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '商品数量',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `number` (`number`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_shop_order_goods`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_shop_order_goods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单id',
  `goods_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '商品id',
  `quantity` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '数量',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '价格',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_smart_device`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_smart_device` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房间id',
  `type` enum('LOCK','HOT_WATER_METER','COLD_WATER_METER','ELECTRIC_METER') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'LOCK' COMMENT '设备类型:门锁, 热水表, 冷水表, 电表',
  `serial_number` char(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '设备编号',
  `supplier` enum('CJOY','DANBAY','YEEUU') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'CJOY' COMMENT '设备供应商, 超仪, 蛋贝, 云柚',
  `created_at` datetime NOT NULL DEFAULT '2017-01-01 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '2017-01-01 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  `store_id` int(10) DEFAULT NULL COMMENT '门店ID',
  PRIMARY KEY (`id`),
  UNIQUE KEY `sn` (`serial_number`)
) ENGINE=InnoDB AUTO_INCREMENT=2581 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_smart_device_record`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_smart_device_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '设备信息记录',
  `type` enum('HOUSE','ROOM') NOT NULL DEFAULT 'ROOM' COMMENT '设备记录（HOUSE,房屋表计，ROOM，房间表计）',
  `smart_device_id` int(11) DEFAULT NULL COMMENT '智能设备id',
  `smart_device_type` enum('LOCK','COLD_WATER_METER','HOT_WATER_METER','ELECTRIC_METER') DEFAULT NULL COMMENT '设备类型(门锁，冷水表，热水表，电表)',
  `last_reading` decimal(10,2) DEFAULT '0.00' COMMENT '上一次读数',
  `this_reading` decimal(10,2) DEFAULT NULL COMMENT '这次读数',
  `status` enum('WAITING','COMFIRM','PROBLEM') DEFAULT 'WAITING' COMMENT '状态（等待确认，确认，有问题）',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL COMMENT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_smart_lock_record`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_smart_lock_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('ROOM','HOUSE') NOT NULL DEFAULT 'ROOM' COMMENT '设备记录（HOUSE,房屋表计，ROOM，房间表计）',
  `smart_device_id` int(11) DEFAULT NULL,
  `open_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '开锁时间',
  `smart_device_type` varchar(10) NOT NULL DEFAULT 'LOCK' COMMENT '设备类型',
  `unlock_person` varchar(128) DEFAULT '' COMMENT '开锁认',
  `unlock_way` enum('SERVER_COMMAND','NORMAL','CALL_API','PASSWORD','DYNAMIC_PWD') NOT NULL DEFAULT 'PASSWORD' COMMENT '开门方式：''SERVER_COMMAND'':服务器接受命令,''NORMAL'':机械开门(传统方式),''CALL_API'':远程网络开门,''PASSWORD'':密码开门,''DYNAMIC_PWD'':动态密码',
  `created_at` datetime DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_store`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_store` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'store_id',
  `name` varchar(255) NOT NULL COMMENT '门店名称',
  `abbreviation` varchar(8) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '公寓名称缩写',
  `contract_type` enum('FDD','NORMAL') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'NORMAL' COMMENT '该公寓签合同类型, FDD:法大大合同, NORMAL:纸质合同',
  `company_id` int(10) unsigned NOT NULL COMMENT '门店所属公司id',
  `rent_type` enum('UNION','DOT') DEFAULT NULL COMMENT '门店模式类型 分布式/集中式',
  `fdd_customer_id` char(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `theme` text COMMENT '主题',
  `features` text COMMENT '特色',
  `province` varchar(255) DEFAULT NULL COMMENT '省份',
  `city` varchar(255) DEFAULT NULL COMMENT '城市',
  `district` varchar(255) DEFAULT NULL COMMENT '区',
  `city_id` int(11) DEFAULT NULL COMMENT '与city表对应',
  `address` text COMMENT '门店地址',
  `contact_user` varchar(255) DEFAULT NULL COMMENT '联系人',
  `contact_phone` varchar(13) DEFAULT NULL COMMENT '联系电话',
  `counsel_phone` varchar(14) DEFAULT NULL COMMENT '咨询电话',
  `counsel_time` varchar(255) DEFAULT NULL COMMENT '咨询时间',
  `environment_id` int(10) unsigned DEFAULT NULL COMMENT '环境配套id（预留）',
  `describe` text,
  `shop` text COMMENT '配套（商场）',
  `relax` text COMMENT '休闲',
  `bus` text COMMENT '配套（交通）',
  `history` text COMMENT '配套（医院）',
  `service_type_ids` varchar(255) DEFAULT NULL COMMENT '门店可选的服务类型（json）{[1,2,3]}',
  `images` text COMMENT '门店图片的url地址（json格式）',
  `status` enum('NORMAL','CLOSE','WAIT') NOT NULL DEFAULT 'NORMAL',
  `payment_merchant_id` char(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `payment_key` char(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `contract_number_prefix` char(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `open` enum('Y','N') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Y' COMMENT '是否开启',
  `pay_online` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否开启在线支付',
  `advisory_time` char(32) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '咨询时间',
  `advisory_phone` char(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '咨询电话',
  `lng` char(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '地址的经度',
  `lat` char(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '地址的纬度',
  `dis_month` smallint(6) NOT NULL DEFAULT '100' COMMENT '一月优惠',
  `dis_quarter` smallint(6) NOT NULL DEFAULT '100' COMMENT '季度优惠',
  `dis_half` smallint(6) NOT NULL DEFAULT '100' COMMENT '半年优惠',
  `dis_year` smallint(6) NOT NULL DEFAULT '100' COMMENT '一年优惠',
  `water_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '冷水价格',
  `hot_water_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '热水价格',
  `electricity_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '用电价格',
  `description` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL COMMENT '公寓描述',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_store_activity`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_store_activity` (
  `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公寓id',
  `activity_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '活动id',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_store_pay`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_store_pay` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) DEFAULT NULL,
  `out_trade_no` varchar(255) DEFAULT NULL,
  `amount` int(10) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `status` enum('DONE','UNDONE') NOT NULL DEFAULT 'UNDONE',
  `resident_id` int(11) DEFAULT NULL,
  `orders` varchar(255) DEFAULT NULL,
  `discount` varchar(255) DEFAULT NULL,
  `data` text,
  `date` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '各种时间',
  `start_date` datetime DEFAULT NULL COMMENT '调起config的时间',
  `notify_date` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=981 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_utility_reading`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_utility_reading` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '订单id',
  `start_reading` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '最新读数',
  `end_reading` float(10,2) NOT NULL DEFAULT '0.00' COMMENT '最新读数',
  `weight` tinyint(4) NOT NULL DEFAULT '0' COMMENT '计算金额的比例',
  `created_at` datetime NOT NULL DEFAULT '2017-01-01 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '2017-01-01 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8953 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='水电表的具体读数';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fx_admin`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fx_admin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fxid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '梵响员工id',
  `name` varchar(128) DEFAULT '' COMMENT '用户名',
  `position` enum('ADMIN','FINANCE','APARTMENT') DEFAULT NULL COMMENT '职位',
  `nickname` varchar(128) DEFAULT '' COMMENT '昵称',
  `phone` varchar(13) DEFAULT '' COMMENT '用户手机号',
  `unionid` varchar(32) NOT NULL COMMENT 'UNIONID',
  `openid` varchar(32) NOT NULL DEFAULT '' COMMENT '梵响后台开放平台的openid',
  `hiredate` date NOT NULL DEFAULT '0000-00-00' COMMENT '入职时间',
  `token` varchar(32) DEFAULT '' COMMENT '登录的token',
  `status` enum('ENABLE','DISABLE') NOT NULL DEFAULT 'ENABLE' COMMENT '用户的状态',
  `avatar` varchar(256) DEFAULT '' COMMENT '头像',
  `login_count` smallint(6) DEFAULT '0' COMMENT '登录的次数',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `waid` (`fxid`) USING BTREE,
  UNIQUE KEY `phone` (`phone`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fx_company`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fx_company` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `bxid` int(10) DEFAULT NULL,
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '公司名称',
  `nickname` varchar(64) DEFAULT NULL COMMENT '公司简称',
  `address` text NOT NULL COMMENT '公司地址',
  `contact_user` varchar(64) NOT NULL DEFAULT '' COMMENT '联系人',
  `contact_phone` varchar(11) NOT NULL DEFAULT '0' COMMENT '联系电话',
  `base_position` enum('SUPER') NOT NULL DEFAULT 'SUPER' COMMENT 'super',
  `phone` varchar(14) DEFAULT NULL,
  `openid` varchar(64) DEFAULT '' COMMENT '微信openid',
  `unionid` varchar(64) DEFAULT NULL,
  `license` varchar(128) NOT NULL COMMENT '营业执照',
  `remark` text COMMENT '备注信息',
  `status` enum('NORMAL','CLOSE','UNSCAN') NOT NULL DEFAULT 'UNSCAN' COMMENT '状态(正常，关闭，未扫码)',
  `privilege` varchar(255) NOT NULL DEFAULT 'MOD_BASE' COMMENT '权限，开通的模块\nMOD_ALL\nMOD_BASE,MOD_A,MOD_B,MOD_C',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  `expiretime` timestamp NOT NULL DEFAULT '2037-12-30 16:00:00' COMMENT '失效时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `version` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `opendoor_records`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `opendoor_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '设备id',
  `room_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房间id',
  `type` enum('0','1','2','3') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT '密码类型',
  `pwd_id` varchar(16) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '密码id',
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '开锁时间',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=192814 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `orders`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `number` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '订单号',
  `sequence_number` char(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '流水号',
  `apartment_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公寓的id',
  `room_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房型id',
  `room_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房间id',
  `employee_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '员工的id',
  `resident_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '住户id',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '客户id',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '支付金额',
  `paid` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '实付金额',
  `pay_type` enum('JSAPI','BANK','ALIPAY','DEPOSIT') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'BANK' COMMENT '支付方式,微信支付,刷卡,支付宝,押金抵扣',
  `type` enum('ROOM','DEIVCE','UTILITY','REFUND','DEPOSIT_R','DEPOSIT_O','MANAGEMENT','OTHER','RESERVE','CLEAN','WATER','ELECTRICITY','COMPENSATION','REPAIR','HOT_WATER','OVERDUE') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'ROOM' COMMENT '订单类型 房间 设备  水电费 退房 预订 清洁费 水费 电费 赔偿费 维修费 热水水费 滞纳金',
  `year` int(11) NOT NULL DEFAULT '0' COMMENT '年份',
  `month` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '几个月',
  `status` enum('GENERATE','AUDITED','PENDING','CONFIRM','COMPLATE','REFUND','EXPIRE','CLOSE') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'PENDING' COMMENT '订单状态',
  `deal` enum('UNDONE','DONE') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'UNDONE' COMMENT '是否处理',
  `pay_date` date NOT NULL DEFAULT '1970-01-01' COMMENT '支付截止日或者实际支付时间',
  `remark` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `data` varchar(1000) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '数据',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `discount_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '优惠金额',
  `pay_status` enum('PAYMENT','RENEWALS') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'PAYMENT' COMMENT '是否首次支付',
  `other_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '水电费或者设备的id',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41469 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `public_city`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `public_city` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `city_id` varchar(20) NOT NULL,
  `city` varchar(50) NOT NULL,
  `province_id` varchar(20) NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=345 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='行政区域地州市信息表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `public_district`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `public_district` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `district_id` varchar(20) NOT NULL,
  `district` varchar(50) NOT NULL,
  `city_id` varchar(20) NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3124 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='行政区域县区信息表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `public_province`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `public_province` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `province_id` varchar(20) NOT NULL,
  `province` varchar(50) NOT NULL,
  `deleted_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8 COMMENT='省份信息表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `room_balance`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room_balance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房间的id',
  `year` year(4) NOT NULL DEFAULT '2016' COMMENT '该汇总记录所属的年份',
  `balance` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '房间的欠费情况,正表示有未缴的账单,负表示有预缴的费用',
  `deposit` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '房间的押金情况汇总',
  `created_at` datetime NOT NULL DEFAULT '1970-01-01 01:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '1970-01-01 01:00:00' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

-- 任务流相关数据表
CREATE TABLE `boss_taskflow` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'boss_taskflow 任务流表',
  `company_id` int(11) unsigned NOT NULL,
  `store_id` int(11) unsigned NOT NULL COMMENT '门店id',
  `template_id` int(11) unsigned NOT NULL,
  `room_id` int(10) unsigned NOT NULL,
  `serial_number` varchar(32) NOT NULL COMMENT '编号',
  `name` varchar(255) NOT NULL COMMENT '任务流模板的名称',
  `type` enum('CHECKOUT','PRICE') NOT NULL COMMENT '任务流模板的类型（CHECKOUT退房,PRICE调价）',
  `description` text NOT NULL COMMENT '描述',
  `create_role` enum('EMPLOYEE','CUSTOMER') NOT NULL COMMENT '发起人的角色（员工发起还是用户发起）',
  `employee_id` int(10) unsigned DEFAULT NULL COMMENT '创建或发起的员工id',
  `customer_id` int(11) DEFAULT NULL COMMENT '创建任务流的用户id',
  `leavel` smallint(4) unsigned NOT NULL DEFAULT '0' COMMENT '优先级 越小优先级越高，最小是0',
  `step_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最近处理过的step_id',
  `status` enum('AUDIT','APPROVED','CLOSED','UNAPPROVED') NOT NULL DEFAULT 'AUDIT',
  `remark` text,
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uni_sn` (`serial_number`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `boss_taskflow_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `step_id` int(11) DEFAULT NULL,
  `taskflow_id` int(11) DEFAULT NULL COMMENT '对应的任务流id',
  `company_id` int(11) unsigned NOT NULL,
  `store_id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL COMMENT '步骤的名称',
  `seq` int(11) unsigned NOT NULL COMMENT '步骤序号',
  `type` enum('CHECKOUT','PRICE') NOT NULL,
  `employee_id` int(11) DEFAULT NULL COMMENT '处理员工id',
  `status` enum('AUDIT','APPROVED','UNAPPROVED','CLOSED') NOT NULL DEFAULT 'AUDIT' COMMENT '待审核，审核通过，审核未通过',
  `remark` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `boss_taskflow_step` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `step_template_id` int(10) unsigned DEFAULT NULL COMMENT '步骤模板id',
  `taskflow_id` int(11) DEFAULT NULL COMMENT '对应的任务流id',
  `company_id` int(11) unsigned NOT NULL,
  `store_id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL COMMENT '步骤的名称',
  `seq` int(11) unsigned NOT NULL COMMENT '步骤序号',
  `type` enum('CHECKOUT','PRICE') NOT NULL,
  `position_ids` varchar(255) DEFAULT NULL COMMENT '可操作职位',
  `employee_ids` varchar(255) DEFAULT NULL COMMENT '可操作员工ids',
  `employee_id` int(11) DEFAULT NULL COMMENT '处理员工id',
  `status` enum('AUDIT','APPROVED','UNAPPROVED','CLOSED') NOT NULL DEFAULT 'AUDIT' COMMENT '待审核，审核通过，审核未通过',
  `remark` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `boss_taskflow_step_template` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '任务流步骤',
  `company_id` int(11) unsigned NOT NULL,
  `template_id` int(11) unsigned NOT NULL COMMENT '任务流模板id',
  `name` varchar(255) DEFAULT NULL COMMENT '步骤的名称',
  `seq` int(11) unsigned NOT NULL COMMENT '步骤序号',
  `type` enum('CHECKOUT','PRICE') NOT NULL,
  `position_ids` varchar(255) DEFAULT NULL COMMENT '可操作职位',
  `employee_ids` varchar(255) DEFAULT NULL COMMENT '可操作员工ids',
  `data` text,
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `boss_taskflow_template` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(11) unsigned NOT NULL,
  `name` varchar(255) NOT NULL COMMENT '任务流模板的名称',
  `type` enum('CHECKOUT','PRICE') NOT NULL COMMENT '任务流模板的类型（CHECKOUT退房）',
  `description` text COMMENT '描述',
  `employee_id` int(10) unsigned NOT NULL COMMENT '创建或修改的操作人id',
  `data` text COMMENT '一些备注信息',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

CREATE TABLE `boss_price_control` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` int(10) unsigned NOT NULL,
  `store_id` int(10) unsigned NOT NULL,
  `room_id` int(10) unsigned NOT NULL,
  `type` enum('ROOM','MANAGEMENT') NOT NULL COMMENT '调价类型 调房租调物业费',
  `taskflow_id` int(10) unsigned NOT NULL,
  `status` enum('AUDIT','DONE','CLOSED') NOT NULL COMMENT '状态 审核中，执行调价完成，关闭，',
  `employee_id` int(11) NOT NULL COMMENT '申请调价的员工',
  `ori_price` decimal(10,2) NOT NULL,
  `new_price` decimal(10,2) NOT NULL,
  `remark` text NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;










