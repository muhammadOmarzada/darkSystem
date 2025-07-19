-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 16 Jul 2025 pada 04.06
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `darksystem`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `akun`
--

CREATE TABLE `akun` (
  `id_akun` int(3) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data untuk tabel `akun`
--

INSERT INTO `akun` (`id_akun`, `username`, `email`, `password`) VALUES
(19, 'Muhammad Omarzada', 'a@gmail.com', '$2y$10$UvdxfANFjzia2p8xEoEDduJfJ39PccD0inyYdcnPSX6W8/50dOLtO');

-- --------------------------------------------------------

--
-- Struktur dari tabel `penjualan`
--

CREATE TABLE `penjualan` (
  `id_penjualan` int(10) NOT NULL,
  `id_user` int(3) NOT NULL,
  `id_pertandingan` int(3) NOT NULL,
  `ticket_type` enum('reguler','vip') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data untuk tabel `penjualan`
--

INSERT INTO `penjualan` (`id_penjualan`, `id_user`, `id_pertandingan`, `ticket_type`) VALUES
(97, 19, 2, 'reguler'),
(98, 19, 2, 'reguler'),
(99, 19, 2, 'reguler'),
(100, 19, 2, 'reguler'),
(101, 19, 2, 'reguler'),
(102, 19, 2, 'reguler'),
(103, 19, 2, 'reguler'),
(104, 19, 2, 'reguler'),
(105, 19, 2, 'reguler'),
(106, 19, 2, 'reguler'),
(107, 19, 2, 'vip'),
(108, 19, 2, 'vip'),
(109, 19, 2, 'vip'),
(110, 19, 2, 'vip'),
(111, 19, 2, 'vip'),
(112, 19, 3, 'reguler'),
(113, 19, 3, 'reguler'),
(114, 19, 3, 'reguler'),
(115, 19, 3, 'reguler'),
(116, 19, 3, 'reguler'),
(117, 19, 3, 'reguler'),
(118, 19, 3, 'reguler'),
(119, 19, 3, 'vip'),
(120, 19, 3, 'vip'),
(121, 19, 3, 'vip'),
(122, 19, 3, 'vip'),
(123, 19, 3, 'vip'),
(124, 19, 3, 'vip'),
(125, 19, 3, 'vip'),
(126, 19, 4, 'reguler'),
(127, 19, 4, 'reguler'),
(128, 19, 4, 'reguler'),
(129, 19, 4, 'reguler'),
(130, 19, 4, 'reguler'),
(131, 19, 4, 'vip'),
(132, 19, 4, 'vip'),
(133, 19, 4, 'vip'),
(134, 19, 4, 'vip'),
(135, 19, 4, 'vip'),
(136, 19, 5, 'reguler'),
(137, 19, 5, 'reguler'),
(138, 19, 5, 'reguler'),
(139, 19, 5, 'reguler'),
(140, 19, 5, 'reguler'),
(141, 19, 5, 'reguler'),
(142, 19, 5, 'vip'),
(143, 19, 5, 'vip'),
(144, 19, 5, 'vip'),
(145, 19, 5, 'vip'),
(146, 19, 5, 'vip'),
(147, 19, 5, 'vip'),
(148, 19, 6, 'reguler'),
(149, 19, 6, 'reguler'),
(150, 19, 6, 'reguler'),
(151, 19, 6, 'reguler'),
(152, 19, 6, 'vip'),
(153, 19, 6, 'vip'),
(154, 19, 6, 'vip'),
(155, 19, 6, 'vip'),
(156, 19, 6, 'vip'),
(157, 19, 6, 'vip'),
(158, 19, 7, 'reguler'),
(159, 19, 7, 'reguler'),
(160, 19, 7, 'reguler'),
(161, 19, 7, 'vip'),
(162, 19, 7, 'vip'),
(163, 19, 7, 'vip'),
(164, 19, 8, 'reguler'),
(165, 19, 8, 'reguler'),
(166, 19, 8, 'reguler'),
(167, 19, 8, 'reguler'),
(168, 19, 8, 'vip'),
(169, 19, 8, 'vip'),
(170, 19, 8, 'vip'),
(171, 19, 8, 'vip'),
(172, 19, 9, 'reguler'),
(173, 19, 9, 'reguler'),
(174, 19, 9, 'reguler'),
(175, 19, 9, 'reguler'),
(176, 19, 9, 'reguler'),
(177, 19, 9, 'reguler'),
(178, 19, 9, 'reguler'),
(179, 19, 9, 'reguler'),
(180, 19, 9, 'reguler'),
(181, 19, 9, 'reguler'),
(182, 19, 9, 'vip'),
(183, 19, 9, 'vip'),
(184, 19, 9, 'vip'),
(185, 19, 9, 'vip'),
(186, 19, 10, 'reguler'),
(187, 19, 10, 'reguler'),
(188, 19, 10, 'reguler'),
(189, 19, 10, 'reguler'),
(190, 19, 10, 'reguler'),
(191, 19, 10, 'reguler'),
(192, 19, 10, 'reguler'),
(193, 19, 10, 'reguler'),
(194, 19, 10, 'reguler'),
(195, 19, 10, 'reguler'),
(196, 19, 10, 'vip'),
(197, 19, 10, 'vip'),
(198, 19, 10, 'vip'),
(199, 19, 10, 'vip'),
(200, 19, 10, 'vip'),
(201, 19, 11, 'reguler'),
(202, 19, 11, 'reguler'),
(203, 19, 11, 'reguler'),
(204, 19, 11, 'reguler'),
(205, 19, 11, 'reguler'),
(206, 19, 11, 'vip'),
(207, 19, 11, 'vip'),
(208, 19, 11, 'vip'),
(209, 19, 11, 'vip'),
(210, 19, 11, 'vip'),
(211, 19, 11, 'vip'),
(212, 19, 11, 'vip'),
(213, 19, 12, 'reguler'),
(214, 19, 12, 'reguler'),
(215, 19, 12, 'reguler'),
(216, 19, 12, 'reguler'),
(217, 19, 12, 'reguler'),
(218, 19, 12, 'reguler'),
(219, 19, 12, 'vip'),
(220, 19, 12, 'vip'),
(221, 19, 12, 'vip'),
(222, 19, 12, 'vip'),
(223, 19, 12, 'vip'),
(224, 19, 12, 'vip'),
(225, 19, 13, 'reguler'),
(226, 19, 13, 'reguler'),
(227, 19, 13, 'reguler'),
(228, 19, 13, 'reguler'),
(229, 19, 13, 'reguler'),
(230, 19, 13, 'reguler'),
(231, 19, 13, 'reguler'),
(232, 19, 13, 'reguler'),
(233, 19, 13, 'reguler'),
(234, 19, 13, 'reguler'),
(235, 19, 13, 'vip'),
(236, 19, 13, 'vip'),
(237, 19, 13, 'vip'),
(238, 19, 14, 'reguler'),
(239, 19, 14, 'reguler'),
(240, 19, 14, 'reguler'),
(241, 19, 14, 'reguler'),
(242, 19, 14, 'reguler'),
(243, 19, 14, 'vip'),
(244, 19, 14, 'vip'),
(245, 19, 14, 'vip'),
(246, 19, 14, 'vip'),
(247, 19, 14, 'vip'),
(248, 19, 15, 'reguler'),
(249, 19, 15, 'reguler'),
(250, 19, 15, 'reguler'),
(251, 19, 15, 'vip'),
(252, 19, 15, 'vip'),
(253, 19, 15, 'vip'),
(254, 19, 15, 'vip'),
(255, 19, 15, 'vip'),
(256, 19, 15, 'vip'),
(257, 19, 16, 'reguler'),
(258, 19, 16, 'reguler'),
(259, 19, 16, 'reguler'),
(260, 19, 16, 'reguler'),
(261, 19, 16, 'reguler'),
(262, 19, 16, 'reguler'),
(263, 19, 16, 'vip'),
(264, 19, 16, 'vip'),
(265, 19, 16, 'vip'),
(266, 19, 17, 'reguler'),
(267, 19, 17, 'reguler'),
(268, 19, 17, 'reguler'),
(269, 19, 17, 'reguler'),
(270, 19, 17, 'reguler'),
(271, 19, 17, 'reguler'),
(272, 19, 17, 'reguler'),
(273, 19, 17, 'vip'),
(274, 19, 17, 'vip'),
(275, 19, 17, 'vip'),
(276, 19, 17, 'vip'),
(277, 19, 17, 'vip'),
(278, 19, 2, 'reguler'),
(279, 19, 2, 'reguler'),
(280, 19, 2, 'reguler'),
(281, 19, 2, 'reguler'),
(282, 19, 2, 'reguler');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pertandingan`
--

CREATE TABLE `pertandingan` (
  `id_pertandingan` int(11) NOT NULL,
  `deskripsi_pertandingan` varchar(255) NOT NULL,
  `jadwal` date NOT NULL,
  `jam` time NOT NULL,
  `id_tiket_reguler` int(3) NOT NULL,
  `id_tiket_vip` int(3) NOT NULL,
  `id_tim1` int(3) NOT NULL,
  `id_tim2` int(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pertandingan`
--

INSERT INTO `pertandingan` (`id_pertandingan`, `deskripsi_pertandingan`, `jadwal`, `jam`, `id_tiket_reguler`, `id_tiket_vip`, `id_tim1`, `id_tim2`) VALUES
(2, '0 VS 2', '2025-03-07', '15:15:00', 2, 2, 11, 3),
(3, '2 VS 1', '2025-03-07', '18:30:00', 3, 3, 9, 8),
(4, '2 VS 0', '2025-03-08', '14:30:00', 4, 4, 9, 4),
(5, '2 VS 0', '2025-03-08', '17:30:00', 5, 5, 6, 7),
(6, '2 VS 0', '2025-03-08', '20:45:00', 6, 6, 3, 5),
(7, '2 VS 1', '2025-03-09', '14:30:00', 7, 7, 10, 6),
(8, '2 VS 1', '2025-03-09', '17:30:00', 8, 8, 7, 5),
(9, '2 VS 1', '2025-03-09', '20:45:00', 9, 9, 8, 11),
(10, '1 VS 2', '2025-03-14', '15:15:00', 10, 10, 5, 4),
(11, '2 VS 0', '2025-03-14', '18:30:00', 11, 11, 10, 11),
(12, '1 VS 2', '2025-03-15', '14:30:00', 12, 12, 11, 7),
(13, '2 VS 0', '2025-03-15', '17:30:00', 13, 13, 3, 8),
(14, '1 VS 2', '2025-03-15', '20:45:00', 14, 14, 6, 9),
(15, '0 VS 2', '2025-03-16', '14:30:00', 15, 15, 4, 6),
(16, '2 VS 0', '2025-03-16', '17:30:00', 16, 16, 7, 9),
(17, '2 VS 0', '2025-03-16', '20:45:00', 17, 17, 8, 10),
(18, '2 VS 1', '2025-03-21', '15:15:00', 18, 18, 5, 10),
(19, '0 VS 2', '2025-03-21', '19:30:00', 19, 19, 11, 9),
(20, '1 VS 2', '2025-03-22', '14:30:00', 20, 20, 8, 4),
(21, '2 VS 0', '2025-03-22', '17:30:00', 21, 21, 5, 6),
(22, '2 VS 1', '2025-03-22', '20:45:00', 22, 22, 3, 7),
(23, '2 VS 1', '2025-03-23', '14:30:00', 23, 23, 6, 11),
(24, '1 VS 2', '2025-03-23', '17:30:00', 24, 24, 4, 3),
(25, '2 VS 1', '2025-03-23', '20:45:00', 25, 25, 7, 10),
(26, '1 VS 2', '2025-04-18', '15:15:00', 26, 26, 11, 5),
(27, '1 VS 2', '2025-04-18', '18:15:00', 27, 27, 9, 10),
(28, '1 VS 2', '2025-04-19', '14:30:00', 28, 28, 8, 6),
(29, '2 VS 1', '2025-04-19', '17:30:00', 29, 29, 3, 9),
(30, '2 VS 1', '2025-04-19', '20:30:00', 30, 30, 4, 7),
(31, '1 VS 2', '2025-04-20', '14:30:00', 31, 31, 5, 8),
(32, '2 VS 0', '2025-04-20', '17:30:00', 32, 32, 6, 3),
(33, '1 VS 2', '2025-04-20', '20:30:00', 33, 33, 10, 4),
(34, '2 VS 0', '2025-04-25', '15:15:00', 34, 34, 4, 11),
(35, '2 VS 0', '2025-04-25', '18:15:00', 35, 35, 9, 5),
(36, '0 VS 2', '2025-04-26', '14:30:00', 36, 36, 10, 3),
(37, '1 VS 2', '2025-04-26', '17:30:00', 37, 37, 7, 8),
(38, '1 VS 2', '2025-04-26', '20:30:00', 38, 38, 9, 6),
(39, '2 VS 1', '2025-04-27', '14:30:00', 39, 39, 7, 11),
(40, '1 VS 2', '2025-04-27', '17:30:00', 40, 40, 4, 5),
(41, '1 VS 2', '2025-04-27', '20:30:00', 41, 41, 10, 8),
(42, '0 VS 2', '2025-05-02', '15:15:00', 42, 42, 11, 10),
(43, '0 VS 2', '2025-05-02', '18:15:00', 43, 43, 7, 4),
(44, '0 VS 2', '2025-05-03', '14:30:00', 44, 44, 9, 7),
(45, '0 VS 2', '2025-05-03', '17:30:00', 45, 45, 8, 5),
(46, '2 VS 1', '2025-05-03', '20:30:00', 46, 46, 3, 6),
(47, '0 VS 2', '2025-05-04', '14:30:00', 47, 47, 11, 8),
(48, '2 VS 1', '2025-05-04', '17:30:00', 48, 48, 5, 3),
(49, '2 VS 1', '2025-05-04', '20:30:00', 49, 49, 4, 9),
(50, '2 VS 0', '2025-05-09', '15:15:00', 50, 50, 4, 10),
(51, '1 VS 2', '2025-05-09', '18:15:00', 51, 51, 6, 8),
(52, '0 VS 2', '2025-05-10', '14:30:00', 52, 52, 10, 7),
(53, '0 VS 2', '2025-05-10', '17:30:00', 53, 53, 9, 3),
(54, '2 VS 0', '2025-05-10', '20:30:00', 54, 54, 5, 11),
(55, '2 VS 1', '2025-05-11', '14:30:00', 55, 55, 3, 4),
(56, '0 VS 2', '2025-05-11', '17:30:00', 56, 56, 11, 6),
(57, '2 VS 1', '2025-05-11', '20:30:00', 57, 57, 8, 9),
(58, '0 VS 2', '2025-05-16', '15:15:00', 58, 58, 8, 7),
(59, '2 VS 0', '2025-05-16', '18:15:00', 59, 59, 6, 10),
(60, '2 VS 1', '2025-05-17', '14:30:00', 60, 60, 4, 8),
(61, '1 VS 2', '2025-05-17', '17:30:00', 61, 61, 7, 3),
(62, '2 VS 1', '2025-05-17', '20:30:00', 62, 62, 6, 5),
(63, '0 VS 2', '2025-05-18', '14:30:00', 63, 63, 3, 10),
(64, '2 VS 0', '2025-05-18', '17:30:00', 64, 64, 5, 9),
(65, '0 VS 2', '2025-05-18', '20:30:00', 65, 65, 11, 4),
(66, '1 VS 2', '2025-05-23', '15:15:00', 66, 66, 6, 4),
(67, '1 VS 2', '2025-05-23', '18:15:00', 67, 67, 10, 5),
(68, '2 VS 1', '2025-05-24', '14:30:00', 68, 68, 9, 11),
(69, '2 VS 0', '2025-05-24', '17:30:00', 69, 69, 8, 3),
(70, '2 VS 0', '2025-05-24', '20:30:00', 70, 70, 5, 7),
(71, '0 VS 2', '2025-05-25', '14:30:00', 71, 71, 10, 9),
(72, '2 VS 0', '2025-05-25', '17:30:00', 72, 72, 7, 6),
(73, '2 VS 0', '2025-05-25', '20:30:00', 73, 73, 3, 11);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tiket_reguler`
--

CREATE TABLE `tiket_reguler` (
  `id_tiket_reguler` int(3) NOT NULL,
  `jumlah` int(4) NOT NULL,
  `harga` int(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data untuk tabel `tiket_reguler`
--

INSERT INTO `tiket_reguler` (`id_tiket_reguler`, `jumlah`, `harga`) VALUES
(2, 4485, 85000),
(3, 4493, 85000),
(4, 4495, 85000),
(5, 4494, 85000),
(6, 4496, 85000),
(7, 4497, 85000),
(8, 4496, 85000),
(9, 4490, 85000),
(10, 4490, 85000),
(11, 4495, 85000),
(12, 4494, 85000),
(13, 4490, 85000),
(14, 4495, 85000),
(15, 4497, 85000),
(16, 4494, 85000),
(17, 4493, 85000),
(18, 4500, 8500),
(19, 4500, 85000),
(20, 4500, 85000),
(21, 4500, 85000),
(22, 4500, 85000),
(23, 4500, 85000),
(24, 4500, 85000),
(25, 4500, 85000),
(26, 4500, 85000),
(27, 4500, 85000),
(28, 4500, 85000),
(29, 4500, 85000),
(30, 4500, 85000),
(31, 4500, 85000),
(32, 4500, 85000),
(33, 4500, 85000),
(34, 4500, 85000),
(35, 4500, 85000),
(36, 4500, 85000),
(37, 4500, 85000),
(38, 4500, 85000),
(39, 4500, 85000),
(40, 4500, 85000),
(41, 4500, 85000),
(42, 4500, 85000),
(43, 4500, 85000),
(44, 4500, 85000),
(45, 4500, 85000),
(46, 4500, 85000),
(47, 4500, 85000),
(48, 4500, 85000),
(49, 4500, 85000),
(50, 4500, 85000),
(51, 4500, 85000),
(52, 4500, 85000),
(53, 4500, 85000),
(54, 4500, 85000),
(55, 4500, 85000),
(56, 4500, 85000),
(57, 4500, 85000),
(58, 4500, 85000),
(59, 4500, 85000),
(60, 4500, 85000),
(61, 4500, 85000),
(62, 4500, 85000),
(63, 4500, 85000),
(64, 4500, 85000),
(65, 4500, 85000),
(66, 4500, 85000),
(67, 4500, 85000),
(68, 4500, 85000),
(69, 4500, 85000),
(70, 4500, 85000),
(71, 4500, 85000),
(72, 4500, 85000),
(73, 4500, 85000);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tiket_vip`
--

CREATE TABLE `tiket_vip` (
  `id_tiket_vip` int(3) NOT NULL,
  `jumlah` int(4) NOT NULL,
  `harga` int(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data untuk tabel `tiket_vip`
--

INSERT INTO `tiket_vip` (`id_tiket_vip`, `jumlah`, `harga`) VALUES
(2, 1495, 145000),
(3, 1493, 145000),
(4, 1495, 145000),
(5, 1494, 145000),
(6, 1494, 145000),
(7, 1497, 145000),
(8, 1496, 145000),
(9, 1496, 145000),
(10, 1495, 145000),
(11, 1493, 145000),
(12, 1494, 145000),
(13, 1497, 145000),
(14, 1495, 145000),
(15, 1494, 145000),
(16, 1497, 145000),
(17, 1495, 145000),
(18, 1500, 145000),
(19, 1500, 145000),
(20, 1500, 145000),
(21, 1500, 145000),
(22, 1500, 145000),
(23, 1500, 145000),
(24, 1500, 145000),
(25, 1500, 145000),
(26, 1500, 145000),
(27, 1500, 145000),
(28, 1500, 145000),
(29, 1500, 145000),
(30, 1500, 145000),
(31, 1500, 145000),
(32, 1500, 145000),
(33, 1500, 145000),
(34, 1500, 145000),
(35, 1500, 145000),
(36, 1500, 145000),
(37, 1500, 145000),
(38, 1500, 145000),
(39, 1500, 145000),
(40, 1500, 145000),
(41, 1500, 145000),
(42, 1500, 145000),
(43, 1500, 145000),
(44, 1500, 145000),
(45, 1500, 145000),
(46, 1500, 145000),
(47, 1500, 145000),
(48, 1500, 145000),
(49, 1500, 145000),
(50, 1500, 145000),
(51, 1500, 145000),
(52, 1500, 145000),
(53, 1500, 145000),
(54, 1500, 145000),
(55, 1500, 145000),
(56, 1500, 145000),
(57, 1500, 145000),
(58, 1500, 145000),
(59, 1500, 145000),
(60, 1500, 145000),
(61, 1500, 145000),
(62, 1500, 145000),
(63, 1500, 145000),
(64, 1500, 145000),
(65, 1500, 145000),
(66, 1500, 145000),
(67, 1500, 145000),
(68, 1500, 145000),
(69, 1500, 145000),
(70, 1500, 145000),
(71, 1500, 145000),
(72, 1500, 145000),
(73, 1500, 145000);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tim`
--

CREATE TABLE `tim` (
  `id_tim` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `peringkat` int(2) NOT NULL,
  `logo` varchar(255) NOT NULL,
  `match_point` int(3) NOT NULL,
  `match_wl` varchar(6) NOT NULL,
  `net_game_win` int(3) NOT NULL,
  `game_wl` varchar(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `tim`
--

INSERT INTO `tim` (`id_tim`, `nama`, `peringkat`, `logo`, `match_point`, `match_wl`, `net_game_win`, `game_wl`) VALUES
(3, 'RRQ HOSHI', 1, 'uploads/logos/logo_68678f949c665.png', 12, '12-4', 11, '25-14'),
(4, 'GEEK FAM', 2, 'uploads/logos/logo_686790a36dee6.png', 11, '11-5', 8, '25-17'),
(5, 'ONIC', 3, 'uploads/logos/logo_686790b05c92f.png', 10, '10-6', 7, '24-17'),
(6, 'BIGETRON ESPORTS', 4, 'uploads/logos/logo_686790bf94bc0.png', 9, '9-7', 5, '23-18'),
(7, 'ALTER EGO ESPORTS', 5, 'uploads/logos/logo_686790d5701fe.png', 9, '9-7', 4, '22-18'),
(8, 'TEAM LIQUID ID', 6, 'uploads/logos/logo_6867910a37925.png', 9, '9-7', 2, '22-20'),
(9, 'EVOS', 7, 'uploads/logos/logo_686791142d303.png', 7, '7-9', -2, '19-21'),
(10, 'DEWA UNITED ESPORTS', 8, 'uploads/logos/logo_6867912605d89.png', 5, '5-11', -9, '15-24'),
(11, 'NAVI', 9, 'uploads/logos/logo_6867913068e29.png', 0, '0-16', -26, '6-32');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user`
--

CREATE TABLE `user` (
  `id_user` int(11) NOT NULL,
  `nama` varchar(50) NOT NULL,
  `no_telepon` int(13) NOT NULL,
  `email` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user`
--

INSERT INTO `user` (`id_user`, `nama`, `no_telepon`, `email`) VALUES
(19, 'Muhammad Omarzada Nadiluhung Nugroho', 2147483647, 'a@gmail.com');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `akun`
--
ALTER TABLE `akun`
  ADD PRIMARY KEY (`id_akun`);

--
-- Indeks untuk tabel `penjualan`
--
ALTER TABLE `penjualan`
  ADD PRIMARY KEY (`id_penjualan`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_pertandingan` (`id_pertandingan`);

--
-- Indeks untuk tabel `pertandingan`
--
ALTER TABLE `pertandingan`
  ADD PRIMARY KEY (`id_pertandingan`),
  ADD KEY `id_tiket_reguler` (`id_tiket_reguler`),
  ADD KEY `id_tiket_vip` (`id_tiket_vip`),
  ADD KEY `id_tim1` (`id_tim1`),
  ADD KEY `id_tim2` (`id_tim2`);

--
-- Indeks untuk tabel `tiket_reguler`
--
ALTER TABLE `tiket_reguler`
  ADD PRIMARY KEY (`id_tiket_reguler`);

--
-- Indeks untuk tabel `tiket_vip`
--
ALTER TABLE `tiket_vip`
  ADD PRIMARY KEY (`id_tiket_vip`);

--
-- Indeks untuk tabel `tim`
--
ALTER TABLE `tim`
  ADD PRIMARY KEY (`id_tim`);

--
-- Indeks untuk tabel `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `akun`
--
ALTER TABLE `akun`
  MODIFY `id_akun` int(3) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT untuk tabel `penjualan`
--
ALTER TABLE `penjualan`
  MODIFY `id_penjualan` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=283;

--
-- AUTO_INCREMENT untuk tabel `pertandingan`
--
ALTER TABLE `pertandingan`
  MODIFY `id_pertandingan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT untuk tabel `tiket_reguler`
--
ALTER TABLE `tiket_reguler`
  MODIFY `id_tiket_reguler` int(3) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT untuk tabel `tiket_vip`
--
ALTER TABLE `tiket_vip`
  MODIFY `id_tiket_vip` int(3) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT untuk tabel `tim`
--
ALTER TABLE `tim`
  MODIFY `id_tim` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `user`
--
ALTER TABLE `user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `penjualan`
--
ALTER TABLE `penjualan`
  ADD CONSTRAINT `penjualan_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`),
  ADD CONSTRAINT `penjualan_ibfk_2` FOREIGN KEY (`id_pertandingan`) REFERENCES `pertandingan` (`id_pertandingan`);

--
-- Ketidakleluasaan untuk tabel `pertandingan`
--
ALTER TABLE `pertandingan`
  ADD CONSTRAINT `pertandingan_ibfk_1` FOREIGN KEY (`id_tiket_reguler`) REFERENCES `tiket_reguler` (`id_tiket_reguler`),
  ADD CONSTRAINT `pertandingan_ibfk_2` FOREIGN KEY (`id_tiket_vip`) REFERENCES `tiket_vip` (`id_tiket_vip`),
  ADD CONSTRAINT `pertandingan_ibfk_3` FOREIGN KEY (`id_tim1`) REFERENCES `tim` (`id_tim`),
  ADD CONSTRAINT `pertandingan_ibfk_4` FOREIGN KEY (`id_tim2`) REFERENCES `tim` (`id_tim`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
