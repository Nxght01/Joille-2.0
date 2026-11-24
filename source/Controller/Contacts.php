<?php

namespace Source\Controller;

use Source\Models\Contact;
use Source\Support\Mailer;

/**
 * Mensagens do formulário de contato da área pública.
 *
 * É a segunda funcionalidade de e-mail pedida na Etapa 07.1: a mensagem é
 * gravada no banco (para o painel administrativo) e encaminhada por e-mail à
 * caixa de suporte, com Reply-To do visitante, além de um recibo automático
 * para quem escreveu.
 *
 * `insert` é pública; a listagem e a baixa exigem token de administrador.
 */
class Contacts extends Api
{
    public function insert(array $data): void
    {
        if (!isset($data["name"], $data["email"], $data["message"]) ||
            trim((string)$data["name"]) === "" ||
            trim((string)$data["message"]) === "" ||
            !filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
            $this->call(400, "bad_request",
                "Os campos name, email e message são obrigatórios e o e-mail deve ser válido.",
                "error")->back();
            return;
        }

        $name = trim((string)$data["name"]);
        $email = trim((string)$data["email"]);
        $message = trim((string)$data["message"]);
        $subject = isset($data["subject"]) && trim((string)$data["subject"]) !== ""
            ? trim((string)$data["subject"])
            : "Contato pelo site";

        if (mb_strlen($message) < 10) {
            $this->call(400, "bad_request", "A mensagem deve ter no mínimo 10 caracteres.", "error")->back();
            return;
        }

        $contact = new Contact(null, $name, $email, $subject, $message);

        if (!$contact->insert()) {
            $this->call(500, "internal_server_error", $contact->getErrorMessage(), "error")->back();
            return;
        }

        // A mensagem já está salva: uma falha de SMTP não pode fazer o visitante
        // perder o que escreveu, então o envio é reportado como aviso.
        $delivered = $this->notify($name, $email, $subject, $message);

        if (Mailer::isCaptureMode()) {
            $message = "Modo de teste: a mensagem foi gravada e os e-mails foram "
                . "capturados na caixa de entrada local, sem sair para a internet.";
        } elseif ($delivered) {
            $message = "Mensagem enviada com sucesso! Em breve entraremos em contato.";
        } else {
            $message = "Sua mensagem foi salva para a equipe, mas não foi possível encaminhar o e-mail agora.";
        }

        $this->call(201, "created", $message, "success")->back([
            "id" => $contact->getId(),
            "name" => $name,
            "email" => $email,
            "subject" => $subject,
            "mail_delivered" => $delivered && !Mailer::isCaptureMode(),
            "mail_status" => Mailer::isCaptureMode() ? 'captured' : ($delivered ? 'sent' : 'failed'),
            "inbox_url" => Mailer::isCaptureMode() ? Mailer::captureInboxUrl() : null
        ]);
    }

    public function listAll(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        $contacts = new Contact();

        // Linhas do schema antigo podem ter nome/e-mail/mensagem nulos, e a
        // entidade do frontend recusa esses valores — uma linha inconsistente
        // não pode derrubar a tela inteira, então ela é omitida.
        $rows = array_filter(
            $contacts->selectLatest(),
            fn($row) => !empty($row->contactName) && !empty($row->contactEmaill) && !empty($row->message)
        );

        $response = array_map(fn($row) => [
            "id" => (int)$row->id,
            "name" => $row->contactName,
            "email" => $row->contactEmaill,
            "subject" => $row->subject,
            "message" => $row->message,
            "answered" => (int)$row->answered,
            "created_at" => $row->created_at
        ], $rows);

        // array_values: array_filter preserva as chaves e o json_encode viraria
        // um objeto em vez de uma lista
        $this->call(200, "success", "Mensagens de contato", "success")->back(array_values($response));
    }

    public function update(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        if (empty($data["contactId"]) || !filter_var($data["contactId"], FILTER_VALIDATE_INT)) {
            $this->call(400, "bad_request",
                "ID da mensagem é obrigatório e deve ser um número inteiro", "error")->back();
            return;
        }

        $answered = 1;
        if (array_key_exists("answered", $data)) {
            $answered = filter_var($data["answered"], FILTER_VALIDATE_INT);
            if ($answered === false || !in_array($answered, [0, 1], true)) {
                $this->call(400, "bad_request", "O campo answered deve ser 0 ou 1", "error")->back();
                return;
            }
        }

        $contact = new Contact();
        if (!$contact->markAnswered((int)$data["contactId"], $answered)) {
            $this->call(404, "not_found", $contact->getErrorMessage(), "error")->back();
            return;
        }

        $this->call(200, "success", "Mensagem atualizada com sucesso", "success")
            ->back(["id" => (int)$data["contactId"], "answered" => $answered]);
    }

    public function delete(array $data): void
    {
        if (!$this->authToken(1)) {
            $this->authFail();
            return;
        }

        if (empty($data["contactId"]) || !filter_var($data["contactId"], FILTER_VALIDATE_INT)) {
            $this->call(400, "bad_request",
                "ID da mensagem é obrigatório e deve ser um número inteiro", "error")->back();
            return;
        }

        $contact = new Contact();
        if (!$contact->deleteById((int)$data["contactId"])) {
            $this->call(404, "not_found", $contact->getErrorMessage(), "error")->back();
            return;
        }

        $this->call(200, "success", "Mensagem excluída com sucesso", "success")->back();
    }

    /**
     * Encaminha a mensagem ao suporte e manda o recibo ao visitante.
     * @return bool true somente quando o e-mail para o suporte saiu
     */
    private function notify(string $name, string $email, string $subject, string $message): bool
    {
        $support = trim(CONF_MAIL_SUPPORT) !== "" ? CONF_MAIL_SUPPORT : CONF_MAIL_USER;

        if (trim((string)$support) === "") {
            return false;
        }

        $safeName = htmlspecialchars($name, ENT_QUOTES, "UTF-8");
        $safeEmail = htmlspecialchars($email, ENT_QUOTES, "UTF-8");
        $safeSubject = htmlspecialchars($subject, ENT_QUOTES, "UTF-8");
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, "UTF-8"));

        $mailer = new Mailer();
        $delivered = $mailer->send(
            $support,
            CONF_COMPANY_NAME,
            "[Contato] {$safeSubject}",
            <<<HTML
            <p><strong>De:</strong> {$safeName} &lt;{$safeEmail}&gt;</p>
            <p><strong>Assunto:</strong> {$safeSubject}</p>
            <hr style="border:none;border-top:1px solid #e6ded8">
            <p>{$safeMessage}</p>
            HTML,
            $email // Reply-To: responder no cliente de e-mail já vai ao visitante
        );

        if (!$delivered) error_log('Joille: falha ao encaminhar contato por SMTP. ' . $mailer->getErrorMessage());

        if ($delivered) {
            (new Mailer())->send(
                $email,
                $name,
                "Recebemos sua mensagem",
                <<<HTML
                <p>Olá, {$safeName}.</p>
                <p>Sua mensagem chegou até nós e será respondida em breve. Abaixo, uma
                cópia do que você enviou:</p>
                <blockquote style="margin:12px 0;padding:10px 14px;border-left:3px solid #d9cec6;color:#7a6a60">
                    {$safeMessage}
                </blockquote>
                HTML
            );
        }

        return $delivered;
    }
}
