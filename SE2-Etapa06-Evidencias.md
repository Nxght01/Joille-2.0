# SE2 — Etapa 06 — JavaScript orientado a objetos (Joille)

> Registro histórico da implementação anterior. Para a revisão de 24/09/2026,
> resultados atuais (159 asserções de POO e 9 testes HTTP), alterações e limites
> da verificação, consulte [o guia atualizado](SE2-Etapa06-Guia.md).

Refatoração do frontend da Etapa 05 para POO clássica (`class`) e POO
prototípica, **sem alterar o comportamento do sistema**.

- **Branch:** `javascript-poo`
- **Ambiente:** Apache 2.4.65 · PHP 8.3.28 · MySQL 8.4.7 (WAMP)
- **Verificação da POO:** `node poo-test.mjs` — **94 asserções, 94 aprovadas**
- **Verificação da API:** `php api-test.php` — **74 asserções, 74 aprovadas**

O backend não foi tocado nesta etapa: as 74 asserções da Etapa 05 continuam
passando, o que comprova que o comportamento não mudou.

---

## 1. Arquitetura

```
                        POO CLÁSSICA (class)                 POO PROTOTÍPICA
                        ────────────────────                 ───────────────
  transporte      HttpClientBase
                        │  extends
  API do Joille   ApiService  ──── converte payload ────►  Entity
                        │  extends                            │  extends
  por recurso     ServiceService, CategoryService,         Service, Category,
                  FaqService, FaqTypeService,              Faq, FaqType, User
                  UserService
                        │                                      │
                        └──────────► composição ◄──────────────┘
                                          │
                                   CrudController  (protótipo)
                                          ├── FormController (protótipo)
                                          └── Toast          (Object.create)
                                          │
                                     scripts de tela
```

Regra de dependência: a tela conhece o serviço, o serviço não conhece a tela.

## 2. Parte A — POO clássica

### 2.1 Classes de entidade

| Classe | Arquivo | Tabela |
|--------|---------|--------|
| `Entity` | `_common/classes/entities/Entity.js` | — (base) |
| `Service` | `_common/classes/entities/Service.js` | `services` |
| `Category` | `_common/classes/entities/Category.js` | `categories` |
| `Faq` | `_common/classes/entities/Faq.js` | `questions` |
| `FaqType` | `_common/classes/entities/FaqType.js` | `types` |
| `User` | `_common/classes/entities/User.js` | `users` |

Cada uma tem campos privados, construtor, getters/setters com validação,
métodos próprios e conversão nos dois sentidos com o payload da API.

| Requisito | Onde |
|-----------|------|
| Propriedades privadas | `#id`, `#name`, `#value`, `#password`… em todas as classes |
| Construtor | recebe o payload da API com desestruturação e valores padrão |
| Getters/setters com validação | `set name` exige texto; `set value` exige número ≥ 0; `set email` valida formato; `set typeId` aceita só 1 ou 2 |
| Métodos principais | `Service.formattedValue`, `Faq.resume()`, `User.toSession()`, `User.firstName`, `Category.label`, `isNew`, `isActive` |
| Conversão API ↔ objeto | `fromApi()`, `collectionFromApi()`, `toJSON()`, `toFormData()` |

### 2.2 Serviços HTTP derivados de `HttpClientBase`

Herança em dois níveis: `HttpClientBase → ApiService → <Recurso>Service`.

| Classe | Recurso | `list` | `find` | `create` | `update` | `remove` |
|--------|---------|:--:|:--:|:--:|:--:|:--:|
| `ServiceService` | `/services` | herdado | herdado | herdado | herdado | herdado |
| `CategoryService` | `/categories` | herdado | herdado | herdado | herdado | herdado |
| `FaqService` | `/faqs` | herdado | herdado | herdado | herdado | herdado |
| `FaqTypeService` | `/faqs-types` | herdado | herdado | herdado | herdado | herdado |
| `UserService` | `/users` | sobrescrito | sobrescrito | sobrescrito | sobrescrito | sobrescrito |

`UserService` sobrescreve o CRUD porque as rotas de usuário não são REST puras
(`/users/register`, `/users/admin/{id}`). **O login é `POST`**, como o backend
define em `Users:auth` — não foi presumido `GET`.

Os serviços não manipulam HTML: `poo-test.mjs` inspeciona o código-fonte das
seis classes de serviço e das seis entidades e confirma que nenhuma contém
`document.`, `innerHTML`, `querySelector` ou `createElement`.

