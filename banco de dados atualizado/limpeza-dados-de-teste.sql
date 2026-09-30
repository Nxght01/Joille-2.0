-- ---------------------------------------------------------------------------
-- Limpeza dos dados de teste
--
-- O `api-test.php` cria registros com o prefixo `ZZ ` e usuários `zz.*`. Até a
-- correção deste script ele apenas os INATIVAVA (active = 0), então cada execução
-- deixava lixo permanente no banco — as telas de Usuários e Categorias do painel
-- chegaram a mostrar 33 usuários e 34 categorias de teste.
--
-- Este script apaga esses registros de verdade. É seguro rodar quantas vezes for
-- preciso e NÃO toca em nenhum dado real: só remove o que casa com os padrões de
-- teste (`ZZ %`, `zz.%@joille.com`, `%@joille.test`).
--
-- Como executar:
--   mysql -u root joille < "banco de dados atualizado/limpeza-dados-de-teste.sql"
--   (ou cole na aba SQL do phpMyAdmin, com o banco `joille` selecionado)
--
-- A ordem importa: os filhos saem antes dos pais, senão as chaves estrangeiras
-- bloqueiam a exclusão.
-- ---------------------------------------------------------------------------

USE `joille`;

START TRANSACTION;

-- 1. Imagens dos serviços de teste
--    (a FK já é ON DELETE CASCADE, mas ser explícito deixa o script legível)
DELETE FROM `service_images`
 WHERE `services_id` IN (SELECT `id` FROM `services` WHERE `name` LIKE 'ZZ %');

-- 2. Serviços de teste (precisam sair antes das categorias que eles referenciam)
DELETE FROM `services` WHERE `name` LIKE 'ZZ %';

-- 3. Categorias de teste
DELETE FROM `categories` WHERE `name` LIKE 'ZZ %';

-- 4. Perguntas de teste (antes dos tipos que elas referenciam)
DELETE FROM `questions` WHERE `question` LIKE 'ZZ %';

-- 5. Tipos de FAQ de teste
DELETE FROM `types` WHERE `description` LIKE 'ZZ %';

-- 6. Tokens de recuperação dos usuários de teste
--    (também é ON DELETE CASCADE; explícito por clareza)
DELETE FROM `password_resets`
 WHERE `users_id` IN (
     SELECT `id` FROM `users`
      WHERE `email` LIKE 'zz.%@joille.com' OR `email` LIKE '%@joille.test'
 );

-- 7. Mensagens de contato de teste
DELETE FROM `contacts`
 WHERE `contactEmaill` LIKE 'zz.%'
    OR `contactEmaill` LIKE '%@joille.test';

-- 8. Usuários de teste
--    O admin@joille.com NÃO casa com nenhum dos padrões e é preservado.
DELETE FROM `users`
 WHERE `email` LIKE 'zz.%@joille.com'
    OR `email` LIKE '%@joille.test';

COMMIT;

-- Conferência: o que sobrou deve ser só dado real
SELECT 'usuarios'   AS tabela, COUNT(*) AS restantes FROM `users`
UNION ALL SELECT 'categorias', COUNT(*) FROM `categories`
UNION ALL SELECT 'servicos',   COUNT(*) FROM `services`
UNION ALL SELECT 'perguntas',  COUNT(*) FROM `questions`
UNION ALL SELECT 'tipos_faq',  COUNT(*) FROM `types`
UNION ALL SELECT 'contatos',   COUNT(*) FROM `contacts`;
