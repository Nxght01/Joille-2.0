# views/assets/

Assets do front-end, separados por área. Cada área tem `scripts/` e `styles/`.

| Pasta       | Área | Consumido por |
|-------------|------|---------------|
| `_common/`  | compartilhado | todas |
| `public/`   | site público  | `themes/web/` |
| `app/`      | área restrita do cliente | `themes/app/` |
| `admin/`    | painel administrativo | `themes/adm/` |

## Arquitetura (Etapa 06)

```
                        POO CLÁSSICA (class)                 POO PROTOTÍPICA
                        ────────────────────                 ───────────────
  transporte      HttpClientBase
                        │  extends
  API do Joille   ApiService  ──── converte payload ────►  Entity
                        │  extends                            │  extends
  por recurso     ServiceService, ServiceImageService,      Service, ServiceImage,
                  CategoryService, FaqService,              Category, Faq, FaqType,
                  FaqTypeService, UserService,              User, ContactMessage
                  ContactService, PasswordService,
                  ReportService
                        │                                      │
                        └──────────► composição ◄──────────────┘
                                          │
                                   CrudController  (protótipo)
                                          ├── FormController (protótipo)
                                          ├── ImageGallery   (protótipo)
                                          └── Toast          (Object.create)
                                          │
                                     scripts de tela
                                          │
                                    ChartPanel (Object.create) ── Chart.js
```

Regra de dependência: **a tela conhece o serviço; o serviço não conhece a tela.**
Nenhum serviço ou entidade toca em `document`, `innerHTML` ou `querySelector` —
isso é verificado pelo `poo-test.mjs` na raiz do projeto.

## `_common/classes/entities/` — POO clássica

Uma classe por entidade, todas herdando de `Entity`.

| Classe | Tabela | Destaques |
|--------|--------|-----------|
| `Entity` | — | `#id` privado, `fromApi`, `collectionFromApi`, `toFormData`, `isNew` |
| `Service` | `services` | `formattedValue` (BRL), `categoryName` vindo do JOIN, `coverImageUrl`, `imagesCount`, `hasImages` |
| `ServiceImage` | `service_images` | `url` derivada do `path` quando a API não a manda, `isCover`, `alt` |
| `Category` | `categories` | `label` para os `<select>` |
| `Faq` | `questions` | `resume(limit)` para listas estreitas |
| `FaqType` | `types` | `label` para os `<select>` |
| `User` | `users` | senha **só de escrita**, `toSession()`, `firstName`, `isAdmin` |
| `ContactMessage` | `contacts` | valida e-mail e tamanho mínimo da mensagem, `formattedDate`, `isAnswered` |

Todo campo é privado (`#campo`) e só é alcançado por getter/setter. Os setters
validam e **lançam** `TypeError`/`RangeError`; as telas capturam e transformam
em toast amarelo. Consequência prática: `new Service({})` falha de propósito —
uma entidade inválida nunca chega a existir.

A conversão de nomes acontece nas duas pontas: a API fala `snake_case`
(`categories_id`) e o JavaScript fala `camelCase` (`categoriesId`).

## `_common/classes/services/` — serviços HTTP

Herdam `list`, `find`, `create`, `update` e `remove` de `ApiService`, que por sua
vez herda o transporte de `HttpClientBase`.

| Classe | Recurso | Acrescenta |
|--------|---------|-----------|
| `ApiService` | — | `toResult()`, `toPayload()`, CRUD genérico |
| `ServiceService` | `/services` | `listAdmin`, `listByCategory`, `listPaginated` |
| `ServiceImageService` | `/service-images` | `listByService`, `upload` (N arquivos), `setCover`, `remove` |
| `CategoryService` | `/categories` | `listAdmin` |
| `FaqService` | `/faqs` | `listAdmin`, `listByType` |
| `FaqTypeService` | `/faqs-types` | só o CRUD herdado |
| `UserService` | `/users` | `login` (**POST**), `loginAdmin`, `register`, `me`, `updatePhoto`… |
| `ContactService` | `/contacts` | `setAnswered`; o `create` herdado serve ao formulário público |
| `PasswordService` | `/passwords` | `forgot`, `validateToken`, `reset` |
| `ReportService` | `/reports` | `dashboard`, `servicesPdf`, `usersPdf`, `faqsPdf` |

`ApiService.toResult()` normaliza o envelope e anexa as entidades já
convertidas, então a tela recebe `{ ok, code, type, message, entities, entity }`.

`UserService` sobrescreve o CRUD porque as rotas de usuário não são REST puras:
o cadastro é `/users/register`, a área administrativa fica em `/users/admin/...`
e **o login é POST**, como o backend exige.

