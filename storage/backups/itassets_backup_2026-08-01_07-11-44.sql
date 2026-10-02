-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: itassets
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `asset_history`
--

DROP TABLE IF EXISTS `asset_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `asset_history` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` enum('created','updated','deleted','assigned','transferred','maintenance','retired') NOT NULL,
  `field_changed` varchar(50) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_history_asset` (`asset_id`),
  KEY `idx_history_user` (`user_id`),
  KEY `idx_history_action` (`action`),
  KEY `idx_history_created` (`created_at`),
  CONSTRAINT `asset_history_ibfk_1` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `asset_history_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_history`
--

LOCK TABLES `asset_history` WRITE;
/*!40000 ALTER TABLE `asset_history` DISABLE KEYS */;
INSERT INTO `asset_history` VALUES (7,13,1,'created',NULL,NULL,'{\"asset_tag\":\"PC-003\",\"type\":\"pc\",\"hostname\":\"\",\"ip_address\":\"10.97.2.192\",\"mac_address\":\"AA:BB:CC:DD:EE:02\",\"vendor\":\"Lenovo\",\"model\":\"ThinkCentre\",\"serial_number\":\"\",\"os\":\"Windows11\",\"cpu\":\"i5 11th gen\",\"ram_gb\":\"8\",\"storage_gb\":\"512\",\"status\":\"active\",\"purchase_date\":\"\",\"warranty_end\":\"\",\"location\":\"Office\",\"floor\":\"\",\"department_id\":\"1\",\"assigned_to\":\"1\",\"notes\":\"\",\"created_by\":1}','10.97.2.192','2026-07-29 15:54:50'),(8,13,1,'updated','hostname','','WSIT15','10.97.2.192','2026-07-29 15:59:40'),(9,13,1,'updated','purchase_date','0000-00-00','','10.97.2.192','2026-07-29 15:59:40'),(10,13,1,'updated','warranty_end','0000-00-00','','10.97.2.192','2026-07-29 15:59:40'),(21,13,4,'updated','type','pc','laptop','10.97.2.31','2026-07-30 09:00:53'),(22,13,4,'updated','purchase_date','0000-00-00','','10.97.2.31','2026-07-30 09:00:53'),(23,13,4,'updated','warranty_end','0000-00-00','','10.97.2.31','2026-07-30 09:00:53'),(33,5,1,'updated','ip_address','192.168.1.1','','10.97.2.192','2026-07-31 08:10:58'),(34,5,1,'updated','floor','Ground Floor','','10.97.2.192','2026-07-31 08:10:58'),(39,5,1,'updated','hostname','CORE-SWITCH-01','Unmanaged Switch','10.97.2.192','2026-07-31 11:16:32'),(40,5,1,'updated','purchase_date','0000-00-00','','10.97.2.192','2026-07-31 11:16:32'),(41,5,1,'updated','warranty_end','0000-00-00','','10.97.2.192','2026-07-31 11:16:32'),(51,13,5,'updated','purchase_date','0000-00-00','','10.97.2.192','2026-07-31 16:07:02'),(52,13,5,'updated','warranty_end','0000-00-00','','10.97.2.192','2026-07-31 16:07:02'),(53,13,5,'updated','assigned_to','1','5','10.97.2.192','2026-07-31 16:07:02'),(54,13,5,'updated','type','laptop','pc','10.97.2.192','2026-07-31 16:07:22'),(55,13,5,'updated','purchase_date','0000-00-00','','10.97.2.192','2026-07-31 16:07:22'),(56,13,5,'updated','warranty_end','0000-00-00','','10.97.2.192','2026-07-31 16:07:22'),(57,21,5,'created',NULL,NULL,'{\"asset_tag\":\"0-90-IT-1872\",\"type\":\"pc\",\"hostname\":\"WSIT12\",\"ip_address\":\"10.97.2.31\",\"mac_address\":\"\",\"vendor\":\"Dell\",\"model\":\"OptiPlex\",\"serial_number\":null,\"os\":\"Windows11\",\"cpu\":\"i5\",\"ram_gb\":\"16\",\"storage_gb\":\"512\",\"status\":\"active\",\"purchase_date\":\"\",\"warranty_end\":\"\",\"location\":\"Main Office\",\"floor\":\"Building 3\",\"department_id\":\"1\",\"assigned_to\":null,\"notes\":\"\",\"created_by\":5}','10.97.2.192','2026-07-31 16:54:48'),(58,21,5,'updated','purchase_date','0000-00-00','','10.97.2.192','2026-07-31 16:55:36'),(59,21,5,'updated','warranty_end','0000-00-00','','10.97.2.192','2026-07-31 16:55:36'),(60,21,5,'updated','purchase_date','0000-00-00','','10.97.2.192','2026-07-31 16:56:10'),(61,21,5,'updated','warranty_end','0000-00-00','','10.97.2.192','2026-07-31 16:56:10'),(62,21,5,'updated','purchase_date','0000-00-00','','10.97.2.192','2026-08-01 08:06:11'),(63,21,5,'updated','warranty_end','0000-00-00','','10.97.2.192','2026-08-01 08:06:11'),(64,5,5,'updated','purchase_date','0000-00-00','','10.97.2.192','2026-08-01 08:06:25'),(65,5,5,'updated','warranty_end','0000-00-00','','10.97.2.192','2026-08-01 08:06:25'),(66,5,5,'updated','purchase_date','0000-00-00','','10.97.2.192','2026-08-01 08:06:39'),(67,5,5,'updated','warranty_end','0000-00-00','','10.97.2.192','2026-08-01 08:06:39'),(68,5,5,'updated','assigned_to','5','','10.97.2.192','2026-08-01 08:06:39'),(69,5,5,'updated','assigned_name','Justin Marcus Santiago','cj aviila','10.97.2.192','2026-08-01 08:06:39'),(70,5,5,'updated','purchase_date','0000-00-00','','10.97.2.192','2026-08-01 08:10:51'),(71,5,5,'updated','warranty_end','0000-00-00','','10.97.2.192','2026-08-01 08:10:51'),(72,5,5,'updated','assigned_name','cj aviila','','10.97.2.192','2026-08-01 08:10:51'),(73,21,5,'updated','purchase_date','0000-00-00','','10.97.2.192','2026-08-01 08:11:32'),(74,21,5,'updated','warranty_end','0000-00-00','','10.97.2.192','2026-08-01 08:11:32'),(75,21,5,'updated','assigned_name','Christian Jerwin Avilla','Christian Avila','10.97.2.192','2026-08-01 08:11:32');
/*!40000 ALTER TABLE `asset_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `asset_tag` varchar(50) NOT NULL,
  `type` enum('pc','laptop','switch','server','printer','monitor','other') NOT NULL,
  `hostname` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `mac_address` varchar(17) DEFAULT NULL,
  `vendor` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `os` varchar(100) DEFAULT NULL,
  `cpu` varchar(100) DEFAULT NULL,
  `ram_gb` int(10) unsigned DEFAULT NULL,
  `storage_gb` int(10) unsigned DEFAULT NULL,
  `status` enum('active','inactive','maintenance','retired','lost','reserved') NOT NULL DEFAULT 'active',
  `purchase_date` date DEFAULT NULL,
  `warranty_end` date DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `floor` varchar(50) DEFAULT NULL,
  `department_id` int(10) unsigned DEFAULT NULL,
  `section` varchar(100) DEFAULT NULL,
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `assigned_name` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `pos_x` float DEFAULT NULL,
  `pos_y` float DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_tag` (`asset_tag`),
  UNIQUE KEY `serial_number` (`serial_number`),
  KEY `idx_assets_type` (`type`),
  KEY `idx_assets_status` (`status`),
  KEY `idx_assets_dept` (`department_id`),
  KEY `idx_assets_assigned` (`assigned_to`),
  KEY `idx_assets_hostname` (`hostname`),
  KEY `idx_assets_ip` (`ip_address`),
  KEY `idx_assets_location` (`location`(100)),
  KEY `assets_ibfk_3` (`created_by`),
  CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `assets_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `assets_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assets`
