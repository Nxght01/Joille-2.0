<?php

namespace Source\Controller;

use Source\Models\PasswordReset;
use Source\Models\User;
use Source\Support\Mailer;

/**
 * Recuperação de senha por e-mail (Etapa 07.1).
 *
 * Fluxo em três rotas públicas:
 *   1. POST /passwords/forgot          — gera um token e envia o link por e-mail;
 *   2. GET  /passwords/validate/{token} — a tela de nova senha confere o link antes de se exibir;
 *   3. POST /passwords/reset           — troca a senha e consome o token.
 *
 * A rota 1 responde sempre 200, mesmo quando o e-mail não existe: informar o
 * contrário permitiria descobrir quais e-mails têm conta no sistema.
 */
class Passwords extends Api
{
    public function forgot(array $data): void
    {
        if (!isset($data["email"]) || !filter_var($data["email"], FILTER_VALIDATE_EMAIL)) {
            $this->call(400, "bad_request", "Informe um e-mail válido.", "error")->back();
            return;
        }

        // A mesma resposta vale para e-mail existente e inexistente: uma resposta
        // diferente permitiria descobrir quais e-mails têm conta no sistema.
        // No modo de teste o texto muda (nada é enviado de verdade), mas continua
        // igual nos dois casos, para não abrir essa brecha.
        $capture = Mailer::isCaptureMode();

        $genericMessage = $capture
            ? "Modo de teste: nenhum e-mail sai para a internet. Se este endereço "
                . "estiver cadastrado, a mensagem foi gravada na caixa de entrada local — "
                . "abra-a para clicar no link."
            : "Se este e-mail estiver cadastrado, você receberá um link "
                . "para criar uma nova senha em alguns instantes.";

        $genericData = $capture ? ["inbox_url" => Mailer::captureInboxUrl()] : null;

        $user = new User();
        if (!$user->selectActiveByEmail($data["email"])) {
            $this->call(200, "success", $genericMessage, "success")->back($genericData);
            return;
        }

        $reset = new PasswordReset();
        $token = $reset->issueFor((int)$user->getId());

        if ($token === null) {
            $this->call(500, "internal_server_error", $reset->getErrorMessage(), "error")->back();
            return;
        }

        $link = url("redefinir-senha") . "?token=" . urlencode($token);
        $expire = CONF_PASSWORD_RESET_EXPIRE;
        $name = htmlspecialchars((string)$user->getName(), ENT_QUOTES, "UTF-8");

        $mailer = new Mailer();
        $sent = $mailer->send(
            (string)$user->getEmail(),
            (string)$user->getName(),
            "Recuperação de senha",
            <<<HTML
            <p>Olá, {$name}.</p>
            <p>Recebemos um pedido para criar uma nova senha na sua conta. Clique no
            botão abaixo para escolher a senha nova:</p>
            <p style="margin:20px 0">
                <a href="{$link}"
                   style="background:#3d2c23;color:#f5f2ee;text-decoration:none;
                          padding:12px 22px;border-radius:6px;display:inline-block">
                    Criar nova senha
                </a>
            </p>
            <p style="font-size:13px;color:#7a6a60">
                O link vale por {$expire} minutos e só pode ser usado uma vez.
                Se não foi você que pediu, ignore este e-mail — sua senha atual continua valendo.
            </p>
            <p style="font-size:12px;color:#7a6a60;word-break:break-all">{$link}</p>
            HTML
        );

        if (!$sent) {
            // O token já foi gravado; invalidá-lo evita deixar um link válido
            // que ninguém recebeu circulando no banco.
            $reset->invalidateFor((int)$user->getId());

            $this->call(502, "bad_gateway",
                "Não foi possível enviar o e-mail: " . $mailer->getErrorMessage(), "error")->back();
            return;
        }

        $this->call(200, "success", $genericMessage, "success")->back($genericData);
    }

    public function validateToken(array $data): void
    {
        if (empty($data["token"])) {
            $this->call(400, "bad_request", "Token é obrigatório.", "error")->back();
            return;
        }

        $reset = new PasswordReset();
        $record = $reset->findValid((string)$data["token"]);

        if (!$record) {
            $this->call(404, "not_found", $reset->getErrorMessage(), "error")->back();
            return;
        }

        // Só o primeiro nome e o e-mail mascarado, para a tela confirmar de quem
        // é a conta sem expor o endereço completo a quem tenha o link.
        $this->call(200, "success", "Link válido", "success")->back([
            "name" => explode(" ", (string)$record->name)[0],
            "email" => $this->maskEmail((string)$record->email),
            "expires_at" => $record->expires_at
        ]);
    }

    public function reset(array $data): void
    {
        if (empty($data["token"]) || empty($data["password"]) || empty($data["confirmPassword"])) {
            $this->call(400, "bad_request",
                "Os campos token, password e confirmPassword são obrigatórios.", "error")->back();
            return;
        }

        if ($data["password"] !== $data["confirmPassword"]) {
            $this->call(400, "bad_request", "As senhas não conferem.", "error")->back();
            return;
        }

        if (strlen($data["password"]) < 8) {
            $this->call(400, "bad_request", "A nova senha deve ter no mínimo 8 caracteres.", "error")->back();
            return;
        }

        $reset = new PasswordReset();
        $record = $reset->findValid((string)$data["token"]);

        if (!$record) {
            $this->call(404, "not_found", $reset->getErrorMessage(), "error")->back();
            return;
        }

        // O token é consumido primeiro: se dois pedidos chegarem juntos, só o
        // que conseguiu marcar `used_at` segue adiante.
        if (!$reset->consume((int)$record->id)) {
            $this->call(409, "conflict", $reset->getErrorMessage(), "error")->back();
            return;
        }

        $user = new User();
        if (!$user->resetPassword((int)$record->users_id, (string)$data["password"])) {
            $this->call(500, "internal_server_error", $user->getErrorMessage(), "error")->back();
            return;
        }

        // Aviso de segurança: o titular precisa saber que a senha mudou.
        $mailer = new Mailer();
        $mailer->send(
            (string)$record->email,
            (string)$record->name,
            "Sua senha foi alterada",
            "<p>A senha da sua conta no " . CONF_COMPANY_NAME . " acabou de ser alterada.</p>
             <p>Se não foi você, entre em contato com a nossa equipe imediatamente.</p>"
        );

        $this->call(200, "success", "Senha alterada com sucesso. Você já pode entrar com ela.", "success")->back();
    }

    /** joao.silva@gmail.com -> jo*******@gmail.com */
    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode("@", $email, 2), 2, "");

        if (strlen($local) <= 2) {
            return str_repeat("*", strlen($local)) . "@" . $domain;
        }

        return substr($local, 0, 2) . str_repeat("*", strlen($local) - 2) . "@" . $domain;
    }
}
