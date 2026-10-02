-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: login_system
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
-- Table structure for table `concern_submission_tokens`
--

DROP TABLE IF EXISTS `concern_submission_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `concern_submission_tokens` (
  `token` varchar(64) NOT NULL,
  `user_id` varchar(30) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`token`),
  UNIQUE KEY `uniq_user_token` (`user_id`,`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `concern_submission_tokens`
--

LOCK TABLES `concern_submission_tokens` WRITE;
/*!40000 ALTER TABLE `concern_submission_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `concern_submission_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `concern_submissions`
--

DROP TABLE IF EXISTS `concern_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `concern_submissions` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(30) NOT NULL,
  `request_key` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `concern_id` int(6) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_request` (`user_id`,`request_key`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `concern_submissions`
--

LOCK TABLES `concern_submissions` WRITE;
/*!40000 ALTER TABLE `concern_submissions` DISABLE KEYS */;
INSERT INTO `concern_submissions` VALUES (1,'u1','k1','2026-07-16 01:24:08',NULL);
/*!40000 ALTER TABLE `concern_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `concerns`
--

DROP TABLE IF EXISTS `concerns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `concerns` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` varchar(30) NOT NULL,
  `sender_name` varchar(100) NOT NULL,
  `department` varchar(50) NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','repaired','canceled') DEFAULT 'active',
  `repaired_date` timestamp NULL DEFAULT NULL,
  `repaired_by` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `canceled_reason` text DEFAULT NULL,
  `canceled_date` timestamp NULL DEFAULT NULL,
  `canceled_by` varchar(100) DEFAULT NULL,
  `ticket_number` varchar(30) DEFAULT NULL,
  `priority` enum('Critical','High','Medium','Low') DEFAULT 'Medium',
  `backed_up_at` timestamp NULL DEFAULT NULL,
  `backed_up_status` varchar(20) DEFAULT NULL,
  `backed_up_remarks_hash` varchar(32) DEFAULT NULL,
  `backed_up_canceled_reason_hash` varchar(32) DEFAULT NULL,
  `backed_up_repaired_by_hash` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_number` (`ticket_number`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `concerns`
--

LOCK TABLES `concerns` WRITE;
/*!40000 ALTER TABLE `concerns` DISABLE KEYS */;
INSERT INTO `concerns` VALUES (1,'user','christian','IT','nasiraan','uploads/cisco_4321-removebg-preview.png','2026-02-12 02:59:09','repaired','2026-02-12 03:00:55','nico/ian/paul',NULL,NULL,NULL,NULL,'INC-20260212-001','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','8913981a015c208beb1bcf5fbabb2a03'),(2,'user','christian','IT','nasiraan','uploads/cisco_4321-removebg-preview.png','2026-02-12 02:59:25','repaired','2026-02-12 03:00:51','paul/lebron',NULL,NULL,NULL,NULL,'INC-20260212-002','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','17888cf24c48aee13086d213ab57f91f'),(3,'user','nico','TC','ewan','','2026-02-12 03:06:41','repaired','2026-02-12 03:34:27','nico/ian/paul',NULL,NULL,NULL,NULL,'INC-20260212-003','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','8913981a015c208beb1bcf5fbabb2a03'),(4,'user','nico','TC','ewan','','2026-02-12 03:06:44','repaired','2026-02-12 03:34:07','nico/ian/paul',NULL,NULL,NULL,NULL,'INC-20260212-004','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','8913981a015c208beb1bcf5fbabb2a03'),(5,'user','nico','TC','ewan','','2026-02-12 03:06:47','repaired','2026-02-12 03:34:03','nico',NULL,NULL,NULL,NULL,'INC-20260212-005','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','410ec15153a6dff0bed851467309bcbd'),(6,'user','nico','TC','ewan','','2026-02-12 03:06:51','repaired','2026-02-12 03:33:58','nico/ian/paul',NULL,NULL,NULL,NULL,'INC-20260212-006','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','8913981a015c208beb1bcf5fbabb2a03'),(7,'user','nico','TC','ewan','','2026-02-12 03:17:56','repaired','2026-02-12 03:33:52','nico/ian/paul',NULL,NULL,NULL,NULL,'INC-20260212-007','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','8913981a015c208beb1bcf5fbabb2a03'),(8,'user','kristyan','Engr','mowiee wowiee','','2026-02-12 03:22:13','repaired','2026-02-12 03:33:43','nico/ian/paul',NULL,NULL,NULL,NULL,'INC-20260212-008','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','8913981a015c208beb1bcf5fbabb2a03'),(10,'user','asda','Finance','dasd','','2026-02-12 03:53:40','canceled',NULL,NULL,NULL,'oo namn',NULL,NULL,'INC-20260212-009','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','8a55ccabf5cc003e32749cda313c28ad','d41d8cd98f00b204e9800998ecf8427e'),(11,'user','asda','HR','rwerwe','','2026-02-13 05:15:46','canceled',NULL,NULL,NULL,'dw','2026-06-03 06:43:58','admin','INC-20260213-001','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','1f2121f36f817bd18540e5fa7de06f59','d41d8cd98f00b204e9800998ecf8427e'),(12,'user','qweqwe','PP','r3r32r','uploads/ecs4100-removebg-preview.png','2026-02-13 05:16:19','canceled',NULL,NULL,NULL,'ik',NULL,NULL,'INC-20260213-002','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','7bb450ffbcb8b2c73b5f66663190353c','d41d8cd98f00b204e9800998ecf8427e'),(13,'user','qweqwe','PP','dsad','','2026-02-13 05:31:25','canceled',NULL,NULL,NULL,'sadwdwad','2026-02-13 06:11:06','admin','INC-20260213-003','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','bc083ef5a9a65010e86526c88b0a258f','d41d8cd98f00b204e9800998ecf8427e'),(14,'user ','kristyan','TP','daw','','2026-02-13 06:14:32','canceled',NULL,NULL,NULL,'dw','2026-06-03 06:43:56','admin','INC-20260213-004','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','1f2121f36f817bd18540e5fa7de06f59','d41d8cd98f00b204e9800998ecf8427e'),(15,'icon','paul','Logistics','dasdwd','','2026-02-16 08:32:18','canceled',NULL,NULL,NULL,'dwd','2026-06-03 06:43:52','admin','INC-20260216-001','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','19af0630fd5b74a3f63a2ac9769b613d','d41d8cd98f00b204e9800998ecf8427e'),(16,'cj','nico','Finance','dasdw','','2026-02-16 08:33:03','canceled',NULL,NULL,NULL,'dw','2026-06-03 06:43:54','admin','INC-20260216-002','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','1f2121f36f817bd18540e5fa7de06f59','d41d8cd98f00b204e9800998ecf8427e'),(17,'cj','cj','Finance','ahh sht he we go again','','2026-02-18 00:05:51','canceled',NULL,NULL,NULL,'ayaw ipagawa','2026-06-03 03:22:07','admin','INC-20260218-001','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','5fed2f05d998044d7c364e811c87330e','d41d8cd98f00b204e9800998ecf8427e'),(18,'userhr','cj','HR','mabagal ang zimbra','uploads/ChatGPT Image Jun 3, 2026, 02_36_37 PM.ico','2026-06-03 06:44:45','repaired','2026-06-03 06:44:49','paul',NULL,NULL,NULL,NULL,'INC-20260603-001','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','6c63212ab48e8401eaf6b59b95d816a9'),(19,'userhr','nico','HR','dasd','uploads/ticket.webp','2026-06-03 07:30:31','repaired','2026-06-03 07:30:36','ako',NULL,NULL,NULL,NULL,'INC-20260603-002','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','1cd13479e5609d79971c69051158a27f'),(20,'userhr','nicowdwad','HR','awdawd','','2026-06-03 07:32:06','repaired','2026-06-03 07:40:43','greg',NULL,NULL,NULL,NULL,'INC-20260603-003','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','ea26b0075d29530c636d6791bb5d73f4'),(21,'userhr','nicowdwadertger','HR','gferge','','2026-06-03 07:40:15','repaired','2026-06-03 07:49:37','greg',NULL,NULL,NULL,NULL,'INC-20260603-004','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','ea26b0075d29530c636d6791bb5d73f4'),(22,'userhr','nico','HR','5ty45t45','','2026-06-03 07:49:49','canceled',NULL,NULL,NULL,'wdw','2026-07-15 00:53:48','programmers','INC-20260603-005','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','9d03f4c2dae07ef9153d4b31328c110d','d41d8cd98f00b204e9800998ecf8427e'),(23,'userhr','nico','HR','casc','','2026-06-03 07:54:26','repaired','2026-06-03 08:01:02','greg',NULL,NULL,NULL,NULL,'INC-20260603-006','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','ea26b0075d29530c636d6791bb5d73f4'),(24,'production','maam cora','PP','sabi ni marc','uploads/SMPIC Logo.jpg','2026-06-04 00:25:09','canceled',NULL,NULL,NULL,'test','2026-06-04 00:30:00','admin','INC-20260604-001','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','098f6bcd4621d373cade4e832627b4f6','d41d8cd98f00b204e9800998ecf8427e'),(25,'userhr','maam cora ','HR','my mouse is degraded','','2026-06-04 08:37:33','canceled',NULL,NULL,NULL,'asdw','2026-07-15 00:53:31','programmers','INC-20260604-002','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','99bd628c72e82b5e8524ae661ca417d7','d41d8cd98f00b204e9800998ecf8427e'),(26,'production','Cora','PP','request for J bortanog biometrics','','2026-06-06 07:09:40','repaired','2026-06-06 07:10:41','IT personnel',NULL,NULL,NULL,NULL,'INC-20260606-001','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','6ce10d652c385f4bf3404dde1cdb578d'),(27,'pc','albert espiritu','PC-OFC','ssss','','2026-06-30 08:06:17','canceled',NULL,NULL,NULL,'test only','2026-06-30 08:07:20','programmers','INC-20260630-001','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','74895c86867f3069cc929e4d7b59ccb8','d41d8cd98f00b204e9800998ecf8427e'),(28,'pp','nico','FIN','dwd','','2026-07-14 00:45:08','repaired','2026-07-14 07:06:16','jm',NULL,NULL,NULL,NULL,'INC-20260714-001','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','3da770cc56ed4407b6aaf10ad4e72b4d'),(29,'pp','jm','ING','waaaaaaaw','','2026-07-14 02:12:00','canceled',NULL,NULL,NULL,'dw','2026-07-14 07:05:19','programmers','INC-20260714-002','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','1f2121f36f817bd18540e5fa7de06f59','d41d8cd98f00b204e9800998ecf8427e'),(30,'pp','cj','ING','malaking hotdog','','2026-07-14 02:20:46','canceled',NULL,NULL,NULL,'k','2026-07-14 07:02:54','programmers','INC-20260714-003','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','8ce4b16b22b58894aa86c421e8759df3','d41d8cd98f00b204e9800998ecf8427e'),(31,'pp','hays','ING','wow','','2026-07-14 02:22:26','repaired','2026-07-14 02:27:04','greg',NULL,NULL,NULL,NULL,'INC-20260714-004','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','ea26b0075d29530c636d6791bb5d73f4'),(32,'pp','nico','ING','dswa','','2026-07-14 02:24:29','repaired','2026-07-14 02:26:54','jm',NULL,NULL,NULL,NULL,'INC-20260714-005','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','3da770cc56ed4407b6aaf10ad4e72b4d'),(33,'pp','Justin Marcus Santiago','ING','helloo naki online lang ako','','2026-07-14 06:03:34','canceled',NULL,NULL,NULL,'haw','2026-07-14 07:00:13','programmers','INC-20260714-006','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','5a49a647820fcbb4021e4278ffe74ffb','d41d8cd98f00b204e9800998ecf8427e'),(34,'pp','Justin Marcus Santiago','ING','Pasuyo pa ayos naman ng vm ko hinde nagana','uploads/width_877.png','2026-07-14 06:06:40','repaired','2026-07-14 07:06:13','jm',NULL,NULL,NULL,NULL,'INC-20260714-007','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','3da770cc56ed4407b6aaf10ad4e72b4d'),(35,'pp','Justin Marcus Santiago','ING','pakisuyo naman sa printer ko may nalabas ng bading','','2026-07-14 06:11:25','repaired','2026-07-14 07:05:54','jm',NULL,NULL,NULL,NULL,'INC-20260714-008','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','3da770cc56ed4407b6aaf10ad4e72b4d'),(36,'pp','Justin Marcus Santiago','ING','hello','','2026-07-14 06:31:39','repaired','2026-07-14 07:05:48','paul',NULL,NULL,NULL,NULL,'INC-20260714-009','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','6c63212ab48e8401eaf6b59b95d816a9'),(37,'pp','nico','INJ','hello again','','2026-07-15 00:59:43','active',NULL,NULL,NULL,NULL,NULL,NULL,'INC-20260715-001','Medium','2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(38,'pp','nico','INJ','hello again','','2026-07-15 00:59:55','active',NULL,NULL,NULL,NULL,NULL,NULL,'INC-20260715-002','Medium','2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(39,'pp','nico','INJ','hello again','','2026-07-15 01:00:08','active',NULL,NULL,NULL,NULL,NULL,NULL,'INC-20260715-003','Medium','2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(40,'pp','nico','INJ','hello again','','2026-07-15 01:00:21','active',NULL,NULL,NULL,NULL,NULL,NULL,'INC-20260715-004','Medium','2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(41,'pp','nico','INJ','hello again','','2026-07-15 01:00:34','active',NULL,NULL,NULL,NULL,NULL,NULL,'INC-20260715-005','Medium','2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(42,'pp','nico','INJ','hello again','','2026-07-15 01:00:45','canceled',NULL,NULL,NULL,'fe','2026-07-15 01:16:12','programmers','INC-20260715-006','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','2d917f5d1275e96fd75e6352e26b1387','d41d8cd98f00b204e9800998ecf8427e'),(43,'pp','nico','INJ','hello again','','2026-07-15 01:00:56','canceled',NULL,NULL,NULL,'fee','2026-07-15 01:16:09','programmers','INC-20260715-007','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','d4319fefc66c701f24c875afda6360d6','d41d8cd98f00b204e9800998ecf8427e'),(44,'pp','nico','INJ','hello again','','2026-07-15 01:01:08','canceled',NULL,NULL,NULL,'hello','2026-07-15 01:02:00','programmers','INC-20260715-008','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','5d41402abc4b2a76b9719d911017c592','d41d8cd98f00b204e9800998ecf8427e'),(45,'pp','Justin Marcus Santiago','INJ','dwqd','','2026-07-15 01:30:34','active',NULL,NULL,NULL,NULL,NULL,NULL,'INC-20260715-009','Medium','2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(46,'pp','Justin Marcus Santiago','INJ','dasd','','2026-07-15 01:41:51','active',NULL,NULL,NULL,NULL,NULL,NULL,'INJ20260715-010','Medium','2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(47,'pp','dasd','INJ','wdad','','2026-07-15 01:43:13','active',NULL,NULL,NULL,NULL,NULL,NULL,'INJ-20260715-011','Medium','2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(48,'pp','Justin Marcus Santiago','INJ','hi','','2026-07-15 06:15:59','active',NULL,NULL,NULL,NULL,NULL,NULL,'INC-20260715-012','Medium','2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(49,'pp','Justin Marcus Santiago','INJ','hoy','','2026-07-16 01:02:16','active',NULL,NULL,NULL,NULL,NULL,NULL,'INC-20260716-001','Medium','2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(50,'pp','Justin Marcus Santiago','INJ','fe','','2026-07-16 01:37:36','active',NULL,NULL,NULL,NULL,NULL,NULL,'INC-20260716-002','Medium','2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(53,'pp','Justin Marcus Santiago','INJ','wowdasd','','2026-07-16 08:52:06','active',NULL,NULL,NULL,NULL,NULL,NULL,'INC-20260716-003','Medium','2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(54,'pp','regergrewf','INJ','wedwad','','2026-07-16 08:52:16','active',NULL,NULL,NULL,NULL,NULL,NULL,'INC-20260716-004','Medium','2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(55,'pp','Justin Marcus Santiago','INJ','hi goodmornig','','2026-07-17 00:26:38','canceled',NULL,NULL,NULL,'not it related','2026-07-27 03:28:36','programmers','INC-20260717-001','Medium','2026-07-27 05:06:03','canceled','d41d8cd98f00b204e9800998ecf8427e','8d8a1eb28a73a0dbf21b398752708ecd','d41d8cd98f00b204e9800998ecf8427e'),(56,'pp','nico','INJ','dwd','','2026-07-17 00:46:55','active',NULL,NULL,NULL,NULL,NULL,NULL,'SMPIC-20260717-002','Medium','2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(57,'pp','Justin Marcus Santiago','INJ','hello my pineapple','','2026-07-17 00:59:26','active',NULL,NULL,NULL,NULL,NULL,NULL,'SMPIC-20260717-003','Medium','2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(58,'pp','Justin Marcus Santiago','INJ','hello goodmorning my pinya','','2026-07-17 01:05:41','active',NULL,NULL,NULL,NULL,NULL,NULL,'SMPIC-20260717-004','Medium','2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(59,'pp','nico','INJ','dwdqrwf','','2026-07-17 01:17:59','active',NULL,NULL,NULL,NULL,NULL,NULL,'SMPIC-20260717-005','Medium','2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(60,'pp','Justin Marcus Santiago','INJ','dwd','','2026-07-17 01:41:27','canceled',NULL,NULL,NULL,'u7u','2026-07-20 00:57:53','programmers','INC-20260717-006','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','506e45c93d64f94b7f77295750db5180','d41d8cd98f00b204e9800998ecf8427e'),(61,'pp','Justin Marcus Santiago','INJ','dasd','','2026-07-17 07:17:46','repaired','2026-07-20 00:08:15','jm',NULL,NULL,NULL,NULL,'INC-20260717-007','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','3da770cc56ed4407b6aaf10ad4e72b4d'),(62,'pp','Justin Marcus Santiago','INJ','testing','','2026-07-17 07:19:06','canceled',NULL,NULL,NULL,'dwa','2026-07-17 07:30:27','programmers','INC-20260717-008',NULL,'2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','f495be79bad3d692686f63d43283c1f8','d41d8cd98f00b204e9800998ecf8427e'),(63,'pp','Justin Marcus Santiago','INJ','hotdog','','2026-07-17 07:20:29','canceled',NULL,NULL,NULL,'dw','2026-07-17 07:30:24','programmers','INJ-20260717-009',NULL,'2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','1f2121f36f817bd18540e5fa7de06f59','d41d8cd98f00b204e9800998ecf8427e'),(64,'pp','Justin Marcus Santiago','INJ','dry run','','2026-07-20 00:56:55','canceled',NULL,NULL,NULL,'uyu','2026-07-20 00:57:49','programmers','INJ-20260720-001',NULL,'2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','544f77a211b7c8f6343e821067c9317d','d41d8cd98f00b204e9800998ecf8427e'),(65,'pc','tinay','PC-OFC','biometrics','','2026-07-20 01:03:01','active',NULL,NULL,NULL,NULL,NULL,NULL,'PC-OFC-20260720-002',NULL,'2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(66,'pp','sd','INJ','dw','','2026-07-20 01:14:05','active',NULL,NULL,NULL,NULL,NULL,NULL,'INJ-20260720-003',NULL,'2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(67,'pp','Juan Dela Cruz','INJ','Refill Printer Ink','','2026-07-20 03:49:47','active',NULL,NULL,NULL,NULL,NULL,NULL,'INJ-20260720-004',NULL,'2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(68,'pp','ddw','INJECTION','dwd','','2026-07-20 07:44:26','canceled',NULL,NULL,NULL,'sadada','2026-07-21 06:34:48','programmers','INJECTION-20260720-005',NULL,'2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','6a5f5a7aeab526e0ec00d002c4dd4c32','d41d8cd98f00b204e9800998ecf8427e'),(69,'INJ','Justin Marcus Santiago','INJECTION','hello','','2026-07-22 02:33:34','active',NULL,NULL,NULL,NULL,NULL,NULL,'INJECTION-20260722-001',NULL,'2026-07-23 06:08:29','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(70,'HRAD','nico','HUMAN RESOURCES','refill printer','','2026-07-22 05:06:43','canceled',NULL,NULL,'Control number - 5452026','already full','2026-07-24 01:20:53','programmers','HUMAN RESOURCES-20260722-002','Medium','2026-07-24 04:04:34','canceled','f90fd57bf6a09e9d1be7c2a93c243da0','70cdf2b8a88c2a9e77354680f09b83f6','d41d8cd98f00b204e9800998ecf8427e'),(71,'HRAD','dasd','HUMAN RESOURCES','adw','','2026-07-22 05:06:58','canceled',NULL,NULL,'hello','Not IT concern','2026-07-23 01:51:40','programmers','HUMAN RESOURCES-20260722-003','Medium','2026-07-23 06:14:10','canceled','5d41402abc4b2a76b9719d911017c592','deae54eb8b4a9e887a0cc142c4707c8d','d41d8cd98f00b204e9800998ecf8427e'),(72,'HRAD','Justin Marcus Santiago','HUMAN RESOURCES','test','','2026-07-22 05:19:15','canceled',NULL,NULL,NULL,'sdw','2026-07-23 01:45:11','programmers','HUMAN RESOURCES-20260722-004','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','e6b1d85b6b7cc0ef1959d94f0cc3e2d8','d41d8cd98f00b204e9800998ecf8427e'),(73,'HRAD','Justin Marcus Santiago','HUMAN RESOURCES','test','','2026-07-22 05:25:28','repaired','2026-07-22 05:28:21','jm',NULL,NULL,NULL,NULL,'HUMAN RESOURCES-20260722-005','Medium','2026-07-23 06:08:29','repaired','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','3da770cc56ed4407b6aaf10ad4e72b4d'),(74,'HRAD','Justin Marcus Santiago','HUMAN RESOURCES','test','','2026-07-23 01:36:39','canceled',NULL,NULL,NULL,'dw','2026-07-23 01:39:51','programmers','HUMAN RESOURCES-20260723-001','Medium','2026-07-23 06:08:29','canceled','d41d8cd98f00b204e9800998ecf8427e','1f2121f36f817bd18540e5fa7de06f59','d41d8cd98f00b204e9800998ecf8427e'),(75,'HRAD','Justin Marcus Santiago','HUMAN RESOURCES','refill ink:\r\nBlack','','2026-07-27 03:26:57','active',NULL,NULL,NULL,NULL,NULL,NULL,'HUMAN RESOURCES-20260727-001','Medium','2026-07-27 05:06:03','active','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e','d41d8cd98f00b204e9800998ecf8427e'),(76,'networks','christioan','HUMAN RESOURCES','wala lang po','','2026-07-28 05:20:20','canceled',NULL,NULL,NULL,'burger ka sakin','2026-07-28 05:21:17','programmers','HUMAN RESOURCES-20260728-001','Medium','2026-07-28 05:26:39','canceled','d41d8cd98f00b204e9800998ecf8427e','521b39c287b3f345a7ea77169805d2ab','d41d8cd98f00b204e9800998ecf8427e');
/*!40000 ALTER TABLE `concerns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_queue`
--

DROP TABLE IF EXISTS `email_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `email_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `to_email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `status` enum('queued','sending','sent','failed') DEFAULT 'queued',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `max_attempts` int(11) NOT NULL DEFAULT 5,
  `available_at` datetime NOT NULL DEFAULT current_timestamp(),
  `sent_at` datetime DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_queue`
--

LOCK TABLES `email_queue` WRITE;
/*!40000 ALTER TABLE `email_queue` DISABLE KEYS */;
INSERT INTO `email_queue` VALUES (1,'programmers@smpiclaguna.com','New concern SMPIC-20260717-003 submitted','Hello,\n\nNew IT concern has been submitted.\n\nTicket: SMPIC-20260717-003\nSubmitted by: Justin Marcus Santiago\nDepartment: INJ\nPriority: Medium\nDescription: hello my pineapple\n\nRegards,\nTicketing System\n','sent',1,5,'2026-07-17 08:59:26','2026-07-17 09:04:28',''),(2,'programmers@smpiclaguna.com','New concern SMPIC-20260717-004 submitted','Hello,\n\nNew IT concern has been submitted.\n\nTicket: SMPIC-20260717-004\nSubmitted by: Justin Marcus Santiago\nDepartment: INJ\nPriority: Medium\nDescription: hello goodmorning my pinya\n\nRegards,\nTicketing System\n','sent',1,5,'2026-07-17 09:05:41','2026-07-17 09:16:13',''),(3,'programmers@smpiclaguna.com','New concern SMPIC-20260717-005 submitted','Hello,\n\nNew IT concern has been submitted.\n\nTicket: SMPIC-20260717-005\nSubmitted by: nico\nDepartment: INJ\nPriority: Medium\nDescription: dwdqrwf\n\nRegards,\nTicketing System\n','queued',0,5,'2026-07-17 09:17:59',NULL,'');
/*!40000 ALTER TABLE `email_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `instructions`
--

DROP TABLE IF EXISTS `instructions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `instructions` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `instruction_text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `tittle` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `instructions`
--

LOCK TABLES `instructions` WRITE;
/*!40000 ALTER TABLE `instructions` DISABLE KEYS */;
INSERT INTO `instructions` VALUES (18,'install xamp','2026-07-21 05:26:17','web deployment'),(19,'10.97.2.10','2026-07-21 05:26:57','New Drop Box'),(20,'10.97.2.15','2026-07-21 05:27:41','Old Drop Box');
/*!40000 ALTER TABLE `instructions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meta`
--

DROP TABLE IF EXISTS `meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meta` (
  `name` varchar(100) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meta`
--

LOCK TABLES `meta` WRITE;
/*!40000 ALTER TABLE `meta` DISABLE KEYS */;
INSERT INTO `meta` VALUES ('schema_version','1');
/*!40000 ALTER TABLE `meta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(30) NOT NULL,
  `concern_id` int(11) DEFAULT NULL,
  `ticket_number` varchar(30) DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'email',
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,'pp',63,'INJ-20260717-009','INJ','email','Hello,\n\nYour concern has been canceled.\n\nTicket: INJ-20260717-009\nSubmitted by: Justin Marcus Santiago\nDepartment: INJ\nCanceled by: programmers\nReason: dw\n\nRegards,\nTicketing System\n','2026-07-17 07:30:24',NULL),(2,'pp',62,'INC-20260717-008','INJ','email','Hello,\n\nYour concern has been canceled.\n\nTicket: INC-20260717-008\nSubmitted by: Justin Marcus Santiago\nDepartment: INJ\nCanceled by: programmers\nReason: dwa\n\nRegards,\nTicketing System\n','2026-07-17 07:30:27',NULL),(3,'pp',64,'INJ-20260720-001','INJ','email','Hello,\n\nYour concern has been canceled.\n\nTicket: INJ-20260720-001\nSubmitted by: Justin Marcus Santiago\nDepartment: INJ\nCanceled by: programmers\nReason: uyu\n\nRegards,\nTicketing System\n','2026-07-20 00:57:49',NULL),(4,'pp',60,'INC-20260717-006','INJ','email','Hello,\n\nYour concern has been canceled.\n\nTicket: INC-20260717-006\nSubmitted by: Justin Marcus Santiago\nDepartment: INJ\nCanceled by: programmers\nReason: u7u\n\nRegards,\nTicketing System\n','2026-07-20 00:57:53',NULL),(5,'pp',68,'INJECTION-20260720-005','INJECTION','email','Hello,\n\nYour concern has been canceled.\n\nTicket: INJECTION-20260720-005\nSubmitted by: ddw\nDepartment: INJECTION\nCanceled by: programmers\nReason: sadada\n\nRegards,\nTicketing System\n','2026-07-21 06:34:48',NULL),(6,'HRAD',74,'HUMAN RESOURCES-20260723-001','HUMAN RESOURCES','email','Hello,\n\nYour concern has been canceled.\n\nTicket: HUMAN RESOURCES-20260723-001\nSubmitted by: Justin Marcus Santiago\nDepartment: HUMAN RESOURCES\nCanceled by: programmers\nReason: dw\n\nRegards,\nTicketing System\n','2026-07-23 01:39:51',NULL),(7,'HRAD',72,'HUMAN RESOURCES-20260722-004','HUMAN RESOURCES','email','Hello,\n\nYour concern has been canceled.\n\nTicket: HUMAN RESOURCES-20260722-004\nSubmitted by: Justin Marcus Santiago\nDepartment: HUMAN RESOURCES\nCanceled by: programmers\nReason: sdw\n\nRegards,\nTicketing System\n','2026-07-23 01:45:11',NULL),(8,'HRAD',71,'HUMAN RESOURCES-20260722-003','HUMAN RESOURCES','email','Hello,\n\nYour concern has been canceled.\n\nTicket: HUMAN RESOURCES-20260722-003\nSubmitted by: dasd\nDepartment: HUMAN RESOURCES\nCanceled by: programmers\nReason: Not IT concern\n\nRegards,\nTicketing System\n','2026-07-23 01:51:40',NULL),(9,'HRAD',70,'HUMAN RESOURCES-20260722-002','HUMAN RESOURCES','email','Hello,\n\nYour concern has been canceled.\n\nTicket: HUMAN RESOURCES-20260722-002\nSubmitted by: nico\nDepartment: HUMAN RESOURCES\nCanceled by: programmers\nReason: already full\n\nRegards,\nTicketing System\n','2026-07-24 01:20:53',NULL),(10,'pp',55,'INC-20260717-001','INJ','email','Hello,\n\nYour concern has been canceled.\n\nTicket: INC-20260717-001\nSubmitted by: Justin Marcus Santiago\nDepartment: INJ\nCanceled by: programmers\nReason: not it related\n\nRegards,\nTicketing System\n','2026-07-27 03:28:36',NULL),(11,'networks',76,'HUMAN RESOURCES-20260728-001','HUMAN RESOURCES','email','Hello,\n\nYour concern has been canceled.\n\nTicket: HUMAN RESOURCES-20260728-001\nSubmitted by: christioan\nDepartment: HUMAN RESOURCES\nCanceled by: programmers\nReason: burger ka sakin\n\nRegards,\nTicketing System\n','2026-07-28 05:21:20',NULL);
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_sequences`
--

DROP TABLE IF EXISTS `ticket_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_sequences` (
  `date_key` varchar(8) NOT NULL,
  `next_seq` int(11) NOT NULL,
  PRIMARY KEY (`date_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_sequences`
--

LOCK TABLES `ticket_sequences` WRITE;
/*!40000 ALTER TABLE `ticket_sequences` DISABLE KEYS */;
INSERT INTO `ticket_sequences` VALUES ('20260716',5),('20260717',6);
/*!40000 ALTER TABLE `ticket_sequences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(10) NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (21,'programmers','$2y$10$Jic/eAHLwFaIL23zCnHSluYySFONfs.XGBrQuQKhaOLtzJjhydHcO','admin','IT','programmers@smpiclaguna.com'),(23,'HRAD','$2y$10$x1g1n/opo7YwAkbC.Se1hO4.PgfDv95xpHL1u5KVOTDmv.0hkFqA6','user','HUMAN RESOURCES',''),(24,'CLINIC','$2y$10$Ti5lW6Nwhc06sBxEaULVVurcOpn..HLknS9vFeXEq4AMRT5lqMBWm','user','CLINIC',''),(25,'PURCH','$2y$10$4rx4ebSV18jWCQsnKVaoz.wbEIqs31NYuuywnar7UdszTzfUWl9wq','user','PURCHASING',''),(26,'ADMIN','$2y$10$Cc.QPrG5P2J4TKB8m6YAF.xKcwuZpq2GceYj0G.pDRpf.EVDPrvSm','user','ADMIN',''),(27,'GENSERVE','$2y$10$5ocLKx8EM7h5N0mYW5oGKedGUdAWumVfvTbu8S4iqBBDey4fDBjzq','user','GENSERVICES',''),(28,'ACC','$2y$10$rlCfN2JHAnWmZVbr4yzgF.f38PnlJeodMflJG/Ia6m6d9HYMoyG8G','user','ACCOUNTING',''),(29,'TREAS','$2y$10$DNxwheZ5d.hGzc/2qhwLhuoqsaWtirimM8iPYkw8sanGr9gPe33wG','user','TREASURY',''),(30,'QA','$2y$10$gdYsjBxl6aeMn4irOPNxS.mnUmcEBMWUrV5us.hUjSRlEvm0InA5S','user','QUALITY ASSURANCE',''),(31,'IMS','$2y$10$66VFgeXtVxLuyKRRrryh0uFaLsNOHIWhd4NHFZ4pIi5vtrGegSn9W','user','IMS',''),(32,'PC','$2y$10$vYaPUP0.cFfhxuAzsK0OceS2fpRYOpnn.SHwkkalm/ObyRlzc0Ng6','user','PRODUCTION CONTROL',''),(33,'RMW','$2y$10$Ly6IQaRaHabgERnAKFdkieQI5HyE8VDkc2GjySuzjkjGX8ekY8FgK','user','RAW MATERIAL WAREHOUSE',''),(34,'LOG','$2y$10$rzmeQi68k4HvP6cE8.A.cOXg6AMr2gg3wWCAgXXbE29gI/GvmtZuq','user','LOGISTICS',''),(35,'PW','$2y$10$i1i.ncYODtTQDqd1eFCgyuMW0UjtP3TIfQaOoqx5IlaQnNqUpuEpe','user','PLASTIC WAREHOUSE',''),(36,'TW','$2y$10$T9BhdQs8Jw52Pk0shA58vOKLWJYXApYhXAmo/q83vyfAs.fbBZjNq','user','TUBE WAREHOUSE',''),(37,'PE','$2y$10$zHup2QmjgRw9FAx0724NUOeZe8VfiOSy9eYoNkijTi85BDXMVUAi.','user','PROCESS ENGINEERING',''),(39,'ME','$2y$10$PUlJOsJC4OfPBAVYmSaaSuBJ3R1eS.JrnljEbns.xoZN7uzSZ2sJG','user','MAINTENANCE ENGINEERING',''),(40,'FC','$2y$10$93cIxyPi92vn/weipGhFLOS5.ipzwjv4vNeeMEf8hhy0HSVksOEoW','user','FACILITY',''),(41,'MO','$2y$10$9k2wH2k1ZPiLy9Xhx/IY4OuQayjCr.wnTP0O4EjnUFC4Y2WQQntMC','user','MOLD-OFFICEC',''),(42,'MF','$2y$10$FmRRtkOfqBk0Q5s3GRvi7.Wz1GpZZoE5iQvkFhbsQQZRR/VdTSNry','user','MOLD FABRICATION',''),(43,'MP','$2y$10$H/g.xn6EUgTX6/r5nXNjceNybKMm.CDKoP9XVGh3cx4y1wt.87qki','user','MOLD REPAIR',''),(44,'CE','$2y$10$E3LRkdR0FrSdX87bCJYVVuPr20Qobwuw6796R/Zz0PIj6mD4CEO5O','user','CONTROLED ENVIRONMENT',''),(45,'INJ','$2y$10$0RiM2Yu0HQawxYiJExd5ieUUywF1ThSz/IfBAdyOV0jQZQeDBOGdW','user','INJECTION',''),(46,'FIN','$2y$10$jymDFA6FlrRsiRMgN7PSS./Fe.8YmMNXAaAtO9Mttfn/BkyCn8E.y','user','FINISHING',''),(47,'TC','$2y$10$Ntuxi5kgfAheNSipeOFv0OR2B5L1ln2OflaunGz.gRgOE6Lr.hUVa','user','TUBE CUTTING',''),(48,'EXT','$2y$10$YgeEeCQ6cpxUryRyCk1VCeFnlR9oiRoaQVx6veVEWqMt9MTVtdRF.','user','EXTRUSION','');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-07  7:41:40
