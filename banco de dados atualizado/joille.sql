-- ---------------------------------------------------------------------------
-- Banco de dados JOILLE — dump completo
--
-- Gerado a partir do banco local já com TODAS as migrations aplicadas:
--   001 — paridade com o padrão acme (users_types, type_id, active, types)
--   002 — service_images, password_resets e ajustes em contacts
--   003 — UNIQUE em users.email, índices de busca e COMMENT nas tabelas
--   004 a 006 — projetos, catálogo da agência e valor preliminar
--
-- Instalação nova em MySQL/MariaDB: execute o arquivo completo.
-- ATENÇÃO: a importação recria as tabelas e substitui os dados existentes.
-- Safe Updates é desativado somente nesta sessão durante a importação e
-- restaurado ao final. Não é necessário alterar preferências do Workbench.
--
-- Importar este arquivo é o caminho mais simples: ele já traz a estrutura final,
-- os índices, os comentários e os dados de referência (perfis de acesso, tipos de
-- FAQ, categorias e o usuário administrador).
--
--   mysql -u root < "banco de dados atualizado/joille.sql"
--
-- Quem JÁ tem o banco `joille` criado não deve reimportar: rode apenas as
-- migrations que ainda faltam, na ordem numérica.
--
-- Administrador inicial: admin@joille.com / admin@123  (troque após o 1º login)
--
-- MySQL Workbench: para abrir o modelo ER atualizado use
-- Database > Reverse Engineer e aponte para o banco `joille`. Os COMMENT das
-- tabelas e colunas aparecem no diagrama. Detalhes em README.md desta pasta.
-- ---------------------------------------------------------------------------


SET @JOILLE_IMPORT_SAFE_UPDATES = @@SESSION.SQL_SAFE_UPDATES;
SET SESSION SQL_SAFE_UPDATES = 0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `joille` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `joille`;
DROP TABLE IF EXISTS `service_requests`;
DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Nome da categoria. Unico entre os registros ATIVOS (regra na aplicacao).',
  `active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Exclusao logica: 1 = ativo, 0 = inativo. Inativa nao aparece na area publica.',
  PRIMARY KEY (`id`),
  KEY `idx_categories_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3 COMMENT='Categorias dos servicos do catalogo (ex.: Basico, Intermediario, Avancado).';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Basico',1),(2,'Intermediário',1),(3,'Avançado',1);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contacts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `users_id` int DEFAULT NULL,
  `contactEmaill` varchar(255) DEFAULT NULL,
  `contactName` varchar(255) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` mediumtext,
  `answered` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_contact_users_idx` (`users_id`),
  CONSTRAINT `fk_contact_users` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `contacts` WRITE;