`PasswordService` e `ReportService` passam `null` como classe de entidade: esses
recursos não devolvem registros do domínio, só o envelope. Nesse caso `toResult()`
delega a `toPlainResult()`.

Os relatórios em PDF usam `HttpClientBase.getBlob()`, não `get()`: o token JWT viaja
no header `Authorization`, que uma navegação normal do navegador não envia — e ler um
PDF como texto o corromperia. `getBlob()` devolve um `Blob` quando o arquivo vem, ou
o envelope JSON quando o backend recusou a requisição.

## `_common/prototypes/` — POO prototípica

Sem `class` aqui. Os métodos ficam no protótipo e são compartilhados por todos
os objetos criados a partir dele.

| Módulo | Como é criado | Compartilhado (protótipo) | Próprio de cada objeto |
|--------|---------------|---------------------------|------------------------|
| `Toast.js` | `Object.create(toastPrototype)` via `createToast()` | `show`, `success`, `warning`, `error`, `fromResponse`, `variantFor`, `resolveContainer` | `selector`, `duration`, `logger` |
| `FormController.js` | função construtora + `new` | `serialize`, `values`, `field`, `fill`, `clear`, `validate`, `isValid`, `setBusy` | `form`, `rules`, `busyLabel` |
| `CrudController.js` | função construtora + `new` | `load`, `render`, `setFilter`, `create`, `edit`, `remove`, `submit`, `bind`, `openModal`, `closeModal` | `service`, `form`, `toast`, `container`, `items`, `filterFn`, `labels`, `onOpen`, `onClose` |
| `ImageGallery.js` | função construtora + `new` | `setServiceId`, `load`, `render`, `upload`, `setCover`, `remove`, `bind` | `service`, `container`, `input`, `serviceId`, `images`, `readOnly` |
| `ChartPanel.js` | `Object.create(chartPanelPrototype)` via `createChartPanel()` | `bar`, `stackedBar`, `doughnut`, `draw`, `destroy`, `clear`, `color` | `palette`, `charts`, `logger` |
| `Accordion.js` | função construtora + `new` | `open`, `close`, `toggle`, `closeAll`, `refresh`, `isOpen`, `bind` | `container`, `itemSelector`, `triggerSelector`, `panelSelector`, `single` |

Dois objetos do mesmo protótipo não duplicam funções:

```js
const a = createToast();
const b = createToast({ duration: 8000 });

Object.getPrototypeOf(a) === Object.getPrototypeOf(b);  // true
a.show === b.show;                                      // true — a MESMA função
Object.hasOwn(a, "show");                               // false — vem do protótipo
a.duration !== b.duration;                              // true — estado é próprio
```

As quatro telas administrativas criam quatro `CrudController` a partir do mesmo
protótipo: cada uma tem seus itens, filtro e rótulos, mas `submit`, `load` e
`render` existem **uma única vez** na memória.

O mesmo vale para a `ImageGallery`: a tela administrativa de Serviços cria uma com os
botões de capa e exclusão, e a página pública de Serviços cria outra com
`readOnly: true` — só a grade de fotos. Duas configurações, um protótipo.

### Ganchos `onOpen` / `onClose` do CrudController

A galeria precisa saber **qual** serviço está aberto, mas o `CrudController` não
conhece galerias. Em vez de acoplar os dois, o controller apenas avisa quando o modal
abre e fecha:

```js
onOpen: (service) => gallery.setServiceId(service?.id ?? null),  // null = novo registro
onClose: () => gallery.setServiceId(null)
```

Como o upload exige um id já gravado, em "Novo Serviço" (`service === null`) a seção
de imagens aparece desabilitada, com o aviso para salvar primeiro.

### Accordion e o FAQ

A página pública de FAQs usa a sanfona do `Accordion`. Três decisões que valem nota:

- **Delegação de eventos.** Um único listener no `<dl>`, não um por pergunta. É o
  que permite a lista chegar da API depois de a página carregar e continuar
  funcionando, sem religar nada.
- **O estado mora no DOM.** `aria-expanded` no `<button>` e a classe `open` no
  painel — não há variável paralela para divergir do que está na tela, e o leitor de
  tela anuncia a mesma coisa que o CSS pinta.
- **A altura é medida na abertura.** `panel.style.maxHeight = panel.scrollHeight`,
  em vez de um `max-height` fixo no CSS. Um `max-height: 300px` cortaria respostas
  longas; medir resolve isso e mantém a transição suave. `refresh()` remede quando a
  janela é redimensionada e o texto reflui.

### ChartPanel e o Chart.js