--

LOCK TABLES `assets` WRITE;
/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
INSERT INTO `assets` VALUES (5,'SW-001','switch','Unmanaged Switch','','AA:BB:CC:DD:EE:05','Cisco','Catalyst 9200','SN-CISCO-005','Cisco IOS','',0,0,'active','0000-00-00','0000-00-00','Server Room','',1,NULL,NULL,NULL,'Core network switch - 48 ports',NULL,-428,602,1,'2026-07-29 15:12:58','2026-08-01 08:10:51'),(13,'PC-003','pc','WSIT15','10.97.2.192','AA:BB:CC:DD:EE:02','Lenovo','ThinkCentre','','Windows11','i5 11th gen',8,512,'active','0000-00-00','0000-00-00','Office','',1,NULL,5,'Justin Marcus Santiago','',NULL,-524,504,1,'2026-07-29 15:54:50','2026-08-01 08:40:35'),(21,'0-90-IT-1872','pc','WSIT12','10.97.2.31','','Dell','OptiPlex',NULL,'Windows11','i5',16,512,'active','0000-00-00','0000-00-00','Main Office','Building 3',1,NULL,6,'Christian Avila','',NULL,-526,407,5,'2026-07-31 16:54:48','2026-08-01 08:43:30');
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_log`
--

DROP TABLE IF EXISTS `audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_created` (`created_at`),
  CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_log`
