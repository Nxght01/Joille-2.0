# API Joille

API REST do projeto Joille. Roteamento por `coffeecode/router`, autenticação por JWT
(`firebase/php-jwt`), acesso a dados por PDO.

- **Base URL:** `http://localhost/projeto/joille2/api` (definida por `CONF_URL_BASE`)
- **Controladores:** `source/Controller/`
- **Autenticação:** header `token: <jwt>` ou `Authorization: Bearer <jwt>`
- **Tipos de usuário:** `1` = ADMIN, `2` = CLIENT

## Envelope de resposta

Toda resposta — de sucesso ou de erro — usa o mesmo formato:

```json
{
    "code": 200,
    "type": "success",
    "status": "success",
    "message": "Lista de Serviços",
    "data": []
}
```

`data` só aparece quando há conteúdo a devolver.

## Códigos de status

| Código | `status`                | Quando |
|--------|-------------------------|--------|
| 200 | `success`               | Requisição processada |
| 201 | `created`               | Registro criado |
| 400 | `bad_request`           | Campo ausente ou inválido |
| 401 | `unauthorized`          | Sem token ou token inválido/expirado |
| 403 | `forbidden`             | Token válido, mas de um perfil sem permissão para a rota |
| 404 | `not_found`             | Recurso ou rota inexistente |
| 409 | `conflict`              | Token de recuperação de senha já consumido |
| 500 | `internal_server_error` | Falha no banco ou erro inesperado |
| 502 | `bad_gateway`           | Falha no envio do e-mail (SMTP indisponível) |

As rotas de relatório em PDF são a única exceção ao envelope: quando dão certo,
respondem `Content-Type: application/pdf` com o arquivo. Se falharem, voltam ao
envelope JSON normal.

**E-mail já cadastrado é 400, não 500.** O cliente consegue corrigir mandando outro
endereço, então é erro de requisição. Vale tanto para `register`/`register-admin`
quanto para `update`/`update-admin` — antes o cadastro devolvia 500 e a atualização
400 para a mesma condição. A unicidade é garantida pelo índice `uq_users_email`
(migration 003), e não só pela checagem em PHP: a comparação ignora maiúsculas, então
`Ana@Joille.com` colide com `ana@joille.com`.

## Rotas

### Usuários — `/users`

| Método | Rota | Auth | Descrição |
|--------|------|------|-----------|
| POST   | `/users/register`        | —      | Cadastra cliente (`type_id` 2). E-mail repetido devolve **400** |
| POST   | `/users/login`           | —      | Login de cliente; devolve o token |
| POST   | `/users/login-admin`     | —      | Login de administrador |
| POST   | `/users/register-admin`  | ADMIN  | Cadastra administrador |
| GET    | `/users/token-validate`  | CLIENT | Confere se o token ainda vale |
| GET    | `/users/me`              | CLIENT | Dados do usuário autenticado |
| PUT    | `/users/update`          | CLIENT | Atualiza nome e e-mail |
| PUT    | `/users/password`        | CLIENT | Troca a senha |
| POST   | `/users/photo`           | CLIENT | Envia nova foto (multipart) |
| GET    | `/users/admin/list`      | ADMIN  | Lista todos os usuários |
| GET    | `/users/admin/{userId}`  | ADMIN  | Busca por id |
| PUT    | `/users/admin/{userId}`  | ADMIN  | Atualiza usuário |
| DELETE | `/users/admin/{userId}`  | ADMIN  | Desativa usuário (soft delete) |

### Serviços — `/services` (entidade principal)

| Método | Rota | Auth | Descrição |
|--------|------|------|-----------|
| GET    | `/services/list`                             | —     | Lista os ativos, com o nome da categoria |
| GET    | `/services/list/{serviceId}`                 | —     | Busca por id |
| GET    | `/services/list/paginator/{page}/{per_page}` | —     | Lista paginada |
| POST   | `/services`                                  | ADMIN | Cadastra |
| PUT    | `/services/{serviceId}`                      | ADMIN | Atualiza (parcial) |
| DELETE | `/services/{serviceId}`                      | ADMIN | Desativa (soft delete) |
| GET    | `/services`                                  | —     | Atalho de `/services/list` |
| GET    | `/services/{serviceId}`                      | —     | Atalho de `/services/list/{serviceId}` |
| GET    | `/services/paginator/{page}/{per_page}`      | —     | Atalho da rota paginada |
| GET    | `/services/admin/list`                       | ADMIN | Lista todos, inclusive inativos |
| GET    | `/services/category/{categoryId}`            | —     | Lista por categoria |

