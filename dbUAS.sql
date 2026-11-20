-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: 127.0.0.1    Database: mbdminiproject
-- ------------------------------------------------------
-- Server version	8.0.32

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
-- Table structure for table `admin`
--

DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin` (
  `id_admin` varchar(255) NOT NULL,
  `nama_admin` varchar(60) NOT NULL,
  `username` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id_admin`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin`
--

LOCK TABLES `admin` WRITE;
/*!40000 ALTER TABLE `admin` DISABLE KEYS */;
INSERT INTO `admin` VALUES ('ADM001','Oktavia Namu Erdita','namyu','oktavia123');
/*!40000 ALTER TABLE `admin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `denda`
--

DROP TABLE IF EXISTS `denda`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `denda` (
  `id_denda` varchar(10) NOT NULL,
  `id_pengembalian` varchar(10) NOT NULL,
  `tanggal_denda` date NOT NULL,
  `jenis_denda` varchar(20) DEFAULT NULL,
  `keterangan` text,
  `jumlah_denda` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_denda`),
  KEY `id_pengembalian` (`id_pengembalian`),
  CONSTRAINT `denda_ibfk_1` FOREIGN KEY (`id_pengembalian`) REFERENCES `pengembalian` (`id_pengembalian`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `denda`
--

LOCK TABLES `denda` WRITE;
/*!40000 ALTER TABLE `denda` DISABLE KEYS */;
/*!40000 ALTER TABLE `denda` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mobil`
--

DROP TABLE IF EXISTS `mobil`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mobil` (
  `id_mobil` varchar(10) NOT NULL,
  `plat_kendaraan` varchar(10) DEFAULT NULL,
  `merk_mobil` varchar(30) NOT NULL,
  `seat_row` varchar(10) NOT NULL,
  `status_mobil` varchar(20) NOT NULL,
  `tarif_sewa` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id_mobil`),
  UNIQUE KEY `plat_kendaraan` (`plat_kendaraan`),
  KEY `idx_merk_mobil` (`merk_mobil`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mobil`
--

LOCK TABLES `mobil` WRITE;
/*!40000 ALTER TABLE `mobil` DISABLE KEYS */;
/*!40000 ALTER TABLE `mobil` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pembayaran`
--

DROP TABLE IF EXISTS `pembayaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pembayaran` (
  `id_pembayaran` varchar(10) NOT NULL,
  `id_transaksi` varchar(10) NOT NULL,
  `tanggal_pembayaran` date NOT NULL,
  `jenis_pembayaran` enum('PELUNASAN','DENDA') DEFAULT NULL,
  `jumlah_pembayaran` decimal(10,2) DEFAULT NULL,
  `metode_pembayaran` enum('CASH','TRANSFER','E-WALLET') DEFAULT NULL,
  `status_pembayaran` enum('LUNAS') DEFAULT NULL,
  PRIMARY KEY (`id_pembayaran`),
  KEY `idx_status_pembayaran` (`status_pembayaran`),
  KEY `idx_transaksi_tanggal` (`id_transaksi`,`tanggal_pembayaran`),
  CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksisewa` (`id_transaksi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pembayaran`
--

LOCK TABLES `pembayaran` WRITE;
/*!40000 ALTER TABLE `pembayaran` DISABLE KEYS */;
/*!40000 ALTER TABLE `pembayaran` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_pembayaran` BEFORE INSERT ON `pembayaran` FOR EACH ROW BEGIN

    DECLARE total_sewa DECIMAL(10,2);

    SELECT total_harga
    INTO total_sewa
    FROM transaksisewa
    WHERE id_transaksi = NEW.id_transaksi;

    IF NEW.jumlah_pembayaran < total_sewa
    AND NEW.jenis_pembayaran = 'PELUNASAN'
    THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
        'Pembayaran kurang dari total harga sewa';

    ELSE

        SET NEW.status_pembayaran = 'LUNAS';

    END IF;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `pengembalian`
--

DROP TABLE IF EXISTS `pengembalian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pengembalian` (
  `id_pengembalian` varchar(10) NOT NULL,
  `id_transaksi` varchar(10) NOT NULL,
  `tanggal_kembali` date NOT NULL,
  `keterlambatan` int DEFAULT '0',
  `status_pengembalian` enum('DIKEMBALIKAN','TERLAMBAT') DEFAULT NULL,
  PRIMARY KEY (`id_pengembalian`),
  UNIQUE KEY `id_transaksi_2` (`id_transaksi`),
  KEY `id_transaksi` (`id_transaksi`),
  CONSTRAINT `pengembalian_ibfk_1` FOREIGN KEY (`id_transaksi`) REFERENCES `transaksisewa` (`id_transaksi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengembalian`
--

LOCK TABLES `pengembalian` WRITE;
/*!40000 ALTER TABLE `pengembalian` DISABLE KEYS */;
/*!40000 ALTER TABLE `pengembalian` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_pengembalian` BEFORE INSERT ON `pengembalian` FOR EACH ROW begin
declare tgl_selesai date;
SELECT tanggal_selesai
    INTO tgl_selesai
    FROM transaksisewa
    WHERE id_transaksi = NEW.id_transaksi;

    IF NEW.tanggal_kembali > tgl_selesai THEN
        SET NEW.keterlambatan =
            DATEDIFF(NEW.tanggal_kembali, tgl_selesai);

        SET NEW.status_pengembalian = 'TERLAMBAT';

    ELSE
        SET NEW.keterlambatan = 0;

        SET NEW.status_pengembalian = 'DIKEMBALIKAN';
    END IF;
end */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_denda` AFTER INSERT ON `pengembalian` FOR EACH ROW BEGIN

    DECLARE nomor_baru INT;
    DECLARE id_baru VARCHAR(10);

    IF NEW.status_pengembalian = 'TERLAMBAT' THEN 

        SELECT COUNT(*) + 1
        INTO nomor_baru
        FROM denda;

        SET id_baru =
            CONCAT('DND', LPAD(nomor_baru, 3, '0'));

        INSERT INTO denda
        (
            id_denda,
            id_pengembalian,
            tanggal_denda,
            jenis_denda,
            keterangan,
            jumlah_denda
        )
        VALUES
        (
            id_baru,
            NEW.id_pengembalian,
            NEW.tanggal_kembali,
            'Keterlambatan',
            CONCAT('Telat ', NEW.keterlambatan, ' hari'),
            hitung_denda(NEW.keterlambatan)
        );

    END IF;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_mobil_kembali` AFTER INSERT ON `pengembalian` FOR EACH ROW BEGIN

    UPDATE mobil m
    JOIN reservasi r
        ON m.id_mobil = r.id_mobil
    JOIN transaksisewa ts
        ON r.id_reservasi = ts.id_reservasi

    SET m.status_mobil = 'Tersedia'

    WHERE ts.id_transaksi = NEW.id_transaksi;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `penyewa`
--

DROP TABLE IF EXISTS `penyewa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `penyewa` (
  `id_penyewa` varchar(10) NOT NULL,
  `nama_penyewa` varchar(60) NOT NULL,
  `no_hp` varchar(15) NOT NULL,
  `no_KTP` varchar(16) NOT NULL,
  `no_simA` varchar(16) NOT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat` text,
  PRIMARY KEY (`id_penyewa`),
  UNIQUE KEY `no_KTP` (`no_KTP`),
  UNIQUE KEY `no_simA` (`no_simA`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `penyewa`
--

LOCK TABLES `penyewa` WRITE;
/*!40000 ALTER TABLE `penyewa` DISABLE KEYS */;
/*!40000 ALTER TABLE `penyewa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reservasi`
--

DROP TABLE IF EXISTS `reservasi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservasi` (
  `id_reservasi` varchar(10) NOT NULL,
  `id_admin` varchar(255) DEFAULT NULL,
  `id_penyewa` varchar(10) DEFAULT NULL,
  `id_mobil` varchar(10) DEFAULT NULL,
  `tanggal_reservasi` date NOT NULL,
  `status_reservasi` enum('disetujui') DEFAULT NULL,
  PRIMARY KEY (`id_reservasi`),
  KEY `id_admin` (`id_admin`),
  KEY `id_mobil` (`id_mobil`),
  KEY `idx_status_reservasi` (`status_reservasi`),
  KEY `idx_penyewa_reservasi` (`id_penyewa`,`tanggal_reservasi`),
  CONSTRAINT `reservasi_ibfk_1` FOREIGN KEY (`id_admin`) REFERENCES `admin` (`id_admin`),
  CONSTRAINT `reservasi_ibfk_2` FOREIGN KEY (`id_penyewa`) REFERENCES `penyewa` (`id_penyewa`),
  CONSTRAINT `reservasi_ibfk_3` FOREIGN KEY (`id_mobil`) REFERENCES `mobil` (`id_mobil`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservasi`
--

LOCK TABLES `reservasi` WRITE;
/*!40000 ALTER TABLE `reservasi` DISABLE KEYS */;
/*!40000 ALTER TABLE `reservasi` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_cek_mobil` BEFORE INSERT ON `reservasi` FOR EACH ROW BEGIN

    DECLARE statusMobil VARCHAR(20);

    SELECT status_mobil
    INTO statusMobil
    FROM mobil
    WHERE id_mobil = NEW.id_mobil;

    IF statusMobil = 'Disewa' THEN

        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT =
        'Mobil sedang disewa dan tidak tersedia';

    END IF;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_cek_penyewa` BEFORE INSERT ON `reservasi` FOR EACH ROW BEGIN
  DECLARE aktif INT;
  SELECT COUNT(*) INTO aktif
  FROM reservasi r
  JOIN transaksisewa ts ON ts.id_reservasi = r.id_reservasi
  LEFT JOIN pengembalian pg ON pg.id_transaksi = ts.id_transaksi
  WHERE r.id_penyewa = NEW.id_penyewa
    AND pg.id_pengembalian IS NULL;
  IF aktif > 0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Penyewa masih memiliki transaksi aktif';
  END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
-- Trigger trg_status_mobil sengaja tidak dibuat.
-- Status mobil berubah menjadi 'Disewa' saat transaksi sewa dibuat, bukan saat reservasi.

/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `transaksisewa`
--

DROP TABLE IF EXISTS `transaksisewa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transaksisewa` (
  `id_transaksi` varchar(10) NOT NULL,
  `id_reservasi` varchar(10) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `total_harga` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id_transaksi`),
  UNIQUE KEY `id_reservasi_2` (`id_reservasi`),
  KEY `id_reservasi` (`id_reservasi`),
  CONSTRAINT `transaksisewa_ibfk_1` FOREIGN KEY (`id_reservasi`) REFERENCES `reservasi` (`id_reservasi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaksisewa`
--

LOCK TABLES `transaksisewa` WRITE;
/*!40000 ALTER TABLE `transaksisewa` DISABLE KEYS */;
/*!40000 ALTER TABLE `transaksisewa` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `trg_total_harga` BEFORE INSERT ON `transaksisewa` FOR EACH ROW BEGIN

    DECLARE harga_perhari DECIMAL(10,2);
    DECLARE lama_sewa INT;

    SELECT m.tarif_sewa
    INTO harga_perhari
    FROM mobil m
    JOIN reservasi r
        ON m.id_mobil = r.id_mobil
    WHERE r.id_reservasi = NEW.id_reservasi;

    SET lama_sewa =
        DATEDIFF
        (
            NEW.tanggal_selesai,
            NEW.tanggal_mulai
        ) + 1;

    SET NEW.total_harga =
        harga_perhari * lama_sewa;

END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Temporary view structure for view `vw_laporan_transaksi`
--

DROP TABLE IF EXISTS `vw_laporan_transaksi`;
/*!50001 DROP VIEW IF EXISTS `vw_laporan_transaksi`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vw_laporan_transaksi` AS SELECT 
 1 AS `nama_penyewa`,
 1 AS `id_reservasi`,
 1 AS `merk_mobil`,
 1 AS `tanggal_mulai`,
 1 AS `tanggal_selesai`,
 1 AS `total_harga`,
 1 AS `jumlah_pembayaran`,
 1 AS `metode_pembayaran`,
 1 AS `status_pembayaran`*/;
SET character_set_client = @saved_cs_client;

--
-- Dumping events for database 'mbdminiproject'
--

--
-- Dumping routines for database 'mbdminiproject'
--
/*!50003 DROP FUNCTION IF EXISTS `hitung_denda` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `hitung_denda`(
    hari_telat INT
) RETURNS decimal(10,2)
    DETERMINISTIC
BEGIN

    DECLARE total_denda DECIMAL(10,2);

    IF hari_telat > 0 THEN
        SET total_denda = hari_telat * 300000;
    ELSE
        SET total_denda = 0;
    END IF;

    RETURN total_denda;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `generate_dummy_mobil` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `generate_dummy_mobil`()
BEGIN

    DECLARE i INT DEFAULT 11;

    WHILE i <= 110 DO

        INSERT INTO mobil
        VALUES
        (
            CONCAT('MBL', LPAD(i,3,'0')),

            CONCAT(
                'KB',
                LPAD(i,4,'0'),

                ELT(FLOOR(1 + RAND()*10),
                'AA',
                'BB',
                'CC',
                'DD',
                'EE',
                'FF',
                'GG',
                'HH',
                'II',
                'JJ')
            ),

            ELT(FLOOR(1 + RAND()*10),
            'Toyota Alphard',
            'Honda Civic',
            'Mitsubishi Pajero',
            'Hyundai Creta',
            'Toyota Fortuner',
            'Honda Brio',
            'Mazda CX-5',
            'BMW Series 3',
            'Wuling Alvez',
            'Toyota Camry'),

            ELT(FLOOR(1 + RAND()*2),
            '2 Baris',
            '3 Baris'),

            ELT(FLOOR(1 + RAND()*2),
            'Tersedia',
            'Disewa'),

            ELT(FLOOR(1 + RAND()*10),
            1500000,
            900000,
            1300000,
            800000,
            1400000,
            500000,
            1100000,
            1700000,
            700000,
            1200000)

        );

        SET i = i + 1;

    END WHILE;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `generate_dummy_penyewa` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `generate_dummy_penyewa`()
BEGIN

    DECLARE i INT DEFAULT 11;

    WHILE i <= 110 DO

        INSERT INTO penyewa
        VALUES
        (
            CONCAT('PYW', LPAD(i,3,'0')),

            CONCAT(

                ELT(FLOOR(1 + RAND()*10),
                'Rafael',
                'Arya',
                'Naufal',
                'Celine',
                'Keisha',
                'Farel',
                'Adrian',
                'Zahra',
                'Farhan',
                'Azzam'),

                ' ',

                ELT(FLOOR(1 + RAND()*10),
                'Pratama',
                'Mahendra',
                'Ramadhan',
                'Azzura',
                'Saputra',
                'Nugraha',
                'Wijaya',
                'Putri',
                'Permata',
                'Reynaldi')

            ),

            CONCAT('08123456', LPAD(i,4,'0')),

            CONCAT('3174010101', LPAD(i,6,'0')),

            CONCAT('SIM', LPAD(i,6,'0')),

            DATE_ADD(
                '1998-01-01',
                INTERVAL FLOOR(RAND()*2000) DAY
            ),

            ELT(FLOOR(1 + RAND()*10),
            'Pontianak',
            'Singkawang',
            'Kubu Raya',
            'Mempawah',
            'Sambas',
            'Ketapang',
            'Sintang',
            'Bengkayang',
            'Sekadau',
            'Landak')

        );

        SET i = i + 1;

    END WHILE;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `tambah_pembayaran` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = cp850 */ ;
/*!50003 SET character_set_results = cp850 */ ;
/*!50003 SET collation_connection  = cp850_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `tambah_pembayaran`(
    IN p_id_pembayaran VARCHAR(10),
    IN p_id_transaksi VARCHAR(10),
    IN p_tanggal DATE,
    IN p_jenis ENUM('DP','PELUNASAN','DENDA'),
    IN p_jumlah DECIMAL(10,2),
    IN p_metode ENUM('CASH','TRANSFER','E-WALLET')
)
BEGIN

    DECLARE total_sewa DECIMAL(10,2);
    DECLARE status_bayar VARCHAR(20);

    SELECT total_harga
    INTO total_sewa
    FROM transaksisewa
    WHERE id_transaksi = p_id_transaksi;

    IF p_jumlah >= total_sewa THEN
        SET status_bayar = 'LUNAS';

    ELSE
        SET status_bayar = 'BELUM LUNAS';

    END IF;

    INSERT INTO pembayaran
    (
        id_pembayaran,
        id_transaksi,
        tanggal_pembayaran,
        jenis_pembayaran,
        jumlah_pembayaran,
        metode_pembayaran,
        status_pembayaran
    )

    VALUES
    (
        p_id_pembayaran,
        p_id_transaksi,
        p_tanggal,
        p_jenis,
        p_jumlah,
        p_metode,
        status_bayar
    );

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Final view structure for view `vw_laporan_transaksi`
--

/*!50001 DROP VIEW IF EXISTS `vw_laporan_transaksi`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = cp850 */;
/*!50001 SET character_set_results     = cp850 */;
/*!50001 SET collation_connection      = cp850_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`adminrental`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `vw_laporan_transaksi` AS select `py`.`nama_penyewa` AS `nama_penyewa`,`r`.`id_reservasi` AS `id_reservasi`,`m`.`merk_mobil` AS `merk_mobil`,`ts`.`tanggal_mulai` AS `tanggal_mulai`,`ts`.`tanggal_selesai` AS `tanggal_selesai`,`ts`.`total_harga` AS `total_harga`,`p`.`jumlah_pembayaran` AS `jumlah_pembayaran`,`p`.`metode_pembayaran` AS `metode_pembayaran`,`p`.`status_pembayaran` AS `status_pembayaran` from ((((`penyewa` `py` join `reservasi` `r` on((`py`.`id_penyewa` = `r`.`id_penyewa`))) join `mobil` `m` on((`r`.`id_mobil` = `m`.`id_mobil`))) join `transaksisewa` `ts` on((`r`.`id_reservasi` = `ts`.`id_reservasi`))) join `pembayaran` `p` on((`ts`.`id_transaksi` = `p`.`id_transaksi`))) */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-18 20:43:58