O Chart.js entra por CDN e vira `window.Chart`. O `ChartPanel` é a única parte do
front-end que sabe disso: as telas chamam `panel.bar(...)`, `panel.stackedBar(...)` e
`panel.doughnut(...)`. Se a biblioteca não carregar, `draw()` devolve `null`, registra
um aviso e o resto do painel continua funcionando — os indicadores numéricos não
dependem dos gráficos. `draw()` também destrói o gráfico anterior do mesmo canvas,
para que um recarregamento não empilhe instâncias.

### Toast e respostas da API

`fromResponse(response)` cobre as três aparências exigidas:

| Situação | Aparência |
|----------|-----------|
| `type: "success"` | verde (`success`) |
| `type: "warning"` | amarelo (`warning`), mesmo sem código 400 |
| `code: 400` — erro de preenchimento, o usuário corrige | amarelo (`warning`) |
| 401, 403, 404, 500 e falhas de rede | vermelho (`error`) |

Ele funciona com respostas **com ou sem** `data`, nunca usa a `data` inteira
como mensagem e, diante de uma resposta inesperada, mostra um aviso genérico
**e registra o objeto original no console** — o erro não é silenciado.

## `_common/scripts/`

| Arquivo | Conteúdo |
|---------|----------|
| `config.js` | `URL_BASE` e `API_BASE_URL`. **Deve espelhar `CONF_URL_BASE` do PHP.** |
| `functions.js` | `getBackendUrl`, `getBackendUrlApi`, `showDataSelect`, `getFirstName` |
| `storage.js` | `SessionStorage` — token e usuário no `localStorage`, isolados por escopo |
| `auth-guard.js` | `AuthGuard` — bloqueia a navegação conferindo o `type_id` esperado |
| `ui.js` | estados de lista (`showLoading`, `showEmpty`, `showListError`) e `createAuthFailureHandler` |

## `_common/styles/`

| Arquivo | Conteúdo |
|---------|----------|
| `states.css` | Primitivos compartilhados pelos **três** temas: estados de lista, toasts, barra de ações, **janela modal** e campos de formulário. Importado antes do CSS específico de cada tela. |
| `gallery.css` | Grade de miniaturas, badge de capa e a grade de 6 colunas da listagem de Serviços. Usado pelo painel e pela área pública. |

### Por que o modal mora em `states.css`

Ele era definido em `admin/styles/editUser.css`, que na prática funcionava como
folha compartilhada — mas **a tela de Categorias não a carregava**. Sem o
`.modal { display: none }`, o formulário de edição aparecia solto no meio da
página ao carregar. Como o modal também é usado pela galeria da área pública e
pelo detalhe das mensagens, o lugar certo é a folha que todos os temas importam.

## `admin/styles/crud.css`

Folha única das quatro telas de CRUD do painel (Usuários, Categorias, Serviços,
FAQs) e da tela de Mensagens. Substituiu `editUser.css` e `editCategory.css`.

Três telas reaproveitam as mesmas classes de casca e de item — Serviços, FAQs e
Mensagens usam todas `.services-list` / `.service-item` —, então a grade **não**
pode ser escolhida por elas. Cada cabeçalho recebe uma classe própria e a linha é
alcançada como irmã dele:

```css
.list-header-users,
.list-header-users ~ .users-container .user-item { grid-template-columns: ...; }
```

| Tela | Classe do cabeçalho | Colunas | Onde a grade é definida |
|------|--------------------|---------|-------------------------|
| Usuários | `list-header-users` | 5 | `crud.css` |
| Categorias | `list-header-categories` | 3 | `crud.css` |
| FAQs | `list-header-faqs` | 4 | `crud.css` |
| Serviços | `list-header-services` | 6 | `gallery.css` |
| Mensagens | `list-header-contacts` | 5 | `contacts.css` |

Antes disso, só `.service-item` tinha `display: grid`: as linhas de `.user-item` e
`.category-item` viravam blocos empilhados, sem colunas.

## Convenções

- Todo script de tela é um ES module: `<script type="module" src="...">`.
- Nenhum script de tela chama `fetch`: as únicas chamadas reais estão em `HttpClientBase`.
- Nenhum `onclick` no HTML — os eventos são registrados em JavaScript.
- O `localStorage` só é tocado por `SessionStorage`.
- Texto que vem do usuário (mensagem de contato, por exemplo) é inserido com
  `textContent`, nunca com `innerHTML`.

## `package.json`

Existe apenas para marcar esta pasta como ES Modules e permitir que o Node
execute `poo-test.mjs`. O navegador não usa esse arquivo.