O payload de um serviço traz também `cover_image` (caminho relativo da imagem de
capa, ou `null`) e `images_count`, para que a listagem já possa exibir a foto sem
uma segunda requisição por serviço.

### Imagens dos serviços — `/service-images` (Etapa 07)

Cada serviço aceita até **10 imagens**. A primeira enviada vira a capa
automaticamente; ao excluir a capa, a mais antiga restante assume o posto.

| Método | Rota | Auth | Descrição |
|--------|------|------|-----------|
| GET    | `/service-images/{serviceId}`                    | —     | Imagens do serviço, capa primeiro |
| POST   | `/service-images/{serviceId}`                    | ADMIN | Envia 1..N imagens (multipart, campo `images[]`) |
| PUT    | `/service-images/{serviceId}/cover/{imageId}`    | ADMIN | Define a capa |
| DELETE | `/service-images/{serviceId}/{imageId}`          | ADMIN | Exclui a imagem (registro + arquivo) |

Validação do upload: JPG/JPEG ou PNG (tipo real do arquivo, não o declarado pelo
navegador), no máximo 4 MB cada, redimensionadas para 1600px de largura.

### Categorias — `/categories` (segunda entidade)

| Método | Rota | Auth |
|--------|------|------|
| GET    | `/categories/list`                             | — |
| GET    | `/categories/list/{categoryId}`                | — |
| GET    | `/categories/list/paginator/{page}/{per_page}` | — |
| POST   | `/categories`                                  | ADMIN |
| PUT    | `/categories/{categoryId}`                     | ADMIN |
| DELETE | `/categories/{categoryId}`                     | ADMIN |
| GET    | `/categories`                                  | — |
| GET    | `/categories/{categoryId}`                     | — |
| GET    | `/categories/admin/list`                       | ADMIN |

### FAQs — `/faqs` e `/faqs-types`

| Método | Rota | Auth |
|--------|------|------|
| GET    | `/faqs`                | — |
| GET    | `/faqs/admin/list`     | ADMIN |
| GET    | `/faqs/type/{typeId}`  | — |
| GET    | `/faqs/{faqId}`        | — |
| POST   | `/faqs`                | ADMIN |
| PUT    | `/faqs/{faqId}`        | ADMIN |
| DELETE | `/faqs/{faqId}`        | ADMIN |
| GET    | `/faqs-types`          | — |
| GET    | `/faqs-types/{typeId}` | — |
| POST   | `/faqs-types`          | ADMIN |
| PUT    | `/faqs-types/{typeId}` | ADMIN |
| DELETE | `/faqs-types/{typeId}` | ADMIN |

### Recuperação de senha — `/passwords` (Etapa 07.1)

| Método | Rota | Auth | Descrição |
|--------|------|------|-----------|
| POST   | `/passwords/forgot`           | — | Gera o token e envia o link por e-mail |
| GET    | `/passwords/validate/{token}` | — | Confere se o link ainda vale |
| POST   | `/passwords/reset`            | — | Grava a nova senha e consome o token |

`POST /passwords/forgot` responde **200 mesmo quando o e-mail não existe** — de
propósito: uma resposta diferente permitiria descobrir quais e-mails têm conta no
sistema. O token vale `CONF_PASSWORD_RESET_EXPIRE` minutos (60 por padrão), é de uso
único, e emitir um novo invalida os anteriores.

Com `CONF_MAIL_MODE = "capture"` (o padrão de desenvolvimento), a mensagem é gravada
em `storage/mail/` em vez de enviada, e a resposta traz
`data.inbox_url` apontando para a caixa de entrada local. O texto muda para avisar do
modo de teste, mas continua **idêntico** para e-mail existente e inexistente.

### Contato — `/contacts`

| Método | Rota | Auth | Descrição |
|--------|------|------|-----------|
| POST   | `/contacts`             | —     | Envia a mensagem do formulário público |
| GET    | `/contacts`             | ADMIN | Últimas 100 mensagens recebidas |
| PUT    | `/contacts/{contactId}` | ADMIN | Marca como respondida (`answered` 0 ou 1) |
| DELETE | `/contacts/{contactId}` | ADMIN | Exclui a mensagem |

O `POST` grava no banco e dispara dois e-mails (PHPMailer): um para a caixa de
suporte, com `Reply-To` do visitante, e um recibo para quem escreveu. Se o SMTP
falhar, a resposta continua **201** — a mensagem não é perdida — e
`data.mail_delivered` volta `false`. No modo `"capture"`, `data.inbox_url` aponta para
a caixa de entrada local.

