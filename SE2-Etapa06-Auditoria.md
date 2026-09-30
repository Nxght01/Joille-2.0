# Auditoria de conformidade — Etapa 06

> Registro da auditoria de 24/09/2026. A solicitação posterior de publicação
> prepara esta implementação para entrega na branch `javascript-poo`; as referências
> abaixo a arquivos sem commit descrevem o estado encontrado na data da auditoria.

> Registro da auditoria de 24/09/2026. A solicitação posterior de publicação
> prepara esta implementação para entrega na branch `javascript-poo`; as referências
> abaixo a arquivos sem commit descrevem o estado encontrado na data da auditoria.

Data: 24/09/2026. Enunciado conferido diretamente no
[GitHub do professor](https://github.com/sorfabiosantos/acme-3am/blob/main/SE2-Etapa06-JavaScriptOrientadoObjetos.md).

## Veredito

**Os requisitos funcionais e de arquitetura examinados estão implementados e os
fluxos exigidos passaram nos testes. A entrega Git ainda está pendente.**
Não é correto declarar a entrega 100% pronta enquanto a implementação está fora
dos commits. Também não há uma versão estável da Etapa 05 identificada no histórico
que permita demonstrar ausência de regressões por comparação.

## Checklist do enunciado

| Exigência | Resultado | Evidência |
|---|---|---|
| Uma classe por entidade usada no frontend | Atende | `views/assets/_common/classes/entities/`: Service, Category, Faq, FaqType, User, ServiceImage, ContactMessage, com base Entity |
| Propriedades privadas quando apropriado | Atende | Campos `#id`, `#name`, `#value`, `#password` etc.; 159 asserções de POO aprovadas |
| Construtores, getters, setters e validações | Atende | Instanciação nas telas; nomes, preços, e-mail e perfil validados; preço negativo recusado também no navegador |
| Métodos próprios das entidades | Atende | `formattedValue`, `resume`, `toSession`, `firstName`, `isActive`, `label` |
| Conversão entre payload e objeto | Atende | `fromApi`, `collectionFromApi`, `toJSON`, `toFormData`; leitura real devolve instâncias das classes |
| Serviços derivados de HttpClientBase | Atende | `HttpClientBase → ApiService → serviços por recurso` |
| Reuso de get/post/put/delete, URL, cabeçalhos e autenticação | Atende | Testes HTTP simulados e testes reais usando os próprios serviços JavaScript |
| Telas usam serviços, sem fetch direto | Atende | Busca no código encontrou chamadas fetch somente em HttpClientBase |
| Serviços sem manipulação de HTML | Atende | Separação verificada na inspeção e na suíte POO |
| CRUD da entidade principal e duas entidades | Atende | Serviços + Categorias + FAQs: criar, listar, buscar, editar e excluir/inativar testados contra o banco real |
| Cadastro, login e perfil pelas rotas reais | Atende | Usuário temporário cadastrado, login POST, perfil lido e atualizado por PUT, releitura confirmada |
| Pelo menos duas funcionalidades prototípicas, sem class | Atende | Toast usa Object.create; FormController e CrudController usam funções construtoras e prototype |
| Métodos compartilhados no protótipo | Atende | Testes com duas instâncias verificam igualdade das funções e estado independente |
| Explicação do compartilhamento no README | Atende | `views/assets/README.MD`, seção POO prototípica, contém o exemplo de dois toasts |
| Toast success/warning/error nas cores exigidas | Atende | Regras CSS verde/amarelo/vermelho e testes das três variantes |
| Respostas com/sem data; nunca mostrar data inteira | Atende | 9 testes HTTP incluem esses casos; mensagem vem de message |
| Resposta inesperada não silenciada | Atende | Fallback visual + registro de erro, cobertos pelos testes |
| Carregamento, sucesso e erro | Atende | ui.js, setBusy e toasts; observados sucesso, validação e erro de sessão no navegador |
| README com arquitetura e execução | Atende | `readme.md`, `views/assets/README.MD`, `SE2-Etapa06-Guia.md` |
| Evidências e explicação de encapsulamento, herança, composição e protótipos | Atende | Guia, testes, esta auditoria e `SE2-Etapa06-live-results.json` |
| Branch javascript-poo | Existe localmente | Branch atual; criada do HEAD local 2419af5 |
| Branch contendo a implementação para entrega | **Pendente** | `views/`, classes, testes e documentos continuam não rastreados; outras mudanças também estão sem commit |
| Partir da versão estável anterior; preservar comportamento | **Não comprovado integralmente** | Histórico não identifica claramente a Etapa 05 estável; testes atuais não substituem comparação com essa versão |

Os exemplos de Product do professor foram adaptados a Service, a entidade principal
do Joille. O enunciado não exige transformar a joalheria no domínio de produtos ou
corridas do ACME. Ele exige aplicar a arquitetura aos recursos do próprio projeto.

## Testes efetivamente executados

- `node poo-test.mjs`: **159 asserções passaram**, zero falhas.
- `node --test http-contract-test.mjs`: **9 testes passaram**, zero falhas.
- `node image-test.mjs`: suíte aprovada.
- Auditoria real: **33 verificações funcionais passaram**, mais a confirmação
  de limpeza por IDs exatos. Resultado registrado em `SE2-Etapa06-live-results.json`.
- PHP: arquivo alterado aprovado na verificação de sintaxe.

A auditoria real usa as classes JavaScript que as telas usam; não substitui fetch.
Ela confirma persistência relendo os registros após POST e PUT, verifica a
inativação após DELETE e confirma que as listas públicas omitem os inativos.
Também verifica que cliente não cria categoria e que categoria com serviço ativo
não pode ser excluída.

Os registros temporários de cada execução foram removidos apenas por seus IDs e
após conferir um marcador exclusivo no nome. Não foi usada a limpeza abrangente
por prefixos da antiga suíte PHP. Não foram enviados e-mails.

## Verificação no navegador e correção encontrada

Na tela administrativa de Serviços:

1. Preço `-1`: setter recusou e exibiu "O valor deve ser um número não negativo".
2. Preço `0`: inicialmente o backend recusou como obrigatório, embora a classe
   JavaScript o aceitasse. Isso era uma inconsistência real.
3. Corrigidos os dois testes de vazio em `source/Controller/Services.php`: PHP
   considera `empty("0")` verdadeiro; a verificação agora compara o texto com `""`.
   A correção cobre tanto criação quanto edição.
4. Após a correção, o navegador cadastrou o serviço de auditoria com preço zero
   e exibiu "Serviço cadastrado com sucesso".
5. Editar recuperou o nome, descrição, categoria e preço zero gravados.
6. Nome e preço foram alterados para `12.50`; a tela exibiu sucesso e atualizou a lista.
7. Excluir pediu confirmação. A releitura da API confirmou `active = 0` e valor `12.5`.
8. O serviço de teste de ID 50 foi removido por ID e marcador exatos ao fim.

As listas administrativas mostram também registros inativos por projeto; exclusão
no CRUD significa inativação, não remoção física imediata.

## Como repetir a auditoria real

Em PowerShell, na raiz `joille2`, com WAMP e banco local ativos:

```powershell
$env:JOILLE_ADMIN_EMAIL = '<email do administrador local>'
$env:JOILLE_ADMIN_PASSWORD = '<senha do administrador local>'
node tests/etapa06-live-audit.mjs
```

O executável PHP padrão é o PHP 8.3.28 deste WAMP. Em outra instalação, configure
`JOILLE_PHP` com seu caminho. Esta suíte cria e remove registros de auditoria;
execute no ambiente local de desenvolvimento. O login pode renovar a sessão
administrativa, exigindo novo login no navegador.

## O que falta para entregar

1. Identificar/documentar a revisão estável anterior exigida pelo professor.
2. Revisar e registrar em commits os arquivos necessários na branch `javascript-poo`.
   Criar uma branch sem commits novos não inclui arquivos não rastreados na entrega.
3. Publicar/entregar essa branch pelo canal solicitado pelo professor.

Esta auditoria não fez commit, push nem declarou aprovação pelo professor. Ela
registra exatamente o que foi inspecionado, testado, corrigido e o que falta.
