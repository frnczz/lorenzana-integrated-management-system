-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: lorinims_db
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
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `unit` varchar(20) DEFAULT 'pcs',
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fermentation_eligible` tinyint(1) NOT NULL DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shelf_life_days` int(11) NOT NULL DEFAULT 365 COMMENT 'Number of days product remains shelf-stable after production. Used for automatic expiry date calculation.',
  PRIMARY KEY (`product_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'Lorins Patis Flavor 150 mL pouch','Lorins Patis Flavor 150 mL pouch','patis-POUCH-150ML.webp','pcs',1,'2026-02-01 05:25:06',1,11.27,730),(2,'Lorins Patis Flavor 350 mL PET bottle','Lorins Patis Flavor 350 mL PET bottle','PATIS-350ML-PETbottle.webp','pcs',1,'2026-02-01 05:25:06',1,22.67,730),(3,'Lorins Patis Flavor with Chili 350 mL PET bottle','Lorins Patis Flavor with Chili 350 mL PET bottle','CHILI-PATIS-350ML-PETbottle.webp','pcs',8,'2026-02-01 05:25:06',1,28.31,730),(4,'Lorins Patis Flavor 1 L','Lorins Patis Flavor 1 Liter','PATIS-1LITER.webp','pcs',1,'2026-02-01 05:25:06',1,68.91,730),(5,'Lorins Patis Flavor 1893 mL (Half Gallon)','Lorins Patis Flavor 1893 mL (Half Gallon)','PATIS-HALF-GALLON.webp','pcs',1,'2026-02-01 05:25:06',1,126.60,730),(6,'Lorins Patis Flavor 3785 mL (Gallon)','Lorins Patis Flavor 3785 mL (Gallon)','PATIS-1GALLON.webp','pcs',1,'2026-02-01 05:25:06',1,221.60,730),(7,'Lorins Soy Sauce 350 mL PET bottle','Lorins Soy Sauce 350 mL PET bottle','SOY-SAUCE-350ML.webp','pcs',2,'2026-02-01 05:25:06',1,19.89,1095),(8,'Lorins Soy Sauce 1 L','Lorins Soy Sauce 1 Liter','SOY-SAUCE-1LITER.webp','pcs',2,'2026-02-01 05:25:06',1,51.63,1095),(9,'Lorins Soy Sauce 3785 mL (Gallon)','Lorins Soy Sauce 3785 mL (Gallon)','SOY-SAUCE-1GALLON.webp','pcs',2,'2026-02-01 05:25:06',1,176.76,1095),(10,'Lorins Coco Suka 150 mL','Lorins Coco Suka 150 mL','COCO-SUKA-150ML.webp','pcs',3,'2026-02-01 05:25:06',1,44.89,365),(11,'Lorins Coco Suka 310 mL','Lorins Coco Suka 310 mL','COCO-SUKA-310ML.webp','pcs',3,'2026-02-01 05:25:06',1,68.25,365),(12,'Lorins Coco Suka 800 mL','Lorins Coco Suka 800 mL','COCO-SUKA-800ML.webp','pcs',3,'2026-02-01 05:25:06',1,156.80,365),(13,'Lorins Budget / Value Pack','Lorins Budget / Value Pack (Vinegar + Fish Sauce + Soy Sauce)','BUDGET-PACK(vinegar, fishsauce-and-soysauce).webp','pcs',3,'2026-02-01 05:25:06',1,56.20,730),(14,'Lorins Alamang Guisado Original 8 oz / 250 g','Lorins Alamang Guisado Original 8 oz / 250 g','ALAMANG-GUISADO-ORIGINAL-8oz.webp','pcs',4,'2026-02-01 05:25:06',1,95.08,365),(15,'Lorins Alamang Guisado Sweet','Lorins Alamang Guisado Sweet','ALAMANG-GUISADO-SWEET-8oz.webp','pcs',4,'2026-02-01 05:25:06',1,95.08,365),(16,'Lorins Alamang Guisado Spicy','Lorins Alamang Guisado Spicy','ALAMANG-GUISADO-SPICY-8oz.webp','pcs',4,'2026-02-01 05:25:06',1,95.08,365),(17,'Lorenzana Bagoong Isda Original 310 mL','Lorenzana Bagoong Isda Original 310 mL','BAGOONG-ISDA-original-310ML.webp','pcs',5,'2026-02-01 05:25:06',1,38.79,730),(18,'Lorins Crab Paste 8 oz','Lorins Crab Paste 8 oz','CRAB-PASTE-8oz.webp','pcs',6,'2026-02-01 05:25:06',1,169.22,730),(19,'Lorins Coconut Milk 400 mL','Lorins Coconut Milk 400 mL tin','COCONUT-MILK.webp','pcs',6,'2026-02-01 05:25:06',1,67.81,365),(20,'Lorins Premium Extra-Virgin Anchovy Extract 200 mL','Lorins Premium Extra-Virgin Anchovy Extract 200 mL','PREMIUM-extra-virgin-anchovy-ectract-200ML.webp','pcs',7,'2026-02-01 05:25:06',1,57.49,365),(21,'Lorins Fish Sauce 800 mL glass bottle','Lorins Fish Sauce 800 mL glass bottle','patis-800ML.webp','pcs',8,'2026-02-01 05:25:06',1,92.42,730),(23,'Lorins Coco Suka Spicy-Sweet 310 mL','Lorins Coco Suka Spicy-Sweet 310 mL','COCO-SUKA-310ML.webp','pcs',8,'2026-02-01 05:25:06',1,68.25,365),(24,'Filtaste Nata de Coco 12 oz','Filtaste Nata de Coco 12 oz','NATA-DE-COCO-12OZ.webp','pcs',9,'2026-02-01 06:38:24',1,60.00,365),(26,'Filtaste Kaong 12 oz','Filtaste Kaong 12 oz','KAONG-12OZ.webp','pcs',9,'2026-02-01 06:38:24',1,85.00,365),(28,'Lorins Patis Puro 150 mL','Lorins Patis Puro 150 mL','patis-PURO-150ML.webp','pcs',1,'2026-02-01 06:38:24',1,33.60,730),(29,'Lorins Patis Puro 310 mL','Lorins Patis Puro 310 mL','patis-PURO-310ML.webp','pcs',1,'2026-02-01 06:38:24',1,48.83,730),(30,'Lorins Patis Puro Chili Mansi 150 mL','Lorins Patis Puro Chili Mansi 150 mL','patis-puro-CHILIMANSI-150ML.webp','pcs',8,'2026-02-01 06:38:24',1,37.70,730),(31,'Lorins Patis Puro Chili Mansi 310 mL','Lorins Patis Puro Chili Mansi 310 mL','patis-puro-CHILIMANSI-310ML.webp','pcs',8,'2026-02-01 06:38:24',1,57.09,730),(32,'Lorins Patis Puro Mansi 150 mL','Lorins Patis Puro Mansi 150 mL','patis-PURO-MANSI-150ML.webp','pcs',8,'2026-02-01 06:38:24',1,36.33,730),(33,'Lorins Patis Flavor 7+1 Tipid Pouch','Lorins Patis Flavor 7+1 Tipid Pouch','Lorins-patis-7+1(patis-flavor-tipidpouch).webp','pcs',1,'2026-02-01 06:38:24',1,78.79,730),(34,'Lorins Patis Twin Pack 1L x 2','Lorins Patis Twin Pack 1 Liter x 2','patis-TWINPACK(1litterx2).webp','pcs',1,'2026-02-01 06:38:24',1,131.01,730),(35,'Lorins Patis Pouch 350 mL','Lorins Patis Pouch 350 mL','patis-POUCH-350ML.webp','pcs',1,'2026-02-01 06:38:24',1,20.66,730),(36,'Lorins Vinegar 350 mL','Lorins Vinegar 350 mL','VINEGAR-350ML.webp','pcs',3,'2026-02-01 06:38:24',1,17.24,1095),(37,'Lorins Vinegar 1 L','Lorins Vinegar 1 Liter','VINEGAR-1LITER.webp','pcs',3,'2026-02-01 06:38:24',1,40.94,1095),(38,'Lorins Vinegar 3785 mL (Gallon)','Lorins Vinegar Gallon','VINEGAR-1GALLON.webp','pcs',3,'2026-02-01 06:38:24',1,151.03,1095),(39,'Lorins Value Pack (Soy Sauce + Vinegar + Free Patis Pouch)','Lorins Value Pack with free patis pouch','BUDGET-PACK(vinegar, fishsauce-and-soysauce).webp','pcs',3,'2026-02-01 06:38:24',1,91.68,730),(40,'Lorins Patis Puro 800 mL','Lorins Patis Puro 800 mL','Lorins_Patis_Puro_800_mL_1772103212.webp','pcs',1,'2026-02-26 09:42:57',1,92.42,730),(41,'Filtaste Nata de Coco 32 oz','Filtaste Nata de Coco 32 oz','NATA-DE-COCO-32OZ.webp','pcs',9,'2026-02-26 09:42:57',1,124.53,365),(42,'Filtaste Kaong 32 oz','Filtaste Kaong 32 oz','KAONG-32OZ.webp','pcs',9,'2026-02-26 09:42:57',1,163.77,365);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `raw_materials`
--

DROP TABLE IF EXISTS `raw_materials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `raw_materials` (
  `material_id` int(11) NOT NULL AUTO_INCREMENT,
  `material_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(20) DEFAULT 'kg',
  `expiry_date` date DEFAULT NULL,
  `warehouse_location` varchar(100) DEFAULT NULL,
  `min_stock_level` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `material_code` varchar(50) DEFAULT NULL,
  `preferred_supplier_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`material_id`),
  UNIQUE KEY `material_code` (`material_code`),
  KEY `preferred_supplier_id` (`preferred_supplier_id`),
  CONSTRAINT `raw_materials_ibfk_1` FOREIGN KEY (`preferred_supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL,
  CONSTRAINT `raw_materials_ibfk_2` FOREIGN KEY (`preferred_supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL,
  CONSTRAINT `raw_materials_ibfk_3` FOREIGN KEY (`preferred_supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `raw_materials`
--

LOCK TABLES `raw_materials` WRITE;
/*!40000 ALTER TABLE `raw_materials` DISABLE KEYS */;
INSERT INTO `raw_materials` VALUES (1,'Soybeans','Raw Material',100.00,'kg','2026-04-02','',100.00,'2026-02-01 05:25:06','2026-03-02 07:35:57',NULL,NULL),(2,'Salt','Label Materials',187.00,'kg',NULL,'',500.00,'2026-02-01 05:25:06','2026-03-08 07:02:14',NULL,NULL),(3,'Sugar','Raw Material',100.00,'kg',NULL,NULL,50.00,'2026-02-01 05:25:06','2026-03-02 07:35:57',NULL,NULL),(6,'Tape','Packaging',99.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',100.00,'2026-02-26 10:01:55','2026-03-07 02:39:27','MAT-20260226-0001',NULL),(9,'Fermented fish','Seafood & Protein Sources',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',10.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(10,'Fish extract','Seafood & Protein Sources',100.00,'liters',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',5.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(11,'Whole fish (Mackerel)','Seafood & Protein Sources',100.00,'kg','2026-03-19','Lot 6720 Brgy San Joaquin Sto Tomas Batangas',8.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(12,'Shrimp paste (alam??ng)','Seafood & Protein Sources',99.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',5.00,'2026-03-02 05:10:13','2026-03-04 13:05:24',NULL,NULL),(13,'Dried shrimp','Seafood & Protein Sources',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',5.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(14,'Iodized salt','Minerals & Salts',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',20.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(15,'Salt (sea salt)','Minerals & Salts',88.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',20.00,'2026-03-02 05:10:13','2026-03-04 11:10:35',NULL,NULL),(16,'Soya beans','Plant-Derived Ingredients',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',15.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(17,'Red hot chili / chili slices','Plant-Derived Ingredients',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',5.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(18,'Calamansi flavor / blend','Plant-Derived Ingredients',100.00,'liters',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',3.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(19,'Caramel (coloring/flavor)','Plant-Derived Ingredients',100.00,'liters',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',5.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(20,'Water','Plant-Derived Ingredients',100.00,'liters',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',50.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(21,'Garlic','Plant-Derived Ingredients',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',10.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(22,'Onion','Plant-Derived Ingredients',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',10.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(23,'Potassium sorbate (preservative)','Additives & Preservatives',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',2.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(24,'Sodium benzoate (preservative)','Additives & Preservatives',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',2.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(25,'Monosodium glutamate (MSG)','Additives & Preservatives',100.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',3.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(26,'Disodium inosinate (flavor enhancer)','Additives & Preservatives',99.00,'kg',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',1.00,'2026-03-02 05:10:13','2026-03-04 12:47:41',NULL,NULL),(27,'Glass bottles','Packaging Materials',97.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',100.00,'2026-03-02 05:10:13','2026-03-08 06:56:33',NULL,NULL),(28,'PET plastic bottles','Packaging Materials',100.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',150.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(29,'Plastic sachets (PET/AL/PE laminate)','Packaging Materials',100.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',500.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(30,'Bottle caps / Lids','Packaging Materials',96.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',200.00,'2026-03-02 05:10:13','2026-03-08 06:58:58',NULL,NULL),(31,'Coated paper label stock','Label Materials',100.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',100.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(32,'Uncoated paper label','Label Materials',99.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',100.00,'2026-03-02 05:10:13','2026-03-02 07:36:34',NULL,NULL),(33,'BOPP film labels','Label Materials',100.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',150.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(34,'Vinyl film labels','Label Materials',100.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',100.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(35,'Polyester (PET) film labels','Label Materials',100.00,'pcs',NULL,'Lot 6720 Brgy San Joaquin Sto Tomas Batangas',100.00,'2026-03-02 05:10:13','2026-03-02 07:35:57',NULL,NULL),(36,'0','Procurement',123.00,'0',NULL,'',0.00,'2026-03-04 12:00:21','2026-03-04 12:00:21','MAT-20260304-0001',NULL);
/*!40000 ALTER TABLE `raw_materials` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-09 18:44:13
