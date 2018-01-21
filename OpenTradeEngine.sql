-- MySQL dump 10.16  Distrib 10.1.26-MariaDB, for osx10.6 (i386)
--
-- Host: 127.0.0.1    Database: opentradeengine
-- ------------------------------------------------------
-- Server version	10.1.26-MariaDB

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
-- Table structure for table `Currencies`
--

DROP TABLE IF EXISTS `Currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Currencies` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Symbol` char(10) NOT NULL,
  `Name` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Currencies_ID_uindex` (`ID`),
  UNIQUE KEY `Currencies_Symbol_uindex` (`Symbol`),
  UNIQUE KEY `Currencies_Name_uindex` (`Name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Currencies`
--

LOCK TABLES `Currencies` WRITE;
/*!40000 ALTER TABLE `Currencies` DISABLE KEYS */;
INSERT INTO `Currencies` VALUES (1,'USD','US Dollar'),(2,'BTC','Bitcoin');
/*!40000 ALTER TABLE `Currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EXAMPLEBuys`
--

DROP TABLE IF EXISTS `EXAMPLEBuys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `EXAMPLEBuys` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `TS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Price` decimal(16,8) unsigned NOT NULL,
  `Quantity` decimal(16,8) unsigned NOT NULL,
  `Type` varchar(10) NOT NULL,
  `Owner` int(11) NOT NULL,
  `Symbol` int(11) NOT NULL,
  `FeePercent` decimal(6,3) NOT NULL DEFAULT '0.000',
  PRIMARY KEY (`ID`),
  KEY `EXAMPLEBuys_Traders_ID_fk` (`Owner`),
  KEY `EXAMPLEBuys_Symbols_ID_fk` (`Symbol`),
  CONSTRAINT `EXAMPLEBuys_Symbols_ID_fk` FOREIGN KEY (`Symbol`) REFERENCES `Symbols` (`ID`),
  CONSTRAINT `EXAMPLEBuys_Traders_ID_fk` FOREIGN KEY (`owner`) REFERENCES `Traders` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EXAMPLEBuys`
--

LOCK TABLES `EXAMPLEBuys` WRITE;
/*!40000 ALTER TABLE `EXAMPLEBuys` DISABLE KEYS */;
/*!40000 ALTER TABLE `EXAMPLEBuys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EXAMPLESells`
--

DROP TABLE IF EXISTS `EXAMPLESells`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `EXAMPLESells` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `TS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Price` decimal(16,8) unsigned NOT NULL,
  `Quantity` decimal(16,8) unsigned NOT NULL,
  `Type` varchar(10) NOT NULL,
  `Owner` int(11) NOT NULL,
  `Symbol` int(11) NOT NULL,
  `FeePercent` decimal(6,3) NOT NULL DEFAULT '0.000',
  PRIMARY KEY (`ID`),
  KEY `EXAMPLESells_Traders_ID_fk` (`Owner`),
  KEY `EXAMPLESells_Symbols_ID_fk` (`Symbol`),
  CONSTRAINT `EXAMPLESells_Symbols_ID_fk` FOREIGN KEY (`Symbol`) REFERENCES `Symbols` (`ID`),
  CONSTRAINT `EXAMPLESells_Traders_ID_fk` FOREIGN KEY (`owner`) REFERENCES `Traders` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EXAMPLESells`
--

LOCK TABLES `EXAMPLESells` WRITE;
/*!40000 ALTER TABLE `EXAMPLESells` DISABLE KEYS */;
/*!40000 ALTER TABLE `EXAMPLESells` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EXAMPLETrades`
--

DROP TABLE IF EXISTS `EXAMPLETrades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `EXAMPLETrades` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `TS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Price` decimal(16,8) NOT NULL,
  `Quantity` decimal(16,8) NOT NULL,
  `Type` varchar(10) NOT NULL,
  `Side` varchar(4) NOT NULL,
  `Owner` int(11) NOT NULL,
  `ActingTraderID` int(11) NOT NULL,
  `Volume` decimal(16,8) NOT NULL,
  `BuyFee` decimal(16,8) NOT NULL,
  `SellFee` decimal(16,8) NOT NULL,
  `TotalRight` decimal(16,8) NOT NULL,
  `TotalLeft` decimal(16,8) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EXAMPLETrades`
--

LOCK TABLES `EXAMPLETrades` WRITE;
/*!40000 ALTER TABLE `EXAMPLETrades` DISABLE KEYS */;
/*!40000 ALTER TABLE `EXAMPLETrades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EXAMPLETransactions`
--

DROP TABLE IF EXISTS `EXAMPLETransactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `EXAMPLETransactions` (
  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `TS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Sender` varchar(300) NOT NULL,
  `Receiver` varchar(300) NOT NULL,
  `Amount` decimal(16,8) NOT NULL DEFAULT '0.00000000',
  `ReceiverID` int(11) NOT NULL,
  `SenderID` int(11) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `EXAMPLETransactions_Traders_ID_fk` (`SenderID`),
  KEY `EXAMPLETransactions_Traders2_ID_fk` (`ReceiverID`),
  CONSTRAINT `EXAMPLETransactions_Traders2_ID_fk` FOREIGN KEY (`ReceiverID`) REFERENCES `Traders` (`ID`),
  CONSTRAINT `EXAMPLETransactions_Traders_ID_fk` FOREIGN KEY (`SenderID`) REFERENCES `Traders` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EXAMPLETransactions`
--

LOCK TABLES `EXAMPLETransactions` WRITE;
/*!40000 ALTER TABLE `EXAMPLETransactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `EXAMPLETransactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `EXAMPLEWithdrawals`
--

DROP TABLE IF EXISTS `EXAMPLEWithdrawals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `EXAMPLEWithdrawals` (
  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `TS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Address` varchar(300) NOT NULL DEFAULT '',
  `Currency` int(11) NOT NULL,
  `Amount` decimal(16,8) NOT NULL DEFAULT '0.00000000',
  `Trader` int(11) NOT NULL DEFAULT '0',
  `TransactionID` varchar(300) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `EXAMPLEWithdrawals_Currencies_ID_fk` (`Currency`),
  KEY `EXAMPLEWithdrawals_Traders_ID_fk` (`Trader`),
  CONSTRAINT `EXAMPLEWithdrawals_Currencies_ID_fk` FOREIGN KEY (`Currency`) REFERENCES `Currencies` (`ID`),
  CONSTRAINT `EXAMPLEWithdrawals_Traders_ID_fk` FOREIGN KEY (`Trader`) REFERENCES `Traders` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `EXAMPLEWithdrawals`
--

LOCK TABLES `EXAMPLEWithdrawals` WRITE;
/*!40000 ALTER TABLE `EXAMPLEWithdrawals` DISABLE KEYS */;
/*!40000 ALTER TABLE `EXAMPLEWithdrawals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `FeeTotals`
--

DROP TABLE IF EXISTS `FeeTotals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `FeeTotals` (
  `Currency` int(11) NOT NULL,
  `Total` decimal(32,8) NOT NULL DEFAULT '0.00000000',
  PRIMARY KEY (`Currency`),
  UNIQUE KEY `FeeTotals_Currency_uindex` (`Currency`),
  CONSTRAINT `FeeTotals_Currencies_ID_fk` FOREIGN KEY (`Currency`) REFERENCES `Currencies` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `FeeTotals`
--

LOCK TABLES `FeeTotals` WRITE;
/*!40000 ALTER TABLE `FeeTotals` DISABLE KEYS */;
/*!40000 ALTER TABLE `FeeTotals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `OrderErrors`
--

DROP TABLE IF EXISTS `OrderErrors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `OrderErrors` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `TS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Error` varchar(255) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `OrderErrors`
--

LOCK TABLES `OrderErrors` WRITE;
/*!40000 ALTER TABLE `OrderErrors` DISABLE KEYS */;
/*!40000 ALTER TABLE `OrderErrors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Symbols`
--

DROP TABLE IF EXISTS `Symbols`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Symbols` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Symbol` char(20) NOT NULL,
  `LeftCurrency` int(11) NOT NULL,
  `RightCurrency` int(11) NOT NULL,
  `MakerFee` decimal(16,8) NOT NULL DEFAULT '0.00000000',
  `TakerFee` decimal(16,8) NOT NULL DEFAULT '0.00000000',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Symbols_ID_uindex` (`ID`),
  UNIQUE KEY `Symbols_code_uindex` (`Symbol`),
  KEY `Symbols_Currencies_ID_fk` (`LeftCurrency`),
  KEY `Symbols_Currencies2_ID_fk` (`RightCurrency`),
  CONSTRAINT `Symbols_Currencies2_ID_fk` FOREIGN KEY (`rightCurrency`) REFERENCES `Currencies` (`ID`),
  CONSTRAINT `Symbols_Currencies_ID_fk` FOREIGN KEY (`leftCurrency`) REFERENCES `Currencies` (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Symbols`
--

LOCK TABLES `Symbols` WRITE;
/*!40000 ALTER TABLE `Symbols` DISABLE KEYS */;
INSERT INTO `Symbols` VALUES (1,'EXAMPLE',1,2,0.01000000,0.00000000);
/*!40000 ALTER TABLE `Symbols` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `TraderCurrencies`
--

DROP TABLE IF EXISTS `TraderCurrencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `TraderCurrencies` (
  `Currency` int(11) NOT NULL,
  `Balance` decimal(16,8) NOT NULL DEFAULT '0.00000000',
  `Trader` int(11) NOT NULL,
  `HeldBalance` decimal(16,8) NOT NULL DEFAULT '0.00000000',
  `PendingBalance` decimal(16,8) NOT NULL DEFAULT '0.00000000',
  `Completed` decimal(16,8) NOT NULL DEFAULT '0.00000000',
  UNIQUE KEY `TraderCurrencies_Currency_Trader_pk` (`Currency`,`Trader`),
  KEY `TraderCurrencies_Traders__fk` (`Trader`),
  CONSTRAINT `TraderCurrencies_Currencies.ID__fk` FOREIGN KEY (`Currency`) REFERENCES `Currencies` (`ID`),
  CONSTRAINT `TraderCurrencies_Traders__fk` FOREIGN KEY (`Trader`) REFERENCES `Traders` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `TraderCurrencies`
--

LOCK TABLES `TraderCurrencies` WRITE;
/*!40000 ALTER TABLE `TraderCurrencies` DISABLE KEYS */;
INSERT INTO `TraderCurrencies` VALUES (1,10000.00000000,4,0.00000000,0.00000000,0.00000000),(1,10000.00000000,5,0.00000000,0.00000000,0.00000000),(2,10000.00000000,4,0.00000000,0.00000000,0.00000000),(2,10000.00000000,5,0.00000000,0.00000000,0.00000000);
/*!40000 ALTER TABLE `TraderCurrencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Traders`
--

DROP TABLE IF EXISTS `Traders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Traders` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `TS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UserName` varchar(30) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `PasswordHash` char(60) NOT NULL,
  `BirthDate` date NOT NULL DEFAULT '0000-00-00',
  `PhoneNumber` varchar(22) NOT NULL,
  `SecurityQuestion` varchar(300) NOT NULL,
  `SecurityAnswer` varchar(255) NOT NULL,
  `PIN` char(4) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `AddressLineOne` varchar(255) NOT NULL,
  `AddressLineTwo` varchar(255) NOT NULL,
  `PostCode` varchar(10) NOT NULL,
  `City` varchar(50) NOT NULL,
  `RegisterIP` varchar(45) NOT NULL,
  `Referrer` int(11) NOT NULL,
  `Activated` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `AccountNumber` varchar(255) NOT NULL,
  `Points` decimal(16,8) unsigned NOT NULL DEFAULT '0.00000000',
  `PointsEarned` decimal(16,8) unsigned NOT NULL DEFAULT '0.00000000',
  `PinCount` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `PassCount` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `RecoverCount` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `TransactionCount` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `UserName` (`UserName`),
  KEY `Traders_Traders_ID_fk` (`Referrer`),
  CONSTRAINT `Traders_Traders_ID_fk` FOREIGN KEY (`Referrer`) REFERENCES `Traders` (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Traders`
--

LOCK TABLES `Traders` WRITE;
/*!40000 ALTER TABLE `Traders` DISABLE KEYS */;
INSERT INTO `Traders` VALUES (3,'2017-10-21 17:51:54','test','test','test','test','2017-10-31','999999999','a','a','111','a','','a','a','a','a',3,0,'1111111111',0.00000000,0.00000000,0,0,0,0),(4,'2017-10-21 18:50:20','buyer','John','Smith','$2y$12$XJmVGSZz/gM.Ho3aHLlJEuSdHZO38QvyaFioKphljtnphdkch5vNa','2010-11-11','999-999-9999','Question','Answer','555','buyer@example.com','','','','','',3,0,'',0.00000000,0.00000000,0,0,0,0),(5,'2017-10-21 18:50:20','seller','Joe','Smithie','$2y$12$4.x9ZhanHsvtGr..4PZXduFX7zw1uocIVLNC8jbAtztRfVykQ8UBe','0000-00-00','999-888-9999','Question','Answer','444','seller@example.com','','','','','',3,0,'',0.00000000,0.00000000,0,0,0,0);
/*!40000 ALTER TABLE `Traders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `WithdrawErrors`
--

DROP TABLE IF EXISTS `WithdrawErrors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `WithdrawErrors` (
  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `TS` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `Amount` varchar(300) NOT NULL DEFAULT '',
  `Address` varchar(300) NOT NULL DEFAULT '',
  `Trader` int(11) NOT NULL DEFAULT '0',
  `Currency` int(11) NOT NULL DEFAULT '0',
  `Message` varchar(300) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `WithdrawErrors_Currencies_ID_fk` (`Currency`),
  KEY `WithdrawErrors_Traders_ID_fk` (`Trader`),
  CONSTRAINT `WithdrawErrors_Currencies_ID_fk` FOREIGN KEY (`Currency`) REFERENCES `Currencies` (`ID`),
  CONSTRAINT `WithdrawErrors_Traders_ID_fk` FOREIGN KEY (`Trader`) REFERENCES `Traders` (`ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `WithdrawErrors`
--

LOCK TABLES `WithdrawErrors` WRITE;
/*!40000 ALTER TABLE `WithdrawErrors` DISABLE KEYS */;
/*!40000 ALTER TABLE `WithdrawErrors` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-01-21 14:32:01
