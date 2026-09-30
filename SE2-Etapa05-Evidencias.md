# SE2 — Etapa 05 — Integração do backend com o frontend (Joille)

Evidências da integração das telas do Joille ao web service da equipe.

- **Base do site:** `http://localhost/projeto/joille2`
- **Base da API:** `http://localhost/projeto/joille2/api`
- **Ambiente de teste:** Apache 2.4.65 · PHP 8.3.28 · MySQL 8.4.7 (WAMP)
- **Script de verificação:** `php api-test.php` — **74 asserções, 74 aprovadas**

Nenhum mock é utilizado: todas as telas consomem a API real.

---

## 1. Entidades integradas

| Papel | Entidade | Tela | Relacionamento |
|-------|----------|------|----------------|
| Principal | **Serviços** | `/admin/editar-servicos` e `/services` | `services.categories_id → categories.id` |
| Relacionada 1 | **Categorias** | `/admin/editar-categoria` | referenciada por `services` |
| Relacionada 2 | **FAQs** | `/admin/editar-faqs` | `questions.types_id → types.id` |
| Suporte | **Usuários** | `/admin/editar-usuario`, `/cadastro`, `/app/` | `users.type_id → users_types.id` |

## 2. Tabela de operações CRUD

Todas executadas pela interface e confirmadas pelo `api-test.php`.

### 2.1 Serviços (entidade principal)

| Operação | Tela | Método | Rota | Perfil | Resultado |
|----------|------|--------|------|--------|-----------|
| Cadastrar | Admin › Serviços › **+ Novo Serviço** | POST | `/services` | ADMIN | `201 created` |
| Listar (admin) | Admin › Serviços | GET | `/services/admin/list` | ADMIN | `200 success` |
| Listar (público) | Site › Serviços | GET | `/services` | público | `200 success` — só ativos |
| Filtrar | Site › Serviços › filtro | GET | `/services/category/{categoryId}` | público | `200 success` |
| Detalhar | Admin › Serviços › Editar | GET | `/services/{serviceId}` | público | `200 success` — traz `category_name` |
| Editar | Admin › Serviços › Editar › Salvar | PUT | `/services/{serviceId}` | ADMIN | `200 success` — update parcial |
| Inativar | Admin › Serviços › Excluir (com confirmação) | DELETE | `/services/{serviceId}` | ADMIN | `200 success` — `active = 0` |

### 2.2 Categorias (relacionada)

| Operação | Tela | Método | Rota | Perfil | Resultado |
|----------|------|--------|------|--------|-----------|
| Cadastrar | Admin › Categorias › **+ Nova Categoria** | POST | `/categories` | ADMIN | `201 created` |
| Listar (admin) | Admin › Categorias | GET | `/categories/admin/list` | ADMIN | `200 success` |
| Listar (público) | usado nos filtros/selects | GET | `/categories` | público | `200 success` |
| Detalhar | Admin › Categorias › Editar | GET | `/categories/{categoryId}` | público | `200 success` |
| Editar | Admin › Categorias › Editar › Salvar | PUT | `/categories/{categoryId}` | ADMIN | `200 success` |
| Inativar | Admin › Categorias › Excluir | DELETE | `/categories/{categoryId}` | ADMIN | `200 success`; `400` se houver serviço ativo vinculado |

### 2.3 FAQs (relacionada)

| Operação | Tela | Método | Rota | Perfil | Resultado |
|----------|------|--------|------|--------|-----------|
| Cadastrar | Admin › FAQs › **+ Nova Pergunta** | POST | `/faqs` | ADMIN | `201 created` |
| Listar (admin) | Admin › FAQs | GET | `/faqs/admin/list` | ADMIN | `200 success` |
| Listar (público) | Site › FAQs | GET | `/faqs` | público | `200 success` |
| Filtrar por tipo | Admin › FAQs › filtro | GET | `/faqs/type/{typeId}` | público | `200 success` |
| Detalhar | Admin › FAQs › Editar | GET | `/faqs/{faqId}` | público | `200 success` |
| Editar | Admin › FAQs › Editar › Salvar | PUT | `/faqs/{faqId}` | ADMIN | `200 success` |
| Inativar | Admin › FAQs › Excluir | DELETE | `/faqs/{faqId}` | ADMIN | `200 success` |

