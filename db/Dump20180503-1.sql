-- MySQL dump 10.13  Distrib 5.7.17, for macos10.12 (x86_64)
--
-- Host: 120.79.181.154    Database: funxdata
-- ------------------------------------------------------
-- Server version	5.7.21

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

--
-- Table structure for table `boss_building`
--

DROP TABLE IF EXISTS `boss_building`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_building` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '楼栋id',
  `store_id` int(11) DEFAULT NULL COMMENT '所属门店的id',
  `name` varchar(255) DEFAULT NULL COMMENT '楼栋名称',
  `number` int(11) DEFAULT NULL COMMENT '几号楼',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_contract`
--

DROP TABLE IF EXISTS `boss_contract`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_contract` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int(10) NOT NULL DEFAULT '0' COMMENT '房间id',
  `resident_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '住户id',
  `type` enum('FDD','NORMAL') NOT NULL DEFAULT 'NORMAL' COMMENT '合同类型, FDD:法大大合同, NORMAL:其他合同',
  `serial_number` char(32) NOT NULL DEFAULT '' COMMENT '法大大系统需要的合同编号',
  `doc_title` char(64) NOT NULL DEFAULT '' COMMENT '合同标题',
  `download_url` text COMMENT '合同下载链接',
  `view_url` text COMMENT '合同预览链接',
  `status` enum('GENERATED','SIGNED_A','SIGNED_B','SIGNING','ARCHIVED') NOT NULL DEFAULT 'GENERATED' COMMENT '合同的状态',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `deletetd_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_contract_template`
--

DROP TABLE IF EXISTS `boss_contract_template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_contract_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '合同模板id',
  `rent_type` enum('LONG','SHORT') DEFAULT 'LONG',
  `name` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL COMMENT '模板的链接地址',
  `fdd_tpl_id` varchar(32) DEFAULT NULL COMMENT 'fdd上传id',
  `fdd_tpl_path` varchar(255) DEFAULT NULL COMMENT 'fdd路径',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_employee`
--

