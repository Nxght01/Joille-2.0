# Campos e exemplos complementares da API

## Dados de escrita

| Recurso | Campos aceitos e validações |
|---|---|
| Cadastro de usuário | `name`, `email` válido e único, `password` com pelo menos 8 caracteres; cadastro público sempre cria CLIENT |
| Cadastro administrativo | Mesmos campos; `type_id` 1 (ADMIN) ou 2 (CLIENT); exige ADMIN |
| Login | `email`, `password`; POST em `/users/login` ou `/users/login-admin` |
| Perfil próprio | PUT `/users/update`: `name`, `email`, `address` opcional até 255 caracteres, `phone` opcional até 30 |
| Senha | PUT `/users/password`: `password`, `newPassword`, `confirmNewPassword`; confirmação igual e mínimo 8 caracteres |
| Foto | POST `/users/photo`: multipart `photo`, JPEG/PNG real, até 4 MB; ID exclusivamente do JWT |
| Serviços | `categories_id` ativo, `name` e `description` de 1 a 255 caracteres, `value` numérico não negativo; PUT aceita alterações parciais e `active` 0/1 |
| Categorias | `name`; PUT aceita `active` 0/1; excluir categoria com serviço ativo retorna 400 |
| FAQ | `types_id`, `question`, `answer`; PUT aceita `active` 0/1; despublicadas só podem ser consultadas por ADMIN |
| Tipo de FAQ | `description`; PUT aceita `active` 0/1 |
| Contato | `name`, `email`, `subject` opcional, `message` com mínimo 10 caracteres; administração marca `answered` 0/1 |
| Recuperação | POST `/passwords/forgot`: `email`; POST `/passwords/reset`: `token`, `password`, `confirmPassword` |

Paginação de serviços e categorias: `page >= 1`, `per_page` de 1 a 100. A resposta
contém `data: {page, perPage, total, data: [...]}`. Listas públicas omitem inativos;
um ID inativo retorna 404 para visitantes e clientes. Administradores podem ler
registros inativos para reativá-los. Repetir uma atualização sem mudar valores
retorna 200. Administradores não podem inativar ou remover seu próprio perfil.

## Solicitações de serviços

| Método | Rota | Permissão | Resultado |
|---|---|---|---|
| GET | `/requests` | CLIENT ou ADMIN | Cliente vê só as próprias; ADMIN vê todas |
| GET | `/requests/{id}` | Dono ou ADMIN | Detalhe persistido |
| POST | `/requests` | CLIENT | Cria pendente com dono e preço definidos no servidor |
| PUT | `/requests/{id}` | Dono com solicitação pendente | Edita `description` e `quantity` |
| PUT | `/requests/{id}` | ADMIN | Atualiza `status` e `admin_notes` |
| DELETE | `/requests/{id}` | Dono com solicitação pendente ou ADMIN | Cancela e preserva histórico |

Descrição: 10 a 2000 caracteres. Quantidade: inteiro de 1 a 100. `services_id`
deve apontar para serviço e categoria ativos. Não se pode escolher `users_id`,
`unit_price` ou o estado inicial no POST. O preço é capturado do catálogo na
criação e preservado nas edições. O total é `quantity * unit_price`.

Estados: `pending` → `in_progress` → `completed`. Uma solicitação pendente ou em
atendimento pode passar a `cancelled`. Estados finais não voltam a pendente.
Uma transição proibida ou edição de pedido já iniciado retorna **409**.
Acesso a ID de outro cliente retorna **404**, evitando expor sua existência.

Exemplo autenticado:

```http
POST /api/requests
Authorization: Bearer <token-do-cliente>
Content-Type: application/json

{"services_id":1,"description":"Polir duas alianças de prata.","quantity":2}
```

```json
{
  "code": 201, "type": "success", "status": "created",
  "message": "Solicitação criada com sucesso.",
  "data": {
    "id": 7, "users_id": 28, "services_id": 1,
    "description": "Polir duas alianças de prata.", "quantity": 2,
    "unit_price": "150.00", "total": "300.00", "status": "pending",
    "admin_notes": null, "service_name": "Polimento",
    "customer_name": "Cliente", "customer_email": "cliente@example.test",
    "created_at": "2026-11-20 12:00:00", "updated_at": "2026-11-20 12:00:00"
  }
}
```

Exemplo de resposta de erro, sem dados internos:

```json
{"code":401,"type":"error","status":"unauthorized","message":"Usuário não está autenticado (sem token).","data":null}
```

Criações retornam 201; leituras, edições e cancelamentos retornam 200. Dados
inválidos retornam 400, falta de sessão 401, perfil indevido 403, ID ausente 404,
conflito de estado 409 e falha inesperada 500. Todos os erros usam `data: null`.

## Integração mobile

1. Configurar a URL acessível ao dispositivo. `localhost` no celular aponta para
   o próprio celular; usar o IP do computador na rede ou o endereço da hospedagem.
2. Efetuar POST de login, ler `data.token` e incluir `Authorization: Bearer ...`
   em cada chamada protegida. Nunca incluir o segredo de assinatura no aplicativo.
3. Consumir serviços/categorias, criar solicitações e consultar `/requests`.
4. Enviar fotos como multipart, sem converter para Base64. Utilizar as URLs
   públicas retornadas na galeria para exibir imagens.
5. Tratar 401 voltando ao login, 403 como falta de permissão e 409 como conflito.

O código do aplicativo nativo pertence ao projeto da outra disciplina e não está
incluído neste repositório. A API e seus testes não substituem essa demonstração.
