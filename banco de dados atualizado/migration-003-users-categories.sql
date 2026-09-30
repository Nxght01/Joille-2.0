-- ---------------------------------------------------------------------------
-- Migration 003 — integridade de `users` e `categories`
--
-- A ESTRUTURA NÃO MUDA: nenhuma tabela, coluna ou tipo é criado, removido ou
-- renomeado. Esta migration só acrescenta o que faltava para o banco *garantir*
-- as regras que hoje existem apenas no PHP, e comentários que fazem o diagrama
-- do MySQL Workbench se explicar sozinho.
--
-- O que ela faz:
--   1. UNIQUE em `users.email` — a unicidade era checada só em PHP
--      (`User::emailInUse()`), então duas requisições simultâneas conseguiriam
--      gravar o mesmo e-mail. Agora o banco recusa.
--   2. Índice em `categories.name` e `services.name` — as verificações de
--      "nome já cadastrado" fazem `WHERE name = ?` e faziam varredura completa.
--   3. COMMENT nas tabelas e colunas de `users`, `users_types` e `categories`.
--
-- Por que `categories.name` NÃO é UNIQUE: a regra do sistema é "nome único entre
-- os registros ATIVOS" (`Category::nameInUse()` filtra `active = 1`), de propósito
-- — uma categoria inativada não deve impedir que o mesmo nome volte a ser usado.
-- Um UNIQUE simples proibiria isso e quebraria o comportamento atual, então aqui
-- entra apenas um índice de busca.
--
-- PRÉ-REQUISITO: não pode haver e-mail duplicado. Confira antes:
--     SELECT email, COUNT(*) c FROM users GROUP BY email HAVING c > 1;
-- Se a consulta devolver linhas, resolva os duplicados antes de rodar.
-- (Rodar `limpeza-dados-de-teste.sql` primeiro costuma resolver.)
--
-- Como executar:
--   mysql -u root joille < "banco de dados atualizado/migration-003-users-categories.sql"
--
-- Rode uma única vez: um ADD INDEX repetido dá erro de índice duplicado.
-- ---------------------------------------------------------------------------

USE `joille`;

START TRANSACTION;

-- ---------------------------------------------------------------------------
-- 1. Unicidade do e-mail no banco, não só na aplicação
-- ---------------------------------------------------------------------------

-- A collation da coluna é utf8mb3_general_ci (case-insensitive), então
-- "Ana@Joille.com" e "ana@joille.com" são tratados como o MESMO e-mail — que é
-- exatamente o que se espera de um login.
ALTER TABLE `users`
  ADD UNIQUE KEY `uq_users_email` (`email`);

-- ---------------------------------------------------------------------------
-- 2. Índices de busca por nome
-- ---------------------------------------------------------------------------

ALTER TABLE `categories`
  ADD KEY `idx_categories_name` (`name`);

ALTER TABLE `services`
  ADD KEY `idx_services_name` (`name`);

-- ---------------------------------------------------------------------------
-- 3. Documentação embutida no schema (aparece no MySQL Workbench)
-- ---------------------------------------------------------------------------

ALTER TABLE `users_types`
  COMMENT = 'Perfis de acesso. 1 = ADMIN (painel administrativo), 2 = CLIENT (area do cliente).',
  MODIFY COLUMN `name` varchar(255) NOT NULL
    COMMENT 'Nome do perfil: ADMIN ou CLIENT. Usado por Api::authToken().',
  MODIFY COLUMN `active` tinyint(1) NOT NULL DEFAULT 1
    COMMENT 'Exclusao logica: 1 = ativo, 0 = inativo.';

ALTER TABLE `users`
  COMMENT = 'Contas do sistema: administradores e clientes, separados por type_id.',
  MODIFY COLUMN `type_id` int(11) NOT NULL DEFAULT 2
    COMMENT 'Perfil de acesso (users_types). 1 = ADMIN, 2 = CLIENT.',
  MODIFY COLUMN `status_id` int(11) DEFAULT NULL
    COMMENT 'Reservado: vinculo opcional com status. Nao utilizado pela aplicacao.',
  MODIFY COLUMN `services_id` int(11) DEFAULT NULL
    COMMENT 'Reservado: vinculo opcional com services. Nao utilizado pela aplicacao.',
  MODIFY COLUMN `name` varchar(255) NOT NULL
    COMMENT 'Nome completo informado no cadastro.',
  MODIFY COLUMN `email` varchar(255) NOT NULL
    COMMENT 'E-mail de login. UNICO (uq_users_email) e comparado sem diferenciar maiusculas.',
  MODIFY COLUMN `password` varchar(255) NOT NULL
    COMMENT 'Hash gerado por password_hash(). NUNCA a senha em texto puro.',
  MODIFY COLUMN `photo` varchar(255) DEFAULT NULL
    COMMENT 'Caminho relativo da foto de perfil, ex.: uploads/images/2026/08/arquivo.jpg.',
  MODIFY COLUMN `active` tinyint(1) NOT NULL DEFAULT 1
    COMMENT 'Exclusao logica: 1 = ativo, 0 = inativo. Inativo nao consegue autenticar.';

ALTER TABLE `categories`
  COMMENT = 'Categorias dos servicos do catalogo (ex.: Basico, Intermediario, Avancado).',
  MODIFY COLUMN `name` varchar(255) NOT NULL
    COMMENT 'Nome da categoria. Unico entre os registros ATIVOS (regra na aplicacao).',
  MODIFY COLUMN `active` tinyint(1) NOT NULL DEFAULT 1
    COMMENT 'Exclusao logica: 1 = ativo, 0 = inativo. Inativa nao aparece na area publica.';

COMMIT;

-- Conferência dos índices criados
SHOW INDEX FROM `users` WHERE `Key_name` = 'uq_users_email';