### 2.4 Usuários

| Operação | Tela | Método | Rota | Perfil | Resultado |
|----------|------|--------|------|--------|-----------|
| Cadastrar (público) | Site › Cadastro | POST | `/users/register` | público | `201 created` |
| Cadastrar (admin) | Admin › Usuários › **+ Novo Administrador** | POST | `/users/register-admin` | ADMIN | `201 created` |
| Listar | Admin › Usuários | GET | `/users/admin/list` | ADMIN | `200 success` — sem hash de senha |
| Detalhar | Admin › Usuários › Editar | GET | `/users/admin/{userId}` | ADMIN | `200 success` |
| Editar | Admin › Usuários › Editar › Salvar | PUT | `/users/admin/{userId}` | ADMIN | `200 success` |
| Inativar | Admin › Usuários › Excluir | DELETE | `/users/admin/{userId}` | ADMIN | `200 success` |
| Ver perfil | Área restrita › Perfil | GET | `/users/me` | CLIENT | `200 success` |
| Editar perfil | Área restrita › Perfil | PUT | `/users/update` | CLIENT | `200 success` |
| Trocar senha | Área restrita › Redefinir Senha | PUT | `/users/password` | CLIENT | `200 success` |
| Trocar foto | Área restrita › Perfil | POST | `/users/photo` | CLIENT | `200 success` |

## 3. Fluxo de autenticação

```text
Cadastro → resposta da API → Login → token → acesso a rota protegida → Logout
```

| Passo | Tela | Rota | Evidência |
|-------|------|------|-----------|
| Cadastro | `/cadastro` | `POST /users/register` | `201 created`; senha nunca trafega ou é guardada em texto puro no cliente |
| Login | `/login` | `POST /users/login` | `200 success` com `data.token` |
| Sessão | — | — | `SessionStorage.saveSession(token, user)` grava `joille_token` e `joille_user` |
| Rota protegida | `/app/` | `GET /users/token-validate`, `GET /users/me` | `200 success` com `Authorization: Bearer <token>` |
| Logout | menu do site e da área restrita | — | `SessionStorage.clearSession()` e redirecionamento |

Escopos de sessão separados, para que a sessão do cliente não sobrescreva a do administrador:

| Escopo | Chaves no `localStorage` | Perfil |
|--------|--------------------------|--------|
| `user` | `joille_token`, `joille_user` | CLIENT (`type_id = 2`) |
| `admin` | `joille_admin_token`, `joille_admin_user` | ADMIN (`type_id = 1`) |

O `AuthGuard` bloqueia a navegação no cliente, mas **não é a única barreira**: toda
rota protegida chama `Api::authToken(int $typeId)`, que valida assinatura,
emissor, expiração, o perfil do token e, no banco, se a conta existe e está ativa.

## 4. Códigos HTTP e mensagens

| Código | `status` | Situação verificada |
|--------|----------|---------------------|
| 200 | `success` | Operação concluída |
| 201 | `created` | Registro criado |
| 400 | `bad_request` | Campo ausente/inválido, e-mail duplicado, senha curta, categoria com serviço vinculado |
| 401 | `unauthorized` | Sem token, token inválido/expirado/adulterado, credenciais erradas, conta inativada |
| 403 | `forbidden` | Token válido, porém de outro perfil (cliente em rota de admin e vice-versa) |
| 404 | `not_found` | Recurso ou rota inexistente |
| 500 | `internal_server_error` | Falha interna |

Envelope de sucesso:

```json
{ "code": 200, "type": "success", "status": "success", "message": "Lista de Serviços", "data": [] }
```

Envelope de erro (sempre com `data: null`):

```json
{ "code": 403, "type": "error", "status": "forbidden", "message": "Usuário autenticado, mas sem permissão para acessar este recurso.", "data": null }
```

## 5. Estados de interface

