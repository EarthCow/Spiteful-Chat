-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 18, 2022 at 02:34 AM
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
-- Table structure for table `chats`
--

CREATE TABLE `chats` (
  `chat_id` int(11) NOT NULL,
  `sender` int(11) NOT NULL,
  `receiver` int(11) NOT NULL,
  `last_message` varchar(256) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `media_id` int(11) NOT NULL,
  `msg_id` int(11) NOT NULL,
  `filename` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `msg_id` int(11) NOT NULL,
  `chat_id` int(11) NOT NULL,
  `sender` int(11) NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `user_id` int(11) NOT NULL,
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

INSERT INTO `profiles` (`user_id`, `google_id`, `username`, `name`, `email`, `email_verified`, `picture`, `chats`, `token`, `token_generated`, `last_active`, `creation_date`) VALUES
(1, '103044655181071952086', 'EarthCow', 'Griffin Garman', 'coppergriffing@gmail.com', 1, 'https://lh3.googleusercontent.com/a/ALm5wu1I6bkQW450Y6Me75A5Bz476Cpyi4O5MLlSleby8A=s96-c', NULL, 'HDXaCQvmo7hXl/z4GKlL3zz3GG+rZPOvNyLv08Lb5REau3SHR3bzRnkIf3FtaCJBQ82I/47gxU4x8gpB9TWb5QUSZG2Ljju2x7B7aZ59ULCiMKEGQb9Y3GGmkxhGqBPBR/87j7OxT0SytlJakckpTCh5klZcKHBN72LZ3IuPk8w=', '2022-12-18 05:34:52', '2022-12-18 07:34:03', '2022-11-22 06:33:54'),
(2, '112261126696325270722', 'Wade', 'Wade Ports', 'wadeports@gmail.com', 1, 'https://lh3.googleusercontent.com/a/ALm5wu3y5-RheCwpL46nR7nAgDlLTKRtyv2AIMnqL2QL=s96-c', NULL, 'NtgRkMg0lOd7WmYroZX9ARGj7UY06XEu88BdVymuA0uhej1+nXhDdCQQ8gHF0Kvvj4EpVvET3wrYESuXOHTDj2itP75sBinkvBrsJ2DjU2jve4MwmLThGKPD8MwaChecH+cnaHvAXO2D/n7RSUThGDcsT5Yl7fvn/o7aFyHmf/s=', '2022-12-18 07:24:03', '2022-12-18 07:24:12', '2022-11-22 06:34:52'),
(3, '101068151223591481185', 'Dave', 'David Whipper', 'davewhipper69@gmail.com', 1, 'https://lh3.googleusercontent.com/a/AEdFTp6Iljby8x5_6CaKVu2TO3TklB8U6ojb6zIDmiRr=s96-c', NULL, 'do3ovd0VjHUh/NpyKGYQXJ6TsN/Ee6r9lbTpzNhV4wd766NdjAb6l4GFVq2zISnIe/DMPvbTAOg+DyHFlQM0NKYLHF/ggGVSy0oeS6MiM0v/Fan4QS6cKmrC8Pxc7M/nG+JNj435PGWZAdgEg08ELG2U7UPS/51GGGg/uXrHat8=', '2022-12-18 07:23:50', '2022-12-18 07:23:50', '2022-12-16 16:16:38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chats`
--
ALTER TABLE `chats`
  ADD PRIMARY KEY (`chat_id`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`media_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`msg_id`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chats`
--
ALTER TABLE `chats`
  MODIFY `chat_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `media_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `msg_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
