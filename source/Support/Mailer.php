<?php

namespace Source\Support;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

/**
 * Envio de e-mails com PHPMailer.
 *
 * Dois modos, escolhidos por CONF_MAIL_MODE:
 *
 *  - "smtp"    envia de verdade, com as credenciais de CONF_MAIL_USER e
 *              CONF_MAIL_PASSWD (esta última uma "Senha de app" do Gmail, nunca
 *              a senha real da conta);
 *  - "capture" grava a mensagem em storage/mail/ em vez de enviar, para que os
 *              fluxos de recuperação de senha e de contato possam ser testados
 *              pelo frontend sem depender de servidor de e-mail.
 *
 * A classe nunca lança exceção para o chamador: send() devolve bool e o motivo
 * da falha fica em getErrorMessage(), para que o controlador decida o código
 * HTTP da resposta.
 */
class Mailer
{
    public const MODE_SMTP = "smtp";
    public const MODE_CAPTURE = "capture";

    private PHPMailer $mail;
    private ?string $errorMessage = null;
    private ?string $lastCapturePath = null;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /** Caminho relativo do último e-mail gravado no modo "capture". */
    public function getLastCapturePath(): ?string
    {
        return $this->lastCapturePath;
    }

    public static function isCaptureMode(): bool
    {
        return CONF_MAIL_MODE === self::MODE_CAPTURE;
    }

    /** URL da caixa de entrada local, para orientar quem está testando. */
    public static function captureInboxUrl(): string
    {
        return url(CONF_MAIL_CAPTURE_DIR . "/");
    }

    /**
     * No modo "capture" está sempre pronto; no modo "smtp" depende das credenciais.
     */
    public function isConfigured(): bool
    {
        if (self::isCaptureMode()) {
            return true;
        }

        return trim(CONF_MAIL_USER) !== "" && trim(CONF_MAIL_PASSWD) !== "";
    }