## 3. Parte B — POO prototípica

Três funcionalidades reutilizáveis, nenhuma usando `class`.

| Módulo | Criação | Métodos compartilhados no protótipo | Estado próprio |
|--------|---------|-------------------------------------|----------------|
| `Toast` | `Object.create(toastPrototype)` | `show`, `success`, `warning`, `error`, `fromResponse`, `variantFor`, `resolveContainer` | `selector`, `duration`, `logger` |
| `FormController` | função construtora + `new` | `serialize`, `values`, `field`, `fill`, `clear`, `validate`, `isValid`, `setBusy` | `form`, `rules`, `busyLabel` |
| `CrudController` | função construtora + `new` | `load`, `render`, `setFilter`, `create`, `edit`, `remove`, `submit`, `bind`, `openModal`, `closeModal` | `service`, `form`, `toast`, `items`, `filterFn`, `labels` |

### Dois objetos do mesmo protótipo

```js
const a = createToast();
const b = createToast({ duration: 8000 });

Object.getPrototypeOf(a) === Object.getPrototypeOf(b);  // true
a.show === b.show;                                      // true — a MESMA função
Object.hasOwn(a, "show");                               // false — vem do protótipo
a.duration !== b.duration;                              // true — estado é próprio
```

**O que é compartilhado:** as funções. Elas existem uma única vez, em
`toastPrototype`, e todos os toasts apenas delegam a elas pela cadeia de
protótipos. **O que não é compartilhado:** `selector`, `duration` e `logger`,
gravados como propriedades próprias de cada objeto pela fábrica.

O mesmo vale para as quatro telas administrativas: elas criam quatro
`CrudController` distintos, cada um com seus itens, filtro e rótulos, mas
`submit`, `load` e `render` existem uma única vez na memória.

## 4. Conceitos, com o ponto exato do código

| Conceito | Onde | Como se manifesta |
|----------|------|-------------------|
| **Encapsulamento** | entidades | Campos `#privados` sem acesso externo; a escrita passa por setters que validam e lançam erro. `Object.keys(service)` devolve `[]`. Em `User`, a senha tem setter mas **não tem getter**: entra no payload, nunca sai do objeto. |
| **Herança** | serviços e entidades | `Service extends Entity`; `ServiceService extends ApiService extends HttpClientBase`. `ServiceService` reaproveita `list()` da base sem redefinir; `UserService` sobrescreve porque suas rotas são diferentes. |
| **Composição** | `CrudController` | Não herda de ninguém: **reúne** um `ApiService`, um `FormController` e um `Toast`. Trocar qualquer colaborador não exige mexer no controller. |
| **Protótipos** | `Toast`, `FormController`, `CrudController` | Métodos no protótipo, estado na instância; objetos diferentes apontam para o mesmo objeto de métodos. |
| **Polimorfismo** | `Entity.fromApi()` | `static fromApi()` usa `new this(...)`, então `Service.fromApi()` devolve `Service` e `Category.fromApi()` devolve `Category`, com um único método na base. |

## 5. Evidências do CRUD pelas telas

Todas as telas foram reescritas sobre as classes e protótipos. O CRUD continua
integrado às rotas reais (mesmas da Etapa 05, backend inalterado).

| Tela | Classe de serviço | Entidade | Operações |
|------|-------------------|----------|-----------|
| Admin › Serviços | `ServiceService` + `ServiceImageService` | `Service`, `ServiceImage` | criar, listar, detalhar, editar, inativar, enviar/excluir imagens, definir capa |
| Admin › Categorias | `CategoryService` | `Category` | criar, listar, detalhar, editar, inativar |
| Admin › FAQs | `FaqService` + `FaqTypeService` | `Faq`, `FaqType` | criar, listar, detalhar, editar, inativar |
| Admin › Usuários | `UserService` | `User` | criar, listar, detalhar, editar, inativar |
| Admin › Dashboard | `ReportService` | — | indicadores, gráficos e relatórios em PDF |
| Admin › Mensagens | `ContactService` | `ContactMessage` | listar, marcar respondida, excluir |
| Admin › Login | `UserService` | `User` | `loginAdmin` (POST) |
| Site › Serviços | `ServiceService` + `CategoryService` | `Service`, `Category` | listar e filtrar por categoria |
| Site › FAQs | `FaqService` | `Faq` | listar as publicadas em sanfona (`Accordion`) |
| Site › Login | `UserService` | `User` | `login` (POST) |
| Site › Cadastro | `UserService` | `User` | `register` |
| Site › Contato | `ContactService` | `ContactMessage` | enviar mensagem (grava e dispara e-mail) |
| Site › Esqueci a senha | `PasswordService` | — | `forgot` |
| Site › Nova senha | `PasswordService` | — | `validateToken`, `reset` |
| App › Perfil | `UserService` | `User` | `me`, `updateProfile`, `updatePhoto` |
| App › Senha | `UserService` | — | `updatePassword` |
| App › Serviços | `ServiceService` | `Service` | listar |

