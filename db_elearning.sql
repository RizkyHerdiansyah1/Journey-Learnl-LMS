-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 18 Des 2025 pada 04.32
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
-- Database: `db_elearning`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `enrollment`
--

CREATE TABLE `enrollment` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `kelas_id` int(11) NOT NULL,
  `tanggal_enroll` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','completed','dropped') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `enrollment`
--

INSERT INTO `enrollment` (`id`, `user_id`, `kelas_id`, `tanggal_enroll`, `status`) VALUES
(7, 6, 4, '2025-12-18 03:25:50', 'active');

-- --------------------------------------------------------

--
-- Struktur dari tabel `hasil_kuis`
--

CREATE TABLE `hasil_kuis` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `materi_id` int(11) NOT NULL,
  `skor` int(11) NOT NULL,
  `total_soal` int(11) NOT NULL,
  `jawaban_detail` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`jawaban_detail`)),
  `tanggal_mengerjakan` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kelas`
--

CREATE TABLE `kelas` (
  `id` int(11) NOT NULL,
  `nama_kelas` varchar(150) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `dosen_id` int(11) NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `thumbnail` varchar(255) DEFAULT 'default-class.jpg',
  `total_courses` int(11) DEFAULT 0,
  `tanggal_dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kelas`
--

INSERT INTO `kelas` (`id`, `nama_kelas`, `deskripsi`, `dosen_id`, `kategori`, `thumbnail`, `total_courses`, `tanggal_dibuat`) VALUES
(1, 'Introduction to Web Development', 'Learn the fundamentals of HTML, CSS, and JavaScript', 1, 'Web Development', 'default-class.jpg', 12, '2025-12-04 02:43:19'),
(2, 'Data Structures & Algorithms', 'Master essential algorithms and data structures', 2, 'Computer Science', 'default-class.jpg', 15, '2025-12-04 02:43:19'),
(3, 'Etos Sandi', 'Etos sandi untuk mahasiswa kelas 2A', 1, 'Other', 'default-class.jpg', 0, '2025-12-14 09:54:49'),
(4, 'Matematika Diskrit', 'Mempelajari metode pembuktian, graf, relasi, dan pencacahan', 1, 'Other', 'default-class.jpg', 0, '2025-12-15 02:18:01');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kuis`
--