    /**
     * @param string $toMail   destinatário
     * @param string $toName   nome do destinatário
     * @param string $subject  assunto
     * @param string $body     corpo em HTML
     * @param string|null $replyTo endereço de resposta (usado no formulário de contato)
     */
    public function send(
        string $toMail,
        string $toName,
        string $subject,
        string $body,
        ?string $replyTo = null
    ): bool {
        $this->errorMessage = null;
        $this->lastCapturePath = null;
        if (!filter_var($toMail, FILTER_VALIDATE_EMAIL)) {
            $this->errorMessage = "Destinatário inválido.";
            return false;
        }

        if (self::isCaptureMode()) {
            return $this->capture($toMail, $toName, $subject, $body, $replyTo);
        }

        if (!$this->isConfigured()) {
            $this->errorMessage = "O envio de e-mail não está configurado no servidor "
                . "(defina CONF_MAIL_MODE ou preencha CONF_MAIL_USER e CONF_MAIL_PASSWD "
                . "em source/Config/Config.php).";
            return false;
        }

        try {
            $this->mail->isSMTP();
            $this->mail->SMTPAuth = true;
            $this->mail->SMTPDebug = SMTP::DEBUG_OFF;
            $this->mail->Timeout = 20;
            $this->mail->CharSet = PHPMailer::CHARSET_UTF8;
            $this->mail->Host = CONF_MAIL_HOST;
            $this->mail->Port = CONF_MAIL_PORT;
            $this->mail->Username = CONF_MAIL_USER;
            $this->mail->Password = CONF_MAIL_PASSWD;
            $this->mail->SMTPSecure = CONF_MAIL_SECURITY === "ssl"
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;

            $sender = trim(CONF_MAIL_SENDER_MAIL) !== "" ? CONF_MAIL_SENDER_MAIL : CONF_MAIL_USER;

            $this->mail->clearAllRecipients();
            $this->mail->clearReplyTos();
            $this->mail->setFrom($sender, CONF_MAIL_SENDER_NAME);
            $this->mail->addAddress($toMail, $toName);

            if ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $this->mail->addReplyTo($replyTo);
            }

            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body = $this->template($subject, $body);
            $this->mail->AltBody = strip_tags(str_replace(["<br>", "<br/>", "</p>"], "\n", $body));

            $this->mail->send();

            return true;
        } catch (Exception $exception) {
            // ErrorInfo do PHPMailer é mais descritivo que a mensagem da exceção
            $this->errorMessage = $this->mail->ErrorInfo ?: $exception->getMessage();
            return false;
        }
    }

    /**
     * Modo de teste: grava a mensagem como HTML em storage/mail/ em vez de enviar.
     *
     * O arquivo recebe um cabeçalho com destinatário, assunto e data, para que a
     * caixa de entrada local mostre as mesmas informações de um cliente de e-mail.
     */
    private function capture(
        string $toMail,
        string $toName,
        string $subject,
        string $body,
        ?string $replyTo
    ): bool {
        $dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR
            . str_replace("/", DIRECTORY_SEPARATOR, CONF_MAIL_CAPTURE_DIR);

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            $this->errorMessage = "Não foi possível criar a pasta de captura de e-mails ({$dir}).";
            return false;
        }

        // Prefixo com data/hora para a listagem sair em ordem cronológica
        $slug = preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($subject));
        $fileName = date("Ymd-His") . "-" . uniqid() . "-" . trim((string)$slug, "-") . ".html";
        $path = $dir . DIRECTORY_SEPARATOR . $fileName;

        $meta = [
            "Para" => "{$toName} <{$toMail}>",
            "Assunto" => $subject,
            "Data" => date("d/m/Y H:i:s"),
        ];

        if ($replyTo !== null) {
            $meta["Responder para"] = $replyTo;
        }

        $rows = "";
        foreach ($meta as $label => $value) {
            $value = htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
            $rows .= "<tr><th align=\"left\">{$label}</th><td>{$value}</td></tr>";
        }

        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <title>{$meta["Assunto"]}</title>
        </head>
        <body style="margin:0;background:#e9e4de">
            <div style="max-width:600px;margin:0 auto;padding:16px;
                        font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#3d2c23">
                <p style="background:#fff3cd;border:1px solid #ffe08a;border-radius:6px;padding:10px">
                    <strong>Modo de teste.</strong> Este e-mail não foi enviado para a internet:
                    foi gravado em <code>{$fileName}</code> porque
                    <code>CONF_MAIL_MODE</code> está em <code>"capture"</code>.
                </p>
                <table cellpadding="6" style="width:100%;background:#fff;border-radius:6px">{$rows}</table>
            </div>
            {$this->template($subject, $body)}
        </body>
        </html>
        HTML;

        if (file_put_contents($path, $html) === false) {
            $this->errorMessage = "Não foi possível gravar o e-mail em {$path}.";
            return false;
        }

        $this->lastCapturePath = CONF_MAIL_CAPTURE_DIR . "/" . $fileName;

        return true;
    }

    /** Envolve o corpo da mensagem no layout institucional. */
    private function template(string $title, string $body): string
    {
        $company = CONF_COMPANY_NAME;
        $slogan = CONF_COMPANY_SLOGAN;
        $year = date("Y");

        return <<<HTML
        <div style="font-family:Arial,Helvetica,sans-serif;background:#f5f2ee;padding:24px">
            <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:8px;overflow:hidden">
                <div style="background:#3d2c23;color:#f5f2ee;padding:20px 24px">
                    <h1 style="margin:0;font-size:20px">{$company}</h1>
                    <p style="margin:4px 0 0;font-size:12px;opacity:.8">{$slogan}</p>
                </div>
                <div style="padding:24px;color:#3d2c23;font-size:15px;line-height:1.6">
                    <h2 style="margin:0 0 16px;font-size:17px">{$title}</h2>
                    {$body}
                </div>
                <div style="padding:16px 24px;background:#f5f2ee;color:#7a6a60;font-size:12px">
                    &copy; {$year} {$company} — mensagem enviada pelo site Joille.
                </div>
            </div>
        </div>
        HTML;
    }
}
