-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 12, 2022 at 02:24 PM
-- Server version: 10.4.25-MariaDB
-- PHP Version: 8.1.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `spiteful_chat`
--

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `id` int(11) NOT NULL,
  `google_id` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(256) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified` int(11) NOT NULL,
  `picture` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `chats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`chats`)),
  `token` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_generated` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_active` timestamp NOT NULL DEFAULT current_timestamp(),
  `creation_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `profiles`
--

INSERT INTO `profiles` (`id`, `google_id`, `username`, `name`, `email`, `email_verified`, `picture`, `chats`, `token`, `token_generated`, `last_active`, `creation_date`) VALUES
(1, '103044655181071952086', 'EarthCow', 'Griffin Garman', 'coppergriffing@gmail.com', 1, 'https://lh3.googleusercontent.com/a/ALm5wu1I6bkQW450Y6Me75A5Bz476Cpyi4O5MLlSleby8A=s96-c', '{\"2\":{\"filename\":\"3355002136390e30582a5b8.49270644\",\"recipient\":\"Wade\",\"recipientName\":\"Wade Ports\",\"recipientImage\":\"https:\\/\\/lh3.googleusercontent.com\\/a\\/ALm5wu3y5-RheCwpL46nR7nAgDlLTKRtyv2AIMnqL2QL=s96-c\",\"lastMessage\":\"baby-coin.jpg\",\"lastModified\":\"12\\/11\\/2022 05:53:08\",\"lastModifiedTime\":1670799188}}', 'bc/VUu4ael5QRsrXl58JL7EYH9oMw2r+Sy8pEUkyFQvZutWgK07rFB5wMei9ewcRnUySGMYgJkHqvcn1txaSthziCLg8j9LsDg2zQxSTQVXwMRZ6yfZxM2zhaU4RqzITavCytP+Y+GHwKn6i651wtLnMUZrF4VIHPVsUpl+o2SE=', '2022-12-12 18:54:33', '2022-12-12 18:54:34', '2022-11-22 06:33:54'),
(2, '112261126696325270722', 'Wade', 'Wade Ports', 'wadeports@gmail.com', 1, 'https://lh3.googleusercontent.com/a/ALm5wu3y5-RheCwpL46nR7nAgDlLTKRtyv2AIMnqL2QL=s96-c', '{\"1\":{\"filename\":\"3355002136390e30582a5b8.49270644\",\"recipient\":\"EarthCow\",\"recipientName\":\"Griffin Garman\",\"recipientImage\":\"https:\\/\\/lh3.googleusercontent.com\\/a\\/ALm5wu1I6bkQW450Y6Me75A5Bz476Cpyi4O5MLlSleby8A=s96-c\",\"lastMessage\":\"baby-coin.jpg\",\"lastModified\":\"12\\/11\\/2022 05:53:08\",\"lastModifiedTime\":1670799188}}', 'Fd2dGkwz7C7c+nCjHE/Mi1Fdpl8gyzY0R7moO9jqnpwcyAq2owOiKCsTZAtunkcx9F0G+pUWpaD/9Uom/XR7FsxEmRKx5ivpOkAcBco+Wgb50rmZuqyconRgac5hCerO3jHNJAmt7RYohvhkcZEPeSgkuTliPICT6CO2+xMRqic=', '2022-11-22 06:34:52', '2022-11-22 07:06:04', '2022-11-22 06:34:52');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