/*!40000 ALTER TABLE `contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `contacts` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `users_id` int NOT NULL,
  `token` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_password_resets_token` (`token`),
  KEY `fk_password_resets_users_idx` (`users_id`),
  CONSTRAINT `fk_password_resets_users` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `price` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `types_id` int NOT NULL DEFAULT '1',
  `question` varchar(255) NOT NULL,
  `answer` mediumtext NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_questions_types_idx` (`types_id`),
  CONSTRAINT `fk_questions_types` FOREIGN KEY (`types_id`) REFERENCES `types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `questions` WRITE;
/*!40000 ALTER TABLE `questions` DISABLE KEYS */;
INSERT INTO `questions` VALUES (1,1,'O que é o sistema Joille?','O Joille é um sistema de gerenciamento desenvolvido para empresas de pequeno porte dos ramos de joalheria e bijuteria, com foco em organização e praticidade.','2025-12-17 21:41:57',1),(2,1,'Para quem o sistema é indicado?','O sistema é indicado para pequenos empreendedores que buscam uma solução simples e eficiente para gerenciar seus serviços.','2025-12-17 21:41:57',1),(3,1,'Preciso instalar algum programa para usar o Joille?','Não. O Joille é um sistema online e pode ser acessado diretamente pelo navegador.','2025-12-17 21:41:57',1),(4,1,'O sistema funciona em celulares e tablets?','Sim. O Joille é responsivo e pode ser utilizado em computadores, tablets e smartphones.','2025-12-17 21:41:57',1),(5,1,'Meus dados ficam seguros no sistema?','Sim. O sistema utiliza boas práticas de segurança para proteger os dados dos usuários.','2025-12-17 21:41:57',1),(6,1,'Como posso entrar em contato com a equipe?','Você pode utilizar a aba Contato disponível no site ou entrar em contato pelas redes sociais.','2025-12-17 21:41:57',1);
/*!40000 ALTER TABLE `questions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `service_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_images` (
  `id` int NOT NULL AUTO_INCREMENT,
  `services_id` int NOT NULL,
  `path` varchar(255) NOT NULL,
  `caption` varchar(255) DEFAULT NULL,
  `cover` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_service_images_services_idx` (`services_id`),
  CONSTRAINT `fk_service_images_services` FOREIGN KEY (`services_id`) REFERENCES `services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `service_images` WRITE;
/*!40000 ALTER TABLE `service_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `service_images` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `value` varchar(15) DEFAULT NULL,
  `categories_id` int NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_services_categories1_idx` (`categories_id`),
  KEY `idx_services_name` (`name`),
  CONSTRAINT `fk_services_categories1` FOREIGN KEY (`categories_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,'Polimento','Polimento de joias','150',1,1);
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `details` varchar(255) DEFAULT NULL,
  `endDate` varchar(255) DEFAULT NULL,
  `startDate` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `status` WRITE;
/*!40000 ALTER TABLE `status` DISABLE KEYS */;
/*!40000 ALTER TABLE `status` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `description` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `types` WRITE;
/*!40000 ALTER TABLE `types` DISABLE KEYS */;
INSERT INTO `types` VALUES (1,'Geral',1),(2,'Conta e Acesso',1),(3,'Serviços',1);
/*!40000 ALTER TABLE `types` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type_id` int NOT NULL DEFAULT '2' COMMENT 'Perfil de acesso (users_types). 1 = ADMIN, 2 = CLIENT.',
  `status_id` int DEFAULT NULL COMMENT 'Reservado: vinculo opcional com status. Nao utilizado pela aplicacao.',
  `services_id` int DEFAULT NULL COMMENT 'Reservado: vinculo opcional com services. Nao utilizado pela aplicacao.',
  `name` varchar(255) NOT NULL COMMENT 'Nome completo informado no cadastro.',
  `email` varchar(255) NOT NULL COMMENT 'E-mail de login. UNICO (uq_users_email) e comparado sem diferenciar maiusculas.',
  `password` varchar(255) NOT NULL COMMENT 'Hash gerado por password_hash(). NUNCA a senha em texto puro.',
  `photo` varchar(255) DEFAULT NULL COMMENT 'Caminho relativo da foto de perfil, ex.: uploads/images/2026/08/arquivo.jpg.',
  `active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Exclusao logica: 1 = ativo, 0 = inativo. Inativo nao consegue autenticar.',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `fk_users_status1_idx` (`status_id`),
  KEY `fk_users_services1_idx` (`services_id`),
  KEY `fk_users_users_types_idx` (`type_id`),
  CONSTRAINT `fk_users_services1` FOREIGN KEY (`services_id`) REFERENCES `services` (`id`),
  CONSTRAINT `fk_users_status1` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`),
  CONSTRAINT `fk_users_users_types` FOREIGN KEY (`type_id`) REFERENCES `users_types` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb3 COMMENT='Contas do sistema: administradores e clientes, separados por type_id.';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (26,1,NULL,NULL,'Administrador','admin@joille.com','$2y$10$P9F7N0FLcGIKoAvyqEilJeWUrli1UC9ZRC0SjqNPNyqMugtMvH27i',NULL,1);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users_categories` WRITE;
/*!40000 ALTER TABLE `users_categories` DISABLE KEYS */;
/*!40000 ALTER TABLE `users_categories` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'Nome do perfil: ADMIN ou CLIENT. Usado por Api::authToken().',
  `active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Exclusao logica: 1 = ativo, 0 = inativo.',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb3 COMMENT='Perfis de acesso. 1 = ADMIN (painel administrativo), 2 = CLIENT (area do cliente).';
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users_types` WRITE;
/*!40000 ALTER TABLE `users_types` DISABLE KEYS */;
INSERT INTO `users_types` VALUES (1,'ADMIN',1),(2,'CLIENT',1);
/*!40000 ALTER TABLE `users_types` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


-- Execute uma vez, depois das migrations 001 a 003. Preserva os dados existentes.
ALTER TABLE users ADD COLUMN address VARCHAR(255) DEFAULT NULL,
                  ADD COLUMN phone VARCHAR(30) DEFAULT NULL;

CREATE TABLE service_requests (
    id INT NOT NULL AUTO_INCREMENT,
    users_id INT NOT NULL,
    services_id INT NOT NULL,
    description TEXT NOT NULL,
    quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL,
    status ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
    admin_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_requests_user (users_id, created_at),
    CONSTRAINT fk_requests_user FOREIGN KEY (users_id) REFERENCES users(id),
    CONSTRAINT fk_requests_service FOREIGN KEY (services_id) REFERENCES services(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COMMENT='Solicitacoes dos clientes; preco capturado do catalogo no momento da criacao';


-- Catálogo atual: agência de tecnologia para empresas joalheiras.
-- Catálogo da agência Joille. Aplicar pelo script com backup; mantém solicitações e imagens anteriores.
UPDATE services SET active=0 WHERE name NOT IN ('Diagnóstico e projeto de infraestrutura','Implantação de redes e conectividade','Desenvolvimento de sistemas de gestão','Integração de máquinas e produção','Integração de sistemas e dados','Suporte e evolução contínua');
UPDATE categories SET active=0 WHERE name NOT IN ('Redes e infraestrutura','Sistemas de gestão','Integração e automação','Suporte e evolução');
INSERT INTO categories (name,active) SELECT 'Redes e infraestrutura',1 WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name='Redes e infraestrutura');
INSERT INTO categories (name,active) SELECT 'Sistemas de gestão',1 WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name='Sistemas de gestão');
INSERT INTO categories (name,active) SELECT 'Integração e automação',1 WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name='Integração e automação');
INSERT INTO categories (name,active) SELECT 'Suporte e evolução',1 WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name='Suporte e evolução');
INSERT INTO services (name,description,value,categories_id,active) SELECT 'Diagnóstico e projeto de infraestrutura','Mapeamento de áreas, máquinas e sistemas da empresa joalheira. Planejamento da rede física e digital, requisitos de acesso e etapas de implantação.','0',c.id,1 FROM categories c WHERE c.name='Redes e infraestrutura' AND NOT EXISTS (SELECT 1 FROM services WHERE name='Diagnóstico e projeto de infraestrutura') ORDER BY c.id LIMIT 1;
INSERT INTO services (name,description,value,categories_id,active) SELECT 'Implantação de redes e conectividade','Projeto e implantação de cabeamento, redes locais, Wi-Fi corporativo e conexão entre unidades. Dimensionamento após avaliação técnica do ambiente.','0',c.id,1 FROM categories c WHERE c.name='Redes e infraestrutura' AND NOT EXISTS (SELECT 1 FROM services WHERE name='Implantação de redes e conectividade') ORDER BY c.id LIMIT 1;
INSERT INTO services (name,description,value,categories_id,active) SELECT 'Desenvolvimento de sistemas de gestão','Criação de sistemas web para integrar produção, estoque, comercial e administração de empresas joalheiras, com módulos definidos conforme o processo de cada negócio.','0',c.id,1 FROM categories c WHERE c.name='Sistemas de gestão' AND NOT EXISTS (SELECT 1 FROM services WHERE name='Desenvolvimento de sistemas de gestão') ORDER BY c.id LIMIT 1;
INSERT INTO services (name,description,value,categories_id,active) SELECT 'Integração de máquinas e produção','Conexão de máquinas e coleta de dados para acompanhar a operação. Compatibilidade, interfaces e requisitos de segurança avaliados antes da implantação.','0',c.id,1 FROM categories c WHERE c.name='Integração e automação' AND NOT EXISTS (SELECT 1 FROM services WHERE name='Integração de máquinas e produção') ORDER BY c.id LIMIT 1;
INSERT INTO services (name,description,value,categories_id,active) SELECT 'Integração de sistemas e dados','Integração entre ERP, sistemas internos e aplicações web por APIs e conectores. Organização do fluxo de informações entre setores e unidades da empresa.','0',c.id,1 FROM categories c WHERE c.name='Integração e automação' AND NOT EXISTS (SELECT 1 FROM services WHERE name='Integração de sistemas e dados') ORDER BY c.id LIMIT 1;
INSERT INTO services (name,description,value,categories_id,active) SELECT 'Suporte e evolução contínua','Manutenção, acompanhamento e evolução dos sistemas e redes implantados. Escopo, periodicidade e condições de suporte definidos em proposta.','0',c.id,1 FROM categories c WHERE c.name='Suporte e evolução' AND NOT EXISTS (SELECT 1 FROM services WHERE name='Suporte e evolução contínua') ORDER BY c.id LIMIT 1;
UPDATE questions SET active=0 WHERE question NOT IN ('O que a Joille oferece?','Como solicito um orçamento?','O envio do briefing confirma a contratação?','Vocês trabalham com redes físicas e sistemas digitais?','Qualquer máquina pode ser integrada?','Como acompanho meu projeto?');
INSERT INTO questions (types_id,question,answer,created_at,active) SELECT 1,'O que a Joille oferece?','A Joille é uma agência de tecnologia para o ramo joalheiro. Oferecemos criação de sistemas de gestão, infraestrutura de redes e integração de áreas, dados e máquinas.',NOW(),1 WHERE NOT EXISTS (SELECT 1 FROM questions WHERE question='O que a Joille oferece?');
INSERT INTO questions (types_id,question,answer,created_at,active) SELECT 1,'Como solicito um orçamento?','Crie sua conta, escolha uma solução e envie o briefing em Meus projetos. Informe a empresa, os locais envolvidos, as áreas e os sistemas ou máquinas que deseja integrar. A equipe analisa o escopo e entra em contato.',NOW(),1 WHERE NOT EXISTS (SELECT 1 FROM questions WHERE question='Como solicito um orçamento?');
INSERT INTO questions (types_id,question,answer,created_at,active) SELECT 1,'O envio do briefing confirma a contratação?','Não. Os serviços são sob orçamento. Preço, prazo, escopo e condições de suporte são definidos após a análise e precisam ser alinhados com sua empresa antes da execução.',NOW(),1 WHERE NOT EXISTS (SELECT 1 FROM questions WHERE question='O envio do briefing confirma a contratação?');
INSERT INTO questions (types_id,question,answer,created_at,active) SELECT 1,'Vocês trabalham com redes físicas e sistemas digitais?','Sim. O catálogo contempla planejamento e implantação de redes físicas, desenvolvimento de sistemas web e integração de dados. A necessidade de visitas presenciais é definida na análise do projeto.',NOW(),1 WHERE NOT EXISTS (SELECT 1 FROM questions WHERE question='Vocês trabalham com redes físicas e sistemas digitais?');
INSERT INTO questions (types_id,question,answer,created_at,active) SELECT 1,'Qualquer máquina pode ser integrada?','A viabilidade depende das interfaces, protocolos, documentação e condições de cada equipamento. A compatibilidade e os requisitos de segurança são avaliados antes de propor a integração.',NOW(),1 WHERE NOT EXISTS (SELECT 1 FROM questions WHERE question='Qualquer máquina pode ser integrada?');
INSERT INTO questions (types_id,question,answer,created_at,active) SELECT 1,'Como acompanho meu projeto?','Acesse Meus projetos na área do cliente para consultar o briefing, a situação e os retornos da equipe. As etapas são Em análise, Em execução e Entregue; projetos cancelados permanecem no histórico.',NOW(),1 WHERE NOT EXISTS (SELECT 1 FROM questions WHERE question='Como acompanho meu projeto?');

-- Valor total preliminar do projeto. NULL significa ainda não estimado.
ALTER TABLE service_requests
    ADD COLUMN preliminary_value DECIMAL(12,2) NULL DEFAULT NULL
    COMMENT 'Estimativa total do projeto definida pelo administrador';

SET SESSION SQL_SAFE_UPDATES = @JOILLE_IMPORT_SAFE_UPDATES;