CREATE TABLE `kuis` (
  `id` int(11) NOT NULL,
  `materi_id` int(11) NOT NULL,
  `pertanyaan` text NOT NULL,
  `pilihan_a` varchar(255) NOT NULL,
  `pilihan_b` varchar(255) NOT NULL,
  `pilihan_c` varchar(255) NOT NULL,
  `pilihan_d` varchar(255) NOT NULL,
  `jawaban_benar` enum('A','B','C','D') NOT NULL,
  `poin` int(11) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kuis`
--

INSERT INTO `kuis` (`id`, `materi_id`, `pertanyaan`, `pilihan_a`, `pilihan_b`, `pilihan_c`, `pilihan_d`, `jawaban_benar`, `poin`) VALUES
(1, 2, 'Apa kepanjangan dari HTML?', 'Hyper Text Markup Language', 'High Tech Modern Language', 'Home Tool Markup Language', 'Hyperlinks and Text Markup Language', 'A', 10),
(2, 2, 'Tag mana yang digunakan untuk membuat heading terbesar?', '<h6>', '<h1>', '<heading>', '<head>', 'B', 10),
(3, 2, 'Atribut mana yang digunakan untuk link ke halaman lain?', 'src', 'link', 'href', 'url', 'C', 10),
(7, 26, 'Apakah relasi “membagi” pada himpunan bilangan bulat positif \\r\\nadalah refleksif?', 'Tiidak', 'Ya', 'Bisa, jika', 'Tidak, jika', 'D', 10),
(8, 26, 'Suatu relasi 𝑅 pada himpunan 𝐴 dikatakan simetris jika\\r\\nkapanpun  (b, 𝑎) ∈𝑅', 'Anti simetris', 'Simetris', 'reflektif', 'simetris', 'B', 10),
(9, 26, 'Tentukan komposit dari relasi 𝑅 dan 𝑆, dengan R adalah relasi dari \\r\\n{1, 2, 3} ke {1,2,3,4} dengan 𝑅 = {(1,1),(1,4),(2,3),(3,1),(3,4)}', 'Anti simetris', 'Simetris', 'reflektif', 'simetris', 'A', 10);

-- --------------------------------------------------------

--
-- Struktur dari tabel `materi`
--

CREATE TABLE `materi` (
  `id` int(11) NOT NULL,
  `kelas_id` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `tipe` enum('video','quiz','text') NOT NULL,
  `konten` text DEFAULT NULL,
  `urutan` int(11) NOT NULL,
  `durasi_menit` int(11) DEFAULT 0,
  `tanggal_dibuat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `materi`
--

INSERT INTO `materi` (`id`, `kelas_id`, `judul`, `tipe`, `konten`, `urutan`, `durasi_menit`, `tanggal_dibuat`) VALUES
(1, 1, 'Pengenalan HTML', 'video', 'https://www.youtube.com/embed/qz0aGYrrlhU', 1, 45, '2025-12-04 02:43:20'),
(2, 1, 'Quiz: HTML Basics', 'quiz', '', 2, 10, '2025-12-04 02:43:20'),
(3, 1, 'CSS Fundamentals', 'video', 'https://www.youtube.com/embed/1Rs2ND1ryYc', 3, 45, '2025-12-04 02:43:20'),
(24, 4, 'Relasi dan Sifat-sifatnya', 'video', 'https://www.youtube.com/embed/xrsSY2-4NsQ?si', 1, 30, '2025-12-15 02:21:00'),
(25, 4, 'Relasi dan Sifat-sifatnya', 'text', 'Definisi 1:\r\nDiberikan 𝐴 dan 𝐵 adalah himpunan. Relasi biner dari 𝐴 ke 𝐵 adalah \r\nhimpunan bagian dari A x B\r\n\r\nDefinisi 2:\r\nSuatu relasi pada himpunan 𝑨 adalah relasi dari 𝐴 ke 𝐴.\r\n\r\nDefinisi 3:\r\nSuatu relasi 𝑅 pada himpunan 𝐴 dikatakan refleksif jika\r\nuntuk setiap elemen 𝑎 ∈ 𝐴.', 2, 30, '2025-12-15 02:23:59'),
(26, 4, 'Relasi dan Sifat-sifatnya', 'quiz', '', 3, 30, '2025-12-15 02:24:11');

-- --------------------------------------------------------

--
-- Struktur dari tabel `progress`
--

CREATE TABLE `progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `materi_id` int(11) NOT NULL,
  `status` enum('locked','in_progress','completed') DEFAULT 'locked',
  `tanggal_mulai` timestamp NULL DEFAULT NULL,
  `tanggal_selesai` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `progress`
--

INSERT INTO `progress` (`id`, `user_id`, `materi_id`, `status`, `tanggal_mulai`, `tanggal_selesai`) VALUES
(32, 6, 24, 'completed', '2025-12-18 03:25:50', '2025-12-18 03:27:08'),
(33, 6, 25, 'completed', '2025-12-18 03:27:08', '2025-12-18 03:27:19'),
(34, 6, 26, 'in_progress', '2025-12-18 03:27:19', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('dosen','mahasiswa') NOT NULL,
  `foto_profil` varchar(255) DEFAULT 'default.jpg',
  `tanggal_daftar` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `foto_profil`, `tanggal_daftar`) VALUES
(1, 'Dr. Budi Santoso', 'budi.dosen@gmail.com', '$2y$10$uM9MrScOdrv.dEUDT6uBA.3uEQSdGpHVbMTtO7QzUBmCGiJ.2nF1S', 'dosen', 'default.jpg', '2025-12-04 02:43:17'),
(2, 'Prof. Siti Nurhaliza', 'siti.dosen@gmail.com', '$2y$10$uM9MrScOdrv.dEUDT6uBA.3uEQSdGpHVbMTtO7QzUBmCGiJ.2nF1S', 'dosen', 'default.jpg', '2025-12-04 02:43:17'),
(6, 'User', 'user.123@gmail.com', '$2y$10$/BR/e1S4IFk.ifcTLtuSK.163uJSt3Nk5qS060a9kfEkYJ.FGWqjG', 'mahasiswa', 'default.jpg', '2025-12-18 03:25:25');

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_mahasiswa_stats`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_mahasiswa_stats` (
`id` int(11)
,`nama` varchar(100)
,`enrolled_classes` bigint(21)
,`completed_materials` bigint(21)
,`total_materials` bigint(21)
,`average_progress` decimal(24,0)
,`hours_learned` decimal(32,0)
);

-- --------------------------------------------------------

--
-- Struktur untuk view `v_mahasiswa_stats`
--
DROP TABLE IF EXISTS `v_mahasiswa_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_mahasiswa_stats`  AS SELECT `u`.`id` AS `id`, `u`.`nama` AS `nama`, count(distinct `e`.`kelas_id`) AS `enrolled_classes`, count(distinct case when `p`.`status` = 'completed' then `p`.`materi_id` end) AS `completed_materials`, count(distinct `p`.`materi_id`) AS `total_materials`, round(count(distinct case when `p`.`status` = 'completed' then `p`.`materi_id` end) * 100.0 / nullif(count(distinct `p`.`materi_id`),0),0) AS `average_progress`, sum(case when `p`.`status` = 'completed' then `m`.`durasi_menit` else 0 end) AS `hours_learned` FROM (((`users` `u` left join `enrollment` `e` on(`u`.`id` = `e`.`user_id`)) left join `progress` `p` on(`u`.`id` = `p`.`user_id`)) left join `materi` `m` on(`p`.`materi_id` = `m`.`id`)) WHERE `u`.`role` = 'mahasiswa' GROUP BY `u`.`id`, `u`.`nama` ;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `enrollment`
--
ALTER TABLE `enrollment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`user_id`,`kelas_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_kelas` (`kelas_id`);

--
-- Indeks untuk tabel `hasil_kuis`
--
ALTER TABLE `hasil_kuis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_materi` (`materi_id`);

--
-- Indeks untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dosen` (`dosen_id`);

--
-- Indeks untuk tabel `kuis`
--
ALTER TABLE `kuis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_materi` (`materi_id`);

--
-- Indeks untuk tabel `materi`
--
ALTER TABLE `materi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kelas` (`kelas_id`),
  ADD KEY `idx_urutan` (`urutan`);

--
-- Indeks untuk tabel `progress`
--
ALTER TABLE `progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_progress` (`user_id`,`materi_id`),
  ADD KEY `materi_id` (`materi_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `enrollment`
--
ALTER TABLE `enrollment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `hasil_kuis`
--
ALTER TABLE `hasil_kuis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `kuis`
--
ALTER TABLE `kuis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `materi`
--
ALTER TABLE `materi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT untuk tabel `progress`
--
ALTER TABLE `progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `enrollment`
--
ALTER TABLE `enrollment`
  ADD CONSTRAINT `enrollment_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollment_ibfk_2` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `hasil_kuis`
--
ALTER TABLE `hasil_kuis`
  ADD CONSTRAINT `hasil_kuis_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hasil_kuis_ibfk_2` FOREIGN KEY (`materi_id`) REFERENCES `materi` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `kelas_ibfk_1` FOREIGN KEY (`dosen_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `kuis`
--
ALTER TABLE `kuis`
  ADD CONSTRAINT `kuis_ibfk_1` FOREIGN KEY (`materi_id`) REFERENCES `materi` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `materi`
--
ALTER TABLE `materi`
  ADD CONSTRAINT `materi_ibfk_1` FOREIGN KEY (`kelas_id`) REFERENCES `kelas` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `progress`
--
ALTER TABLE `progress`
  ADD CONSTRAINT `progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `progress_ibfk_2` FOREIGN KEY (`materi_id`) REFERENCES `materi` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