### Relatórios e indicadores — `/reports`

| Método | Rota | Auth | Resposta |
|--------|------|------|----------|
| GET    | `/reports/dashboard`    | ADMIN | JSON com os totais e as séries dos gráficos |
| GET    | `/reports/services/pdf` | ADMIN | `application/pdf` — catálogo de serviços |
| GET    | `/reports/users/pdf`    | ADMIN | `application/pdf` — usuários por perfil |
| GET    | `/reports/faqs/pdf`     | ADMIN | `application/pdf` — perguntas frequentes |

`data` de `/reports/dashboard`:

```json
{
    "totals": { "services": 4, "categories": 7, "users": 6, "faqs": 6,
                "pending_contacts": 0, "catalog_value": 423722 },
    "services_by_category": [ { "label": "Polimento", "total": 2, "total_value": 3100 } ],
    "users_by_type":        [ { "label": "ADMIN", "active": 2, "inactive": 0 } ],
    "faqs_by_type":         [ { "label": "Geral", "published": 4, "unpublished": 1 } ]
}
```

Os PDFs precisam do header `Authorization`, que uma navegação normal do navegador
não envia — por isso o frontend os busca via `fetch` e os abre como Blob
(`ReportService.servicesPdf()`), em vez de usar um link direto.

## Exemplos de requisição

```bash
BASE=http://localhost/projeto/joille2/api

# Login de administrador
TOKEN=$(curl -s -X POST "$BASE/users/login-admin" \
  -d "email=admin@joille.com&password=admin@123" | jq -r .data.token)

# Entidade principal, no formato do enunciado
curl -s "$BASE/services/list"
curl -s "$BASE/services/list/1"
curl -s "$BASE/services/list/paginator/1/10"

curl -s -X POST "$BASE/services" -H "Authorization: Bearer $TOKEN" \
  -d "categories_id=1&name=Banho de ouro&description=18k&value=200"

curl -s -X PUT "$BASE/services/1" -H "Authorization: Bearer $TOKEN" -d "value=250"
curl -s -X DELETE "$BASE/services/1" -H "Authorization: Bearer $TOKEN"

# Imagens da entidade principal
curl -s -X POST "$BASE/service-images/1" -H "Authorization: Bearer $TOKEN" \
  -F "images[]=@foto1.jpg" -F "images[]=@foto2.png" -F "caption=Peça finalizada"
curl -s "$BASE/service-images/1"
curl -s -X PUT "$BASE/service-images/1/cover/3" -H "Authorization: Bearer $TOKEN"
curl -s -X DELETE "$BASE/service-images/1/3" -H "Authorization: Bearer $TOKEN"

# Recuperação de senha
curl -s -X POST "$BASE/passwords/forgot" -d "email=cliente@exemplo.com"
curl -s "$BASE/passwords/validate/<token-recebido-no-email>"
curl -s -X POST "$BASE/passwords/reset" \
  -d "token=<token>&password=novaSenha123&confirmPassword=novaSenha123"

# Contato
curl -s -X POST "$BASE/contacts" \
  -d "name=Ana&email=ana@exemplo.com&subject=Orçamento&message=Gostaria de um orçamento."

# Relatórios
curl -s "$BASE/reports/dashboard" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/reports/services/pdf" -H "Authorization: Bearer $TOKEN" -o catalogo.pdf
```

## Testes

`api-test.php`, na raiz do projeto, percorre todas as rotas e confere o código HTTP
e o envelope de cada uma (inclusive upload real de imagens e a assinatura `%PDF-`
dos relatórios):

```bash
php api-test.php     # 125 verificações
```

A suíte limpa os próprios registros no fim (prefixo `ZZ `), então pode ser
executada quantas vezes for preciso.

## Ordem de registro das rotas

O `dispatch()` do CoffeeCode percorre **todas** as rotas e mantém a última que casar
(não há `break`). Por isso, quando uma rota literal e uma com parâmetro têm o mesmo
número de segmentos, a **literal precisa ser registrada depois**:

| Par conflitante | Registro correto |
|-----------------|------------------|
| `/users/admin/list` × `/users/admin/{userId}` | `{userId}` primeiro, `list` depois |
| `/services/list` × `/services/{serviceId}` | `{serviceId}` primeiro, `list` depois |
| `/categories/list` × `/categories/{categoryId}` | `{categoryId}` primeiro, `list` depois |

`api-test.php` verifica isso explicitamente: a seção 11 confere que
`GET /services/list` cai em `listAll`, e não em `listById` com o id `"list"`.
