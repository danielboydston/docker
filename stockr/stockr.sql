-- phpMyAdmin SQL Dump
-- version 4.7.9
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Generation Time: Mar 26, 2018 at 01:04 AM
-- Server version: 10.2.13-MariaDB-10.2.13+maria~jessie
-- PHP Version: 7.2.2

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `stockr`
--
CREATE DATABASE IF NOT EXISTS `stockr` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `stockr`;

-- --------------------------------------------------------

--
-- Table structure for table `calcs`
--

DROP TABLE IF EXISTS `calcs`;
CREATE TABLE `calcs` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `calculation` varchar(255) NOT NULL,
  `comparisonID` int(11) NOT NULL,
  `active` enum('yes','no') NOT NULL DEFAULT 'yes'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `calcs`
--

INSERT INTO `calcs` (`id`, `name`, `calculation`, `comparisonID`, `active`) VALUES
(1, 'Last Price', '[latestPrice]', 2, 'yes'),
(2, 'Last Price', '[latestPrice]', 3, 'yes'),
(3, 'Percent Gain', '(([latestPrice] - [costBasis]) / [costBasis]) * 100', 2, 'yes'),
(4, 'Percent Gain', '(([latestPrice] - [costBasis]) / [costBasis]) * 100', 3, 'yes'),
(5, 'Percent Gains Lost', '(([watchHigh] - [latestPrice]) / ([watchHigh] - [costBasis])) * 100', 2, 'yes');

-- --------------------------------------------------------

--
-- Table structure for table `comparisons`
--

