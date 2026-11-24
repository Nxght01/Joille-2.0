<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/vendor/autoload.php';
use Source\Support\Mailer;
echo 'Modo: ' . CONF_MAIL_MODE . "\n";
echo 'Servidor: ' . CONF_MAIL_HOST . ':' . CONF_MAIL_PORT . ' (' . CONF_MAIL_SECURITY . ")\n";
echo 'Remetente: ' . CONF_MAIL_USER . "\nDestino de contato: " . CONF_MAIL_SUPPORT . "\n";
$mailer = new Mailer();
if (Mailer::isCaptureMode()) { fwrite(STDERR, "O modo capture não envia pela internet.\n"); exit(1); }
if (!$mailer->isConfigured()) {
    fwrite(STDERR, "Falta a senha de app. Execute scripts/configure-gmail.ps1 no PowerShell.\n"); exit(2);
}
if (!in_array('--send', $argv, true)) { echo "Credencial presente. Use --send para testar o envio real.\n"; exit; }
if (!$mailer->send(CONF_MAIL_SUPPORT, CONF_COMPANY_NAME, '[Joille] Teste de envio SMTP', '<p>Teste de configuração do envio de e-mails do site Joille.</p><p>As mensagens do formulário de contato serão encaminhadas para esta conta.</p>')) {
    fwrite(STDERR, 'Falha: ' . $mailer->getErrorMessage() . "\n"); exit(1);
}
echo "Mensagem aceita pelo servidor SMTP. Confira a caixa de entrada e o spam do Gmail.\n";
