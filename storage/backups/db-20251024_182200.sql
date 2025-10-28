-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: chandusoft
-- ------------------------------------------------------
-- Server version	8.4.3

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
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
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `catalog_items`
--

DROP TABLE IF EXISTS `catalog_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `catalog_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `slug` varchar(160) NOT NULL,
  `title` varchar(160) NOT NULL,
  `price` decimal(10,2) DEFAULT '0.00',
  `short_desc` varchar(280) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_catalog_status` (`status`),
  KEY `idx_catalog_title` (`title`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catalog_items`
--

LOCK TABLES `catalog_items` WRITE;
/*!40000 ALTER TABLE `catalog_items` DISABLE KEYS */;
INSERT INTO `catalog_items` VALUES (2,'banner2','Banner2',200.00,'dssdsdas','/uploads/2025/10/img_68fb6281b49634.32014910.jpg','published','2025-10-24 11:27:09','2025-10-18 12:54:31'),(4,'poster1','Poster1',450.00,'asas as as as','/uploads/2025/10/img_68fb62dac3a4e0.31089596.jpg','published','2025-10-24 11:28:32','2025-10-18 13:25:23'),(16,'product','product',166.00,'sgsdg','','published','2025-10-24 11:26:01','2025-10-20 07:27:46'),(20,'efdfsdf','efdfsdf',787.00,'','/uploads/2025/10/img_68fb629ba6df36.49401913.jpg','published','2025-10-24 11:27:26','2025-10-20 08:21:30'),(21,'ewaas','DairyFarm Products',1232.00,'dfdfdf','/../uploads///68f8c6c3709f1.jpg','published','2025-10-23 07:33:58','2025-10-20 08:36:39'),(23,'diwali-sparklers','Diwali Sparklers',300.00,'celebrate today','/../uploads///68f8c69540804.png','published','2025-10-22 11:57:14','2025-10-20 10:19:50'),(24,'health-wealth','Health & Wealth',456.00,'5464','/../uploads///68f8c6547c2ca.jpg','published','2025-10-23 07:34:45','2025-10-22 05:48:06'),(25,'books','Books',2000.00,'fdgfg','/../uploads/2025/10/img_68f8b7df346e32.81056560.jpg','published','2025-10-22 10:54:26','2025-10-22 10:54:26'),(26,'tech','Tech',12500.00,'ythgm','/../uploads/2025/10/img_68f8b9542fd7d6.40383457.jpg','published','2025-10-22 11:00:39','2025-10-22 11:00:39'),(27,'crackers','Crackers',300.00,'dfghgjdfjghj','/../uploads///68f9c1615f899.jpg','published','2025-10-23 05:48:08','2025-10-22 12:22:08'),(28,'entertainment-magazines','Entertainment Magazines',350.00,'Explore what\'s is happening in the cine world.','/../uploads/2025/10/img_68f9e33bb15f54.05534406.png','published','2025-10-23 08:11:39','2025-10-23 08:11:39'),(29,'working-employees','Working Employees',25000.00,'fdffgfgfgfgfgfggg','/uploads/2025/10/img_68fa128e600752.04334092.jpg','published','2025-10-23 11:33:34','2025-10-23 10:27:41'),(30,'edasas','edasas',564.00,'asasas','/uploads/2025/10/img_68fa1ee339b387.31338855.jfif','published','2025-10-23 12:26:11','2025-10-23 12:26:11'),(31,'cowboy','Cowboy',567.00,'sasas','/uploads/2025/10/img_68fa1f3660cdc9.71530793.jpg','published','2025-10-23 12:27:34','2025-10-23 12:27:34'),(32,'hatboy','hatboy',667.00,'asasa','/uploads/2025/10/img_68fa1fc6de18f1.51691259.jpg','published','2025-10-23 12:29:59','2025-10-23 12:29:59'),(33,'qsada','qsada',323.00,'','/uploads/2025/10/img_68fb005b32cf87.79171636.png','published','2025-10-24 07:55:03','2025-10-23 13:40:03');
/*!40000 ALTER TABLE `catalog_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enquiries`
--

DROP TABLE IF EXISTS `enquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enquiries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enquiries`
--

LOCK TABLES `enquiries` WRITE;
/*!40000 ALTER TABLE `enquiries` DISABLE KEYS */;
INSERT INTO `enquiries` VALUES (1,23,'sasas','siva@gmail.com','asasasa','2025-10-20 12:43:54'),(2,23,'sameer','saleem@gmail.com','dsghshsdhsg','2025-10-22 03:33:08'),(3,23,'sameer','saleem@gmail.com','dsghshsdhsg','2025-10-22 03:33:31'),(4,23,'sameer','Sameer.Mohammed@chandusoft.com','ouoipuiopio','2025-10-22 03:40:47'),(5,23,'venkat','vamsi@gmail.com','redsdadaa','2025-10-22 04:19:25'),(6,23,'venkat','vamsi@gmail.com','redsdadaa','2025-10-22 04:19:55'),(7,23,'tfrtgdf','sameera@gmail.com','asas a','2025-10-22 04:36:55'),(8,23,'bbbbb','asasa@gmail.com','asasa','2025-10-22 04:38:21'),(9,23,'Siva','siva@gmail.com','gfgffgf','2025-10-22 04:49:20'),(10,23,'ramoji','as3121@gmail.com','hghgghgh','2025-10-22 05:12:51'),(11,24,'ghf','saleem@gmail.com','gfhs','2025-10-22 05:56:16'),(12,24,'jaisai','jaisai@gmail.com','I have worked on this. Thank you','2025-10-22 10:43:44');
/*!40000 ALTER TABLE `enquiries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leads` (
  `id` int NOT NULL AUTO_INCREMENT,
  `Name` varchar(100) NOT NULL,
  `Message` text NOT NULL,
  `Email` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT (now()),
  `ip` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=115 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
INSERT INTO `leads` VALUES (1,'sameer','df','chandusoft.net@gmail.com','2025-10-01 10:24:39',NULL),(2,'sameer','puff','chandusoft.net@gmail.com','2025-10-01 10:24:39',NULL),(3,'sameer Md','ko','sameer.mohammad@chandusoft.com','2025-10-01 10:24:39',NULL),(4,' afdas','sgfg','sameer.mohammad@chandusoft.com','2025-10-01 10:29:06',NULL),(5,'sdfsdfertwe','dgfdfg','EFesfwearf@gmail.com','2025-10-01 11:09:22',NULL),(6,'sameer','srgfsgsfg','sameer.mohammad@chandusoft.com','2025-10-01 11:09:50',NULL),(8,'Mohammad Sameer','Hai Crearted a message','sameer.mohammad@chandusoft.com','2025-10-03 03:52:14',NULL),(10,'sa','afdgdhgsdfghh','test@example.com','2025-10-03 12:01:50',NULL),(12,'sameer','2erygdfhhxcncvbk','dfhsghf435234@gmail.com','2025-10-03 12:23:48',NULL),(14,'Sameer MD','Hi im Created by user','sameermohammad@chandusoft.com','2025-10-04 07:55:34',NULL),(16,'venkat','Your website is looking good','venkat12@yahoo.com','2025-10-06 12:16:34',NULL),(17,'jaisai','this is jaisai reporting','jaisai@gmail.com','2025-10-07 07:33:43',NULL),(20,'sameera','hi, welcome to my world!','sameera@gmail.com','2025-10-07 07:42:14',NULL),(22,'jaysayee','i\'m from mangalagiri','jaisai@gmail.com','2025-10-07 07:45:20',NULL),(23,'Siva','asa as as a','siva@gmail.com','2025-10-07 07:50:34',NULL),(24,'vamsi','this is vamsi reporting','vamsi@gmail.com','2025-10-07 08:05:59',NULL),(25,'sameer','asa as as ','saleem@gmail.com','2025-10-07 08:06:10',NULL),(26,'kumar','as as a asa ','kumar2@gmail.com','2025-10-07 08:17:47',NULL),(27,'rudra','asa asasa ','rudra@gmail.com','2025-10-07 08:29:35',NULL),(28,'phani','this is phani','phanikumar.bvvn@chandusoft.com','2025-10-07 08:59:21',NULL),(31,'cstl','asasa as a','cstltest4@gmail.com','2025-10-07 09:10:11',NULL),(34,'sameer','fdhszrdfg','saleem@gmail.com','2025-10-07 09:38:25',NULL),(37,'reeee','zvbxvbxvb','vamsi@gmail.com','2025-10-07 09:49:15',NULL),(38,'sameer MM','Hi im created','sameer565@gmail.com','2025-10-07 09:50:07',NULL),(41,'sameer MM','Hi im created','sameer565@gmail.com','2025-10-07 09:55:28',NULL),(42,'jaisaeee','hi im boy','jai@gmail.com','2025-10-07 09:56:31',NULL),(44,'sameer','jgd.jh','sameer565@gmail.com','2025-10-07 09:58:02',NULL),(45,'Siva','srtgertaw3rgt','jaisai@gmail.com','2025-10-07 10:00:50',NULL),(46,'itala','hii ','ittala@gmail.com','2025-10-07 10:02:26',NULL),(48,'sameer','tyusrthcyfrh','sameera@gmail.com','2025-10-07 10:14:28',NULL),(55,'Sai KUmar','dfgfdbf','Kumar@gmail.com','2025-10-07 10:21:01',NULL),(58,'venkat','sfzgasfg','siva@gmail.com','2025-10-07 10:55:47',NULL),(60,'fdgdfbg','asgadfg','sameera@gmail.com','2025-10-07 10:58:51',NULL),(61,'Md Sameer','Good Evening ......','sameermohammad@gmail.com','2025-10-07 11:06:25',NULL),(62,'Md Sameer','Good Evening ......','sameermohammad@gmail.com','2025-10-07 11:20:35',NULL),(63,'Md Sameer','Good Evening ......','sameermohammad@gmail.com','2025-10-07 11:42:05',NULL),(64,'Siva','asasas','saleem@gmail.com','2025-10-07 11:48:05',NULL),(66,'asas','asas asas','asasas@gmail.com','2025-10-07 11:53:02',NULL),(69,'asas','asas asas','asasas@gmail.com','2025-10-07 12:55:51',NULL),(70,'narayana','as aas as a ','narayana@gmail.com','2025-10-07 12:58:02',NULL),(71,'fdgdfbg','asasa','saleem@gmail.com','2025-10-07 13:01:58',NULL),(72,'Siva','asas as a','vamsi@gmail.com','2025-10-07 13:11:06',NULL),(73,'Siva','asasas','asas@gmail.com','2025-10-07 13:19:34',NULL),(74,'fdgdfbg','asas as as','sameera@gmail.com','2025-10-07 13:21:46',NULL),(75,'Umar','Hellooo......','umar@gmail.com','2025-10-08 03:50:27',NULL),(76,'Umar','Hellooo......','umar@gmail.com','2025-10-08 04:28:11',NULL),(77,'samwer','ooooo','zfgdfsd@gmail.com','2025-10-08 04:29:02',NULL),(78,'samwer','ooooo','zfgdfsd@gmail.com','2025-10-08 04:32:51',NULL),(79,'samwer','ooooo','zfgdfsd@gmail.com','2025-10-08 04:33:01',NULL),(80,'samwer','ooooo','zfgdfsd@gmail.com','2025-10-08 04:34:59',NULL),(81,'Shahid','Haii......!!!','shahid@gmail.com','2025-10-08 04:38:37',NULL),(84,'tjst','ydfgdfh','siva@gmail.com','2025-10-08 05:00:06',NULL),(85,'fdgdfbg','fdjgjh','saleem@gmail.com','2025-10-08 05:12:00',NULL),(86,'venkat','agdhgjsg','gjdfgj@gmail.com','2025-10-08 05:12:46',NULL),(87,'sameer md','lgdf','sameera@gmail.com','2025-10-08 06:02:04',NULL),(88,'sameer md','lgdf','sameera@gmail.com','2025-10-08 06:03:06',NULL),(89,'uyuy','dfhjd','uyuyu@gmail.com','2025-10-08 06:52:04',NULL),(90,'uyuy','dfhjd','uyuyu@gmail.com','2025-10-08 06:52:18',NULL),(92,'nnbbvnc','rzgxszfg','dfnbn@gmail.com','2025-10-08 06:55:49',NULL),(93,'sameer','asa asa sa','saleem@gmail.com','2025-10-08 11:56:32',NULL),(94,'phani','asasasa asas as','phanikumar@gmail.com','2025-10-08 13:48:54',NULL),(95,'phani','asasasa asas as','phanikumar@gmail.com','2025-10-08 13:49:06',NULL),(96,'phani','your website looks good.','phanikumar@gmail.com','2025-10-08 13:54:45',NULL),(97,'narayana','s as as asa','narayana@gmail.com','2025-10-08 14:00:26',NULL),(98,'sameer',';KDSFH','mmm@gmail.com','2025-10-09 05:15:13',NULL),(99,'Sofia','Hi...','sofi@gmail.com','2025-10-09 05:40:03',NULL),(100,'shakira','Hai hello...','shakira@gmail.com','2025-10-09 05:45:38',NULL),(101,'sameer','asa as as a sas a','sameer@gmail.com','2025-10-09 10:07:20',NULL),(102,'sameer','asas','saleem@gmail.com','2025-10-09 12:23:47',NULL),(103,'Ruhi','Hi  Dear...','ruhi@gmail.com','2025-10-10 02:59:14',NULL),(104,'asasa','asas as a','sameera@gmail.com','2025-10-10 11:13:57',NULL),(105,'salma','as asedc c dsd','salmashaik@gmail.com','2025-10-10 11:17:18',NULL),(106,'asas','asasas asas','Sameer.Mohammed@chandusoft.com','2025-10-10 11:18:07',NULL),(107,'asas','asasas asas','Sameer.Mohammed@chandusoft.com','2025-10-10 11:33:23',NULL),(108,'qwqwq','this is not correct','sudheer@gmail.com','2025-10-10 11:34:16',NULL),(109,'Haleem','Hai im good','haleem@gmail.com','2025-10-13 03:38:00',NULL),(110,'Haleem','Hai im good','haleem@gmail.com','2025-10-13 03:39:05',NULL),(111,'asas','asasa asa','asasa@gmail.com','2025-10-13 08:08:50',NULL),(112,'sameer','asasas asas','siva@gmail.com','2025-10-13 10:39:35',NULL),(113,'Haleem khN','dasgasgdfghk','haleemkhn@gmail.com','2025-10-18 08:00:47',NULL),(114,'asas','dfgsdfhsdh','as3121@gmail.com','2025-10-22 10:59:15',NULL);
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `content_html` text,
  `status` enum('published','draft','archived') DEFAULT 'draft',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (1,'Service','fgdfgdf',NULL,'archived','2025-10-20 10:46:08'),(5,'Work','done',NULL,'archived','2025-10-20 11:02:46'),(6,'Careers','careers',NULL,'archived','2025-10-20 10:54:46'),(9,'Services','services','<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n  <meta charset=\"UTF-8\" />\r\n  <title>Chandusoft</title>\r\n  <link rel=\"stylesheet\" href=\"style.css\" />\r\n  <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css\">\r\n\r\n  \r\n</head>\r\n<body>\r\n\r\n   <div id=\"header\"></div>\r\n   <?php include(\"header.php\"); ?>\r\n   <main>\r\n    <section id = \"Services\">\r\n         <div class=\"services-container\">\r\n      \r\n      <div class=\"service-card\">\r\n        <i class=\"fas fa-building icon-blue\"></i>\r\n        <h3>Enterprise Application Solution</h3>\r\n        <p>Robust enterprise apps for seamless business operations.</p>\r\n      </div>\r\n\r\n      <div class=\"service-card\">\r\n        <i class=\"fas fa-mobile-alt icon-green\"></i>\r\n        <h3>Mobile Application Solution</h3>\r\n        <p>Cross-platform mobile apps with modern UI/UX.</p>\r\n      </div>\r\n\r\n      <div class=\"service-card\">\r\n        <i class=\"fas fa-laptop icon-black\"></i>\r\n        <h3>Web Portal Design & Solution</h3>\r\n        <p>Custom web portals for business and customer engagement.</p>\r\n      </div>\r\n\r\n      <div class=\"service-card\">\r\n        <i class=\"fas fa-tools icon-yellow\"></i>\r\n        <h3>Web Portal Maintenance & Content Management</h3>\r\n        <p>Continuous support, updates, and content handling.</p>\r\n      </div>\r\n\r\n      <div class=\"service-card\">\r\n        <i class=\"fas fa-vial icon-purple\"></i>\r\n        <h3>QA & Testing</h3>\r\n        <p>Quality assurance and testing for bug-free releases.</p>\r\n      </div>\r\n\r\n      <div class=\"service-card\">\r\n        <i class=\"fas fa-phone icon-red\"></i>\r\n        <h3>Business Process Outsourcing</h3>\r\n        <p>End-to-end BPO services with 24/7 operations.</p>\r\n      </div>\r\n\r\n    </div>\r\n    </section>\r\n   </main>\r\n   <div id=\"footer\"></div>\r\n      <?php include(\"footer.php\"); ?>\r\n</body>\r\n</html>','archived','2025-10-23 09:37:28'),(15,'About','about','<!DOCTYPE html>\r\n<html lang=\"en\">\r\n<head>\r\n  <meta charset=\"UTF-8\" />\r\n  <title>Chandusoft</title>\r\n  <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css\">\r\n  <link rel=\"stylesheet\" href=\"style.css\" />\r\n  \r\n</head>\r\n<body>\r\n\r\n  <div id=\"header\"></div>\r\n  <?php include(\"header.php\"); ?>\r\n   <main>\r\n  \r\n  \r\n   <section id=\"About Us\">\r\n  <h2>About Us</h2>\r\n  <p>\r\n    <span class=\"highlight\">Chandusoft</span> is a well-established company with over \r\n    <span class=\"highlight\">15 years of experience</span> in delivering \r\n    <span class=\"highlight\">IT and BPO solutions</span>.\r\n    We have a <i>team</i> of more than <span class=\"highlight\">200 skilled professionals</span> \r\n    operating from multiple locations.\r\n\r\n    One of our key strengths is <span class=\"highlight\">24/7 operations</span>, which allows us to \r\n    support clients across different time zones.\r\n\r\n    We place a strong emphasis on <span class=\"highlight\">data integrity</span> and \r\n    <span class=\"highlight\">security</span>, which has helped us earn long-term trust from our partners.\r\n\r\n    Our core service areas include \r\n    <span class=\"highlight\">Software Development</span>, \r\n    <span class=\"highlight\">Medical Process Services</span>, and \r\n    <span class=\"highlight\">E-Commerce Solutions</span>, all backed by a commitment to \r\n    <span class=\"highlight\">quality</span> and <span class=\"highlight\">process excellence</span>.\r\n  </p>\r\n</section>\r\n\r\n    </main>\r\n    <div id=\"footer\"></div>\r\n      <?php include(\"footer.php\"); ?>\r\n</body>\r\n</html>','draft','2025-10-10 09:23:47'),(16,'Dashboard','dashboard','this is dashboard','draft','2025-10-08 17:49:08'),(17,'ABC','abc','asas as as','draft','2025-10-09 09:46:25'),(19,'Chaitanya','chaitanya','asa as a','draft','2025-10-08 18:25:59'),(20,'Courses','courses','Welcome to course page... Thank U','draft','2025-10-10 09:23:10'),(21,'Security','security','We have provided a tough security','published','2025-10-24 16:40:26'),(22,'Cstl','cstl','our employement','archived','2025-10-10 08:30:46'),(23,'Reviews','reviews','overall rating to our company','draft','2025-10-09 11:51:41'),(24,'History','history','Since 2010','draft','2025-10-09 11:51:24'),(25,'ABC','abc-1760790343','asas as','archived','2025-10-23 09:37:22'),(26,'All','all','12233','archived','2025-10-23 09:37:19');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=134 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'site_name',''),(2,'site_tagline',''),(3,'site_logo','\\images\\chandusoft_logo.png'),(4,'footer_text','');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `role` enum('admin','editor') NOT NULL DEFAULT 'editor',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (8,'sameer','sameer.mohammad@chandusoft.com','$2y$10$EIAV/7uFCVEvUMLopDD6gulNCzoKqsFbh6kFgCMl4R9dHojSISD7a','2025-10-03 10:07:06','editor'),(9,'musthafa','text@example.com','$2y$10$6K07Ur4z2RnrIrXwhW0QwOIqrvrEfjvUCVifjScQ9t8R71R9rZgju','2025-10-03 10:08:54','editor'),(10,'phani','test5@example.com','$2y$10$s3ujiUpWO8hNbffxS0iBq.TFqd3MjpbSdHZeb1GLhO1VbFeN4JhhO','2025-10-03 11:03:09','editor'),(11,'jaisai','test6@example.com','$2y$10$W9DUIO2PoUyJCNEPTko26eCJDjA9P5.GYbWDQXx1RSWggLI.av7um','2025-10-03 11:46:44','editor'),(12,'sameer','chandusoft.net@gmail.com','$2y$10$4YWp74qG3yyZ/0ja2//pLO1.7MpBvYsf7EaNl4SiiEHc9XPQzwiB6','2025-10-04 04:03:10','editor'),(15,'kumar','kumar1@chandusoft.com','$2y$10$SaVdFPjkzDdA3.USYhdeQe1yGDxoz6.oxeRwIW95mMDRgQaeMZ9T.','2025-10-04 05:54:40','editor'),(16,'musthafa SK','mustafa@gmail.com','$2y$10$.JchqLvR4ZNquDe8Zz/1N..uDeFHrLDAzMA2yWeOTkgZ2tc.zOpLm','2025-10-04 07:10:59','editor'),(18,'phani','kumar@gmail.com','$2y$10$/Li/qy9JhMtjCw5d0PLlkuXwGJOVSFovSawAmHGMF51Pdi465UyXG','2025-10-04 07:20:39','editor'),(22,'Sameer M','sameer23344@gmail.com','$2y$10$cy4hcjNo/TqJm9RY53H0Feri966QRRQIWlch9Tb3gx3RjckBoJZTu','2025-10-04 07:51:36','editor'),(24,'phani','phaniku@gmail.com','$2y$10$mu3rPgtVTILBoRi5HZkx6eRwXMhIvzVPwf9JsCraRU2kCxXgvFPri','2025-10-04 10:51:06','editor'),(25,'jaisai CH','jaisai@gmail.com','$2y$10$4GTN1tm502I4TvnQ8Bp7N.vt5xBdWgcVpXr8VXLI0WkVP2XpkGa4a','2025-10-04 11:27:32','editor'),(27,'venkat','venkat12@rediffmail.com','$2y$10$5A0sLGZl3ioUlerrCXwBWuvnEYnFN6eqKvoQVO3keAFNGWDzaCvZq','2025-10-04 12:49:13','editor'),(29,'Siva','siva@gmail.com','$2y$10$ha6JoNzeiZLKXpm233h7X.nwyc0QmnPGtGt/Hqrj7JU5TzEODSac2','2025-10-06 04:22:07','editor'),(31,'saleema','saleemashaik@gmail.com','$2y$10$aW3CA4WFtSjuq9eEqxu7C..JHLWhMr3.H.XTG3b/dQZ9dNVqx/KYu','2025-10-09 07:50:07','editor'),(33,'jaysaiy','jaysaiy@gmail.com','$2y$10$8tC90UDAwgySXfQAm12Neu10D4sL1/vyQ70Q7rs.BMw4E.2txxGTC','2025-10-09 08:32:53','editor'),(34,'fdgdfbg','sameera@gmail.com','$2y$10$1pwUhHzD0N0zFZgcJdAhs.71kJKuqV1U0kNHm7.LP4PWCYZU/2Sri','2025-10-09 09:44:58','editor'),(35,'Md','md@gmail.com','$2y$10$NY6.6Zqllv0Y5EktbVIiUemrgms5enMngjhAuSP6zF0jn5TWcrN8q','2025-10-09 09:45:33','editor'),(37,'narayan','narayan121@gmail.com','$2y$10$oh4H1q0Uq231JEuZL8MYP.BfClMPNv5q3xGA9RtoNc7dAvqUYxy0u','2025-10-09 10:50:00','editor'),(38,'nagarjuna','nagarjuna@gmail.com','$2y$10$6leGxa7qBIOZyPmXA1fEfeQ7H/VMcXeFrZQfs60iVqwt7gpCbPBH.','2025-10-09 11:42:27','editor'),(39,'akkineni','akkineni@gmail.com','$2y$10$98AQxhU.bwRwv85.s3KmyeBzPWs.N4YpbdGtZCiyuMTS1P2d5jtyi','2025-10-09 11:56:46','editor'),(40,'venky','venkatesh@gmail.com','$2y$10$NIlRAqxkjiAflJKbsMGTOu4mUPrUhor5qNlF4U5ZNOvp0PwQFV2lm','2025-10-10 08:30:53','editor'),(41,'sameer','as3121@gmail.com','$2y$10$qdfDIdh0egyGu8i.VYasKO8lz5mEsFzO6n5H6vn3XY9Ttwyb/h8f.','2025-10-13 10:14:28','editor'),(42,'salmankhan','salmankhan@gmail.com','$2y$10$qKy39NLRFUaBf.MGbXUDW.Ne/0HhbPgfvbXsppz/1cUUIizEs0JiO','2025-10-13 12:42:56','editor'),(43,'sameer .md','sameer.md@gmail.com','$2y$10$m08LYKKKjsiCukxq6VqPgei.7./bQOI/Gt1GrFUdABwTVoFMW9mIG','2025-10-18 07:32:11','editor'),(51,'Test User','testuser@example.com','$2y$10$7NTbwzlapKi/aL4OuhrLF.6FaGcQ1.A1SWgI8MDGwEXxzgwTuBqsa','2025-10-22 09:31:51','editor'),(52,'jaisai CH','jaisaichin@gmail.com','$2y$10$N1klAaMVaJiOBtrnmOeUwu4V4qNm4oPQJa1utZLT4gldhO.JgMbhe','2025-10-22 10:35:10','admin');
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

-- Dump completed on 2025-10-24 18:22:00
