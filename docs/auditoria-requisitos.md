# Auditoria dos requisitos do trabalho

Revisão de **20/11/2026**. Projeto: `joille2`. Integrantes: Elias Lindner Lanzarin
e Gustavo Neumann. Turma a confirmar.

## Referências e método

Foi conferido o enunciado fornecido pelo usuário e os complementos oficiais de
[integração](https://github.com/sorfabiosantos/acme-3am/blob/main/SE2-Etapa05-Integra%C3%A7%C3%A3oBackend-Frontend.md),
[JavaScript orientado a objetos](https://github.com/sorfabiosantos/acme-3am/blob/main/SE2-Etapa06-JavaScriptOrientadoObjetos.md)
e [upload](https://github.com/sorfabiosantos/acme-3am/blob/main/SE2-Etapa07-Upload-de-Imagens-Arquivos.md).
O enunciado não contém uma Etapa 04; nenhum requisito foi presumido para esse número.

A revisão combinou leitura de rotas, modelos e telas, testes de contrato e POO,
testes HTTP com PHP e MySQL reais e navegação pelo navegador. Os testes de escrita
usam um banco descartável na porta 33316. O banco habitual não recebe registros
de teste; nele foi aplicada somente a migration 004, após backup integral.

## Conferência por etapa

| Etapa | Aplicação e evidência | Situação |
|---|---|---|
| SE1 01 definição | [Documento com seis seções e universo de discurso](definicao-do-trabalho.md), autores identificados | Conteúdo preparado; turma e entrega externa pendentes |
| SE1 01 Drive | Exige pasta e documento editáveis pelo professor e link na planilha da turma | Pendente externo; não foi presumido compartilhamento |
| SE1 02 pública | Home, Sobre, Contato com formulário, localização, cadastro, login e FAQs em `themes/web` | Implementado |
| SE1 02 aplicação | Perfil com nome, e-mail, endereço, telefone, senha e foto; solicitações próprias com criação, leitura, edição e cancelamento | Corrigido e integrado |
| SE1 02 administração | Dashboard, perfil próprio, usuários/administradores, serviços, categorias, FAQs e solicitações | Corrigido e integrado |
| SE1 02 FAQs | Criar, consultar, editar, excluir logicamente, publicar e despublicar; consulta pública respeita publicação | Validado |
| SE1 03 banco | MySQL via PDO, SQL completo, migrations, universo de discurso, ER atual em SVG e diagrams.net | Atualizado |
| SE1 03 rotas | Serviços e categorias têm listagem, ID, paginação, POST, PUT e DELETE no formato solicitado | Validado |
| SE1 03 JWT | Assinatura, validade, identidade por ID, conta ativa e perfil conferidos no backend | Corrigido e validado |
| SE1 03 documentação | [API](../api/readme.md), parâmetros, respostas, permissões e coleção Postman | Atualizado |
| SE2 05 integração | Cadastro/login/logout, CRUDs ligados à API, armazenamento por área, mensagens e estados de lista | Validado |
| SE2 06 POO clássica | Entidades em `classes/entities`, serviços derivados de `HttpClientBase`, sem fetch nas telas | Validado |
| SE2 06 protótipos | Toast, FormController, CrudController, ImageGallery, Accordion e ChartPanel usados nas telas | 159 verificações POO aprovadas |
| SE2 06 branch | A branch `javascript-poo` existe no remoto; esta revisão completa está em `etapa07-imagens` | Branches identificadas |
| SE2 07 perfil | Upload multipart, prévia, MIME real, limite, ID obtido do JWT e substituição após persistência | Validado, inclusive rollback |
| SE2 07 galeria | Relação 1:N com FK, duas imagens independentes, listagem persistente, remoção individual e limpeza | Validado, inclusive rollback |
| SE2 08 apresentação | [Slides locais](apresentacao.html) com introdução, objetivos, telas e API; [roteiro de 8 minutos](roteiro-apresentacao.md) | Material preparado |
| SE2 08 demonstração presencial | Apresentação em dupla, tempo e perguntas do professor | Depende dos integrantes |

## Tecnologias obrigatórias

| Requisito | Onde se aplica |
|---|---|
| Composer | `composer.json`, `composer.lock` e autoload PSR-4 |
| Git e GitHub | Repositório `Nxght01/Joille-2.0`; esta revisão está na branch `etapa07-imagens` |
| HTML5, CSS, JavaScript | Temas PHP geram HTML5; assets por área; módulos ES |
| PHP OO e MVC | `source/Models`, `source/Controller`, `source/App`, `themes` |
| Rotas amigáveis de terceiros | CoffeeCode Router, estendido em `source/Core/Router.php` para JSON |
| JWT de terceiros | Firebase PHP-JWT em `source/Core/JWTToken.php` |
| PDF de terceiros | Dompdf em `source/Support/PdfReport.php` |
| Gráficos de terceiros | Chart.js no dashboard |
| Imagens de terceiros e classe da disciplina | `Upload` valida; `ImageUploader` usa CoffeeCode Uploader para processar |
| E-mail de terceiros | PHPMailer, contato e recuperação; SMTP configurável e captura local explícita |
| Tratamento de erros | Envelopes HTTP/JSON, exceções centralizadas, estados de erro e toasts |
| API para mobile | JWT Bearer, JSON e multipart; contratos e coleção Postman em `api/` |
| Banco MySQL/MariaDB | PDO, chaves estrangeiras, migrations e exclusão lógica |

## Correções principais

- A área do cliente ganhou o fluxo completo de solicitações, que antes não existia.
- O administrador passou a ter perfil próprio, troca de senha e upload de foto.
- O e-mail deixou de ser usado como identidade da sessão: alterá-lo não invalida o acesso.
- JSON passou a ser lido corretamente, com rejeição de corpos malformados e objetos inesperados.
- Falhas de conexão ou persistência retornam erro HTTP sem expor SQL ou caminhos internos.
- Atualizações sem mudança de valor são aceitas; a exclusão repetida continua retornando 404.
- Serviços validam preço e categoria; paginação valida limites e omite inativos.
- FAQs despublicadas e serviços inativos não ficam acessíveis por ID sem administração.
- Links de carrinho e cadastro de produtos passaram a levar aos fluxos implementados.
- URLs dos módulos acompanham a pasta e a porta do site; navegação administrativa se ajusta a telas menores.
- Foram bloqueados acessos HTTP a código interno, testes e backups; a suíte PHP só roda por CLI.

## Resultados e reprodução

- `node poo-test.mjs`: **159 verificações aprovadas**.
- `node --test http-contract-test.mjs`: **9 testes aprovados**.
- `node image-test.mjs`: validações de arquivo e concorrência do componente aprovadas.
- `php api-test.php`: **140 verificações aprovadas**.
- `node tests/acceptance.mjs`: **91 verificações aprovadas**; detalhes em [aceite.json](../evidencias/auditoria/aceite.json).
- Sintaxe PHP conferida nos arquivos da aplicação; Composer validou o manifesto.

No Windows com os binários Wamp indicados nos scripts:

```powershell
./tests/start-env.ps1
./tests/run-tests.ps1
./tests/stop-env.ps1
```

O ambiente fica em `http://127.0.0.1:8086`, usa o banco `joille_test` e dados em
`.runtime/db`. O script recusa testes destrutivos fora desse diretório. A
configuração real e os dados atuais são preservados. Ajuste os caminhos dos
binários se sua instalação do Wamp usar outras versões.

## Pendências que não se resolvem apenas no código

Confirmar a turma; compartilhar Drive e informar a planilha; configurar SMTP
para envio externo; realizar a apresentação.
O aplicativo nativo da disciplina de Dispositivos Móveis não está neste repositório
e sua integração não foi comprovada. A estrutura segue a separação MVC da base
ACME disponível, com PHP compartilhado na raiz para evitar duas cópias das classes;
o link específico de estrutura no Moodle não veio no enunciado e precisa de
conferência caso seja diferente do repositório ACME citado.
