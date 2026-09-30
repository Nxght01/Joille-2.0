-- ---------------------------------------------------------------------------
-- Migration 001 — alinhamento do schema do Joille com o padrão do acme-3am
--
-- O que esta migration faz:
--   1. cria a tabela `users_types` (1 = ADMIN, 2 = CLIENT), espelhando o acme;
--   2. adiciona `users.type_id` e `users.active`, exigidos por Api::authToken()
--      e por User::login()/permissionVerify();
--   3. adiciona `active` em `categories` e `services`, exigido por
--      Model::softDeleteById();
--   4. cria a tabela `types` (tipos de FAQ) e adiciona `questions.types_id`
--      e `questions.active`, espelhando faqs/faqs_categories do acme.
--
-- Como executar:
--   mysql -u root joille < "banco de dados atualizado/migration-001-acme-parity.sql"
--   (ou cole o conteúdo na aba SQL do phpMyAdmin, com o banco `joille` selecionado)
--
-- A migration é idempotente nas partes que criam tabelas, mas os ALTER TABLE
-- falham se executados duas vezes — rode uma única vez.
-- ---------------------------------------------------------------------------

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

USE `joille`;

-- ---------------------------------------------------------------------------
-- 1. Tipos de usuário
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `users_types` (`id`, `name`, `active`) VALUES
(1, 'ADMIN', 1),
(2, 'CLIENT', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ---------------------------------------------------------------------------
-- 2. users.type_id e users.active
-- ---------------------------------------------------------------------------

ALTER TABLE `users`
  ADD COLUMN `type_id` int(11) NOT NULL DEFAULT 2 AFTER `id`,
  ADD COLUMN `active` tinyint(1) NOT NULL DEFAULT 1;

-- Os usuários que já existiam viram clientes ativos
UPDATE `users` SET `type_id` = 2, `active` = 1;

ALTER TABLE `users`
  ADD KEY `fk_users_users_types_idx` (`type_id`),
  ADD CONSTRAINT `fk_users_users_types` FOREIGN KEY (`type_id`) REFERENCES `users_types` (`id`);

-- Administrador inicial — e-mail: admin@joille.com / senha: admin@123
-- TROQUE ESTA SENHA APÓS O PRIMEIRO LOGIN.
INSERT INTO `users` (`type_id`, `status_id`, `services_id`, `name`, `email`, `password`, `photo`, `active`)
VALUES (1, NULL, NULL, 'Administrador', 'admin@joille.com', '$2y$10$P9F7N0FLcGIKoAvyqEilJeWUrli1UC9ZRC0SjqNPNyqMugtMvH27i', NULL, 1);

-- ---------------------------------------------------------------------------
-- 3. active em categories e services (soft delete)
-- ---------------------------------------------------------------------------

ALTER TABLE `categories`
  ADD COLUMN `active` tinyint(1) NOT NULL DEFAULT 1;

ALTER TABLE `services`
  ADD COLUMN `active` tinyint(1) NOT NULL DEFAULT 1;

UPDATE `categories` SET `active` = 1;
UPDATE `services` SET `active` = 1;

-- ---------------------------------------------------------------------------
-- 4. Tipos de FAQ + questions.types_id / questions.active
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `description` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

INSERT INTO `types` (`id`, `description`, `active`) VALUES
(1, 'Geral', 1),
(2, 'Conta e Acesso', 1),
(3, 'Serviços', 1)
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

ALTER TABLE `questions`
  ADD COLUMN `types_id` int(11) NOT NULL DEFAULT 1 AFTER `id`,
  ADD COLUMN `active` tinyint(1) NOT NULL DEFAULT 1;

UPDATE `questions` SET `types_id` = 1, `active` = 1;

ALTER TABLE `questions`
  ADD KEY `fk_questions_types_idx` (`types_id`),
  ADD CONSTRAINT `fk_questions_types` FOREIGN KEY (`types_id`) REFERENCES `types` (`id`);

COMMIT;