DROP TABLE IF EXISTS `comparisons`;
CREATE TABLE `comparisons` (
  `id` int(11) NOT NULL,
  `operator` varchar(2) NOT NULL,
  `words` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `comparisons`
--

INSERT INTO `comparisons` (`id`, `operator`, `words`) VALUES
(1, '==', 'Equal To'),
(2, '>', 'Greater Than'),
(3, '<', 'Less Than'),
(4, '>=', 'Greater Than or Equal To'),
(5, '<=', 'Less Than or Equal To'),
(6, '!=', 'Not Equal To');

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
  `id` int(11) NOT NULL,
  `item` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `triggerID` int(11) NOT NULL,
  `quoteID` int(11) NOT NULL,
  `notificationDate` bigint(20) NOT NULL DEFAULT 0,
  `triggerThreshold` decimal(6,2) NOT NULL DEFAULT 0.00,
  `triggerValue` decimal(6,2) NOT NULL DEFAULT 0.00,
  `state` enum('yes','no') NOT NULL DEFAULT 'yes',
  `sentDate` bigint(20) NOT NULL DEFAULT 0,
  `status` enum('new','sent') NOT NULL DEFAULT 'new'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `quoteFieldMap`
--

DROP TABLE IF EXISTS `quoteFieldMap`;
CREATE TABLE `quoteFieldMap` (
  `id` int(11) NOT NULL,
  `quoteSourceID` int(11) NOT NULL,
  `remote` varchar(50) NOT NULL,
  `local` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `quotes`
--

DROP TABLE IF EXISTS `quotes`;
CREATE TABLE `quotes` (
  `id` int(11) NOT NULL,
  `quoteSourceID` int(11) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `companyName` varchar(255) DEFAULT NULL,
  `primaryExchange` varchar(30) DEFAULT NULL,
  `sector` varchar(30) DEFAULT NULL,
  `calculationPrice` varchar(30) DEFAULT NULL,
  `open` decimal(6,2) DEFAULT NULL,
  `openTime` bigint(20) DEFAULT NULL,
  `close` decimal(6,2) DEFAULT NULL,
  `closeTime` bigint(20) DEFAULT NULL,
  `high` decimal(6,2) DEFAULT NULL,
  `low` decimal(6,2) DEFAULT NULL,
  `latestPrice` decimal(6,2) DEFAULT NULL,
  `latestSource` varchar(30) DEFAULT NULL,
  `latestTime` varchar(30) DEFAULT NULL,
  `latestUpdate` bigint(20) DEFAULT NULL,
  `latestVolume` int(11) DEFAULT NULL,
  `iexRealtimePrice` decimal(6,2) DEFAULT NULL,
  `iexRealtimeSize` int(11) DEFAULT NULL,
  `iexLastUpdated` bigint(20) DEFAULT NULL,
  `delayedPrice` decimal(6,2) DEFAULT NULL,
  `delayedPriceTime` bigint(20) DEFAULT NULL,
  `previousClose` decimal(6,2) DEFAULT NULL,
  `priceChange` decimal(6,2) DEFAULT NULL,
  `changePercent` decimal(5,4) DEFAULT NULL,
  `iexMarketPercent` decimal(5,4) DEFAULT NULL,
  `iexVolume` int(11) DEFAULT NULL,
  `avgTotalVolume` bigint(20) DEFAULT NULL,
  `iexBidPrice` decimal(6,2) DEFAULT NULL,
  `iexBidSize` decimal(6,2) DEFAULT NULL,
  `iexAskPrice` decimal(6,2) DEFAULT NULL,
  `iexAskSize` decimal(6,2) DEFAULT NULL,
  `marketCap` bigint(20) DEFAULT NULL,
  `peRatio` decimal(6,2) DEFAULT NULL,
  `week52High` decimal(6,2) DEFAULT NULL,
  `week52Low` decimal(6,2) DEFAULT NULL,
  `ytdChange` decimal(5,4) DEFAULT NULL,
  `status` enum('new','processed') NOT NULL DEFAULT 'new'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `quoteSources`
--

DROP TABLE IF EXISTS `quoteSources`;
CREATE TABLE `quoteSources` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL,
  `active` enum('yes','no') NOT NULL DEFAULT 'yes'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `timezones`
--

DROP TABLE IF EXISTS `timezones`;
CREATE TABLE `timezones` (
  `id` int(11) NOT NULL,
  `timezone` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `triggers`
--

DROP TABLE IF EXISTS `triggers`;
CREATE TABLE `triggers` (
  `id` int(11) NOT NULL,
  `watchID` int(11) NOT NULL,
  `calcID` int(11) NOT NULL,
  `threshold` varchar(50) NOT NULL,
  `prompt` enum('buy','sell','custom') NOT NULL DEFAULT 'sell',
  `customPrompt` varchar(30) NOT NULL DEFAULT '',
  `triggered` enum('yes','no') NOT NULL DEFAULT 'no',
  `triggerDate` bigint(20) NOT NULL,
  `acknowledged` enum('yes','no') NOT NULL DEFAULT 'no',
  `error` enum('yes','no') NOT NULL DEFAULT 'no',
  `error_message` varchar(255) NOT NULL,
  `lastEval` bigint(20) NOT NULL,
  `lastValue` decimal(6,2) NOT NULL,
  `high` decimal(6,2) NOT NULL,
  `highDate` bigint(20) NOT NULL,
  `low` decimal(6,2) NOT NULL,
  `lowDate` bigint(20) NOT NULL,
  `active` enum('yes','no') NOT NULL DEFAULT 'yes'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `userEmails`
--

DROP TABLE IF EXISTS `userEmails`;
CREATE TABLE `userEmails` (
  `id` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `text` enum('yes','no') NOT NULL DEFAULT 'no',
  `active` enum('yes','no') NOT NULL DEFAULT 'yes'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `firstName` varchar(25) NOT NULL,
  `lastName` varchar(25) NOT NULL,
  `timezoneID` int(11) NOT NULL,
  `active` enum('yes','no') NOT NULL DEFAULT 'yes'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `watches`
--

DROP TABLE IF EXISTS `watches`;
CREATE TABLE `watches` (
  `id` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `symbol` varchar(10) NOT NULL,
  `createDate` datetime NOT NULL,
  `costBasis` decimal(6,2) NOT NULL,
  `qty` decimal(6,2) NOT NULL,
  `quoteSourceID` int(11) NOT NULL,
  `high` decimal(6,2) NOT NULL DEFAULT 0.00,
  `highDate` bigint(20) NOT NULL,
  `low` decimal(6,2) NOT NULL DEFAULT 0.00,
  `lowDate` bigint(20) NOT NULL,
  `latestQuoteID` int(11) NOT NULL,
  `latestSource` varchar(50) NOT NULL,
  `latestTime` bigint(20) NOT NULL,
  `active` enum('yes','no') NOT NULL DEFAULT 'yes'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `calcs`
--
ALTER TABLE `calcs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `comparisonID` (`comparisonID`);

--
-- Indexes for table `comparisons`
--
ALTER TABLE `comparisons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `config`
--
ALTER TABLE `config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quoteID` (`quoteID`),
  ADD KEY `notifications_ibfk_2` (`triggerID`);

--
-- Indexes for table `quoteFieldMap`
--
ALTER TABLE `quoteFieldMap`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quoteSourceID` (`quoteSourceID`);

--
-- Indexes for table `quotes`
--
ALTER TABLE `quotes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `symbol` (`symbol`),
  ADD KEY `quoteSourceID` (`quoteSourceID`);

--
-- Indexes for table `quoteSources`
--
ALTER TABLE `quoteSources`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `timezones`
--
ALTER TABLE `timezones`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `triggers`
--
ALTER TABLE `triggers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `triggerID` (`calcID`),
  ADD KEY `watchID` (`watchID`);

--
-- Indexes for table `userEmails`
--
ALTER TABLE `userEmails`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userID` (`userID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `timezoneID` (`timezoneID`);

--
-- Indexes for table `watches`
--
ALTER TABLE `watches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `userID` (`userID`),
  ADD KEY `symbol` (`symbol`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `calcs`
--
ALTER TABLE `calcs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `comparisons`
--
ALTER TABLE `comparisons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `config`
--
ALTER TABLE `config`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `quoteFieldMap`
--
ALTER TABLE `quoteFieldMap`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `quotes`
--
ALTER TABLE `quotes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5340;

--
-- AUTO_INCREMENT for table `quoteSources`
--
ALTER TABLE `quoteSources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `timezones`
--
ALTER TABLE `timezones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `triggers`
--
ALTER TABLE `triggers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `userEmails`
--
ALTER TABLE `userEmails`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `watches`
--
ALTER TABLE `watches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `calcs`
--
ALTER TABLE `calcs`
  ADD CONSTRAINT `calcs_ibfk_1` FOREIGN KEY (`comparisonID`) REFERENCES `comparisons` (`id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`quoteID`) REFERENCES `quotes` (`id`),
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`triggerID`) REFERENCES `triggers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quoteFieldMap`
--
ALTER TABLE `quoteFieldMap`
  ADD CONSTRAINT `quoteFieldMap_ibfk_1` FOREIGN KEY (`quoteSourceID`) REFERENCES `quoteSources` (`id`);

--
-- Constraints for table `quotes`
--
ALTER TABLE `quotes`
  ADD CONSTRAINT `quotes_ibfk_1` FOREIGN KEY (`quoteSourceID`) REFERENCES `quoteSources` (`id`);

--
-- Constraints for table `triggers`
--
ALTER TABLE `triggers`
  ADD CONSTRAINT `triggers_ibfk_1` FOREIGN KEY (`calcID`) REFERENCES `calcs` (`id`),
  ADD CONSTRAINT `triggers_ibfk_2` FOREIGN KEY (`watchID`) REFERENCES `watches` (`id`);

--
-- Constraints for table `userEmails`
--
ALTER TABLE `userEmails`
  ADD CONSTRAINT `userEmails_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`timezoneID`) REFERENCES `timezones` (`id`);

--
-- Constraints for table `watches`
--
ALTER TABLE `watches`
  ADD CONSTRAINT `watches_ibfk_1` FOREIGN KEY (`userID`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