--

LOCK TABLES `audit_log` WRITE;
/*!40000 ALTER TABLE `audit_log` DISABLE KEYS */;
INSERT INTO `audit_log` VALUES (1,1,'logout','User logged out','10.97.2.192','2026-07-29 16:18:37'),(2,1,'login','User logged in','10.97.2.192','2026-07-29 16:18:51'),(3,1,'logout','User logged out','10.97.2.192','2026-07-29 16:31:07'),(4,NULL,'login','User logged in','10.97.2.192','2026-07-29 16:31:13'),(5,NULL,'profile_update','Profile updated','10.97.2.192','2026-07-29 16:32:08'),(6,NULL,'logout','User logged out','10.97.2.192','2026-07-29 16:32:36'),(7,NULL,'login','User logged in','10.97.2.192','2026-07-29 16:32:44'),(8,NULL,'profile_update','Profile updated','10.97.2.192','2026-07-29 16:33:04'),(9,NULL,'logout','User logged out','10.97.2.192','2026-07-29 16:33:23'),(10,NULL,'login','User logged in','10.97.2.192','2026-07-29 16:33:41'),(11,NULL,'logout','User logged out','10.97.2.192','2026-07-29 16:33:49'),(12,1,'login','User logged in','10.97.2.192','2026-07-29 16:33:55'),(13,1,'profile_update','Profile updated','10.97.2.192','2026-07-29 16:35:24'),(14,1,'logout','User logged out','10.97.2.192','2026-07-29 16:41:25'),(15,NULL,'login','User logged in','10.97.2.192','2026-07-29 16:41:41'),(16,NULL,'logout','User logged out','10.97.2.192','2026-07-29 16:42:52'),(17,1,'login','User logged in','10.97.2.192','2026-07-29 16:43:12'),(18,1,'logout','User logged out','10.97.2.192','2026-07-30 08:56:34'),(19,4,'login','User logged in','10.97.2.31','2026-07-30 08:58:30'),(20,1,'login','User logged in','10.97.2.192','2026-07-30 09:18:47'),(21,4,'login','User logged in','10.97.2.53','2026-07-31 08:12:23'),(22,4,'logout','User logged out','10.97.2.53','2026-07-31 08:14:54'),(23,1,'logout','User logged out','10.97.2.192','2026-07-31 15:32:11'),(24,5,'login','User logged in','10.97.2.192','2026-07-31 15:32:19'),(25,5,'login','User logged in','10.97.2.192','2026-07-31 15:59:41'),(26,5,'logout','User logged out','10.97.2.192','2026-08-01 08:45:44'),(27,5,'login','User logged in','10.97.2.192','2026-08-01 08:59:43'),(28,4,'login','User logged in','10.97.2.53','2026-08-01 11:59:35'),(31,1,'backup','Database backup created: itassets_backup_2026-08-01_07-09-37.sql','127.0.0.1','2026-08-01 07:09:38');
/*!40000 ALTER TABLE `audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `section` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Information Technology','IT',NULL,'Information Technology Department','2026-07-29 15:12:57','2026-07-29 16:14:31'),(2,'Human Resources','HR',NULL,'Human Resources Department','2026-07-29 15:12:57','2026-07-29 16:14:31'),(3,'Finance','FAD',NULL,'Finance and Accounting Department','2026-07-29 15:12:57','2026-08-01 08:14:19'),(4,'Engineering','ENG',NULL,'Engineering Departments','2026-07-29 15:12:57','2026-07-29 16:15:09'),(8,'Procdution Control','PC',NULL,'Production Control ','2026-08-01 08:13:55','2026-08-01 08:15:04'),(9,'Production','PP',NULL,'Plastic Parts','2026-08-01 08:14:46','2026-08-01 08:14:46'),(11,'Tube Cutting','TC',NULL,'Tube Cutting','2026-08-01 08:16:05','2026-08-01 08:16:05'),(12,'Extrusion','EXT',NULL,'Extrusion','2026-08-01 08:17:01','2026-08-01 08:17:01'),(13,'MOLD','MD',NULL,'MOLD\r\n','2026-08-01 08:17:39','2026-08-01 08:17:39');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `network_links`
--

DROP TABLE IF EXISTS `network_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `network_links` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `source_id` int(10) unsigned NOT NULL,
  `target_id` int(10) unsigned NOT NULL,
  `link_type` enum('ethernet','fiber','wifi','virtual') NOT NULL DEFAULT 'ethernet',
  `port_source` varchar(20) DEFAULT NULL,
  `port_target` varchar(20) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_link` (`source_id`,`target_id`),
  KEY `idx_links_source` (`source_id`),
  KEY `idx_links_target` (`target_id`),
  CONSTRAINT `network_links_ibfk_1` FOREIGN KEY (`source_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `network_links_ibfk_2` FOREIGN KEY (`target_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `network_links`
--

LOCK TABLES `network_links` WRITE;
/*!40000 ALTER TABLE `network_links` DISABLE KEYS */;
INSERT INTO `network_links` VALUES (9,13,5,'ethernet',NULL,NULL,'2026-07-31 08:08:42'),(17,21,5,'ethernet',NULL,NULL,'2026-07-31 16:55:08');
/*!40000 ALTER TABLE `network_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'app.name','IT Asset Management System','2026-07-29 15:12:58','2026-08-01 13:11:39'),(2,'app.timezone','Asia/Manila','2026-07-29 15:12:58','2026-08-01 13:11:39'),(3,'company.name','Sagara Metro Plasctics CORP','2026-07-29 15:12:58','2026-08-01 13:11:39'),(4,'notify.expiry_days','30','2026-07-29 15:12:58','2026-08-01 13:11:39'),(5,'backup.enabled','true','2026-07-29 15:12:58','2026-08-01 13:11:39'),(6,'backup.interval','daily','2026-07-29 15:12:58','2026-08-01 13:11:39');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `topology_labels`
--

DROP TABLE IF EXISTS `topology_labels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `topology_labels` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `text` varchar(200) NOT NULL,
  `x` float NOT NULL DEFAULT 0,
  `y` float NOT NULL DEFAULT 0,
  `font_size` int(10) unsigned NOT NULL DEFAULT 16,
  `color` varchar(20) NOT NULL DEFAULT '#64748B',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `topology_labels`
--

LOCK TABLES `topology_labels` WRITE;
/*!40000 ALTER TABLE `topology_labels` DISABLE KEYS */;
INSERT INTO `topology_labels` VALUES (2,'IT',72.1122,-138.647,24,'#1E293B','2026-07-31 11:13:40','2026-07-31 11:30:00'),(3,'hello',213.034,69.1293,24,'#1E293B','2026-07-31 11:33:53','2026-07-31 12:02:35'),(6,'Hello',-173,-109,24,'#1E293B','2026-07-31 12:02:31','2026-07-31 15:16:22'),(9,'Human Resources',-403,-122,24,'#475569','2026-07-31 15:31:31','2026-07-31 16:12:15'),(10,'Main Office',513,-433,24,'#475569','2026-07-31 16:09:01','2026-07-31 16:13:15'),(11,'FAD',23,-123,24,'#475569','2026-07-31 16:09:50','2026-07-31 16:12:15'),(12,'Production',361,-129,24,'#475569','2026-07-31 16:10:13','2026-07-31 16:12:15'),(13,'Engineering',1216,-136,24,'#475569','2026-07-31 16:10:22','2026-07-31 16:13:05'),(14,'IMS/QA',1546,-133,24,'#475569','2026-07-31 16:10:30','2026-07-31 16:13:05'),(16,'Production Control',760,-128,24,'#475569','2026-07-31 16:12:27','2026-07-31 16:13:05');
/*!40000 ALTER TABLE `topology_labels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','operator','viewer') NOT NULL DEFAULT 'viewer',
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','atrazona@smpiclaguna.com','$2y$12$Lz2OcxibSa5eaUY54Y4Tq.qsiCYmMfVAUC4UI5j9kZY9qBh5c87.S','ITSupervisor','admin',NULL,1,'2026-07-30 09:18:46','2026-07-29 15:12:57','2026-07-30 11:00:40'),(4,'nmijares','technicals@smpiclaguna.com','$2y$12$H9OHdFkXCxQfGP1lWb560ObpnM1ymsBFdMyYrO.Jdkau1bUSv6.Qu','Nico Mijares','admin',NULL,1,'2026-08-01 11:59:35','2026-07-30 08:52:27','2026-08-01 11:59:35'),(5,'jsantiago','programmers@smpiclaguna.com','$2y$12$7IzzB7PINo/GYIGAy4iQpuFpazso.2krVdiHGSIk2uJbWQAsklns2','Justin Marcus Santiago','admin',NULL,1,'2026-08-01 08:59:43','2026-07-30 09:23:10','2026-08-01 08:59:43'),(6,'cavilla','networks@smpiclaguna.com','$2y$12$jX2qxw1th6ppxFJSVzpRxeNP75XyrXxFJsvqPMXwxaMD8NxIm5qEy','christian avila','admin',NULL,1,NULL,'2026-07-30 10:25:29','2026-07-30 10:25:29');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'itassets'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-01 13:11:45
