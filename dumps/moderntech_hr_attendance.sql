-- MySQL dump 10.13  Distrib 8.0.42, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: moderntech_hr
-- ------------------------------------------------------
-- Server version	8.0.42

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent') NOT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
INSERT INTO `attendance` VALUES (1,1,'2025-07-25','Present'),(2,1,'2025-07-26','Absent'),(3,1,'2025-07-27','Present'),(4,1,'2025-07-28','Present'),(5,1,'2025-07-29','Present'),(6,2,'2025-07-25','Present'),(7,2,'2025-07-26','Present'),(8,2,'2025-07-27','Absent'),(9,2,'2025-07-28','Present'),(10,2,'2025-07-29','Present'),(11,3,'2025-07-25','Present'),(12,3,'2025-07-26','Present'),(13,3,'2025-07-27','Present'),(14,3,'2025-07-28','Absent'),(15,3,'2025-07-29','Present'),(16,4,'2025-07-25','Absent'),(17,4,'2025-07-26','Present'),(18,4,'2025-07-27','Present'),(19,4,'2025-07-28','Present'),(20,4,'2025-07-29','Present'),(21,5,'2025-07-25','Present'),(22,5,'2025-07-26','Present'),(23,5,'2025-07-27','Absent'),(24,5,'2025-07-28','Present'),(25,5,'2025-07-29','Present'),(26,6,'2025-07-25','Present'),(27,6,'2025-07-26','Present'),(28,6,'2025-07-27','Absent'),(29,6,'2025-07-28','Present'),(30,6,'2025-07-29','Present'),(31,7,'2025-07-25','Present'),(32,7,'2025-07-26','Present'),(33,7,'2025-07-27','Present'),(34,7,'2025-07-28','Absent'),(35,7,'2025-07-29','Present'),(36,8,'2025-07-25','Present'),(37,8,'2025-07-26','Absent'),(38,8,'2025-07-27','Present'),(39,8,'2025-07-28','Present'),(40,8,'2025-07-29','Present'),(41,9,'2025-07-25','Present'),(42,9,'2025-07-26','Present'),(43,9,'2025-07-27','Present'),(44,9,'2025-07-28','Absent'),(45,9,'2025-07-29','Present'),(46,10,'2025-07-25','Present'),(47,10,'2025-07-26','Present'),(48,10,'2025-07-27','Absent'),(49,10,'2025-07-28','Present'),(50,10,'2025-07-29','Present');
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-07-27 21:23:32
