# Roteiro da apresentação Joille

Elias Lindner Lanzarin e Gustavo Neumann. Turma a confirmar.

Abra [apresentacao.html](apresentacao.html) no navegador. Use os botões ou as teclas
direcionais para avançar; F11 deixa o navegador em tela cheia. Os slides e as
capturas estão no repositório e podem ser apresentados sem conexão à internet.

## Slides até três minutos

| Tempo | Conteúdo |
|---|---|
| 0:00–0:25 | Joille, integrantes, setor de joalheria e bijuteria e problema de pedidos dispersos |
| 0:25–0:55 | Público alvo, objetivo geral e objetivos específicos |
| 0:55–1:25 | Área pública e área do cliente, com as capturas reais |
| 1:25–2:00 | Administração, perfil e acompanhamento de solicitações |
| 2:00–2:30 | API, JWT, entidades e galeria 1:N |
| 2:30–3:00 | Validação e início da demonstração |

## Demonstração em cinco minutos

1. **0:00–0:40:** abrir site público, consultar catálogo e FAQs; mostrar cadastro e login.
2. **0:40–1:40:** entrar como cliente, atualizar perfil e foto; criar uma solicitação;
   recarregar para mostrar persistência; editar quantidade e descrição de uma pendente.
3. **1:40–2:50:** entrar como administrador; cadastrar categoria e serviço, editar e
   inativar um registro de demonstração; publicar/despublicar uma FAQ; exibir o CRUD de usuários.
4. **2:50–3:40:** enviar duas imagens a um serviço, recarregar a galeria, excluir uma
   e confirmar que a outra permaneceu. Mostrar a solicitação e atualizar o atendimento.
5. **3:40–4:20:** mostrar gráficos e abrir um PDF; mostrar mensagens e a captura local
   de e-mails, explicando que SMTP externo depende da configuração.
6. **4:20–5:00:** no Postman, executar login e GET protegido com Bearer; repetir sem
   token para mostrar 401. Mostrar um POST/PUT JSON e os metadados da galeria.

Reservar mais **dois minutos para perguntas**, conforme o enunciado. O tempo é por
estudante; combinar previamente a divisão das falas e manter o percurso completo
disponível para ambos. Se o professor exigir o app nativo, abrir também o projeto
da disciplina de Dispositivos Móveis e demonstrar a mesma API.

## Preparação

Usar dados de demonstração; iniciar Wamp, testar login e conexão ao banco, deixar
a coleção Postman importada e verificar os arquivos de imagem antes da aula.
Não mostrar senhas, tokens ou configurações privadas nos slides. Os testes
automatizados devem ser executados no banco descartável, não durante a
apresentação no banco de trabalho.