Implementados em `views/assets/_common/scripts/ui.js` e estilizados em
`views/assets/_common/styles/states.css`.

| Estado | Como aparece | Onde |
|--------|--------------|------|
| Carregando | `showLoading()` — spinner e "Carregando..." | toda lista, antes da requisição |
| Lista vazia | `showEmpty()` — "Nenhum registro cadastrado." | quando `data` vem vazio |
| Erro | `showListError()` — faixa vermelha com a mensagem da API | quando `type !== "success"` |
| Sucesso | `showToast(message)` — toast verde | após criar, editar ou inativar |
| Enviando | `setFormBusy()` — botão desabilitado, "Salvando..." | durante o envio do formulário |
| Serviço indisponível | `serviceUnavailableMessage()` | quando o `fetch` falha (API fora do ar) |

Sucesso **nunca** é exibido quando a API devolve erro: todo `submit` testa
`response.type !== "success"` antes de mostrar a mensagem positiva.

## 6. Requisitos do frontend

| # | Requisito | Situação |
|---|-----------|----------|
| 1 | `async/await`, tratar sucesso e erro, atualizar sem recarregar | Atendido — todas as telas usam `async/await`; as listas são redesenhadas em memória |
| 2 | CRUD completo da entidade principal e de duas relacionadas | Atendido — Serviços, Categorias e FAQs com POST/GET/PUT/DELETE |
| 3 | Estados de carregamento, vazio, sucesso e erro | Atendido — seção 5 |
| 4 | Sem `onclick`, jQuery ou `fetch` espalhado | Atendido — zero `onclick`/jQuery; `fetch` existe apenas em `HttpClientBase` |
| 5 | Cadastro, login, logout, estado autenticado e bloqueio de telas | Atendido — menu público troca para "Olá, {nome} / Minha Conta / Sair"; `AuthGuard` protege `/app/` e `/admin/` |
| 6 | Evidências e tabela de operações | Este documento |

Verificação do requisito 4:

```bash
grep -rn "onclick\|jquery\|jQuery" views/assets --include=*.js   # nenhum resultado
grep -rn "onclick" themes                                        # nenhum resultado
grep -rn "fetch(" views/assets --include=*.js
#   views/assets/_common/classes/HttpClientBase.js  -> únicas chamadas reais (get/post/put/delete e getBlob)
grep -rn "localStorage" views/assets --include=*.js | grep -v storage.js   # nenhum resultado
```

O formulário de contato usava o serviço externo Web3Forms. Desde a Etapa 07.1 ele
envia para a própria API (`POST /contacts`), que grava a mensagem no banco e a
encaminha por e-mail com o PHPMailer — não há mais dependência de terceiros nesse
fluxo, e a resposta segue o envelope padrão do projeto.

## 7. Critérios de aceite

| Critério | Situação |
|----------|----------|
| CRUD executável pelas telas | Atendido nas quatro entidades |
| Cadastro e login criam sessão válida | Atendido — token em `data.token`, salvo pelo `SessionStorage` |
| Rota protegida exige autenticação | Atendido — `401` sem token, `403` com perfil errado |
| Logout encerra a sessão | Atendido — `clearSession()` no site e na área restrita |
| Dados permanecem após atualizar a página | Atendido — `localStorage`; `/app/` revalida com `GET /users/token-validate` |
| Respostas 2xx, 4xx e 5xx com mensagens compreensíveis | Atendido — mensagens da API exibidas em toast ou no estado de erro da lista |
| Mocks identificados e não substituem rota integrada | Não há mocks no projeto |

## 8. Como reproduzir

```bash
# 1. Banco
mysql -u root joille < "banco de dados atualizado/joille.sql"

# 2. Ajustar a URL base, se necessário
#    source/Config/Config.php            -> CONF_URL_BASE
#    views/assets/_common/scripts/config.js -> URL_BASE

# 3. Verificação automatizada da API
php api-test.php

# 4. Telas
#    Administrador: http://localhost/projeto/joille2/admin/login  (admin@joille.com / admin@123)
#    Cliente:       http://localhost/projeto/joille2/cadastro
```
