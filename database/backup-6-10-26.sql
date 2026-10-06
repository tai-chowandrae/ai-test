-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: tb-nl01-linweb573.srv.teamblue-ops.net:3306
-- Generation Time: Oct 06, 2026 at 07:08 PM
-- Server version: 8.0.46-37
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `skilmp_km2work`
--

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `LocationId` int NOT NULL,
  `UserId` int NOT NULL DEFAULT '1',
  `Name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `GooglePlaceId` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `FormattedAddress` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `DefaultTripDescription` text COLLATE utf8mb4_unicode_ci,
  `IsActive` tinyint(1) NOT NULL DEFAULT '1',
  `Latitude` decimal(10,7) DEFAULT NULL,
  `Longitude` decimal(10,7) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`LocationId`, `Name`, `GooglePlaceId`, `FormattedAddress`, `DefaultTripDescription`, `IsActive`, `Latitude`, `Longitude`, `CreatedAt`) VALUES
(1, 'Thuis', 'ChIJ694UGwFUz0cROikvG8PcOCA', 'Wulp 5, 1722 JC Zuid-Scharwoude, Netherlands', NULL, 1, 52.6874321, 4.8218230, '2026-06-14 13:05:46'),
(2, 'Vonk Alkmaar', 'ChIJ26TVO61Xz0cRU6hbtl5V15c', 'Drechterwaard 10-16, 1824 EX Alkmaar, Netherlands', NULL, 1, 52.6409430, 4.7532177, '2026-06-14 13:08:16'),
(3, 'DMI', 'ChIJ699mad03z0cRXAiG5ZABB7k', 'Nieuwe Haven Naval Base, Den Helder, Netherlands', 'DMI', 1, 52.9583382, 4.7838619, '2026-06-14 13:27:57'),
(4, 'Tetrix Heerhugowaard', 'ChIJ8cSdDSFUz0cRwHL_snCl5sA', 'W.M. Dudokweg 66, 1703 DC Heerhugowaard, Netherlands', NULL, 1, 52.6657834, 4.8175391, '2026-06-14 13:43:04'),
(5, 'Techniekcampus', 'ChIJSevDt-FHz0cRu5k380cSiNE', 'Burg, Burgemeester Ritmeesterweg 31, 1784 NV Den Helder, Netherlands', NULL, 1, 52.9328583, 4.7511065, '2026-06-14 14:16:05'),
(6, 'Blue Port Center', 'ChIJXYIfS-E3z0cRVcmblHZhhdQ', 'Het Nieuwe Diep 27b, 1781 AD Den Helder, Netherlands', NULL, 1, 52.9559396, 4.7774692, '2026-06-14 14:19:27'),
(7, 'Vonk Sportlaan', 'ChIJ4Vbj1_03z0cRtA5ala3P4Eo', 'Sportlaan 54, 1782 ND Den Helder, Netherlands', NULL, 1, 52.9519530, 4.7534850, '2026-06-14 14:19:46'),
(8, 'Vonk Sperwerstraat', 'ChIJwYhxjCVOz0cR0m-hx03OEUo', 'Sperwerstraat 4, 1781 XC Den Helder, Netherlands', NULL, 1, 52.9523952, 4.7636102, '2026-06-14 14:20:11'),
(9, 'Vonk Hoorn', 'ChIJd0lzHm6pyEcR6ix9zgZuLMU', 'Blauwe Berg 3, 1625 NT Hoorn, Netherlands', NULL, 1, 52.6488397, 5.0436579, '2026-06-14 14:20:28'),
(10, 'Talland Alkmaar', 'ChIJ-Sb5GrBXz0cR4IpgjTTyM48', 'Kruseman van Eltenweg 4, 1817 BC Alkmaar, Netherlands', NULL, 1, 52.6396333, 4.7433224, '2026-06-14 14:20:50'),
(11, 'Talland Heerhugowaard', 'ChIJM54bOxhUz0cRii_oTgjRx6M', 'Umbriellaan 2, 1702 AJ Heerhugowaard, Netherlands', NULL, 1, 52.6703946, 4.8273040, '2026-06-14 14:21:07'),
(12, 'EHC Petten', 'ChIJyUE9kCtFz0cRr1VIbxrd5WI', 'Westerduinweg 3, 1755 LE Petten, Netherlands', 'nuclear academy', 1, 52.7867160, 4.6783762, '2026-06-14 14:21:24'),
(13, 'Afas stadion', 'ChIJpwF4z9ZXz0cRZUf1l0BHTQo', 'Stadionweg 1, 1812 AZ Alkmaar, Netherlands', NULL, 0, 52.6128108, 4.7420718, '2026-06-14 14:31:07'),
(14, 'ROC Mondriaan', 'ChIJu8EeDhK3xUcRHRwu_xfDb88', 'Kon. Marialaan 9, 2595 GA Den Haag, Netherlands', NULL, 0, 52.0851913, 4.3324621, '2026-06-14 14:34:56'),
(15, 'Scholen aan Zee', 'ChIJe8QPLP1Hz0cRoB9LOASjSAo', 'Doctorandus F. Bijlweg 10, 1784 MC Den Helder, Netherlands', NULL, 1, 52.9411724, 4.7509623, '2026-06-14 14:38:17'),
(16, 'Hoogheemraadschap Den Helder', 'ChIJV4JsvN5Jz0cR1j2AWEmJuYk', 'Oostoeverweg 70, 1786 PS Den Helder, Netherlands', NULL, 0, 52.9291472, 4.7913428, '2026-06-14 14:42:17'),
(17, 'Stichting Praktijkleren', 'ChIJ6zqhoJJGxkcRC3QzGpUrbd4', 'Disketteweg 11, 3821 AR Amersfoort, Netherlands', NULL, 1, 52.1726710, 5.4038540, '2026-06-14 14:44:46'),
(18, 'Vonk Hofstraat', 'ChIJ28yojCVOz0cRrJs6Zm7KcjI', 'Hofstraat 13, 1741 CD Schagen, Netherlands', NULL, 1, 52.7843694, 4.7946611, '2026-06-14 14:49:51'),
(19, 'Regius college', 'ChIJgY0YlCVOz0cR5chHgkC4Tdg', 'Hofstraat 11, 1741 CD Schagen, Netherlands', NULL, 1, 52.7844188, 4.7955354, '2026-06-14 14:50:13'),
(20, 'Firda VEVA', 'ChIJo1to7F7-yEcR0KZlUODWksU', 'Egelantierstraat 70, 8924 EP Leeuwarden, Netherlands', NULL, 0, 53.2096040, 5.8250424, '2026-06-14 14:52:30'),
(21, 'Talland Bed & Business', 'ChIJO3NPduhVz0cRv494x_rZPcs', 'W.M. Dudokweg 45, 1703 DA Heerhugowaard, Netherlands', NULL, 1, 52.6654565, 4.8193694, '2026-06-14 14:57:03'),
(22, 'ICT vanaf morgen', 'ChIJjddqNEZXz0cRKP9zaIssZEA', 'Helderseweg 29, 1817 BA Alkmaar, Netherlands', NULL, 0, 52.6433449, 4.7439126, '2026-06-14 14:58:56'),
(23, 'Nova college Beverwijk', 'ChIJqauNRV7wxUcRiy_aP0eeC1I', 'Laurens Baecklaan 23-25, 1942 LM Beverwijk, Netherlands', NULL, 0, 52.4807241, 4.6454321, '2026-06-14 15:04:55'),
(24, 'MBO Raad', 'ChIJi1MOkNt5xkcRQsPh77nhZxw', 'Tjaskermolenlaan 1, 3447 GE Woerden, Netherlands', NULL, 1, 52.0756170, 4.8905210, '2026-06-14 16:12:57'),
(25, 'Samenwerkingsverband VeVa SenO', 'ChIJ6TJzGbRlxkcRal8DFAMu9EI', 'Harmonielaan 2, 3438 EB Nieuwegein, Netherlands', NULL, 0, 52.0435188, 5.0952018, '2026-06-14 16:13:40'),
(26, 'Van der Valk Akersloot', 'ChIJg8XkT7z5xUcRPTxfAN_C7wg', 'Geesterweg 1A, 1921 NV Akersloot, Netherlands', 'Wijtechniek', 1, 52.5477957, 4.7215321, '2026-06-14 16:17:13'),
(27, 'inholland Alkmaar', 'ChIJkXQa6JlXz0cRevcm_cM9mno', 'Bergerweg 200, 1817 MN Alkmaar, Netherlands', NULL, 1, 52.6418546, 4.7250853, '2026-06-14 16:20:21'),
(28, 'Kampus', 'ChIJiyLGurb3x0cR4MAxJ4mzqbI', 'Morsweg 4, 7461 AK Rijssen, Netherlands', 'HTC aanvraag', 0, 52.3126014, 6.5185369, '2026-06-14 16:24:36'),
(29, 'Echt Human Capital', 'ChIJS8bSAphvxkcRYMgDw6W0yDE', '2e Daalsedijk, huisnummer 14-6, 3551 EJ Utrecht, Netherlands', NULL, 0, 52.1011442, 5.0955024, '2026-06-14 16:26:25'),
(30, 'Gemeente Den Helder', 'ChIJzcC5eOJHz0cRaH2BWuxFHzE', 'Gebouw 66, Willemsoord 66, 1781 AS Den Helder, Netherlands', NULL, 1, 52.9584761, 4.7706189, '2026-06-14 16:32:54'),
(31, 'Vonk Boomgaard', 'ChIJYZlFRxhOz0cRQmTsbqf5hTg', 'De Boomgaard 9, 1741 MD Schagen, Netherlands', NULL, 1, 52.7861304, 4.8095538, '2026-06-14 16:36:36'),
(32, 'Schiphol', 'EipWZXJ0cmVrcGFzc2FnZSwgMTExOCBTY2hpcGhvbCwgTmV0aGVybGFuZHMiLiosChQKEglFOaTOKeHFRxE8-Tu3SwhnCxIUChIJHU4RWyjhxUcR27TNSXntfQs', 'Vertrekpassage, 1118 Schiphol, Netherlands', NULL, 0, 52.3098892, 4.7626193, '2026-06-14 19:55:11'),
(33, 'Fort Beemster', 'ChIJ2V3ZdsYAxkcR31b3R1Gu_DU', 'Nekkerweg 24, 1461 LC Zuidoostbeemster, Netherlands', NULL, 0, 52.5279159, 4.9284673, '2026-06-14 19:57:00'),
(34, 'Vliegkamp de Kooy', 'ChIJ7xY6bEZIz0cRHNTGnEMjPvI', 'Rijksweg 20, 1786 PT Den Helder, Netherlands', NULL, 1, 52.9237327, 4.7879662, '2026-06-14 19:59:22'),
(35, 'Generaal Majoor Kootkazerne', 'ChIJI5OnaAC1x0cRAW4ssNUk_3o', 'Wolweg 100, 3776 LT Stroe, Netherlands', NULL, 0, 52.2120002, 5.7079331, '2026-06-14 20:00:08'),
(36, 'H20 campus', 'ChIJq6ra3jUBxkcRqRBwWQ6oIH4', 'Spinnekop 2-3, 1444 GN Purmerend, Netherlands', NULL, 0, 52.5191188, 4.9730541, '2026-06-14 20:01:11'),
(37, 'Litop', 'ChIJG-bF2D-dxkcRv4g9suNZM08', 'Lage Brugweg 10, 5759 PK Helenaveen, Netherlands', NULL, 0, 51.3863550, 5.9100885, '2026-06-14 20:07:24'),
(38, 'Scalda Vlissingen', 'ChIJR1skfFaZxEcRiYonX7Fcmmk', 'Edisonweg 4a, 4382 NW Vlissingen, Netherlands', NULL, 1, 51.4514463, 3.5892718, '2026-06-14 20:15:30'),
(41, 'Kampanje', 'ChIJGWIO2fo3z0cRieUuWvv7vzU', 'Willemsoord 63, 1781 AS Den Helder, Netherlands', NULL, 1, 52.9586059, 4.7690900, '2026-06-17 13:50:22'),
(42, 'Reuring', 'ChIJpQa-UQCpyEcRjxMto1zSlBY', 'Badhuisweg 1, 1621 LM Hoorn, Netherlands', NULL, 0, 52.6364523, 5.0405485, '2026-07-03 08:53:51'),
(43, 'Brunch Brothers', 'ChIJJ599E9ypyEcR1svjHmp3Fks', 'Roode Steen 2, 1621 CV Hoorn, Netherlands', NULL, 0, 52.6390565, 5.0591103, '2026-07-03 08:55:52'),
(44, 'Talland Purmerend', 'ChIJKYn9WTMBxkcRQzSIbmserL8', 'Karekietpark 4, 1444 HV Purmerend, Netherlands', NULL, 1, 52.5199925, 4.9641856, '2026-09-15 09:58:14'),
(45, 'Wiringerland RSG', 'ChIJec2tbI-zyEcRMZ459GL-cEk', 'Dokter Tamsmalaan 1, 1771 AB Wieringerwerf, Netherlands', NULL, 1, 52.8485190, 5.0236110, '2026-09-15 09:58:45'),
(46, 'DNP Haarlem', 'ChIJDft5IGvhxUcRnqkZzj0GxVo', 'Oudeweg 42, 2031 CC Haarlem, Netherlands', NULL, 0, 52.3863528, 4.6669624, '2026-09-15 10:03:04'),
(47, 'Tanja', 'ChIJuRBjWmSlyEcR3BatVnptt7w', 'De Vooruitgang 43, 1693 DT Wervershoof, Netherlands', NULL, 1, 52.7331825, 5.1579697, '2026-09-25 18:14:19'),
(48, 'Leven van de wind', 'ChIJbfOpOO6zyEcRGrK4NvxXH0A', 'Oosterterpweg 12, 1771 SJ Wieringerwerf, Netherlands', 'Mt mbo', 1, 52.8410000, 5.0424460, '2026-09-28 17:52:40');

-- --------------------------------------------------------

--
-- Table structure for table `tripregistrations`
--

CREATE TABLE `tripregistrations` (
  `TripRegistrationId` int NOT NULL,
  `UserId` int NOT NULL,
  `TripDate` date NOT NULL,
  `StartLocationId` int NOT NULL,
  `EndLocationId` int NOT NULL,
  `IsRoundTrip` tinyint(1) NOT NULL DEFAULT '0',
  `ApplyCommuteCompensation` tinyint(1) NOT NULL DEFAULT '0',
  `TripDescription` text COLLATE utf8mb4_unicode_ci,
  `DistanceMeters` int NOT NULL,
  `DistanceKilometers` decimal(10,2) NOT NULL,
  `CreatedAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tripregistrations`
--

INSERT INTO `tripregistrations` (`TripRegistrationId`, `UserId`, `TripDate`, `StartLocationId`, `EndLocationId`, `IsRoundTrip`, `ApplyCommuteCompensation`, `TripDescription`, `DistanceMeters`, `DistanceKilometers`, `CreatedAt`) VALUES
(10, 1, '2026-01-05', 1, 13, 1, 0, 'Hallo 2026', 30216, 30.22, '2026-06-14 14:31:43'),
(11, 1, '2026-01-06', 5, 9, 0, 0, 'Overleg met Rixt', 50932, 50.93, '2026-06-14 14:32:47'),
(12, 1, '2026-01-06', 9, 1, 0, 0, NULL, 25176, 25.18, '2026-06-14 14:32:58'),
(13, 1, '2026-01-13', 1, 14, 0, 0, 'Presentatie EZK', 108951, 108.95, '2026-06-14 14:35:53'),
(14, 1, '2026-01-14', 14, 2, 0, 0, 'Overleg F&C', 86755, 86.76, '2026-06-14 14:36:13'),
(15, 1, '2026-01-15', 5, 3, 1, 0, 'Soe overleg', 10844, 10.84, '2026-06-14 14:36:43'),
(16, 1, '2026-01-19', 5, 8, 1, 0, 'Managers overleg', 6696, 6.70, '2026-06-14 14:37:49'),
(17, 1, '2026-01-19', 8, 15, 0, 0, 'Overleg met Tjerk', 2807, 2.81, '2026-06-14 14:39:05'),
(18, 1, '2026-01-19', 15, 5, 0, 0, NULL, 1379, 1.38, '2026-06-14 14:39:15'),
(19, 1, '2026-01-20', 5, 12, 1, 0, 'Overleg met nuclear academy', 45654, 45.65, '2026-06-14 14:40:04'),
(20, 1, '2026-01-21', 5, 16, 1, 0, NULL, 15722, 15.72, '2026-06-14 14:42:52'),
(21, 1, '2026-01-22', 1, 9, 0, 0, 'Inspiratieochtend keuzedelen', 25464, 25.46, '2026-06-14 14:43:21'),
(22, 1, '2026-01-22', 9, 5, 0, 0, NULL, 50806, 50.81, '2026-06-14 14:43:30'),
(23, 1, '2026-01-23', 1, 2, 0, 0, NULL, 10108, 10.11, '2026-06-14 14:45:21'),
(24, 1, '2026-01-23', 2, 17, 1, 1, NULL, 99450, 99.45, '2026-06-14 14:45:44'),
(25, 1, '2026-01-23', 2, 1, 0, 0, NULL, 10200, 10.20, '2026-06-14 14:45:53'),
(26, 1, '2026-01-26', 5, 18, 1, 0, NULL, 45974, 45.97, '2026-06-14 14:50:53'),
(27, 1, '2026-01-27', 1, 2, 1, 0, NULL, 20216, 20.22, '2026-06-14 14:51:53'),
(28, 1, '2026-01-28', 5, 20, 0, 0, 'Overleg met Pieter en Marcel', 91142, 91.14, '2026-06-14 14:53:26'),
(29, 1, '2026-01-28', 20, 1, 0, 0, NULL, 102221, 102.22, '2026-06-14 14:53:42'),
(30, 1, '2026-02-02', 5, 7, 1, 0, NULL, 5878, 5.88, '2026-06-14 14:55:09'),
(31, 1, '2026-02-04', 1, 21, 1, 0, 'training Differentieren met AI', 12904, 12.90, '2026-06-14 14:57:30'),
(32, 1, '2026-02-11', 5, 22, 0, 0, 'Regiodeal aanvraag', 38331, 38.33, '2026-06-14 14:59:37'),
(33, 1, '2026-02-11', 22, 10, 0, 0, 'Nuclear academy', 542, 0.54, '2026-06-14 14:59:59'),
(34, 1, '2026-02-11', 10, 1, 0, 0, NULL, 11102, 11.10, '2026-06-14 15:00:11'),
(37, 1, '2026-02-12', 1, 2, 1, 0, NULL, 20216, 20.22, '2026-06-14 15:02:19'),
(41, 1, '2026-02-20', 1, 23, 0, 0, NULL, 35126, 35.13, '2026-06-14 15:05:22'),
(42, 1, '2026-02-20', 23, 5, 0, 0, NULL, 63230, 63.23, '2026-06-14 15:05:39'),
(46, 1, '2026-03-06', 1, 24, 1, 0, 'MBO raad', 196932, 196.93, '2026-06-14 16:15:02'),
(47, 1, '2026-03-09', 5, 7, 1, 0, 'Xedule', 5878, 5.88, '2026-06-14 16:15:29'),
(48, 1, '2026-03-11', 5, 7, 1, 0, NULL, 5878, 5.88, '2026-06-14 16:16:12'),
(52, 1, '2026-03-16', 5, 7, 1, 0, 'Xedule', 5878, 5.88, '2026-06-14 16:18:26'),
(54, 1, '2026-03-19', 27, 1, 1, 0, NULL, 24482, 24.48, '2026-06-14 16:21:05'),
(55, 1, '2026-03-23', 5, 7, 1, 0, 'Xedule', 5878, 5.88, '2026-06-14 16:21:36'),
(56, 1, '2026-03-23', 5, 2, 0, 0, NULL, 40142, 40.14, '2026-06-14 16:21:52'),
(57, 1, '2026-03-23', 2, 1, 0, 0, NULL, 10200, 10.20, '2026-06-14 16:22:00'),
(58, 1, '2026-03-26', 5, 3, 1, 0, 'Soe overleg', 10844, 10.84, '2026-06-14 16:22:38'),
(60, 1, '2026-03-31', 5, 7, 1, 0, 'Xedule', 5878, 5.88, '2026-06-14 16:23:34'),
(61, 1, '2026-04-01', 1, 9, 1, 0, NULL, 50928, 50.93, '2026-06-14 16:23:51'),
(62, 1, '2026-04-02', 1, 28, 1, 0, 'HTC aanvraag', 355276, 355.28, '2026-06-14 16:24:59'),
(63, 1, '2026-04-03', 1, 4, 0, 0, NULL, 6537, 6.54, '2026-06-14 16:25:35'),
(64, 1, '2026-04-03', 4, 29, 0, 0, NULL, 85070, 85.07, '2026-06-14 16:26:49'),
(65, 1, '2026-04-03', 29, 1, 0, 0, NULL, 89910, 89.91, '2026-06-14 16:27:00'),
(66, 1, '2026-04-07', 5, 7, 1, 0, 'Xedule', 5878, 5.88, '2026-06-14 16:27:26'),
(67, 1, '2026-04-14', 5, 15, 1, 0, 'training AI voor OOP', 2758, 2.76, '2026-06-14 16:27:49'),
(68, 1, '2026-04-13', 5, 7, 1, 0, 'Xedule', 5878, 5.88, '2026-06-14 16:29:05'),
(69, 1, '2026-04-11', 1, 5, 1, 0, 'Halve van Den Helder', 72344, 72.34, '2026-06-14 16:29:40'),
(70, 1, '2026-05-06', 5, 7, 1, 0, 'Xedule', 5878, 5.88, '2026-06-14 16:30:59'),
(71, 1, '2026-05-11', 5, 7, 1, 0, 'Xedule', 5878, 5.88, '2026-06-14 16:32:15'),
(72, 1, '2026-05-11', 5, 30, 1, 0, NULL, 9332, 9.33, '2026-06-14 16:33:11'),
(73, 1, '2026-05-12', 5, 2, 1, 0, NULL, 80284, 80.28, '2026-06-14 16:33:45'),
(74, 1, '2026-05-13', 1, 2, 0, 0, NULL, 10108, 10.11, '2026-06-14 16:34:13'),
(75, 1, '2026-05-13', 2, 5, 0, 0, NULL, 40125, 40.13, '2026-06-14 16:34:30'),
(76, 1, '2026-05-13', 5, 1, 0, 0, NULL, 36975, 36.98, '2026-06-14 16:34:39'),
(77, 1, '2026-05-20', 1, 24, 1, 0, NULL, 196932, 196.93, '2026-06-14 16:35:44'),
(78, 1, '2026-05-21', 5, 2, 0, 0, NULL, 40142, 40.14, '2026-06-14 16:36:22'),
(79, 1, '2026-05-21', 2, 31, 0, 0, NULL, 18381, 18.38, '2026-06-14 16:36:55'),
(80, 1, '2026-05-21', 31, 1, 0, 0, NULL, 14622, 14.62, '2026-06-14 16:37:05'),
(81, 1, '2026-05-26', 5, 8, 1, 0, NULL, 6696, 6.70, '2026-06-14 16:37:56'),
(82, 1, '2026-05-27', 5, 7, 1, 0, NULL, 5878, 5.88, '2026-06-14 16:38:29'),
(83, 1, '2026-05-28', 5, 6, 1, 0, NULL, 9796, 9.80, '2026-06-14 16:38:59'),
(84, 1, '2026-05-20', 32, 1, 0, 0, NULL, 64460, 64.46, '2026-06-14 19:55:28'),
(85, 1, '2026-06-01', 5, 30, 1, 0, NULL, 9332, 9.33, '2026-06-14 19:55:54'),
(86, 1, '2026-06-02', 5, 15, 1, 0, 'Pet', 2758, 2.76, '2026-06-14 19:56:26'),
(87, 1, '2026-06-02', 1, 33, 1, 0, 'Luba event', 57152, 57.15, '2026-06-14 19:57:24'),
(88, 1, '2026-06-04', 5, 12, 1, 0, 'nuclear academy', 45654, 45.65, '2026-06-14 19:58:04'),
(90, 1, '2026-06-08', 5, 7, 1, 0, NULL, 5878, 5.88, '2026-06-14 19:58:40'),
(91, 1, '2026-06-09', 5, 34, 1, 0, NULL, 7354, 7.35, '2026-06-14 19:59:34'),
(92, 1, '2026-06-10', 1, 35, 1, 1, 'Veva', 158620, 158.62, '2026-06-14 20:00:27'),
(93, 1, '2026-06-11', 1, 2, 0, 0, NULL, 10108, 10.11, '2026-06-14 20:00:51'),
(94, 1, '2026-06-11', 2, 36, 0, 0, 'Technologietafel', 28130, 28.13, '2026-06-14 20:01:53'),
(95, 1, '2026-06-11', 36, 1, 0, 0, NULL, 34412, 34.41, '2026-06-14 20:01:59'),
(96, 1, '2026-06-12', 5, 6, 1, 0, NULL, 9796, 9.80, '2026-06-14 20:02:16'),
(97, 1, '2025-11-27', 5, 26, 1, 0, 'Wijtechniek', 104928, 104.93, '2026-06-14 20:04:57'),
(99, 1, '2025-12-11', 2, 24, 1, 1, NULL, 94644, 94.64, '2026-06-14 20:06:22'),
(100, 1, '2025-12-11', 2, 1, 1, 0, NULL, 20400, 20.40, '2026-06-14 20:06:33'),
(101, 1, '2025-12-12', 1, 37, 1, 0, NULL, 423772, 423.77, '2026-06-14 20:07:55'),
(102, 1, '2026-03-05', 1, 38, 1, 1, 'Windcolalitie', 348212, 348.21, '2026-06-14 20:16:20'),
(103, 1, '2026-01-22', 5, 23, 0, 0, 'Windcolalitie', 63462, 63.46, '2026-06-14 20:18:21'),
(104, 1, '2026-01-22', 23, 1, 0, 0, NULL, 35835, 35.84, '2026-06-14 20:18:31'),
(107, 1, '2026-06-15', 5, 6, 1, 0, NULL, 9796, 9.80, '2026-06-15 15:05:36'),
(109, 1, '2026-02-11', 1, 5, 0, 0, NULL, 36172, 36.17, '2026-06-15 15:56:52'),
(112, 1, '2026-06-16', 5, 8, 1, 0, NULL, 6696, 6.70, '2026-06-16 07:59:31'),
(113, 1, '2026-06-16', 5, 6, 1, 0, NULL, 9074, 9.07, '2026-06-16 07:59:46'),
(114, 1, '2026-06-16', 5, 41, 0, 0, NULL, 4596, 4.60, '2026-06-17 13:50:54'),
(115, 1, '2026-06-19', 5, 18, 1, 0, 'Overleg met Miriam', 45974, 45.97, '2026-07-03 08:51:29'),
(116, 1, '2026-06-24', 1, 2, 1, 0, NULL, 20216, 20.22, '2026-07-03 08:52:26'),
(117, 1, '2026-06-29', 1, 2, 1, 0, NULL, 20216, 20.22, '2026-07-03 08:53:18'),
(118, 1, '2026-06-29', 5, 42, 0, 0, 'Eten met cluster', 51524, 51.52, '2026-07-03 08:54:46'),
(119, 1, '2026-06-29', 42, 1, 0, 0, NULL, 22139, 22.14, '2026-07-03 08:54:58'),
(120, 1, '2026-06-30', 1, 2, 0, 0, NULL, 10108, 10.11, '2026-07-03 08:55:20'),
(121, 1, '2026-06-30', 2, 43, 0, 0, NULL, 24937, 24.94, '2026-07-03 08:56:09'),
(122, 1, '2026-06-30', 43, 5, 0, 0, NULL, 51593, 51.59, '2026-07-03 08:56:24'),
(123, 1, '2026-07-01', 5, 19, 1, 0, 'overleg met STO', 45856, 45.86, '2026-07-03 09:17:38'),
(125, 1, '2026-08-10', 1, 5, 1, 1, NULL, 52344, 52.34, '2026-08-10 11:19:43'),
(126, 1, '2026-07-02', 5, 8, 1, 0, NULL, 6694, 6.69, '2026-08-10 11:20:51'),
(127, 1, '2026-07-07', 1, 2, 1, 0, NULL, 20214, 20.21, '2026-08-10 11:21:18'),
(128, 1, '2026-07-08', 1, 2, 0, 0, NULL, 10107, 10.11, '2026-08-10 11:21:50'),
(129, 1, '2026-07-08', 2, 4, 0, 0, NULL, 8298, 8.30, '2026-08-10 11:22:01'),
(130, 1, '2026-07-08', 4, 1, 0, 0, NULL, 6561, 6.56, '2026-08-10 11:22:15'),
(131, 1, '2026-08-11', 1, 5, 1, 1, NULL, 52344, 52.34, '2026-08-11 10:31:21'),
(132, 1, '2026-08-11', 5, 8, 1, 0, NULL, 6694, 6.69, '2026-08-11 10:31:33'),
(133, 1, '2026-08-13', 1, 5, 1, 1, NULL, 52344, 52.34, '2026-08-13 23:21:32'),
(134, 1, '2026-08-12', 1, 5, 1, 1, NULL, 52344, 52.34, '2026-08-13 23:21:59'),
(135, 1, '2026-08-14', 1, 8, 1, 1, NULL, 54620, 54.62, '2026-08-14 11:19:07'),
(136, 1, '2026-08-14', 8, 5, 1, 0, NULL, 7892, 7.89, '2026-08-14 11:19:17'),
(137, 1, '2026-08-18', 1, 8, 1, 1, NULL, 54620, 54.62, '2026-08-18 18:03:52'),
(138, 1, '2026-08-18', 8, 5, 1, 0, NULL, 7892, 7.89, '2026-08-18 18:04:08'),
(139, 1, '2026-08-19', 1, 5, 1, 1, NULL, 52344, 52.34, '2026-08-19 16:55:45'),
(140, 1, '2026-08-21', 2, 5, 0, 0, NULL, 40125, 40.13, '2026-08-21 13:13:49'),
(141, 1, '2026-08-21', 5, 8, 0, 0, NULL, 3347, 3.35, '2026-08-21 13:13:58'),
(142, 1, '2026-08-21', 8, 2, 0, 0, NULL, 41263, 41.26, '2026-08-21 13:14:08'),
(143, 1, '2026-08-25', 2, 8, 0, 0, NULL, 41263, 41.26, '2026-08-25 13:33:14'),
(144, 1, '2026-08-25', 8, 7, 0, 0, NULL, 1825, 1.83, '2026-08-25 13:33:23'),
(145, 1, '2026-08-25', 7, 9, 0, 0, NULL, 52669, 52.67, '2026-08-25 13:33:31'),
(146, 1, '2026-08-25', 9, 2, 0, 0, NULL, 26251, 26.25, '2026-08-25 13:33:39'),
(147, 1, '2026-08-26', 2, 8, 0, 0, NULL, 41263, 41.26, '2026-08-26 17:12:16'),
(148, 1, '2026-08-26', 8, 7, 0, 0, NULL, 1825, 1.83, '2026-08-26 17:12:27'),
(149, 1, '2026-08-26', 7, 2, 0, 0, NULL, 42649, 42.65, '2026-08-26 17:12:38'),
(150, 1, '2026-08-28', 2, 5, 0, 0, NULL, 40125, 40.13, '2026-08-28 17:07:12'),
(151, 1, '2026-08-28', 5, 8, 1, 0, NULL, 6694, 6.69, '2026-08-28 17:07:21'),
(152, 1, '2026-08-28', 5, 2, 0, 0, NULL, 40142, 40.14, '2026-08-28 17:07:29'),
(153, 1, '2026-09-01', 2, 8, 1, 0, NULL, 82526, 82.53, '2026-09-01 12:35:58'),
(154, 1, '2026-09-02', 2, 8, 0, 0, NULL, 41263, 41.26, '2026-09-02 12:22:14'),
(155, 1, '2026-09-02', 8, 5, 0, 0, NULL, 3946, 3.95, '2026-09-02 12:22:26'),
(156, 1, '2026-09-02', 5, 2, 0, 0, NULL, 40142, 40.14, '2026-09-02 12:22:40'),
(157, 1, '2026-09-03', 2, 41, 1, 0, 'Offshore experience', 83498, 83.50, '2026-09-15 10:01:19'),
(158, 1, '2026-09-04', 2, 5, 1, 0, NULL, 80250, 80.25, '2026-09-15 10:01:54'),
(159, 1, '2026-09-08', 2, 27, 1, 0, NULL, 7482, 7.48, '2026-09-15 10:02:33'),
(160, 1, '2026-09-09', 2, 46, 1, 0, 'Diploma uitreiking', 77724, 77.72, '2026-09-15 10:03:30'),
(161, 1, '2026-09-10', 2, 44, 0, 0, NULL, 28029, 28.03, '2026-09-15 10:03:49'),
(162, 1, '2026-09-10', 44, 45, 0, 0, NULL, 40613, 40.61, '2026-09-15 10:03:59'),
(163, 1, '2026-09-10', 45, 2, 0, 0, NULL, 34662, 34.66, '2026-09-15 10:04:11'),
(164, 1, '2026-09-15', 2, 8, 1, 0, NULL, 82526, 82.53, '2026-09-15 10:05:21'),
(165, 1, '2026-09-18', 2, 10, 0, 0, NULL, 1845, 1.85, '2026-09-18 19:01:55'),
(166, 1, '2026-09-18', 10, 8, 0, 0, NULL, 40006, 40.01, '2026-09-18 19:02:07'),
(167, 1, '2026-09-18', 8, 2, 0, 0, NULL, 41263, 41.26, '2026-09-18 19:02:17'),
(168, 1, '2026-09-17', 2, 5, 1, 0, NULL, 80250, 80.25, '2026-09-18 19:02:55'),
(169, 1, '2026-09-22', 2, 4, 1, 0, NULL, 16828, 16.83, '2026-09-22 12:37:34'),
(170, 1, '2026-09-23', 2, 7, 1, 0, NULL, 84390, 84.39, '2026-09-23 07:56:17'),
(171, 1, '2026-09-25', 2, 8, 0, 0, NULL, 41263, 41.26, '2026-09-25 18:14:34'),
(172, 1, '2026-09-25', 8, 47, 0, 0, NULL, 51304, 51.30, '2026-09-25 18:14:45'),
(173, 1, '2026-09-25', 47, 2, 0, 0, NULL, 39728, 39.73, '2026-09-25 18:14:56'),
(174, 1, '2026-09-28', 1, 48, 0, 0, 'Mt mbo', 26564, 26.56, '2026-09-28 17:52:55'),
(175, 1, '2026-09-28', 48, 2, 0, 0, NULL, 34263, 34.26, '2026-09-28 17:53:02'),
(176, 1, '2026-09-29', 1, 9, 1, 0, NULL, 43090, 43.09, '2026-09-28 17:53:45'),
(177, 1, '2026-09-30', 2, 8, 0, 0, NULL, 41263, 41.26, '2026-09-30 19:09:46'),
(178, 1, '2026-09-30', 8, 41, 0, 0, NULL, 1875, 1.88, '2026-09-30 19:09:59'),
(179, 1, '2026-09-30', 41, 1, 0, 0, NULL, 38611, 38.61, '2026-09-30 19:10:08'),
(180, 1, '2026-10-01', 2, 5, 1, 0, NULL, 80250, 80.25, '2026-10-01 13:49:57'),
(181, 1, '2026-10-02', 2, 8, 0, 0, NULL, 41263, 41.26, '2026-10-06 13:45:56'),
(182, 1, '2026-10-02', 8, 5, 0, 0, NULL, 3946, 3.95, '2026-10-06 13:46:07'),
(183, 1, '2026-10-02', 5, 2, 0, 0, NULL, 40142, 40.14, '2026-10-06 13:46:16'),
(184, 1, '2026-10-06', 2, 8, 1, 0, NULL, 82526, 82.53, '2026-10-06 13:46:58');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserId` int NOT NULL,
  `FirstName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `LastName` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `EmailAddress` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `PasswordHash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `IsAdmin` tinyint(1) NOT NULL DEFAULT '0',
  `IsActive` tinyint(1) NOT NULL DEFAULT '1',
  `IsCommuteCompensationEnabled` tinyint(1) NOT NULL DEFAULT '1',
  `CommuteCompensationKilometers` decimal(10,2) NOT NULL DEFAULT '20.00',
  `CreatedAt` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserId`, `FirstName`, `LastName`, `EmailAddress`, `PasswordHash`, `IsAdmin`, `CreatedAt`) VALUES
(1, 'Tai-Chow', 'Andrae', 'tc@andrae.nl', '$2y$10$fmA/jvQA4/PvCt38PW/x8Ogl8I1rZAhQdcQfZOrfvwqq30A6.3ASi', 1, '2026-06-13 18:38:18'),
(2, 'aa', 'bb', 'aa@bb.nl', '$2y$10$vgeG4QCE1yoWEmh1dp8NAet0sifqHC4I6C5IKmdbL62eGV4D6Ac8O', 0, '2026-06-13 18:47:10');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`LocationId`),
  ADD KEY `ForeignLocationsUserId` (`UserId`);

--
-- Indexes for table `tripregistrations`
--
ALTER TABLE `tripregistrations`
  ADD PRIMARY KEY (`TripRegistrationId`),
  ADD KEY `ForeignTripRegistrationsUserId` (`UserId`),
  ADD KEY `ForeignTripRegistrationsStartLocationId` (`StartLocationId`),
  ADD KEY `ForeignTripRegistrationsEndLocationId` (`EndLocationId`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserId`),
  ADD UNIQUE KEY `UniqueUsersEmailAddress` (`EmailAddress`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `LocationId` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `tripregistrations`
--
ALTER TABLE `tripregistrations`
  MODIFY `TripRegistrationId` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=185;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserId` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `locations`
--
ALTER TABLE `locations`
  ADD CONSTRAINT `ForeignLocationsUserId` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`);

--
-- Constraints for table `tripregistrations`
--
ALTER TABLE `tripregistrations`
  ADD CONSTRAINT `tripregistrations_ibfk_1` FOREIGN KEY (`UserId`) REFERENCES `users` (`UserId`),
  ADD CONSTRAINT `tripregistrations_ibfk_2` FOREIGN KEY (`StartLocationId`) REFERENCES `locations` (`LocationId`),
  ADD CONSTRAINT `tripregistrations_ibfk_3` FOREIGN KEY (`EndLocationId`) REFERENCES `locations` (`LocationId`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
