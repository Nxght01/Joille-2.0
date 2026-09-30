# source/Core/

Infraestrutura compartilhada por toda a aplicação.

## Connect.php

Singleton de conexão PDO. Lê as constantes `CONF_DB_*` de `source/Config/Config.php`.
Em caso de falha devolve um JSON `500` e encerra a execução.

Opções fixadas: `FETCH_OBJ`, `ERRMODE_EXCEPTION`, `CASE_NATURAL`, `SET NAMES utf8`.

## Model.php

Classe abstrata com o CRUD genérico. Cada model concreto declara apenas:

```php
$this->table      = 'services';   // nome da tabela
$this->primaryKey = 'id';         // chave primária
$this->fillable   = ['categoriesId', 'name', 'active'];  // em camelCase
```

O `Model` converte `camelCase` <-> `snake_case` automaticamente
(`categoriesId` <-> `categories_id`) e lê/escreve os valores pelos getters e setters
do model. Campos `null` são ignorados, o que permite update parcial.

| Método | Descrição |
|--------|-----------|
| `selectById(int $id): bool`        | Hidrata o próprio objeto; `false` se não achar. |
| `selectAll(array $filters = [])`   | Filtros são strings SQL unidas por `AND`. |
| `selectPaginator($page, $perPage)` | Devolve `page`, `perPage`, `total` e `data`. |
| `insert(): bool`                   | Monta o INSERT a partir de `$fillable`; grava o novo id no objeto. |
| `updateById(int $id): bool`        | Monta o UPDATE a partir de `$fillable`. |
| `deleteById(int $id): bool`        | Exclusão física. |
| `softDeleteById(int $id): bool`    | `SET active = 0` — exige a coluna `active` na tabela. |
| `getErrorMessage(): ?string`       | Mensagem do último erro. |
| `getErrorState(): ?string`         | SQLSTATE da última falha de banco. |
| `isDuplicateEntry(): bool`         | A última falha foi violação de índice único (SQLSTATE `23000`)? |

`isDuplicateEntry()` é o que permite ao controlador separar "o cliente mandou um
valor que já existe" (**400**) de "o banco falhou" (**500**). Faz diferença desde a
migration 003, que colocou `UNIQUE` em `users.email`: entre a checagem em PHP e o
`INSERT` cabe outra requisição com o mesmo e-mail, e nesse caso quem recusa é o banco.

## JWTToken.php

`encode(array $payload): string` e `decode(string $token): bool|object`.
Algoritmo `HS512`, validade de 90 minutos, emissor validado contra `CONF_URL_BASE`.
A chave fica em `JWT_SECRET_KEY` (`source/Config/Config.php`).
