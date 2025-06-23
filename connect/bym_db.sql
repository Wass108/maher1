-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 23 juin 2025 à 18:26
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `bym_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`, `email`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$Cv6x7RA/iGyLI2MXKK4RAeHTy8u2LJwgG7DfY.Szn0OKWJtZPuEc.', 'admin@example.com', '2025-05-26 21:51:00', '2025-05-26 22:00:32');

-- --------------------------------------------------------

--
-- Structure de la table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `client` varchar(100) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `btnText` varchar(50) DEFAULT 'See Project',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `hidden` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `projects`
--

INSERT INTO `projects` (`id`, `slug`, `title`, `description`, `year`, `category`, `client`, `website`, `btnText`, `created_at`, `updated_at`, `featured`, `hidden`) VALUES
(1, 'atlantica', 'Atlantica Hotel', 'Description for Atlantica Hotel', 2018, 'hotels', 'Client A', 'http://example.com/atlantica', 'See Project', '2025-03-08 16:32:14', '2025-03-09 16:58:28', 1, 0),
(3, 'mezraya', 'Maison D\'hote Mezraya', 'Description for Maison D\'hote Mezraya', 2018, 'hotels', 'Client C', 'http://example.com/mezraya', 'See Project', '2025-03-08 16:32:14', '2025-03-09 13:39:07', 0, 0),
(4, 'hr', 'Houch Robbana', 'Description for Houch Robbana', 2018, 'loge', 'Client D', 'http://example.com/hr', 'See Project', '2025-03-08 16:32:14', '2025-03-09 16:59:50', 1, 0),
(5, '2s', '2s Tower', 'Description for 2s Tower', 2018, 'hotels', 'Client E', 'http://example.com/2s', 'See Project', '2025-03-08 16:32:14', '2025-03-09 13:43:43', 0, 0),
(7, 'hotel_marina', 'Hotel Marina', 'Description for Hotel Marina', 2018, 'loge', 'Client G', 'http://example.com/hotel_marina', 'See Project', '2025-03-08 16:32:14', '2025-03-09 13:46:29', 0, 0),
(8, 'le_port', 'Le Port', 'Description for Le Port', 2018, 'hotels', 'Client H', 'http://example.com/le_port', 'See Project', '2025-03-08 16:35:24', '2025-03-09 15:48:46', 0, 0),
(9, 'maison_hotes_djerba', 'Maison D\'hotes Djerba', 'Description for Maison D\'hotes Djerba', 2018, 'loge', 'Client I', 'http://example.com/maison_hotes_djerba', 'See Project', '2025-03-08 16:35:24', '2025-03-09 13:46:29', 0, 0),
(11, 'mby', 'MBY', 'Description for MBY', 2018, 'loge', 'Client K', 'http://example.com/mby', 'See Project', '2025-03-08 16:35:24', '2025-03-09 17:00:13', 1, 0),
(12, 'nby', 'NBY', 'Description for NBY', 2018, 'loge', 'Client L', 'http://example.com/nby', 'See Project', '2025-03-08 16:35:24', '2025-03-09 17:01:54', 1, 0),
(13, 'nuage', 'Nuage De point', 'Description for Nuage De point', 2018, 'bim', 'Client M', 'http://example.com/nuage', 'See Project', '2025-03-08 16:35:24', '2025-03-09 14:43:06', 0, 0),
(14, 'cameroun', 'Villa Cameroun', 'Description for Villa Cameroun', 2018, 'loge', 'Client N', 'http://example.com/cameroun', 'See Project', '2025-03-08 16:35:24', '2025-03-09 13:55:45', 0, 0),
(15, 'gab', 'Villa GAB', 'Description for Villa GAB', 2018, 'loge', 'Client O', 'http://example.com/gab', 'See Project', '2025-03-08 16:35:24', '2025-03-09 13:46:29', 0, 0),
(16, 'dakar', 'Villa Dakar', 'Description for Villa Dakar ', 2020, 'loge', 'Client P', 'http://example.com/dakar', 'See Project', '2025-03-08 16:35:24', '2025-03-09 13:46:29', 0, 0),
(17, 'greenpadel', 'GreenPadel ', 'Description for GreenPadel Arena', 2020, 'loge', 'Client Q', 'http://example.com/greenpadel', 'See Project', '2025-03-08 16:35:24', '2025-03-09 19:38:45', 0, 1),
(18, 'siege_orange', 'Siege Orange', 'Description for Siege Orange', 2020, 'loge', 'Client R', 'http://example.com/siege_orange', 'See Project', '2025-03-08 16:35:24', '2025-03-09 13:46:29', 0, 0),
(28, 'test', 'test', '', 0, 'loge', '', '', 'Voir le projet', '2025-06-23 11:43:42', '2025-06-23 11:43:42', 0, 0);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Index pour la table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
