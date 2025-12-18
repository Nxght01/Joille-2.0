-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 18/12/2025 às 19:45
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `joille`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Basico'),
(2, 'Intermediário'),
(3, 'Avançado');

-- --------------------------------------------------------

--
-- Estrutura para tabela `contacts`
--

CREATE TABLE `contacts` (
  `id` int(11) NOT NULL,
  `users_id` int(11) NOT NULL,
  `contactEmaill` varchar(255) DEFAULT NULL,
  `contactName` varchar(255) DEFAULT NULL,
  `message` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `price` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` mediumtext NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `questions`
--

INSERT INTO `questions` (`id`, `question`, `answer`, `created_at`) VALUES
(1, 'O que é o sistema Joille?', 'O Joille é um sistema de gerenciamento desenvolvido para empresas de pequeno porte dos ramos de joalheria e bijuteria, com foco em organização e praticidade.', '2025-12-17 21:41:57'),
(2, 'Para quem o sistema é indicado?', 'O sistema é indicado para pequenos empreendedores que buscam uma solução simples e eficiente para gerenciar seus serviços.', '2025-12-17 21:41:57'),
(3, 'Preciso instalar algum programa para usar o Joille?', 'Não. O Joille é um sistema online e pode ser acessado diretamente pelo navegador.', '2025-12-17 21:41:57'),
(4, 'O sistema funciona em celulares e tablets?', 'Sim. O Joille é responsivo e pode ser utilizado em computadores, tablets e smartphones.', '2025-12-17 21:41:57'),
(5, 'Meus dados ficam seguros no sistema?', 'Sim. O sistema utiliza boas práticas de segurança para proteger os dados dos usuários.', '2025-12-17 21:41:57'),
(6, 'Como posso entrar em contato com a equipe?', 'Você pode utilizar a aba Contato disponível no site ou entrar em contato pelas redes sociais.', '2025-12-17 21:41:57');

-- --------------------------------------------------------

--
-- Estrutura para tabela `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `value` varchar(15) DEFAULT NULL,
  `categories_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `value`, `categories_id`) VALUES
(4, 'D29 Estética', 'Arranhões tirados por Ariel Moreira, desertor do IFSUL', '300000', 1),
(7, 'D29 Estética 3', 'siena do Brenan', '1550', 3),
(8, 'teste ', 'teste para a apresentação', '15000', 2);

-- --------------------------------------------------------

--
-- Estrutura para tabela `status`
--

CREATE TABLE `status` (
  `id` int(11) NOT NULL,
  `details` varchar(255) DEFAULT NULL,
  `endDate` varchar(255) DEFAULT NULL,
  `startDate` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `status_id` int(11) DEFAULT NULL,
  `services_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `status_id`, `services_id`, `name`, `email`, `password`, `photo`) VALUES
(21, NULL, NULL, 'antonio', 'antonio@gmail.com', '$2y$10$14V.qO6ohHIGGq3GEeaHJ.goCYdrM6EtEHMfddaAMk1GuhXu/Yr..', 'uploads/images/2025/12/1c8fdfc18d634b9b0d8000638906bc0469444a6ad48f0.jpg'),
(25, NULL, NULL, 'eliasaaa', 'elias@gmail.com', '$2y$10$53rTFDeBUiiWjDMVCTv9nunH89X2SF2YzDS0soyBjDAQ/NOS3kAfW', 'uploads/images/2025/12/63327ead831a117b5faab1ade0e99d7769444a554df39.jpg');

-- --------------------------------------------------------

--
-- Estrutura para tabela `users_categories`
--

CREATE TABLE `users_categories` (
  `id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_contact_users_idx` (`users_id`);

--
-- Índices de tabela `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_services_categories1_idx` (`categories_id`);

--
-- Índices de tabela `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_users_status1_idx` (`status_id`),
  ADD KEY `fk_users_services1_idx` (`services_id`);

--
-- Índices de tabela `users_categories`
--
ALTER TABLE `users_categories`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `status`
--
ALTER TABLE `status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `users_categories`
--
ALTER TABLE `users_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `fk_contact_users` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`);

--
-- Restrições para tabelas `services`
--
ALTER TABLE `services`
  ADD CONSTRAINT `fk_services_categories1` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`);

--
-- Restrições para tabelas `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_services1` FOREIGN KEY (`services_id`) REFERENCES `services` (`id`),
  ADD CONSTRAINT `fk_users_status1` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
