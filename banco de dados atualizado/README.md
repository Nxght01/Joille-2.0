# Banco de dados — Joille

MySQL / MariaDB, banco `joille`. Todo acesso passa por PDO
(`Source\Core\Connect`); nenhuma consulta é montada fora dos models.

## Arquivos desta pasta

| Arquivo | Para que serve |
|---------|----------------|
| `joille.sql` | **Dump completo.** Estrutura final + índices + comentários + dados de referência. É o ponto de partida de uma instalação nova. |
| `migration-001-acme-parity.sql` | `users_types`, `users.type_id`, `users.active`, `active` em categorias/serviços, tipos de FAQ. |
| `migration-002-images-mail-reports.sql` | `service_images`, `password_resets` e ajustes em `contacts`. |
| `migration-003-users-categories.sql` | `UNIQUE` em `users.email`, índices de busca por nome e `COMMENT` nas tabelas. |
| `limpeza-dados-de-teste.sql` | Apaga os registros criados pelo `api-test.php` (prefixo `ZZ `, usuários `zz.*`). |
| `backup-*.sql` | Backups tirados antes de cada migration. |
| `db-joille-diagram.mwb`, `db.mwb` | Modelos do Workbench **desatualizados** — veja a seção do Workbench. |

## Instalar do zero

```bash
mysql -u root < "banco de dados atualizado/joille.sql"
```

O dump usa `DROP TABLE IF EXISTS` em cada tabela: **não** o importe sobre um banco
que já tem dados que você quer manter. Nesse caso, rode só as migrations que faltam,
na ordem numérica.

Administrador inicial: `admin@joille.com` / `admin@123` — troque a senha após o
primeiro login.

## Entidades

| Tabela | Papel |
|--------|-------|
| `users` | Contas do sistema. `type_id` separa administrador (1) de cliente (2). |
| `users_types` | Perfis de acesso: ADMIN e CLIENT. Conferido em `Api::authToken()`. |
| `services` | **Entidade principal** — o catálogo de serviços. |
| `service_images` | As N imagens de cada serviço, uma marcada como capa. |
| `categories` | Categorias dos serviços. |
| `questions` / `types` | Perguntas frequentes e seus tipos. |
| `contacts` | Mensagens do formulário público. `users_id` é opcional (visitante anônimo). |
| `password_resets` | Tokens de uso único do "esqueci minha senha". |
| `payments`, `status`, `users_categories` | Herdadas do modelo original e **não usadas** pela aplicação. Mantidas para não alterar a estrutura entregue. |

## Regras que o banco garante

| Regra | Onde |
|-------|------|
| E-mail de usuário único (sem diferenciar maiúsculas) | `uq_users_email` |
| Token de recuperação único | `uq_password_resets_token` |
| Imagem sempre ligada a um serviço existente | FK `fk_service_images_services` (`ON DELETE CASCADE`) |
| Token sempre ligado a um usuário existente | FK `fk_password_resets_users` (`ON DELETE CASCADE`) |
| Serviço sempre em uma categoria existente | FK `fk_services_categories1` |
| Pergunta sempre em um tipo existente | FK `fk_questions_types` |

**Nome de categoria e de serviço** é único apenas entre os registros **ativos** —
essa regra fica na aplicação (`Category::nameInUse()`, `Service::nameInUse()`), e não
em um `UNIQUE`, de propósito: um registro inativado não deve impedir que o mesmo nome
volte a ser usado. Um `UNIQUE` simples proibiria isso.

## Exclusão lógica

Quase tudo usa `active = 0` em vez de `DELETE` (`Model::softDeleteById()`), para
preservar histórico. As exceções são `service_images` e `contacts`, apagadas de fato —
a imagem tem um arquivo no disco para remover junto, e uma mensagem excluída não tem
o que preservar.

Consequência: registros inativos continuam aparecendo nas telas do painel (com a
marca "inativo"), o que é intencional — é assim que se reativa um cadastro.

## Limpando o lixo dos testes

O `api-test.php` cria registros com prefixo `ZZ ` e usuários `zz.*`. Desde a
correção da suíte, ele **apaga tudo o que criou** ao terminar, e a última verificação
falha se algo sobrar. Para limpar resíduos de execuções antigas:

```bash
mysql -u root joille < "banco de dados atualizado/limpeza-dados-de-teste.sql"
```

## MySQL Workbench

Os arquivos `.mwb` desta pasta são de maio/2025 e **não** refletem o banco atual —
faltam `service_images`, `password_resets`, as colunas novas de `contacts` e os
índices da migration 003.

Para obter o diagrama ER atualizado, deixe o Workbench ler o banco:

1. **Database › Reverse Engineer…** (`Ctrl+R`)
2. escolha a conexão local (`root`, sem senha, porta 3306)
3. marque o schema **joille**
4. avance até o fim e clique em **Execute**

O Workbench monta o EER completo, com as chaves estrangeiras já ligadas. Os
`COMMENT` de tabela e coluna adicionados pela migration 003 aparecem no painel de
propriedades de cada objeto, então o diagrama se explica sozinho.

Depois disso é possível editar normalmente e, para levar as mudanças ao banco, usar
**Database › Synchronize Model…** — que mostra o SQL antes de aplicar. Salve o
modelo por cima de `db-joille-diagram.mwb` para substituir o arquivo antigo.

> Preferimos reverter-engenhar a manter o `.mwb` na mão: o `.mwb` é um arquivo
> binário, e um modelo editado fora do Workbench corre o risco de não abrir mais.
> O SQL desta pasta é a fonte da verdade.

## Charset

As tabelas usam `utf8mb3` (o antigo `utf8`), herdado do modelo original. Ele cobre
todo o português, inclusive acentos, mas não caracteres de 4 bytes como emoji — e
está marcado como *deprecated* no MySQL 8.4. Migrar para `utf8mb4` é recomendável
antes de um uso real, e não foi feito aqui para não alterar a estrutura entregue.

Na Etapa 07, a relação é `services.id → service_images.services_id` (1:N).
Cada linha contém metadados, caminho relativo, legenda, capa e data; os bytes ficam em disco.
O DELETE de serviço pela API inativa o pai e remove as linhas filhas numa transação,
depois limpa os arquivos. Não substitua esse fluxo por DELETE manual: a FK sozinha
não remove arquivos. A estrutura já está no dump e na migration 002; não reaplique a
migration em um banco que já a recebeu.
