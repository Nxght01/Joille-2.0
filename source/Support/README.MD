# source/Support/

Funções e serviços de apoio.

## Helpers.php

Carregado pelo Composer (`autoload.files`), fora de qualquer namespace.

```php
url();                 // http://localhost/projeto/joille2
url("api");            // http://localhost/projeto/joille2/api
url("views/assets/public/styles/home.css");
```

Use `url()` em **todos** os links e caminhos de asset das views — as rotas amigáveis
quebram caminhos relativos.

## ImageUploader.php

Envolve o `CoffeeCode\Uploader\Image`. Aceita apenas `jpg`, `jpeg` e `png` (validado
pelo **conteúdo** do arquivo, não pelo tipo que o navegador declara), no máximo 4 MB,
grava em `uploads/images/AAAA/MM/` e devolve o caminho relativo à raiz do projeto —
o valor guardado em `users.photo` e em `service_images.path`.

```php
$uploader = new ImageUploader();

// Um arquivo. A largura define o redimensionamento feito no envio.
$path = $uploader->upload($_FILES["photo"], ImageUploader::PROFILE_WIDTH);  // null em erro

// Vários arquivos de um input `multiple` (campo `images[]`)
$paths = $uploader->uploadMany("images", $_FILES);   // [] quando nenhum é válido

// Remove o arquivo do disco
$uploader->remove($path);

$erro = $uploader->getErrorMessage();
```

`uploadMany()` não aborta no primeiro arquivo inválido: envia os que dão, e os
recusados aparecem em `getErrorMessage()`.

`remove()` só apaga caminhos **dentro de `uploads/`** — um valor adulterado no banco
não consegue alcançar outros arquivos do projeto.

A raiz do projeto é fixada internamente com `chdir()` e restaurada em seguida, porque
o uploader resolve caminhos a partir do diretório de trabalho atual — que, numa
requisição de API, é `api/`.

## Mailer.php

Envio de e-mail com PHPMailer, usado pela recuperação de senha e pelo formulário de
contato. Nunca lança exceção para quem chama: `send()` devolve `bool` e o motivo da
falha fica em `getErrorMessage()`, para o controlador escolher o código HTTP.

```php
$mailer = new Mailer();

if (!$mailer->isConfigured()) {
    // modo "smtp" com CONF_MAIL_USER / CONF_MAIL_PASSWD em branco
}

$mailer->send($email, $nome, "Assunto", "<p>corpo em HTML</p>", $replyTo);
```

O corpo recebe o layout institucional (cabeçalho, rodapé e cores do sistema) dentro
da própria classe — quem chama passa só o conteúdo.

### Dois modos (`CONF_MAIL_MODE`)

| Modo | O que faz |
|------|-----------|
| `"smtp"` | Envia de verdade, com as credenciais de `CONF_MAIL_USER`/`CONF_MAIL_PASSWD`. |
| `"capture"` | Grava a mensagem em `storage/mail/` e devolve `true`, sem tocar na rede. |

O modo `"capture"` existe para que os fluxos que dependem de e-mail sejam testáveis
pelo frontend sem servidor de e-mail. Quem chama não muda: é a própria classe que
decide o destino.

```php
Mailer::isCaptureMode();        // true quando o modo de teste está ativo
Mailer::captureInboxUrl();      // URL da caixa de entrada local
$mailer->getLastCapturePath();  // caminho relativo do último arquivo gravado
```

Os controladores usam `isCaptureMode()` só para **ajustar a mensagem** da resposta —
sem isso, o usuário ficaria esperando um e-mail que nunca chega. `Passwords::forgot()`
mantém a resposta idêntica para e-mail existente e inexistente também neste modo, para
não abrir a brecha de enumeração de contas.

## PdfReport.php

Relatórios em PDF com Dompdf. Recebe título, subtítulo, indicadores e uma tabela, e
devolve o arquivo pronto.

```php
$report = new PdfReport("Relatório de Serviços", "Catálogo por categoria");
$report->setSummary(["serviços" => 12, "ativos" => 10])
       ->setColumns(["#", "Serviço", "Valor"])
       ->setRows([[1, "Banho de ouro", "R$ 200,00"]]);

$report->stream("relatorio.pdf");     // false quando a renderização falha
$bytes = $report->getOutput();        // ou os bytes, para gravar/anexar
```

`stream()` limpa os buffers de saída abertos pelo roteador antes de escrever — sem
isso, qualquer espaço já emitido corromperia o PDF.
