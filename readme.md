# JOILLE

Sistema de gerenciamento para empresas de pequeno porte dos ramos de joalheria e
bijuteria. Aplicação MVC em PHP com uma API REST separada.

## Tecnologias

| Camada | Tecnologia |
|--------|-----------|
| Dependências | Composer |
| Linguagem | PHP >= 8.1 orientado a objetos |
| Roteamento amigável | `coffeecode/router` 2.0.2 |
| Views | `league/plates` 3.5 |
| Autenticação | `firebase/php-jwt` (HS512) |
| Upload e manipulação de imagens | `coffeecode/uploader` |
| Documentos PDF | `dompdf/dompdf` |
| Envio de e-mail | `phpmailer/phpmailer` (SMTP) |
| Gráficos | Chart.js 4 (CDN) |
| Banco | MySQL / MariaDB via PDO |
| Front-end | JavaScript ES Modules (sem framework), POO clássica e prototípica |

## Estrutura

```
joille2/
├── index.php                 # roteador da aplicação MVC (web / app / admin)
├── api/
│   ├── index.php             # roteador da API REST
│   └── readme.md             # documentação do Web Service
├── source/
│   ├── Config/               # constantes (banco, URL base, JWT, SMTP, empresa)
│   ├── Support/              # url(), ImageUploader, Mailer, PdfReport
│   ├── Core/                 # Connect (PDO), Model (CRUD), JWTToken
│   ├── Models/               # entidades do domínio
│   ├── Controller/           # controladores da API REST
│   └── App/                  # controladores das views (Plates)
├── themes/
│   ├── web/                  # site público
│   ├── app/                  # área restrita do cliente
│   └── adm/                  # painel administrativo
├── views/assets/
│   ├── _common/
│   │   ├── classes/entities/ # POO clássica: Entity, Service, ServiceImage,
│   │   │                     #   Category, Faq, FaqType, User, ContactMessage
│   │   ├── classes/services/ # HttpClientBase -> ApiService -> <Recurso>Service
│   │   ├── prototypes/       # POO prototípica: Toast, FormController,
│   │   │                     #   CrudController, ImageGallery, ChartPanel, Accordion
│   │   ├── scripts/          # config, sessão, guarda de rota, estados de lista
│   │   └── styles/           # states.css, gallery.css
│   ├── public/ app/ admin/   # scripts e estilos por área
├── uploads/images/           # fotos de perfil e imagens dos serviços (fora do Git)
├── storage/mail/             # e-mails capturados no modo de teste + caixa de entrada local
└── banco de dados atualizado/
    ├── README.md             # entidades, regras, limpeza e uso do Workbench
    ├── joille.sql            # dump completo (já com as migrations aplicadas)
    ├── migration-001-acme-parity.sql
    ├── migration-002-images-mail-reports.sql
    ├── migration-003-users-categories.sql
    └── limpeza-dados-de-teste.sql
```

Cada pasta relevante tem seu próprio `README.MD` com detalhes.

## Instalação

Antes de instalar, copie `source/Config/Config.example.php` para
`source/Config/Config.php` e configure o banco e as URLs locais. O arquivo
`Config.php` fica fora do Git. Substitua `JWT_SECRET_KEY` por uma chave própria;
você pode gerar uma com `php -r "echo bin2hex(random_bytes(64));"`.

O SQL de instalação contém apenas dados de demonstração e o administrador
`admin@joille.com` / `admin@123`. Ele não é um backup do banco em uso.

```bash
composer install
```

1. Crie o banco importando `banco de dados atualizado/joille.sql` — ele já traz a
   estrutura final, os índices e os dados de referência. Se você **já** tem um banco
   `joille` com dados, rode apenas as migrations que faltam, na ordem numérica, em
   vez de reimportar (o dump apaga as tabelas antes de recriá-las).
   Detalhes, diagrama ER e uso do Workbench em
   [`banco de dados atualizado/README.md`](banco%20de%20dados%20atualizado/README.md).
2. Ajuste `CONF_URL_BASE` e `CONF_URL_TEST` em `source/Config/Config.php`.
3. Replique a mesma URL em `views/assets/_common/scripts/config.js`.
4. Gere uma nova `JWT_SECRET_KEY` para qualquer ambiente que não seja local.
5. Configure o SMTP (veja abaixo) para habilitar recuperação de senha e contato.