DROP TABLE IF EXISTS `boss_employee`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_employee` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bxid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公司用户的id',
  `company_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '所属公司',
  `store_ids` varchar(11) DEFAULT NULL COMMENT '可操作的门店（存入json格式）',
  `nickname` varchar(255) DEFAULT NULL COMMENT '昵称',
  `name` varchar(255) DEFAULT NULL COMMENT '姓名',
  `gender` enum('MAN','WOMAN','UNKNOW') NOT NULL DEFAULT 'UNKNOW' COMMENT '性别',
  `phone` char(13) DEFAULT NULL COMMENT '手机号',
  `position` enum('ADMIN','EMPLOYEE') NOT NULL DEFAULT 'EMPLOYEE' COMMENT '职位',
  `openid` varchar(11) DEFAULT NULL COMMENT '员工的openid',
  `unionid` varchar(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_facility`
--

DROP TABLE IF EXISTS `boss_facility`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_facility` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `room_type_id` int(11) DEFAULT NULL COMMENT '房型id',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_house`
--

DROP TABLE IF EXISTS `boss_house`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_house` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '房屋id',
  `rent_type` enum('FULL','HALF') DEFAULT 'FULL' COMMENT '出租类型 （整租，合租）',
  `smart_device_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_reserve_order`
--

DROP TABLE IF EXISTS `boss_reserve_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_reserve_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '接待人',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '客户的id',
  `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公寓id',
  `room_type_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房型id',
  `room_id` int(10) NOT NULL DEFAULT '1' COMMENT '房间ID',
  `name` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '客户姓名',
  `phone` varchar(11) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '客户电话',
  `visit_by` enum('PHONE','VISIT','WEB','WECHAT') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'WECHAT' COMMENT '看房或者预约的途径. PHONE:电话咨询, VISIT:现场看房, WEB:官网预约, WECHAT:订房系统预约',
  `guest_type` enum('A','B','C','D') COLLATE utf8_unicode_ci DEFAULT 'A' COMMENT '客户类型,用 于评价客户的质量',
  `check_in_time` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '入住时间',
  `people_count` smallint(10) NOT NULL DEFAULT '0' COMMENT '入住人数',
  `status` enum('BEGIN','WAIT','INVALID','END') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'BEGIN' COMMENT '预约的状态',
  `visit_time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '看房时间',
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '预约登记时间',
  `work_address` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '工作地点',
  `require` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '需求',
  `info_source` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' COMMENT '信息来源',
  `source` smallint(4) DEFAULT '0' COMMENT '0:初始值,1:58同城,2:豆瓣,3:租房网,4:嗨住,5:zuber,6:中介,7:路过,8:老带新,9:朋友介绍,10:微信,11:同行转介,12:闲鱼,13:蘑菇租房,14:微博,15:其它',
  `Intention` enum('N','Y') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Y' COMMENT '是否有意向入住',
  `reason` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''' ''' COMMENT '未能入住原因',
  `remark` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '备注',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_room`
--

DROP TABLE IF EXISTS `boss_room`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_room` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `store_id` int(11) unsigned DEFAULT NULL COMMENT '门店id',
  `building_id` int(11) unsigned DEFAULT NULL COMMENT '楼栋id',
  `house_id` int(11) DEFAULT NULL COMMENT '房屋id',
  `room_type_id` int(11) unsigned DEFAULT NULL COMMENT '房型id',
  `is_alone` enum('Y','N') NOT NULL DEFAULT 'Y' COMMENT '有没有同住人',
  `house_rent_type` enum('FULL','HALF') DEFAULT 'FULL' COMMENT '房屋出租类型（整租，合租）',
  `rent_type` enum('LONG','SHORT') DEFAULT 'LONG' COMMENT '长租还是短租',
  `hourse_smart_device_id` int(10) unsigned DEFAULT NULL COMMENT '房屋公共智能设备（仅用于房屋有合租的情况）',
  `smart_device_id` int(11) DEFAULT NULL COMMENT '个人智能设备',
  `resident_id` int(11) DEFAULT NULL COMMENT '当前住户id',
  `layer` int(10) unsigned DEFAULT NULL COMMENT '楼层',
  `number` varchar(32) DEFAULT NULL COMMENT '门牌号',
  `area` decimal(10,2) DEFAULT NULL COMMENT '房间面积',
  `rent_price` decimal(10,2) DEFAULT NULL COMMENT '租金价格',
  `property_price` decimal(10,2) DEFAULT NULL,
  `cold_water_price` decimal(10,2) DEFAULT NULL COMMENT '展示的冷水价格',
  `hot_water_price` decimal(10,2) DEFAULT NULL COMMENT '展示的热水价格',
  `electricity_price` decimal(10,2) DEFAULT NULL COMMENT '展示的电费',
  `status` enum('BLANK','RESERVE','RENT','ARREARS','REFUND','OCCUPIED','OTHER','REPAIR') DEFAULT 'BLANK' COMMENT '''房间状态:空 预订 正常出租 欠费出租 退房 占用 其他 维修''',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_room_type`
--

DROP TABLE IF EXISTS `boss_room_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_room_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '房型id',
  `store_id` int(11) NOT NULL,
  `store_name` varchar(255) DEFAULT NULL,
  `building_id` int(10) DEFAULT NULL COMMENT '楼栋id',
  `name` varchar(255) NOT NULL COMMENT '房型名称',
  `feature` text COMMENT '房型特点',
  `area` decimal(10,2) unsigned DEFAULT '0.00' COMMENT '面积',
  `room_number` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '几个房室',
  `hall_number` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '几个大厅',
  `toilet_number` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '几个卫生间',
  `toward` enum('E','W','S','N','EW','SN') NOT NULL COMMENT '东西南北',
  `faclity_id` int(11) DEFAULT NULL COMMENT '配套表id',
  `images` varchar(255) DEFAULT NULL COMMENT '房型图片',
  `facility` varchar(255) DEFAULT NULL COMMENT '配套设施',
  `is_show` enum('Y','N') NOT NULL DEFAULT 'Y' COMMENT '是否展示',
  `description` text COMMENT '描述',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_service_order`
--

DROP TABLE IF EXISTS `boss_service_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_service_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '订单号',
  `sequence_number` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '流水号',
  `store_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '公寓的id',
  `room_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房间id',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '住户id',
  `employee_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '员工id',
  `service_type_id` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '服务类型ID',
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '客户姓名',
  `phone` varchar(11) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '电话号码',
  `addr_from` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '地址 始点',
  `addr_to` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '地址 目的地',
  `estimate_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '预估费用',
  `pay_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '支付费用',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '服务费用',
  `status` enum('SUBMITTED','PENDING','PAID','SERVING','COMPLETED','CANCELED','WAITING','CONFIRMED','EXPIRED') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'SUBMITTED' COMMENT '订单状态. 已提交, 待支付, 已支付, 处理中, 完成, 取消',
  `deal` enum('UNDONE','SDOING','SDONE','PDONE') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'UNDONE' COMMENT '是否处理',
  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '预约时间',
  `remark` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '客户备注',
  `paths` varchar(1000) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '维修上传的图片',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_service_type`
--

DROP TABLE IF EXISTS `boss_service_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_service_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '服务名称',
  `feature` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '服务特点',
  `description` text COLLATE utf8_unicode_ci NOT NULL COMMENT '简介',
  `image_url` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '图片地址',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_shop_address`
--

DROP TABLE IF EXISTS `boss_shop_address`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_shop_address` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '客户id',
  `name` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '收货人',
  `phone` varchar(11) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '电话',
  `apartment` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '公寓',
  `building` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '楼',
  `room_number` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '房间号',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_shop_category`
--

DROP TABLE IF EXISTS `boss_shop_category`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_shop_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '分类名称',
  `is_show` enum('Y','N') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Y' COMMENT '是否显示',
  `sort` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '排序',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_shop_goods`
--

DROP TABLE IF EXISTS `boss_shop_goods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_shop_goods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '分类id',
  `name` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '商品名称',
  `market_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '市场价格',
  `shop_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '本店价格',
  `description` text COLLATE utf8_unicode_ci NOT NULL COMMENT '商品简要描述',
  `detail` text COLLATE utf8_unicode_ci NOT NULL COMMENT '商品详情',
  `quantity` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '商品数量',
  `sale_num` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '已卖出数量',
  `goods_thumb` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '商品缩略图',
  `goods_carousel` varchar(255) COLLATE utf8_unicode_ci DEFAULT '' COMMENT '商品轮播图',
  `on_sale` enum('Y','N') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'Y' COMMENT '是否上架',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `original_link` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '原始地址',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_shop_order`
--

DROP TABLE IF EXISTS `boss_shop_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_shop_order` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '订单号',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '客户id',
  `address_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '地址的id',
  `status` enum('PENDING','PAYMENT','DELIVERED','COMPLETE','CANCEL') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'PENDING' COMMENT '订单状态:已下单，已付款，配送中，已完成，已取消',
  `remark` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT '用户备注',
  `goods_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '商品总金额',
  `pay_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '支付金额',
  `goods_quantity` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '商品数量',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `number` (`number`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_shop_order_goods`
--

DROP TABLE IF EXISTS `boss_shop_order_goods`;
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
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_smart_device`
--

DROP TABLE IF EXISTS `boss_smart_device`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_smart_device` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '设备id',
  `room_id` int(11) DEFAULT NULL COMMENT '设备绑定的房间id',
  `type` enum('LOCK','HOT_WATER_METER','COLD_WATER_METER','ELECTRIC_METER','UNKNOW') DEFAULT 'UNKNOW' COMMENT '设备类型:门锁, 热水表, 冷水表, 电表',
  `serial_number` varchar(255) DEFAULT NULL COMMENT '设备编号',
  `supplier` enum('CJOY','DANBAY','YEEUU','UNKNOW') DEFAULT 'UNKNOW' COMMENT '供应商',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_smart_device_record`
--

DROP TABLE IF EXISTS `boss_smart_device_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_smart_device_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '设备信息记录',
  `type` enum('HOUSE','ROOM') DEFAULT 'ROOM' COMMENT '设备记录（HOUSE,房屋表计，ROOM，房间表计）',
  `smart_device_id` int(11) DEFAULT NULL COMMENT '智能设备id',
  `smart_device_type` enum('LOCK','COLD_WATER_METER','HOT_WATER_METER','ELECTRIC_METER') DEFAULT NULL COMMENT '设备类型(门锁，冷水表，热水表，电表)',
  `last_reading` decimal(10,2) DEFAULT '0.00' COMMENT '上一次读数',
  `this_reading` decimal(10,2) DEFAULT NULL COMMENT '这次读数',
  `status` enum('WAITING','COMFIRM','PROBLEM') DEFAULT 'WAITING' COMMENT '状态（等待确认，确认，有问题）',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_smart_lock_record`
--

DROP TABLE IF EXISTS `boss_smart_lock_record`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_smart_lock_record` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boss_store`
--

DROP TABLE IF EXISTS `boss_store`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boss_store` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'store_id',
  `name` varchar(255) NOT NULL COMMENT '门店名称',
  `company_id` int(10) unsigned NOT NULL COMMENT '门店所属公司id',
  `rent_type` enum('UNION','DOT') DEFAULT NULL COMMENT '门店模式类型 分布式/集中式',
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
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `city`
--

DROP TABLE IF EXISTS `city`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `city` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `province` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `district` varchar(255) DEFAULT NULL COMMENT '区',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `environment`
--

DROP TABLE IF EXISTS `environment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `environment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `shop` varchar(255) DEFAULT NULL,
  `relax` varchar(255) DEFAULT NULL,
  `bus` varchar(255) DEFAULT NULL,
  `hospital` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fx_admin`
--

DROP TABLE IF EXISTS `fx_admin`;
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
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fx_company`
--

DROP TABLE IF EXISTS `fx_company`;
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
  `position` enum('SUPER') NOT NULL DEFAULT 'SUPER' COMMENT 'super',
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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `web_customer`
--

DROP TABLE IF EXISTS `web_customer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `web_customer` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uxid` int(10) unsigned DEFAULT NULL COMMENT '用户id',
  `name` char(32) NOT NULL DEFAULT '' COMMENT '姓名',
  `phone` char(11) NOT NULL DEFAULT '' COMMENT '手机',
  `openid` char(32) DEFAULT '' COMMENT '微信的唯一id',
  `unionid` char(32) DEFAULT NULL COMMENT '微信unionid',
  `subscribe` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否关注',
  `nickname` char(32) NOT NULL DEFAULT '' COMMENT '微信昵称',
  `gender` enum('MAN','WOMAN','UKNOW') NOT NULL DEFAULT 'UKNOW' COMMENT '微信性别',
  `province` char(32) NOT NULL DEFAULT '' COMMENT '省份',
  `city` char(32) NOT NULL DEFAULT '' COMMENT '城市',
  `avatar` varchar(256) NOT NULL DEFAULT '' COMMENT '头像',
  `country` char(32) NOT NULL DEFAULT '' COMMENT '国家',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wechat_openid` (`openid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `web_resident`
--

DROP TABLE IF EXISTS `web_resident`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `web_resident` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '住户id',
  `uxid` int(10) unsigned DEFAULT NULL COMMENT '用户id',
  `room_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '房间的id',
  `customer_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '客户的id',
  `employee_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '办理入住员工的id',
  `discount_id` int(10) NOT NULL DEFAULT '0' COMMENT '对应折扣的记录id',
  `contract_id` int(11) DEFAULT NULL COMMENT '合同id',
  `position` enum('MAIN','OTHER') NOT NULL DEFAULT 'MAIN' COMMENT '身份：主租人，同住人',
  `is_alone` enum('Y','N','O') NOT NULL DEFAULT 'Y' COMMENT '主租人身份判断是Y否N是独租，如果是同住人身份填O',
  `people_count` smallint(5) unsigned DEFAULT '0' COMMENT '同住人数目',
  `main_id` int(11) DEFAULT NULL COMMENT '关联的主租人id，（仅用于有同住人的情况）',
  `name` char(32) NOT NULL DEFAULT '' COMMENT '姓名',
  `phone` char(11) NOT NULL DEFAULT '' COMMENT '手机号',
  `pay_frequency` enum('MONTH','QUARTER','HALFYEAR','FULLYEAR') NOT NULL DEFAULT 'MONTH' COMMENT '付款形式(月付1, 季付3, 半年付6, 年付12)',
  `card_type` enum('IDCARD','P','F','E','C','B','A','6','2','1','0','OTHER') NOT NULL DEFAULT 'IDCARD' COMMENT '证件类型',
  `card_number` char(32) NOT NULL DEFAULT '' COMMENT '证件号码',
  `hurry_people` char(32) NOT NULL DEFAULT '' COMMENT '紧急联系人的姓名',
  `hurry_phone` char(11) NOT NULL DEFAULT '' COMMENT '紧急联系人电话',
  `rent_type` enum('SHORT','LONG') NOT NULL DEFAULT 'LONG' COMMENT '用户是长租还是短租',
  `begin_time` datetime DEFAULT NULL COMMENT '租房开始',
  `end_time` datetime DEFAULT NULL COMMENT '租房结束',
  `leave_time` datetime DEFAULT NULL COMMENT '退租时间',
  `card_front` char(255) NOT NULL DEFAULT '' COMMENT '证件正面',
  `card_back` char(255) NOT NULL DEFAULT '' COMMENT '证件反面',
  `card_hand` char(255) NOT NULL DEFAULT '' COMMENT '手持证件',
  `book_time` datetime DEFAULT NULL COMMENT '预订日期',
  `book_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '定金金额',
  `sign_rent_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '签约的租金',
  `signl_property_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '签约物业费',
  `sign_cold_water_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '冷水价格',
  `sign_hot_water_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '热水价格',
  `sign_electricity_price` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '电价格',
  `discount_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '优惠金额',
  `contact_address` varchar(255) NOT NULL DEFAULT '' COMMENT '通讯地址',
  `deposit_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '押金',
  `deposit_month` smallint(5) unsigned NOT NULL DEFAULT '2' COMMENT '押金月份(待定)',
  `status` enum('NOT_PAY','PRE_RESERVE','PRE_CHECKIN','PRE_CHANGE','PRE_RENEW','RESERVE','NORMAL','NORMAL_REFUND','UNDER_CONTRACT','INVALID') NOT NULL DEFAULT 'NOT_PAY' COMMENT '办理入住未支付 办理预订未支付 预订转入住未支付 换房未支付 续约未支付 预订 正常 正常退房 违约退房 无效',
  `first_pay_money` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '首次支付（待定）',
  `tmp_deposit` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT '临时押金',
  `remark` varchar(255) NOT NULL DEFAULT '' COMMENT '备注',
  `contract_time` smallint(5) unsigned NOT NULL DEFAULT '0' COMMENT '合同 时长',
  `contract_special_term` varchar(255) NOT NULL DEFAULT '' COMMENT '合同中的特殊说明',
  `data` varchar(1024) NOT NULL DEFAULT '' COMMENT '存储住户的一些不常用的信息',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '更新时间',
  `deletetd_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=134 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping events for database 'funxdata'
--

--
-- Dumping routines for database 'funxdata'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-05-03 18:06:12
