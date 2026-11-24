# JOILLE

Integrantes: **Elias Lindner Lanzarin e Gustavo Neumann**. Turma a confirmar.

## Agência de tecnologia para o ramo joalheiro

As seis ilustrações dos serviços estão em `views/assets/public/images/services/`,
com os prompts e a correspondência dos serviços no `catalog.json`. Para instalá-las
em outro banco já configurado com o catálogo da agência, execute
`php scripts/install-service-images.php --apply`. O script copia os PNGs para uploads,
vincula as capas pelo nome do serviço e preserva as imagens já existentes. Executá-lo
novamente não duplica as imagens. As imagens foram criadas com a ferramenta nativa
de geração de imagens e são ilustrações dos serviços.

O catálogo oferece infraestrutura de redes, sistemas de gestão, integração de
máquinas e dados, além de suporte. O cliente envia um briefing em **Meus projetos**;
o administrador acompanha análise, execução e entrega, com retornos para a empresa.
Preço, escopo e prazo são combinados após a análise. Enviar um briefing não confirma
a contratação. O portal recebe e acompanha projetos; as integrações são serviços
a desenvolver conforme cada proposta.

Na instalação nova, o dump `banco de dados atualizado/joille.sql` já inclui o catálogo.
Para converter uma instalação existente, após a migration 004, execute **uma vez**:

```bash
php scripts/migrate-agency.php --apply
```

O script cria um backup integral local ignorado pelo Git e aplica a migration 005
em transação. Serviços, categorias e FAQs anteriores são inativados; solicitações,
valores históricos e imagens são preservados. Não reaplique para atualizar textos:
a conversão inativa serviços fora do catálogo inicial. Edite-os pelo painel.

Para uma limpeza definitiva solicitada pelo responsável, `php scripts/cleanup-inactive.php`
mostra os inativos sem alterar dados. A aplicação exige `--apply --database=joille`,
gera outro backup e recusa a exclusão quando projetos ou mensagens dependem desses
registros. Também limpa tokens de recuperação já usados ou vencidos. Não exclui outros
bancos, arquivos de backup ou tabelas da estrutura.

Convenção da API: `services.value = 0` e `service_requests.unit_price = 0` representam
**sob orçamento**, exibido sem valor monetário no catálogo e nas solicitações.
`quantity` representa o número de unidades/filiais do projeto; o briefing registra
empresa, cidade, áreas, equipamentos, sistemas e prazo desejado. Valores positivos
de solicitações antigas continuam monetários. O retorno do admin não altera o preço
registrado. O administrador pode definir uma estimativa total em **Valor preliminar**,
visível ao cliente, sem multiplicar pelas unidades. Campo vazio remove a estimativa.
Esta versão não inclui emissão ou aceite eletrônico de propostas.

Para habilitar essa opção em bancos existentes: `php scripts/migrate-estimate.php --apply`.
A migration 006 cria `service_requests.preliminary_value` após salvar backup da tabela;
é seguro executar o script novamente. Instalações novas já recebem a coluna no dump.

## Revisão integral do trabalho em novembro de 2026

A revisão inclui solicitações de serviço do cliente, gestão do atendimento,
perfil administrativo, endereço/telefone, suporte a JSON, correções de sessão,
validação e erros. Consulte a [auditoria de cada requisito](docs/auditoria-requisitos.md),
a [definição do trabalho](docs/definicao-do-trabalho.md), o [diagrama ER atual](docs/diagrama-er.svg)
e a [apresentação local](docs/apresentacao.html).

**Quem já tem banco deve aplicar a migration 004 sem reimportar o dump:**

```bash
php scripts/migrate.php --apply
```

O script cria backup integral em arquivo ignorado pelo Git antes de adicionar os
campos e a tabela. É seguro executá-lo novamente: alterações existentes são
detectadas. Para instalação nova, `joille.sql` já inclui a migration 004.

Novas telas: `/app/solicitacoes`, `/admin/solicitacoes`, `/admin/perfil` e
`/admin/redefinir-senha`. A documentação complementar dos
[contratos da API](api/contratos-atualizados.md) inclui a integração mobile.

Testes: 159 verificações de POO, 9 testes de contrato HTTP, testes do componente de
imagens, 140 verificações da API e 117 de aceite. `tests/start-env.ps1` e
`tests/run-tests.ps1` usam PHP/MySQL reais e um banco descartável, separado dos dados
da instalação. Encerrar com `tests/stop-env.ps1`.

Sistema de gerenciamento para empresas de pequeno porte dos ramos de joalheria e
bijuteria. Aplicação MVC em PHP com uma API REST separada.

## Tecnologias

### Envio real de e-mails pelo Gmail

Os contatos são encaminhados para **joilleifsul@gmail.com**, usando essa mesma
conta como remetente, SMTP `smtp.gmail.com:587` e STARTTLS. O endereço do visitante
fica no Reply-To. A confirmação do contato e a recuperação de senha continuam
sendo enviadas ao endereço do próprio usuário.

Na conta Google, ative a verificação em duas etapas e gere uma
[senha de app](https://support.google.com/accounts/answer/185833?hl=pt-BR).
No PowerShell, execute `scripts/configure-gmail.ps1` e cole a senha na entrada
oculta. O script salva `source/Config/Mail.secret.php` (ignorado pelo Git e bloqueado
para acesso web) e envia um teste à conta da Joille. Não use a senha normal da conta.
Como alternativa, configure `JOILLE_MAIL_PASSWORD` no ambiente do processo PHP.

`php scripts/test-mail.php` confere a configuração sem imprimir a senha;
`php scripts/test-mail.php --send` envia um teste real. Aceitação SMTP não garante
que a mensagem vá para a caixa principal: confira também spam. Enquanto faltar
a credencial ou houver falha no SMTP, o contato fica salvo no painel e o formulário
informa que o encaminhamento por e-mail falhou. Os testes automatizados usam
configuração isolada em modo `capture` e não enviam mensagens externas.

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
3. O JavaScript deriva automaticamente a URL base a partir da pasta do site.
4. Gere uma nova `JWT_SECRET_KEY` para qualquer ambiente que não seja local.
5. Configure o SMTP (veja abaixo) para habilitar recuperação de senha e contato.

Usuários já cadastrados para teste:

| Perfil | E-mail | Senha | Onde entra |
|--------|--------|-------|-----------|
| Administrador | `admin@joille.com` | `admin@123` | `/admin/login` |

**Troque essas senhas antes de qualquer uso real.**

O endereço `/admin` redireciona para `/admin/login`. Após autenticar uma conta de
administrador, o sistema abre `/admin/dashboard`. O login administrativo usa o
mesmo tema do login de cliente, com uma sessão separada; ao sair do painel, volta
para `/admin/login`.

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
| Aplicação | `/app/` | cliente logado | Perfil (nome, e-mail, endereço, telefone, senha, foto), catálogo e solicitações de serviços |
| Administrativa | `/admin/` | administrador | Perfil próprio; dashboard com indicadores, gráficos e relatórios em PDF; CRUD de usuários, serviços (com N imagens), categorias e FAQs; solicitações e mensagens de contato |

## Autenticação

Login (`/api/users/login` ou `/api/users/login-admin`) devolve um JWT válido por
90 minutos. O front-end guarda token e usuário no `localStorage` através de
`SessionStorage`, em escopos separados para cliente e administrador, e envia o token
no header `Authorization: Bearer <token>`.

No servidor, `Source\Controller\Api::authToken(int|array $typeId)` decodifica o token e
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
php  api-test.php      # 140 verificações — API: CRUD, autenticação, upload, e-mail, PDF e HTTP
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
