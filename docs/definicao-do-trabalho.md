# Definição do trabalho prático Joille

**Integrantes:** Elias Lindner Lanzarin e Gustavo Neumann

**Professor:** Fábio Luís da Silva Santos

**Turma:** a confirmar
**Revisão:** 20 de novembro de 2026

Este documento descreve o escopo implementado do Joille e complementa a definição
inicial com o universo de discurso do banco. Os resultados se referem a testes
locais; não representam uma medição de uso em uma empresa real.

## Resumo

O Joille é um micro SaaS para pequenas empresas de joalheria e bijuteria. Reúne
catálogo de serviços, solicitações de atendimento, perfis de clientes,
administração de usuários, perguntas frequentes e mensagens de contato. Oferece
galerias de imagens, indicadores, relatórios PDF e uma API REST autenticada por
JWT, disponível para o site e para integração com aplicativos móveis.

## Área de atuação

O sistema se aplica ao setor de serviços ligado à produção, manutenção e
acabamento de joias e bijuterias, como polimento e reparo de peças. Empresas de
pequeno porte podem receber pedidos por canais separados e perder informações
sobre quantidades, descrição do atendimento e situação de cada pedido.

O cenário adotado é uma empresa demonstrativa com uma equipe administrativa e
clientes que solicitam serviços. Cada instalação atende uma empresa. O catálogo
organiza os serviços por categoria; o cliente registra sua necessidade e a
equipe acompanha o atendimento. O sistema mantém o histórico das solicitações
e a estimativa calculada com o preço vigente na criação.

O escopo atual cobre atendimento e catálogo. Cobrança de assinaturas, gestão de
estoque, pagamentos e emissão fiscal ficam como possíveis ampliações. As tabelas
legadas de pagamentos e status não representam funcionalidades implementadas.

## Público alvo

Os usuários são clientes e responsáveis por pequenas empresas de joias e
bijuterias, em geral adultos, com conhecimentos básicos de navegação e uso de
formulários. Podem acessar o sistema em computadores e celulares. Não há faixa
etária obrigatória no cadastro.

Visitantes consultam informações institucionais, serviços, localização e FAQs,
enviam mensagens e criam uma conta. Clientes autenticados mantêm nome, e-mail,
senha, endereço, telefone e foto; criam, consultam, editam e cancelam suas próprias
solicitações enquanto estiverem pendentes. Administradores mantêm seu perfil,
gerenciam usuários e administradores, categorias, serviços e imagens, publicam ou
despublicam FAQs, acompanham solicitações, consultam mensagens, gráficos e PDFs.

## Objetivo geral

Centralizar o catálogo e o acompanhamento de solicitações de serviços de uma
pequena joalheria ou empresa de bijuterias, permitindo que clientes e equipe
administrativa consultem informações consistentes sobre cada atendimento.

## Objetivos específicos

- Oferecer páginas públicas com informações institucionais, contato e FAQs.
- Permitir cadastro, login, recuperação de senha e manutenção do perfil.
- Organizar o catálogo com CRUD de serviços e categorias e galeria de imagens.
- Registrar solicitações vinculadas ao cliente autenticado e acompanhar seu andamento.
- Restringir operações administrativas e impedir acesso aos pedidos de outro cliente.
- Fornecer uma API REST documentada, com JWT, JSON e upload multipart.
- Apresentar indicadores e relatórios PDF a partir dos registros do banco.
- Aplicar PHP orientado a objetos e MVC, JavaScript clássico e prototípico e MySQL/MariaDB.

## Conclusões

O sistema permite demonstrar o percurso desde a consulta pública até a criação e
conclusão de uma solicitação. A integração usa os mesmos registros no site, na
área do cliente, na administração e na API. Os testes locais cobrem cadastro,
permissões, CRUDs, publicação de FAQs, recuperação de senha, PDFs, upload e
consistência entre banco e arquivos, incluindo falhas simuladas de persistência.

A centralização oferece uma base para reduzir a dispersão de informações sobre
atendimentos. Não foram medidos ganhos de produtividade em operação real. O envio
externo de e-mails depende da configuração de SMTP; o ambiente local usa captura
de mensagens. A integração com o aplicativo nativo deve ser demonstrada com o
projeto da disciplina de Dispositivos Móveis. Como evolução, podem ser incluídos
estoque, pagamentos, notificações e atendimento de várias empresas na mesma instalação.

## Universo de discurso do banco de dados

Um **tipo de usuário** (`users_types`) classifica várias **contas** (`users`) como
administrador ou cliente. Cada conta possui nome, e-mail único, hash de senha,
situação, foto e dados de contato. Uma conta pode possuir várias **solicitações**
(`service_requests`) e vários **tokens de recuperação** (`password_resets`). Cada
token pertence a uma única conta, tem validade e registro de consumo.

Uma **categoria** (`categories`) reúne vários **serviços** (`services`). Cada
serviço tem nome, descrição, valor e situação. Pode possuir várias **imagens**
(`service_images`), cada uma vinculada a um único serviço. O banco armazena o
caminho e os metadados; os arquivos permanecem no diretório de uploads. A
inativação do serviço pela API também remove os registros e arquivos da galeria.

Cada **solicitação** pertence a um cliente e a um serviço. Contém descrição,
quantidade, preço unitário capturado do catálogo, situação, observação da equipe
e datas. O preço, o dono e o estado inicial são definidos pelo servidor. Um
cliente acessa somente suas solicitações; o administrador acessa todos os
atendimentos. O cancelamento preserva o registro, e serviços e usuários usam
exclusão lógica para conservar o histórico.

Um **tipo de FAQ** (`types`) agrupa várias **perguntas** (`questions`). Cada
pergunta tem resposta e situação de publicação. Visitantes só consultam
perguntas publicadas. Uma **mensagem de contato** (`contacts`) guarda nome,
e-mail, assunto, mensagem e marcação de resposta; a associação com `users` é
opcional, pois visitantes também podem escrever.

As tabelas `payments`, `status` e `users_categories` foram preservadas do modelo
inicial e estão identificadas como legadas no diagrama. As associações antigas
`users.status_id` e `users.services_id` também não participam do novo fluxo de
solicitações. Não se deve confundir essas estruturas com módulos operacionais.

O [diagrama ER](diagrama-er.svg) e o [arquivo editável no diagrams.net](diagrama-er.drawio)
representam a estrutura atual. O SQL de instalação é
[`joille.sql`](../banco%20de%20dados%20atualizado/joille.sql); instalações existentes
recebem a migration 004 por [`scripts/migrate.php`](../scripts/migrate.php), com
backup prévio e sem reimportar o dump.

## Entrega acadêmica

Antes do envio, confirmar a turma, criar ou localizar a pasta do Google Drive,
compartilhar a pasta e o documento com `fabiosantos@ifsul.edu.br` como editor e
informar o link na planilha da turma. Essas ações externas e o envio ao GitHub
não são comprovados pela existência dos arquivos locais.
