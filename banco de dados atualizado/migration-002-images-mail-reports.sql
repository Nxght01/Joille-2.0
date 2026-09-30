-- ---------------------------------------------------------------------------
-- Migration 002 — imagens da entidade principal, recuperação de senha e contatos
--
-- O que esta migration faz:
--   1. cria `service_images`: as N imagens da entidade principal (services),
--      com marcação de capa (`cover`) — exigido pela Etapa 07;
--   2. cria `password_resets`: tokens de uso único do fluxo "esqueci minha
--      senha" enviado por e-mail (PHPMailer) — Etapa 07.1;
--   3. ajusta `contacts` para receber as mensagens do formulário público
--      (visitante sem login), guardando data e status de leitura.
--
-- Como executar:
--   mysql -u root joille < "banco de dados atualizado/migration-002-images-mail-reports.sql"
--   (ou cole o conteúdo na aba SQL do phpMyAdmin, com o banco `joille` selecionado)
--
-- Rode uma única vez: os CREATE TABLE são idempotentes, os ALTER TABLE não.
-- ---------------------------------------------------------------------------

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

USE `joille`;

-- ---------------------------------------------------------------------------
-- 1. Imagens da entidade principal (N imagens por serviço)
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `service_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `services_id` int(11) NOT NULL,
  `path` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `cover` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_service_images_services_idx` (`services_id`),
  CONSTRAINT `fk_service_images_services`
    FOREIGN KEY (`services_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ---------------------------------------------------------------------------
-- 2. Recuperação de senha por e-mail
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_id` int(11) NOT NULL,
  `token` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_password_resets_token` (`token`),
  KEY `fk_password_resets_users_idx` (`users_id`),
  CONSTRAINT `fk_password_resets_users`
    FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- ---------------------------------------------------------------------------
-- 3. Mensagens do formulário público de contato
-- ---------------------------------------------------------------------------

-- O formulário da área pública é anônimo, então `users_id` passa a ser opcional.
ALTER TABLE `contacts`
  MODIFY COLUMN `users_id` int(11) DEFAULT NULL,
  ADD COLUMN `subject` varchar(255) DEFAULT NULL AFTER `contactName`,
  ADD COLUMN `answered` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN `created_at` timestamp NULL DEFAULT current_timestamp();

COMMIT;