Usuários já cadastrados para teste:

| Perfil | E-mail | Senha | Onde entra |
|--------|--------|-------|-----------|
| Administrador | `admin@joille.com` | `admin@123` | `/admin/login` |
| Administrador | `eliasTest@joille.com` | `12345678` | `/admin/login` |

**Troque essas senhas antes de qualquer uso real.**

### Configuração do e-mail

`CONF_MAIL_MODE`, em `source/Config/Config.php`, decide para onde vão as mensagens.

#### Modo `"capture"` — o padrão, para testar

Nenhum e-mail sai para a internet: cada mensagem é gravada como HTML em
`storage/mail/` e pode ser lida na **caixa de entrada local**, em
<http://localhost/projeto/joille2/storage/mail/>.

Serve para testar pelo frontend, sem conta de e-mail nenhuma:

1. cadastre-se em `/cadastro`;
2. vá em `/esqueci-senha` e informe o e-mail;
3. abra a caixa de entrada local e clique em **Criar nova senha** no e-mail;
4. defina a senha nova e entre com ela em `/login`.

O mesmo vale para `/contato`: a mensagem é gravada no banco (aparece em
**Admin › Mensagens**) e os dois e-mails — o encaminhamento ao suporte e o recibo ao
visitante — ficam na caixa local.

As telas de recuperação e de contato mostram um aviso amarelo enquanto este modo está
ativo, e a caixa de entrada local se recusa a listar qualquer coisa fora dele.

#### Modo `"smtp"` — para enviar de verdade

```php
const CONF_MAIL_MODE    = "smtp";
const CONF_MAIL_USER    = "sua.conta@gmail.com";
const CONF_MAIL_PASSWD  = "senha-de-app-de-16-digitos";
const CONF_MAIL_SENDER_MAIL = "sua.conta@gmail.com";
const CONF_MAIL_SUPPORT = "suporte@suaempresa.com";
```

`CONF_MAIL_PASSWD` **não** é a senha da conta Google: é uma *Senha de app*
(Conta Google › Segurança › pesquisar "Senhas de app"). Se ficar em branco neste modo,
a API responde `502` explicando o que falta — o erro não é silencioso, e o token de
recuperação recém-criado é invalidado para não deixar um link válido que ninguém
recebeu.

## Áreas do sistema

| Área | Rota | Acesso | Conteúdo |
|------|------|--------|----------|
| Pública | `/` | livre | Home, Sobre, Serviços (com galeria de fotos), FAQs, Contato, Localização, Login, Cadastro, Esqueci a senha |
| Aplicação | `/app/` | cliente logado | Perfil (nome, e-mail, senha, foto), catálogo de serviços |
| Administrativa | `/admin/` | administrador | Dashboard com indicadores, gráficos e relatórios em PDF; CRUD de usuários, serviços (com N imagens), categorias e FAQs; mensagens de contato |

## Autenticação

Login (`/api/users/login` ou `/api/users/login-admin`) devolve um JWT válido por
90 minutos. O front-end guarda token e usuário no `localStorage` através de
`SessionStorage`, em escopos separados para cliente e administrador, e envia o token
no header `Authorization: Bearer <token>`.

No servidor, `Source\Controller\Api::authToken(int $typeId)` decodifica o token e
confere no banco se o usuário tem aquele perfil e está ativo. Tipos: `1` = ADMIN,
`2` = CLIENT.

### Recuperação de senha

`/esqueci-senha` → e-mail com link → `/redefinir-senha?token=...` → nova senha.
O token tem 64 caracteres, vale 60 minutos (`CONF_PASSWORD_RESET_EXPIRE`), é de uso
único e emitir um novo invalida os anteriores. Depois da troca, o titular recebe um
segundo e-mail avisando da alteração.

## Imagens

[Etapa 07: fluxos, contratos, testes e capturas após recarga](SE2-Etapa07-Imagens.md).

- **Usuário:** uma foto de perfil, redimensionada para 600px de largura.
- **Serviço (entidade principal):** até 10 imagens, redimensionadas para 1600px,
  com uma marcada como capa — é a que aparece nas listagens.

Validação: JPG/JPEG ou PNG, no máximo 4 MB. O tipo é conferido pelo conteúdo do
arquivo (`finfo` e `getimagesize`), não pelo que o navegador declara. Os arquivos vão para
`uploads/images/AAAA/MM/` e a pasta `uploads/` está no `.gitignore`.

