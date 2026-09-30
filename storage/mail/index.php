<?php
/**
 * Caixa de entrada local — FERRAMENTA DE DESENVOLVIMENTO.
 *
 * Lista os e-mails gravados por Source\Support\Mailer quando CONF_MAIL_MODE está
 * em "capture". Serve para testar, pelo frontend, os fluxos que dependem de
 * e-mail (recuperação de senha e formulário de contato) sem servidor de e-mail.
 *
 * Só funciona no modo "capture": em "smtp" a página se recusa a listar, para que
 * mensagens antigas — que contêm links de recuperação de senha — não fiquem
 * expostas em um ambiente que já envia de verdade.
 */

require __DIR__ . "/../../vendor/autoload.php";

if (CONF_MAIL_MODE !== "capture") {
    http_response_code(403);
    echo "<!DOCTYPE html><html lang=\"pt-BR\"><meta charset=\"UTF-8\">"
        . "<p style=\"font-family:sans-serif\">A caixa de entrada local está desativada: "
        . "<code>CONF_MAIL_MODE</code> não está em <code>\"capture\"</code>.</p>";
    exit;
}

$files = glob(__DIR__ . "/*.html") ?: [];

// Mais recentes primeiro — o nome começa com AAAAMMDD-HHMMSS
rsort($files);

/** Lê o cabeçalho gravado pelo Mailer para montar a listagem. */
function readMeta(string $file): array
{
    $html = (string)file_get_contents($file);
    $meta = ["Para" => "-", "Assunto" => "-", "Data" => "-"];

    foreach (array_keys($meta) as $label) {
        if (preg_match("~<th align=\"left\">{$label}</th><td>(.*?)</td>~s", $html, $found)) {
            $meta[$label] = html_entity_decode($found[1], ENT_QUOTES, "UTF-8");
        }
    }

    return $meta;
}

$e = fn(?string $value) => htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Caixa de entrada local — <?= $e(CONF_COMPANY_NAME); ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; background: #e9e4de; color: #3d2c23;
               font-family: Arial, Helvetica, sans-serif; font-size: 14px; }
        main { max-width: 900px; margin: 0 auto; }
        h1 { font-size: 1.35rem; margin: 0 0 4px; }
        .lead { color: #7a6a60; margin: 0 0 20px; line-height: 1.6; }
        .notice { background: #fff3cd; border: 1px solid #ffe08a; border-radius: 6px;
                  padding: 12px 14px; margin-bottom: 20px; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; background: #fff;
                border-radius: 8px; overflow: hidden; }
        th, td { text-align: left; padding: 11px 14px; border-bottom: 1px solid #ede6e0; }
        th { background: #3d2c23; color: #f5f2ee; font-size: .78rem;
             text-transform: uppercase; letter-spacing: .04em; }
        tr:last-child td { border-bottom: none; }
        td.date { white-space: nowrap; color: #7a6a60; font-size: .85rem; }
        a { color: #8c6a4f; }
        .empty { background: #fff; border-radius: 8px; padding: 32px; text-align: center;
                 color: #7a6a60; }
        code { background: #f1ebe6; padding: 1px 5px; border-radius: 3px; font-size: .9em; }
        footer { margin-top: 18px; color: #7a6a60; font-size: .82rem; line-height: 1.6; }
    </style>
</head>
<body>
<main>
    <h1>Caixa de entrada local</h1>
    <p class="lead">
        <?= count($files); ?> mensagem(ns) capturada(s) em <code><?= $e(CONF_MAIL_CAPTURE_DIR); ?></code>.
    </p>

    <p class="notice">
        <strong>Modo de teste ativo.</strong> Nenhum e-mail está saindo para a internet.
        Para enviar de verdade, troque <code>CONF_MAIL_MODE</code> para <code>"smtp"</code>
        em <code>source/Config/Config.php</code> e preencha <code>CONF_MAIL_USER</code>
        e <code>CONF_MAIL_PASSWD</code>.
    </p>

    <?php if (empty($files)): ?>
        <p class="empty">
            Nenhuma mensagem ainda.<br><br>
            Experimente <a href="<?= url("esqueci-senha"); ?>">recuperar uma senha</a>
            ou <a href="<?= url("contato"); ?>">enviar uma mensagem pelo contato</a>.
        </p>
    <?php else: ?>
        <table>
            <thead>
                <tr><th>Recebido</th><th>Para</th><th>Assunto</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($files as $file):
                $meta = readMeta($file);
                $name = basename($file);
            ?>
                <tr>
                    <td class="date"><?= $e($meta["Data"]); ?></td>
                    <td><?= $e($meta["Para"]); ?></td>
                    <td><?= $e($meta["Assunto"]); ?></td>
                    <td><a href="<?= $e($name); ?>" target="_blank" rel="noopener">abrir</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <footer>
        Para limpar a caixa, apague os arquivos <code>.html</code> desta pasta.
        A pasta <code>storage/</code> está no <code>.gitignore</code>.
    </footer>
</main>
</body>
</html>
