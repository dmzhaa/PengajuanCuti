-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 19, 2026 at 03:29 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_pengajuan_cuti`
--

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_cuti`
--

CREATE TABLE `pengajuan_cuti` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `jenis_cuti` varchar(50) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `jumlah_hari` int NOT NULL,
  `alasan` text NOT NULL,
  `catatan_hrd` text,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pengajuan_cuti`
--

INSERT INTO `pengajuan_cuti` (`id`, `user_id`, `jenis_cuti`, `tanggal_mulai`, `tanggal_selesai`, `jumlah_hari`, `alasan`, `catatan_hrd`, `status`, `created_at`) VALUES
(2, 2, 'Cuti Tahunan', '2026-11-21', '2026-11-22', 1, 'Liburan', 'Selamat liburan', 'approved', '2026-11-18 10:19:08'),
(3, 2, 'Cuti Tahunan', '2026-01-01', '2026-01-02', 1, 'mau main GTA 6', 'no', 'rejected', '2026-11-18 12:36:08'),
(4, 2, 'Cuti Melahirkan', '2026-01-01', '2026-02-01', 31, 'Lahiran', 'Semoga lancar dan selamat', 'approved', '2026-11-19 02:25:15'),
(6, 2, 'Cuti Sakit', '2026-11-19', '2026-11-20', 1, 'Tolong kaki saya sakit', 'semoga lekas sembuh', 'approved', '2026-11-19 02:34:15');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `nik` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('karyawan','hrd') NOT NULL DEFAULT 'karyawan',
  `kuota_cuti` int NOT NULL DEFAULT '12'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nik`, `nama`, `password`, `role`, `kuota_cuti`) VALUES
(1, '123250206', 'Raditya Eka', '12345', 'karyawan', 12),
(2, '123250219', 'Dimas Hafizh', '12345', 'karyawan', 12),
(3, '123250000', 'Admin HRD', '12345', 'hrd', 12);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pengajuan_cuti`
--
ALTER TABLE `pengajuan_cuti`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nik` (`nik`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pengajuan_cuti`
--
ALTER TABLE `pengajuan_cuti`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pengajuan_cuti`
--
ALTER TABLE `pengajuan_cuti`
  ADD CONSTRAINT `pengajuan_cuti_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
