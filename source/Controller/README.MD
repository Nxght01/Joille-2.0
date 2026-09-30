# source/Controller/

Controladores da API REST. Todos estendem `Api` e são referenciados por
`api/index.php` através do namespace `Source\Controller`.

## Api.php — classe base

### `authToken(int $typeId): bool`

Lê o token do header `token` ou `Authorization: Bearer <token>`, decodifica com
`Source\Core\JWTToken` e confere, contra o banco, se o usuário do payload
realmente possui aquele `type_id` e está ativo (`User::permissionVerify()`).
Em caso de sucesso preenche `$this->userAuthId`.

Tipos: **1 = ADMIN**, **2 = CLIENT** (tabela `users_types`).

### `call()` e `back()` — resposta padronizada

```php
$this->call(200, "success", "Lista de Serviços", "success")->back($data);
```

Produz sempre o mesmo envelope:

```json
{
    "code": 200,
    "type": "success",
    "status": "success",
    "message": "Lista de Serviços",
    "data": []
}
```

`back()` sem argumento omite a chave `data`.

## Controladores

| Classe          | Recurso           | Rotas protegidas |
|-----------------|-------------------|------------------|
| `Users`         | `/users`          | tudo, exceto `register`, `login` e `login-admin` |
| `Services`      | `/services`       | escrita e `admin/list` (ADMIN) |
| `ServiceImages` | `/service-images` | escrita (ADMIN); a listagem é pública |
| `Categories`    | `/categories`     | escrita e `admin/list` (ADMIN) |
| `Faqs`          | `/faqs`           | escrita e `admin/list` (ADMIN) |
| `FaqsTypes`     | `/faqs-types`     | escrita (ADMIN) |
| `Passwords`     | `/passwords`      | nenhuma — quem esqueceu a senha não consegue logar |
| `Contacts`      | `/contacts`       | tudo, exceto o `POST` do formulário público |
| `Reports`       | `/reports`        | tudo (ADMIN) |

## Convenções

- O primeiro parâmetro de toda action é `array $data`, montado pelo CoffeeCode Router
  a partir dos parâmetros de rota, da query string e do corpo da requisição.
- Validar antes de tocar no model; devolver `400 bad_request` com a mensagem do campo.
- `401 unauthorized` sempre vem primeiro, antes de qualquer validação de campo.
- Exclusões usam `softDeleteById()` (`active = 0`), não `deleteById()`. Exceções:
  imagens e mensagens de contato são apagadas de fato — não há o que preservar, e a
  imagem tem um arquivo no disco para remover junto.

## Casos que fogem do envelope

`Reports::servicesPdf()`, `usersPdf()` e `faqsPdf()` respondem
`Content-Type: application/pdf` com o arquivo. Se a renderização falhar, o
controlador volta ao envelope JSON com `500` (`streamOrFail()`) — nunca um PDF
truncado.

## Efeitos colaterais que não podem derrubar a requisição

Quando uma action grava algo **e** manda um e-mail, a gravação vem primeiro e a falha
de SMTP não invalida o resultado:

- `Contacts::insert()` responde `201` mesmo sem SMTP, com `data.mail_delivered = false`
  — a mensagem do visitante já está no banco;
- `Passwords::forgot()` faz o contrário de propósito: se o e-mail não sai, o token
  recém-criado é invalidado, para não deixar um link válido que ninguém recebeu.