Excluir uma imagem remove o registro **e** o arquivo do disco; `ImageUploader::remove()`
só apaga arquivos de imagem dentro de `uploads/images/`, para que um registro adulterado no banco
não consiga alcançar outros arquivos do projeto.

## Relatórios e gráficos

O dashboard administrativo consome `GET /api/reports/dashboard` — uma única
requisição com os totais e as séries — e desenha quatro gráficos com o Chart.js:
serviços por categoria, valor do catálogo por categoria, usuários por perfil e FAQs
por tipo.

Três relatórios em PDF são gerados pelo Dompdf (`Source\Support\PdfReport`): catálogo
de serviços, usuários e perguntas frequentes. Como as rotas exigem o header
`Authorization`, o front-end as busca via `fetch` e abre o resultado como Blob.

## API

Documentação das rotas, do envelope de resposta, dos códigos de status e exemplos de
requisição em [`api/readme.md`](api/readme.md).

## Arquitetura do frontend

### Resumo da aplicação no website

O JavaScript do Joille foi organizado em classes que representam e validam
serviços, categorias, usuários e outras entidades. A comunicação com a API
reutiliza operações HTTP por herança. No website, essa estrutura atende ao
cadastro, listagem, edição e exclusão lógica, além de login e atualização de perfil.

Componentes baseados em protótipos compartilham o controle dos formulários,
operações de CRUD e mensagens de sucesso, aviso e erro entre as páginas.
As verificações da Etapa 06 incluem testes de classes, contratos HTTP e operações
reais, com os resultados registrados nos documentos de auditoria.

Checklist conferido contra o enunciado, testes reais e pendências da entrega:
[`SE2-Etapa06-Auditoria.md`](SE2-Etapa06-Auditoria.md).

Guia da Etapa 06, alterações da revisão e exemplos passo a passo:
[`SE2-Etapa06-Guia.md`](SE2-Etapa06-Guia.md).

Detalhada em [`views/assets/README.MD`](views/assets/README.MD). Em resumo:

- **POO clássica** — uma classe por entidade (`Entity` → `Service`, `ServiceImage`,
  `Category`, `Faq`, `FaqType`, `User`, `ContactMessage`) com campos privados e
  validação nos setters; e os serviços HTTP `HttpClientBase` → `ApiService` →
  `<Recurso>Service`, que expõem `list`, `find`, `create`, `update` e `remove`.
- **POO prototípica** — `Toast` e `ChartPanel` (via `Object.create`),
  `FormController`, `CrudController`, `ImageGallery` e `Accordion` (funções
  construtoras com os métodos no protótipo).
- **Composição** — as telas montam um `CrudController` com um serviço, um
  `FormController` e um `Toast`; a tela de serviços acrescenta uma `ImageGallery`.

Nenhum script de tela chama `fetch`; nenhum serviço ou entidade toca no DOM.

## Testes

```bash
node poo-test.mjs      # 159 verificações — POO: encapsulamento, herança, protótipos, composição
node --test http-contract-test.mjs # 9 testes de contratos HTTP, sem gravar no banco
node image-test.mjs    # validação e comportamento da galeria
php  api-test.php      # 133 verificações — API: CRUD, autenticação, upload, e-mail, PDF e HTTP
```

No modo `"capture"`, `api-test.php` percorre o **fluxo completo** de recuperação de
senha: dispara o e-mail, lê o token do arquivo gravado, redefine a senha, entra com
ela e confirma que o link não pode ser reutilizado. Ele também confere que a resposta
de `/passwords/forgot` é idêntica para e-mail existente e inexistente. Os arquivos que
o teste gera são apagados no fim.

`api-test.php` cria registros temporários prefixados com `ZZ ` e usuários `zz.*`, e
**apaga todos eles do banco** ao terminar — a última verificação da suíte falha se
algum sobrar. Rodá-la dez vezes deixa o banco exatamente como estava.

> Isso já não era verdade: até a correção a limpeza apenas *inativava* os registros,
> e cada execução deixava lixo permanente. As telas de Usuários e Categorias do
> painel chegaram a 33 usuários e 34 categorias de teste. Se o seu banco ainda tem
> esse resíduo, rode
> `banco de dados atualizado/limpeza-dados-de-teste.sql` uma vez.