Ganho de reuso: as quatro telas administrativas deixaram de repetir a lógica de
listar / editar / confirmar exclusão / enviar formulário e passaram a apenas
**configurar** um `CrudController`. Hoje elas somam 336 linhas — quase só
configuração (rótulos, template da linha e filtros):

| Tela | Linhas |
|------|--------|
| `edit_services.js` | 97 |
| `edit_faq.js` | 100 |
| `edit_user.js` | 77 |
| `edit_category.js` | 62 |

A lógica que antes estava duplicada nas quatro telas agora existe uma única vez
em `CrudController.prototype`.

## 6. Estados de carregamento, sucesso e erro

| Estado | Responsável | Aparência |
|--------|-------------|-----------|
| Carregando | `ui.showLoading` | spinner + "Carregando..." |
| Lista vazia | `ui.showEmpty` | texto em itálico |
| Erro na lista | `ui.showListError` | faixa vermelha |
| Sucesso | `toast.fromResponse` | toast verde |
| Validação (400) | `toast.fromResponse` | toast amarelo |
| Sem permissão / falha (401, 403, 500) | `toast.fromResponse` | toast vermelho |
| Enviando | `FormController.setBusy` | botão desabilitado, "Salvando..." |
| Serviço indisponível | `ui.serviceUnavailableMessage` | toast vermelho |

Validação em duas camadas antes de chegar à API: `FormController.validate()`
verifica obrigatoriedade, tamanho mínimo, e-mail e confirmação; em seguida os
setters da entidade validam de novo, e o erro lançado vira toast amarelo.

## 7. Resultado da verificação automatizada

```
$ node poo-test.mjs

=== 1. Encapsulamento (campos privados + validação nos setters) ===   18 asserções
=== 2. Herança ===                                                    19 asserções
=== 3. Conversão payload da API <-> objeto ===                        10 asserções
=== 4. POO prototípica: métodos compartilhados ===                    25 asserções
=== 5. Composição (CrudController reúne colaboradores) ===             6 asserções
=== 6. Toast: tratamento das respostas da API ===                      9 asserções
=== 7. Serviços não manipulam HTML ===                                 7 asserções

===== 94 passaram, 0 falharam =====
```

```
$ php api-test.php
===== 74 passaram, 0 falharam =====
```

## 8. Como executar

```bash
# Banco (uma vez)
mysql -u root joille < "banco de dados atualizado/joille.sql"

# URL base — precisa bater nos dois arquivos
#   source/Config/Config.php               -> CONF_URL_BASE
#   views/assets/_common/scripts/config.js -> URL_BASE

# Verificações
node poo-test.mjs      # POO: classes, herança, protótipos, composição
php  api-test.php      # API: CRUD, autenticação, códigos HTTP

# Telas
#   Administrador: http://localhost/projeto/joille2/admin/login
#                  admin@joille.com / admin@123
#   Cliente:       http://localhost/projeto/joille2/cadastro
```

## 9. Observações

- `poo-test.mjs` roda no Node e não precisa de servidor nem de navegador. Para
  que o Node trate os `.js` de `views/assets/` como ES Modules existe um
  `views/assets/package.json` com `{"type": "module"}` — o navegador não usa
  esse arquivo.
- O nome `ServiceService` é proposital: o enunciado pede o padrão
  `<Entidade>Service` (exemplo `ProductService`) e a entidade principal do
  Joille chama-se *Service* (serviço).
- `Service.formattedValue` passou a exibir o valor como moeda brasileira
  (`R$ 1.550,00`) em vez do número cru. É a única mudança visível de
  comportamento, e é de apresentação.
