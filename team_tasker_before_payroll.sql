-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: team_tasker
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
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `fcm_token` text DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'System Control Admin','admin@teamtasker.com',NULL,'$2y$12$Ows5iAQpdIlwJFXQ3div9uGCbF2OSqimuvkLDcXJALO/n/35f2yrq',NULL,NULL,'2026-10-12 06:52:43','2026-10-12 06:52:43');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_logs`
--

DROP TABLE IF EXISTS `attendance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `attendance_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `user_type` varchar(255) DEFAULT 'user',
  `action` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_logs_attendance_id_created_at_index` (`attendance_id`,`created_at`),
  KEY `attendance_logs_user_id_index` (`user_id`),
  CONSTRAINT `attendance_logs_attendance_id_foreign` FOREIGN KEY (`attendance_id`) REFERENCES `attendances` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_logs`
--

LOCK TABLES `attendance_logs` WRITE;
/*!40000 ALTER TABLE `attendance_logs` DISABLE KEYS */;
INSERT INTO `attendance_logs` VALUES (1,1,1,'admin','attendance.created','Attendance record manually created by System Control Admin.',NULL,'{\"created_by\":1}','2026-10-12 10:52:37','2026-10-12 10:52:37'),(2,1,1,'admin','attendance.updated','Attendance corrected by Super Admin System Control Admin',NULL,'{\"old\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"present\",\"approval_status\":\"approved\",\"remarks\":null},\"new\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"present\",\"approval_status\":\"approved\",\"remarks\":null},\"correction_reason\":null}','2026-10-12 10:52:49','2026-10-12 10:52:49'),(3,1,1,'admin','attendance.updated','Attendance corrected by Super Admin System Control Admin',NULL,'{\"old\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"present\",\"approval_status\":\"approved\",\"remarks\":null},\"new\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"present\",\"approval_status\":\"approved\",\"remarks\":null},\"correction_reason\":null}','2026-10-12 10:53:08','2026-10-12 10:53:08'),(4,1,1,'admin','attendance.updated','Attendance corrected by Super Admin System Control Admin',NULL,'{\"old\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"present\",\"approval_status\":\"approved\",\"remarks\":null},\"new\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"present\",\"approval_status\":\"approved\",\"remarks\":null},\"correction_reason\":null}','2026-10-12 11:59:51','2026-10-12 11:59:51'),(5,2,1,'admin','attendance.created','Attendance record manually created by System Control Admin.',NULL,'{\"created_by\":1}','2026-10-12 12:29:15','2026-10-12 12:29:15'),(6,2,1,'admin','attendance.updated','Attendance corrected by Super Admin.',NULL,'{\"old\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"present\",\"approval_status\":\"approved\",\"remarks\":null},\"new\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"present\",\"approval_status\":\"approved\",\"remarks\":null},\"correction_reason\":null}','2026-10-12 12:35:13','2026-10-12 12:35:13'),(7,1,1,'admin','attendance.updated','Attendance corrected by Super Admin.',NULL,'{\"old\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"present\",\"approval_status\":\"approved\",\"remarks\":null},\"new\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"present\",\"approval_status\":\"approved\",\"remarks\":null},\"correction_reason\":null}','2026-10-12 12:36:06','2026-10-12 12:36:06'),(8,1,1,'admin','attendance.updated','Attendance corrected by Super Admin.',NULL,'{\"old\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"present\",\"approval_status\":\"approved\",\"remarks\":null},\"new\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"half_day\",\"approval_status\":\"approved\",\"remarks\":null},\"correction_reason\":null}','2026-10-12 12:36:20','2026-10-12 12:36:20'),(9,1,1,'admin','attendance.updated','Attendance corrected by Super Admin.',NULL,'{\"old\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"half_day\",\"approval_status\":\"pending\",\"remarks\":null},\"new\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"half_day\",\"approval_status\":\"pending\",\"remarks\":null},\"correction_reason\":null}','2026-10-12 12:37:31','2026-10-12 12:37:31'),(10,1,1,'admin','attendance.updated','Attendance corrected by Super Admin.',NULL,'{\"old\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"half_day\",\"approval_status\":\"pending\",\"remarks\":null},\"new\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"half_day\",\"approval_status\":\"pending\",\"remarks\":null},\"correction_reason\":null}','2026-10-12 13:05:38','2026-10-12 13:05:38'),(11,3,1,'admin','attendance.created','Attendance manually created by System Control Admin.',NULL,'{\"created_by\":1}','2026-10-13 05:06:28','2026-10-13 05:06:28'),(12,3,1,'admin','attendance.updated','Attendance corrected by Super Admin.',NULL,'{\"old\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"present\",\"approval_status\":\"approved\",\"remarks\":null},\"new\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"present\",\"approval_status\":\"approved\",\"remarks\":null},\"correction_reason\":null}','2026-10-13 05:21:52','2026-10-13 05:21:52'),(13,3,1,'admin','attendance.updated','Attendance corrected by Super Admin.',NULL,'{\"old\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"present\",\"approval_status\":\"approved\",\"remarks\":null},\"new\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"present\",\"approval_status\":\"approved\",\"remarks\":null},\"correction_reason\":null}','2026-10-13 05:22:03','2026-10-13 05:22:03'),(14,4,1,'admin','attendance.created','Attendance manually created by System Control Admin.',NULL,'{\"created_by\":1}','2026-10-13 05:22:15','2026-10-13 05:22:15'),(15,4,1,'admin','attendance.updated','Attendance corrected by Super Admin.',NULL,'{\"old\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"present\",\"approval_status\":\"draft\",\"remarks\":null},\"new\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"present\",\"approval_status\":\"draft\",\"remarks\":null},\"correction_reason\":null}','2026-10-13 05:22:27','2026-10-13 05:22:27'),(16,3,1,'admin','attendance.status_changed','Approval status changed from Approved to Pending by System Control Admin',NULL,'{\"old\":\"approved\",\"new\":\"pending\",\"reason\":null}','2026-10-13 06:12:07','2026-10-13 06:12:07'),(17,4,1,'admin','attendance.status_changed','Approval status changed from Draft to Approved by System Control Admin',NULL,'{\"old\":\"draft\",\"new\":\"approved\",\"reason\":null}','2026-10-13 06:12:18','2026-10-13 06:12:18'),(18,3,1,'admin','attendance.updated','Attendance corrected by Super Admin System Control Admin',NULL,'{\"old\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"present\",\"remarks\":null},\"new\":{\"check_in\":null,\"check_out\":null,\"working_minutes\":0,\"attendance_status\":\"present\",\"remarks\":null},\"correction_reason\":null}','2026-10-13 06:12:38','2026-10-13 06:12:38'),(19,3,1,'admin','attendance.status_changed','Approval status changed from Pending to Rejected by System Control Admin. Reason: Marked rejected by Super Admin.',NULL,'{\"old\":\"pending\",\"new\":\"rejected\",\"reason\":\"Marked rejected by Super Admin.\"}','2026-10-13 06:12:38','2026-10-13 06:12:38'),(20,3,1,'admin','attendance.status_changed','Approval status changed from Rejected to Pending by System Control Admin',NULL,'{\"old\":\"rejected\",\"new\":\"pending\",\"reason\":null}','2026-10-13 08:21:31','2026-10-13 08:21:31');
/*!40000 ALTER TABLE `attendance_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_requests`
--

DROP TABLE IF EXISTS `attendance_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` enum('Leave','Regularization','Overtime') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `reason` text NOT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `action_by` bigint(20) unsigned DEFAULT NULL,
  `action_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_requests_user_id_foreign` (`user_id`),
  KEY `attendance_requests_action_by_foreign` (`action_by`),
  CONSTRAINT `attendance_requests_action_by_foreign` FOREIGN KEY (`action_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attendance_requests_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_requests`
--

LOCK TABLES `attendance_requests` WRITE;
/*!40000 ALTER TABLE `attendance_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendances`
--

DROP TABLE IF EXISTS `attendances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `attendance_date` date DEFAULT NULL,
  `check_in` time DEFAULT NULL,
  `check_out` time DEFAULT NULL,
  `working_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `attendance_status` enum('present','absent','half_day') NOT NULL DEFAULT 'present',
  `approval_status` enum('draft','pending','approved','rejected') NOT NULL DEFAULT 'draft',
  `remarks` text DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_by_type` varchar(255) DEFAULT 'user',
  `rejection_reason` text DEFAULT NULL,
  `date` date NOT NULL,
  `clock_in` time DEFAULT NULL,
  `clock_out` time DEFAULT NULL,
  `status` enum('Present','Absent','Half-Day','Late','Leave','Holiday') NOT NULL DEFAULT 'Present',
  `work_hours` decimal(5,2) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `clock_in_location` varchar(255) DEFAULT NULL,
  `clock_out_location` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendances_user_id_date_unique` (`user_id`,`date`),
  KEY `attendances_approved_by_foreign` (`approved_by`),
  KEY `idx_att_date_approval` (`attendance_date`,`approval_status`),
  KEY `idx_att_date_status` (`attendance_date`,`attendance_status`),
  CONSTRAINT `attendances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendances`
--

LOCK TABLES `attendances` WRITE;
/*!40000 ALTER TABLE `attendances` DISABLE KEYS */;
INSERT INTO `attendances` VALUES (1,2,'2026-10-12',NULL,NULL,0,'half_day','pending',NULL,NULL,'2026-10-12 10:52:37',1,'user',NULL,'2026-10-12',NULL,NULL,'Present',0.00,NULL,NULL,NULL,NULL,NULL,'2026-10-12 10:52:37','2026-10-12 12:36:20'),(2,3,'2026-10-12',NULL,NULL,0,'present','approved',NULL,NULL,'2026-10-12 12:29:15',1,'user',NULL,'2026-10-12',NULL,NULL,'Present',0.00,NULL,NULL,NULL,NULL,NULL,'2026-10-12 12:29:15','2026-10-12 12:35:13'),(3,3,'2026-10-13',NULL,NULL,0,'present','pending',NULL,'2026-10-13 06:12:07',NULL,NULL,NULL,NULL,'2026-10-13',NULL,NULL,'Present',0.00,NULL,NULL,NULL,NULL,NULL,'2026-10-13 05:06:28','2026-10-13 08:21:31'),(4,2,'2026-10-13',NULL,NULL,0,'present','approved',NULL,NULL,'2026-10-13 06:12:18',1,'admin',NULL,'2026-10-13',NULL,NULL,'Present',0.00,NULL,NULL,NULL,NULL,NULL,'2026-10-13 05:22:15','2026-10-13 06:12:18');
/*!40000 ALTER TABLE `attendances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blocked_users`
--

DROP TABLE IF EXISTS `blocked_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `blocked_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `blocker_id` bigint(20) unsigned NOT NULL,
  `blocked_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blocked_users_blocker_id_blocked_id_unique` (`blocker_id`,`blocked_id`),
  KEY `blocked_users_blocked_id_foreign` (`blocked_id`),
  CONSTRAINT `blocked_users_blocked_id_foreign` FOREIGN KEY (`blocked_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `blocked_users_blocker_id_foreign` FOREIGN KEY (`blocker_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blocked_users`
--

LOCK TABLES `blocked_users` WRITE;
/*!40000 ALTER TABLE `blocked_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `blocked_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_user_permissions`
--

DROP TABLE IF EXISTS `chat_user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_user_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `allowed_user_id` bigint(20) unsigned DEFAULT NULL,
  `allowed_client_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chat_user_permissions_user_id_allowed_user_id_unique` (`user_id`,`allowed_user_id`),
  KEY `chat_user_permissions_allowed_user_id_foreign` (`allowed_user_id`),
  KEY `chat_user_permissions_client_id_foreign` (`client_id`),
  KEY `chat_user_permissions_allowed_client_id_foreign` (`allowed_client_id`),
  CONSTRAINT `chat_user_permissions_allowed_client_id_foreign` FOREIGN KEY (`allowed_client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_user_permissions_allowed_user_id_foreign` FOREIGN KEY (`allowed_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_user_permissions_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_user_permissions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_user_permissions`
--

LOCK TABLES `chat_user_permissions` WRITE;
/*!40000 ALTER TABLE `chat_user_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `chat_user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `client_email_templates`
--

DROP TABLE IF EXISTS `client_email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `client_email_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` longtext NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_email_templates_created_by_foreign` (`created_by`),
  KEY `client_email_templates_updated_by_foreign` (`updated_by`),
  CONSTRAINT `client_email_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `client_email_templates_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_email_templates`
--

LOCK TABLES `client_email_templates` WRITE;
/*!40000 ALTER TABLE `client_email_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `client_email_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `client_emails`
--

DROP TABLE IF EXISTS `client_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `client_emails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `client_email_template_id` bigint(20) unsigned DEFAULT NULL,
  `campaign_id` varchar(255) DEFAULT NULL,
  `campaign_name` varchar(255) DEFAULT NULL,
  `to_email` varchar(255) NOT NULL,
  `to_name` varchar(255) DEFAULT NULL,
  `from_email` varchar(255) DEFAULT NULL,
  `from_name` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `body` longtext NOT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `status` varchar(255) NOT NULL DEFAULT 'queued',
  `message_id` varchar(255) DEFAULT NULL,
  `ticket_id` bigint(20) unsigned DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `sent_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_emails_client_id_foreign` (`client_id`),
  KEY `client_emails_client_email_template_id_foreign` (`client_email_template_id`),
  KEY `client_emails_ticket_id_foreign` (`ticket_id`),
  KEY `client_emails_sent_by_foreign` (`sent_by`),
  KEY `client_emails_campaign_id_index` (`campaign_id`),
  KEY `client_emails_message_id_index` (`message_id`),
  CONSTRAINT `client_emails_client_email_template_id_foreign` FOREIGN KEY (`client_email_template_id`) REFERENCES `client_email_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `client_emails_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `client_emails_sent_by_foreign` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `client_emails_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_emails`
--

LOCK TABLES `client_emails` WRITE;
/*!40000 ALTER TABLE `client_emails` DISABLE KEYS */;
/*!40000 ALTER TABLE `client_emails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `client_imports`
--

DROP TABLE IF EXISTS `client_imports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `client_imports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `imported_by` bigint(20) unsigned DEFAULT NULL,
  `total_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `imported` int(10) unsigned NOT NULL DEFAULT 0,
  `updated` int(10) unsigned NOT NULL DEFAULT 0,
  `skipped` int(10) unsigned NOT NULL DEFAULT 0,
  `failed` int(10) unsigned NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `column_mapping` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`column_mapping`)),
  `duplicate_handling` varchar(255) NOT NULL DEFAULT 'skip',
  `errors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`errors`)),
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_imports_imported_by_foreign` (`imported_by`),
  CONSTRAINT `client_imports_imported_by_foreign` FOREIGN KEY (`imported_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `client_imports`
--

LOCK TABLES `client_imports` WRITE;
/*!40000 ALTER TABLE `client_imports` DISABLE KEYS */;
/*!40000 ALTER TABLE `client_imports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clients`
--

DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `client_type` varchar(255) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `fcm_token` text DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `email_opt_out` tinyint(1) NOT NULL DEFAULT 0,
  `otp` varchar(255) DEFAULT NULL,
  `otp_expires_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clients_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversation_participants`
--

DROP TABLE IF EXISTS `conversation_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversation_participants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `last_read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversation_participants_conversation_id_foreign` (`conversation_id`),
  KEY `conversation_participants_user_id_foreign` (`user_id`),
  KEY `conversation_participants_client_id_foreign` (`client_id`),
  CONSTRAINT `conversation_participants_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversation_participants_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversation_participants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversation_participants`
--

LOCK TABLES `conversation_participants` WRITE;
/*!40000 ALTER TABLE `conversation_participants` DISABLE KEYS */;
/*!40000 ALTER TABLE `conversation_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `conversations`
--

DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL DEFAULT 'private',
  `name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `conversations`
--

LOCK TABLES `conversations` WRITE;
/*!40000 ALTER TABLE `conversations` DISABLE KEYS */;
/*!40000 ALTER TABLE `conversations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inbound_emails`
--

DROP TABLE IF EXISTS `inbound_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inbound_emails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` varchar(255) DEFAULT NULL,
  `in_reply_to` varchar(255) DEFAULT NULL,
  `references` text DEFAULT NULL,
  `from_email` varchar(255) NOT NULL,
  `from_name` varchar(255) DEFAULT NULL,
  `to_email` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body_text` longtext DEFAULT NULL,
  `body_html` longtext DEFAULT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `ticket_id` bigint(20) unsigned DEFAULT NULL,
  `ticket_reply_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'processed',
  `raw_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inbound_emails_client_id_foreign` (`client_id`),
  KEY `inbound_emails_ticket_id_foreign` (`ticket_id`),
  KEY `inbound_emails_ticket_reply_id_foreign` (`ticket_reply_id`),
  KEY `inbound_emails_message_id_index` (`message_id`),
  KEY `inbound_emails_in_reply_to_index` (`in_reply_to`),
  CONSTRAINT `inbound_emails_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inbound_emails_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inbound_emails_ticket_reply_id_foreign` FOREIGN KEY (`ticket_reply_id`) REFERENCES `ticket_replies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inbound_emails`
--

LOCK TABLES `inbound_emails` WRITE;
/*!40000 ALTER TABLE `inbound_emails` DISABLE KEYS */;
/*!40000 ALTER TABLE `inbound_emails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meeting_participants`
--

DROP TABLE IF EXISTS `meeting_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meeting_participants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `meeting_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'participant',
  `status` varchar(255) NOT NULL DEFAULT 'invited',
  `invited_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `joined_at` timestamp NULL DEFAULT NULL,
  `left_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meeting_participants_meeting_id_user_id_unique` (`meeting_id`,`user_id`),
  KEY `meeting_participants_meeting_id_status_index` (`meeting_id`,`status`),
  KEY `meeting_participants_user_id_status_index` (`user_id`,`status`),
  CONSTRAINT `meeting_participants_meeting_id_foreign` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meeting_participants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meeting_participants`
--

LOCK TABLES `meeting_participants` WRITE;
/*!40000 ALTER TABLE `meeting_participants` DISABLE KEYS */;
/*!40000 ALTER TABLE `meeting_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meetings`
--

DROP TABLE IF EXISTS `meetings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meetings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` varchar(255) NOT NULL,
  `mode` varchar(255) NOT NULL,
  `provider` varchar(255) NOT NULL DEFAULT 'livekit',
  `room_name` varchar(255) NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `task_id` bigint(20) unsigned DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'scheduled',
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `meetings_uuid_unique` (`uuid`),
  UNIQUE KEY `meetings_room_name_unique` (`room_name`),
  KEY `meetings_type_status_index` (`type`,`status`),
  KEY `meetings_project_id_status_index` (`project_id`,`status`),
  KEY `meetings_task_id_status_index` (`task_id`,`status`),
  KEY `meetings_created_by_index` (`created_by`),
  CONSTRAINT `meetings_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meetings_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meetings_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meetings`
--

LOCK TABLES `meetings` WRITE;
/*!40000 ALTER TABLE `meetings` DISABLE KEYS */;
/*!40000 ALTER TABLE `meetings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `message_attachments`
--

DROP TABLE IF EXISTS `message_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint(20) unsigned NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(255) NOT NULL,
  `file_size` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `message_attachments_message_id_foreign` (`message_id`),
  CONSTRAINT `message_attachments_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `message_attachments`
--

LOCK TABLES `message_attachments` WRITE;
/*!40000 ALTER TABLE `message_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `message_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `body` text NOT NULL,
  `device_type` varchar(20) DEFAULT 'desktop',
  `is_forwarded` tinyint(1) NOT NULL DEFAULT 0,
  `reactions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`reactions`)),
  `attachment_path` varchar(255) DEFAULT NULL,
  `attachment_type` varchar(255) DEFAULT NULL,
  `attachment_original_name` varchar(255) DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `reply_to_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_conversation_id_foreign` (`conversation_id`),
  KEY `messages_user_id_foreign` (`user_id`),
  KEY `messages_client_id_foreign` (`client_id`),
  KEY `messages_reply_to_id_foreign` (`reply_to_id`),
  CONSTRAINT `messages_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_reply_to_id_foreign` FOREIGN KEY (`reply_to_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=69 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000001_create_cache_table',1),(2,'0001_01_01_000002_create_jobs_table',1),(3,'0001_01_01_000003_create_roles_table',1),(4,'0001_01_01_000003_create_statuses_table',1),(5,'0001_01_01_000004_create_users_table',1),(6,'0001_01_01_000005_create_tasks_table',1),(7,'0001_01_01_000006_create_task_logs_table',1),(8,'0001_01_01_000007_create_time_logs_table',1),(9,'0001_01_01_000008_create_projects_table',1),(10,'2026-08-19_201500_create_meetings_tables',1),(11,'2026_02_12_000000_create_chat_tables',1),(12,'2026_02_12_032901_add_attachments_to_messages_table',1),(13,'2026_02_12_185759_add_status_timestamps_to_messages_table',1),(14,'2026_02_16_181225_create_blocked_users_table',1),(15,'2026_02_16_184525_create_message_attachments_table',1),(16,'2026_02_19_174024_create_tickets_table',1),(17,'2026_02_19_174026_create_settings_table',1),(18,'2026_02_19_174026_create_ticket_replies_table',1),(19,'2026_02_19_175653_add_phone_company_to_users_table',1),(20,'2026_02_19_175903_add_attachments_and_privacy_to_tickets',1),(21,'2026_02_19_180356_add_ticket_number_to_tickets',1),(22,'2026_02_20_003000_add_waiting_for_client_status',1),(23,'2026_02_20_183438_add_ticket_id_to_tasks_table',1),(24,'2026_02_20_193318_expand_tasks_table',1),(25,'2026_02_20_193319_create_tags_table',1),(26,'2026_02_20_193319_create_task_attachments_table',1),(27,'2026_02_20_193319_create_task_dependencies_table',1),(28,'2026_02_20_193320_create_task_tag_table',1),(29,'2026_02_20_193320_create_task_templates_table',1),(30,'2026_02_20_195043_add_sla_tracking_to_tasks_table',1),(31,'2026_02_20_195200_add_is_completed_to_statuses_table',1),(32,'2026_02_20_195812_rename_task_tag_to_tag_task',1),(33,'2026_03_05_180713_add_deleted_at_to_messages_table',1),(34,'2026_03_05_192612_create_notifications_table',1),(35,'2026_03_07_182603_add_email_source_to_ticket_replies_table',1),(36,'2026_03_07_184537_add_fcm_token_to_users_table',1),(37,'2026_06_18_155540_create_task_user_table',1),(38,'2026_06_18_155550_add_project_id_to_tasks_table',1),(39,'2026_06_18_155559_create_admins_table',1),(40,'2026_06_19_160439_create_todos_table',1),(41,'2026_06_20_025631_add_project_id_to_task_logs_table',1),(42,'2026_06_20_053212_add_user_type_to_todos_table',1),(43,'2026_06_21_124312_add_wip_limit_to_statuses_table',1),(44,'2026_06_21_124500_add_wip_limit_to_statuses_table',1),(45,'2026_06_21_152655_create_project_user_table',1),(46,'2026_06_22_120935_add_fcm_token_to_admins_table',1),(47,'2026_06_26_071225_create_chat_user_permissions_table',1),(48,'2026_06_27_051634_add_otp_to_users_table',1),(49,'2026_06_27_052143_create_clients_table',1),(50,'2026_06_27_052224_add_client_id_to_tickets_table',1),(51,'2026_06_27_052308_add_client_id_to_chat_tables',1),(52,'2026_06_27_054803_add_client_id_to_ticket_replies_and_logs',1),(53,'2026_06_27_055000_add_client_id_to_chat_permissions',1),(54,'2026_06_28_160346_change_fcm_token_to_text',1),(55,'2026_07_12_144710_create_attendances_table',1),(56,'2026_07_12_144712_create_attendance_requests_table',1),(57,'2026_08_22_114157_add_reply_forward_reactions_to_messages_table',1),(58,'2026_08_23_130801_add_location_to_attendances',1),(59,'2026_09_07_210800_add_device_type_to_messages_table',1),(60,'2026_09_08_000001_upgrade_attendances_table_structure',1),(61,'2026_09_08_000002_create_attendance_logs_table',1),(62,'2026_09_08_000003_allow_multi_guard_attendance_foreign_keys',1),(63,'2026_09_29_220001_add_import_and_email_fields_to_clients_table',1),(64,'2026_09_29_220002_create_client_imports_table',1),(65,'2026_09_29_220003_create_client_email_templates_table',1),(66,'2026_09_29_220004_create_client_emails_table',1),(67,'2026_09_29_220005_create_inbound_emails_table',1),(68,'2026_09_29_220006_add_message_id_to_tickets_and_replies_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES ('1f22c52f-4f43-45be-9369-fe31b675fbe8','App\\Notifications\\AttendanceNotification','App\\Models\\User',2,'{\"attendance_id\":4,\"action\":\"approved\",\"title\":\"Attendance Approved (13 Oct 2026)\",\"message\":\"Your attendance for 13 Oct 2026 has been approved.\",\"description\":\"Your attendance for 13 Oct 2026 has been approved.\",\"date\":\"2026-10-13\"}',NULL,'2026-10-13 06:12:18','2026-10-13 06:12:18'),('530db173-6d1f-4b48-9942-fc5edcf3deee','App\\Notifications\\AttendanceNotification','App\\Models\\User',3,'{\"attendance_id\":3,\"action\":\"rejected\",\"title\":\"Attendance Rejected (13 Oct 2026)\",\"message\":\"Your attendance for 13 Oct 2026 was rejected. Reason: Marked rejected by Super Admin.\",\"description\":\"Marked rejected by Super Admin.\",\"date\":\"2026-10-13\"}',NULL,'2026-10-13 06:12:38','2026-10-13 06:12:38');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_user`
--

DROP TABLE IF EXISTS `project_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_user_project_id_foreign` (`project_id`),
  KEY `project_user_user_id_foreign` (`user_id`),
  CONSTRAINT `project_user_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_user`
--

LOCK TABLES `project_user` WRITE;
/*!40000 ALTER TABLE `project_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `projects`
--

DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `start_date` date DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `projects_user_id_foreign` (`user_id`),
  CONSTRAINT `projects_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `projects`
--

LOCK TABLES `projects` WRITE;
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Super Admin','super-admin','[\"dashboard.view\",\"tasks.my_tasks\",\"tasks.view\",\"tasks.create\",\"tasks.edit\",\"tasks.delete\",\"tasks.todo\",\"tasks.activity\",\"tasks.board\",\"tasks.calendar\",\"tasks.gantt\",\"projects.view\",\"projects.create\",\"projects.edit\",\"projects.delete\",\"tickets.view\",\"tickets.view_all\",\"tickets.create\",\"tickets.edit\",\"tickets.delete\",\"tickets.reply\",\"tickets.assign\",\"clients.view\",\"clients.create\",\"clients.edit\",\"clients.delete\",\"chat.view\",\"chat.create_staff_group\",\"chat.create_client_group\",\"users.view\",\"users.create\",\"users.edit\",\"users.delete\",\"users.approve\",\"roles.view\",\"roles.create\",\"roles.edit\",\"roles.delete\",\"settings.view\",\"settings.edit\",\"hr.view\",\"hr.manage\",\"hr.attendance_settings\",\"hr.shift_rules\",\"hr.leave_requests\",\"hr.work_reports\",\"hr.timesheets\",\"hr.staff\",\"hr.roles\",\"attendance.view\",\"attendance.create\",\"attendance.update\",\"attendance.delete\",\"attendance.punch\",\"attendance.submit\",\"attendance.approve\",\"attendance.reject\",\"attendance.report\",\"attendance.export\",\"attendance.manage\",\"attendance.dashboard_overview\",\"attendance.daily\",\"attendance.monthly\",\"attendance.calendar_all\",\"attendance.requests_manage\",\"attendance.reports\",\"attendance.settings\",\"payroll.view\",\"payroll.salary_view\",\"payroll.salary_manage\",\"payroll.generate\",\"payroll.payslip_view\",\"payroll.approve\",\"meetings.view\",\"meetings.create\",\"meetings.join\",\"meetings.cancel\",\"meetings.delete\"]','2026-10-12 06:52:42','2026-10-13 09:52:45'),(2,'HR Manager','hr-manager','[\"dashboard.view\",\"hr.view\",\"hr.manage\",\"hr.attendance_settings\",\"hr.shift_rules\",\"hr.leave_requests\",\"hr.work_reports\",\"hr.timesheets\",\"hr.staff\",\"hr.roles\",\"users.view\",\"users.create\",\"users.edit\",\"users.approve\",\"roles.view\",\"roles.create\",\"roles.edit\",\"attendance.view\",\"attendance.create\",\"attendance.update\",\"attendance.punch\",\"attendance.submit\",\"attendance.approve\",\"attendance.reject\",\"attendance.report\",\"attendance.export\",\"attendance.manage\",\"attendance.dashboard_overview\",\"attendance.daily\",\"attendance.monthly\",\"attendance.calendar_all\",\"attendance.requests_manage\",\"attendance.reports\",\"attendance.settings\",\"settings.view\"]','2026-10-12 06:52:42','2026-10-12 06:52:42'),(3,'Manager','manager','[\"dashboard.view\",\"projects.view\",\"projects.create\",\"projects.edit\",\"projects.delete\",\"tasks.my_tasks\",\"tasks.view\",\"tasks.create\",\"tasks.edit\",\"tickets.view\",\"tickets.reply\",\"tickets.assign\",\"clients.view\",\"clients.create\",\"clients.edit\",\"chat.view\",\"users.view\",\"users.create\",\"users.edit\",\"users.approve\",\"roles.view\",\"meetings.view\",\"meetings.create\",\"meetings.join\",\"meetings.cancel\"]','2026-10-12 06:52:42','2026-10-12 06:52:42'),(4,'Client','client','[\"dashboard.view\",\"projects.view\",\"tasks.my_tasks\",\"tasks.view\",\"tasks.create\",\"tasks.edit\",\"chat.view\"]','2026-10-12 06:52:42','2026-10-12 06:52:42');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `statuses`
--

DROP TABLE IF EXISTS `statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statuses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `color` varchar(255) NOT NULL DEFAULT 'primary',
  `order` int(11) NOT NULL DEFAULT 0,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `wip_limit` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `statuses_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `statuses`
--

LOCK TABLES `statuses` WRITE;
/*!40000 ALTER TABLE `statuses` DISABLE KEYS */;
INSERT INTO `statuses` VALUES (1,'Pending','pending','warning',1,1,0,NULL,'2026-10-12 06:52:43','2026-10-12 06:52:43'),(2,'In Progress','in_progress','primary',2,0,0,NULL,'2026-10-12 06:52:43','2026-10-12 06:52:43'),(3,'Completed','completed','success',3,0,0,NULL,'2026-10-12 06:52:43','2026-10-12 06:52:43');
/*!40000 ALTER TABLE `statuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tag_task`
--

DROP TABLE IF EXISTS `tag_task`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tag_task` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `tag_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_tag_task_id_foreign` (`task_id`),
  KEY `task_tag_tag_id_foreign` (`tag_id`),
  CONSTRAINT `task_tag_tag_id_foreign` FOREIGN KEY (`tag_id`) REFERENCES `tags` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_tag_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tag_task`
--

LOCK TABLES `tag_task` WRITE;
/*!40000 ALTER TABLE `tag_task` DISABLE KEYS */;
/*!40000 ALTER TABLE `tag_task` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `color` varchar(255) NOT NULL DEFAULT '#6366f1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tags_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tags`
--

LOCK TABLES `tags` WRITE;
/*!40000 ALTER TABLE `tags` DISABLE KEYS */;
/*!40000 ALTER TABLE `tags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_attachments`
--

DROP TABLE IF EXISTS `task_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_attachments_task_id_foreign` (`task_id`),
  KEY `task_attachments_user_id_foreign` (`user_id`),
  CONSTRAINT `task_attachments_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_attachments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_attachments`
--

LOCK TABLES `task_attachments` WRITE;
/*!40000 ALTER TABLE `task_attachments` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_dependencies`
--

DROP TABLE IF EXISTS `task_dependencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_dependencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `depends_on_id` bigint(20) unsigned NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'blocker',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_dependencies_task_id_foreign` (`task_id`),
  KEY `task_dependencies_depends_on_id_foreign` (`depends_on_id`),
  CONSTRAINT `task_dependencies_depends_on_id_foreign` FOREIGN KEY (`depends_on_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_dependencies_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_dependencies`
--

LOCK TABLES `task_dependencies` WRITE;
/*!40000 ALTER TABLE `task_dependencies` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_dependencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_logs`
--

DROP TABLE IF EXISTS `task_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `note` text NOT NULL,
  `type` varchar(255) NOT NULL DEFAULT 'log',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_logs_user_id_foreign` (`user_id`),
  KEY `task_logs_project_id_foreign` (`project_id`),
  KEY `task_logs_task_id_foreign` (`task_id`),
  KEY `task_logs_client_id_foreign` (`client_id`),
  CONSTRAINT `task_logs_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `task_logs_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_logs_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_logs`
--

LOCK TABLES `task_logs` WRITE;
/*!40000 ALTER TABLE `task_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_templates`
--

DROP TABLE IF EXISTS `task_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `structure` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`structure`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_templates`
--

LOCK TABLES `task_templates` WRITE;
/*!40000 ALTER TABLE `task_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_user`
--

DROP TABLE IF EXISTS `task_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_user_task_id_foreign` (`task_id`),
  KEY `task_user_user_id_foreign` (`user_id`),
  CONSTRAINT `task_user_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_user`
--

LOCK TABLES `task_user` WRITE;
/*!40000 ALTER TABLE `task_user` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `status_id` bigint(20) unsigned DEFAULT NULL,
  `progress` int(11) NOT NULL DEFAULT 0,
  `planned_hours` decimal(8,2) DEFAULT NULL,
  `deadline` timestamp NULL DEFAULT NULL,
  `bucket` varchar(255) DEFAULT NULL,
  `task_id` varchar(255) DEFAULT NULL,
  `priority` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `recurring_interval` varchar(255) DEFAULT NULL,
  `next_occurrence_at` timestamp NULL DEFAULT NULL,
  `estimated_hours` decimal(8,2) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `sla_warning_sent` tinyint(1) NOT NULL DEFAULT 0,
  `sla_breach_sent` tinyint(1) NOT NULL DEFAULT 0,
  `custom_fields` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_fields`)),
  `project_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_user_id_foreign` (`user_id`),
  KEY `tasks_assigned_to_foreign` (`assigned_to`),
  KEY `tasks_status_id_foreign` (`status_id`),
  KEY `tasks_ticket_id_foreign` (`ticket_id`),
  KEY `tasks_parent_id_foreign` (`parent_id`),
  KEY `tasks_project_id_foreign` (`project_id`),
  CONSTRAINT `tasks_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_status_id_foreign` FOREIGN KEY (`status_id`) REFERENCES `statuses` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_replies`
--

DROP TABLE IF EXISTS `ticket_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_replies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `email_source` varchar(255) DEFAULT NULL,
  `message_id` varchar(255) DEFAULT NULL,
  `body` longtext NOT NULL,
  `attachments` varchar(255) DEFAULT NULL,
  `type` enum('internal','client_reply','system') NOT NULL DEFAULT 'client_reply',
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_replies_ticket_id_foreign` (`ticket_id`),
  KEY `ticket_replies_user_id_foreign` (`user_id`),
  KEY `ticket_replies_client_id_foreign` (`client_id`),
  KEY `ticket_replies_message_id_index` (`message_id`),
  CONSTRAINT `ticket_replies_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ticket_replies_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_replies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_replies`
--

LOCK TABLES `ticket_replies` WRITE;
/*!40000 ALTER TABLE `ticket_replies` DISABLE KEYS */;
/*!40000 ALTER TABLE `ticket_replies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tickets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `body` longtext NOT NULL,
  `attachments` varchar(255) DEFAULT NULL,
  `status` enum('open','in_progress','resolved','closed','waiting_for_client') DEFAULT 'open',
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'low',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `email_source` varchar(255) DEFAULT NULL,
  `message_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tickets_ticket_number_unique` (`ticket_number`),
  KEY `tickets_user_id_foreign` (`user_id`),
  KEY `tickets_assigned_to_foreign` (`assigned_to`),
  KEY `tickets_client_id_foreign` (`client_id`),
  KEY `tickets_message_id_index` (`message_id`),
  CONSTRAINT `tickets_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets`
--

LOCK TABLES `tickets` WRITE;
/*!40000 ALTER TABLE `tickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `time_logs`
--

DROP TABLE IF EXISTS `time_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `time_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `duration` int(11) NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `mode` varchar(255) NOT NULL DEFAULT 'Inside Office',
  `bucket` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `time_logs_task_id_foreign` (`task_id`),
  KEY `time_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `time_logs_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `time_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `time_logs`
--

LOCK TABLES `time_logs` WRITE;
/*!40000 ALTER TABLE `time_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `time_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `todos`
--

DROP TABLE IF EXISTS `todos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `todos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `user_type` varchar(255) NOT NULL DEFAULT 'web',
  `title` varchar(255) NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `todos_user_id_foreign` (`user_id`),
  CONSTRAINT `todos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `todos`
--

LOCK TABLES `todos` WRITE;
/*!40000 ALTER TABLE `todos` DISABLE KEYS */;
/*!40000 ALTER TABLE `todos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `fcm_token` text DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 0,
  `profile_image` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `otp` varchar(255) DEFAULT NULL,
  `otp_expires_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_id_foreign` (`role_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,1,'Super Admin','admin@teamtasker.com',NULL,NULL,'2026-10-12 06:52:43','$2y$12$nFNnJ7i6MBvix2QwsMJA0OyeCsiLd0GaMbY.sUkfCR6NeaHfJAkMq',NULL,1,NULL,NULL,'2026-10-12 06:52:43','2026-10-12 06:52:43',NULL,NULL),(2,2,'Shyamsundar Rajbhar','demo@1gmail.com',NULL,NULL,NULL,'$2y$12$tb9IFM0CSs85NjnmBvSa3.2zFC0lixg2LojSTALNypbFbf3nnK/O2',NULL,1,NULL,NULL,'2026-10-12 10:51:47','2026-10-12 10:52:05',NULL,NULL),(3,2,'ankesh','ak@gmail.com',NULL,NULL,NULL,'$2y$12$BaZMdkDe1O.EqQiUryFcKeusXU76EgB03tiSA29ZmyxcjPwSti0y.',NULL,1,NULL,NULL,'2026-10-12 10:59:25','2026-10-12 10:59:25',NULL,NULL);
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

-- Dump completed on 2026-10-13 16:21:19
