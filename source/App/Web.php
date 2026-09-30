<?php

namespace Source\App;

use League\Plates\Engine;

class Web
{
    private $view;

    public function __construct()
    {
        $this->view = new Engine(__DIR__ . "/../../themes/web","php");
    }

    public function home ()
    {
        echo $this->view->render("home",[]);
    }

    public function cadastro ()
    {
        echo $this->view->render("cadastro",[]);
    }

    public function about ()
    {
        echo $this->view->render("about",[]);
    }

    public function contact ()
    {
        echo $this->view->render("contact",[]);
    }

    public function login ()
    {
        echo $this->view->render("login",[]);
    }

    /** Formulário que pede o e-mail para receber o link de recuperação. */
    public function forgotPassword (): void
    {
        echo $this->view->render("forgotPassword",[]);
    }

    /**
     * Tela de nova senha. O token chega na query string (?token=...) e é
     * validado pela API antes de o formulário ser liberado.
     */
    public function resetPassword (): void
    {
        echo $this->view->render("resetPassword",[]);
    }

    public function location ()
    {
        echo $this->view->render("location",[]);
    }

    public function cart(): void
    {
        echo "<h1>Olá, eu sou o carrinho de compras...</h1>";
    }

    public function services(): void
    {
        echo $this->view->render("services",[]);
    }

    /**
     * As perguntas não são mais consultadas aqui: a view as carrega da API
     * (`GET /faqs`), a mesma que o app mobile consome. Uma fonte só, sem
     * duplicar a consulta entre o PHP e o Web Service.
     */
    public function faqs(): void
    {
        echo $this->view->render("faqs",[]);
    }

    /**
     * Página de erro amigável. O roteador redireciona para /ops/{errcode} quando
     * o dispatch falha; aqui o código vira uma mensagem que o visitante entende,
     * sem expor detalhes internos da aplicação.
     */
    public function error (array $data): void
    {
        $messages = [
            400 => ["Requisição inválida", "O endereço acessado não está no formato esperado."],
            403 => ["Acesso não permitido", "Você não tem permissão para acessar esta página."],
            404 => ["Página não encontrada", "O endereço que você tentou acessar não existe ou foi movido."],
            405 => ["Ação não permitida", "Esta página não aceita o tipo de requisição enviado."],
            501 => ["Recurso indisponível", "Este recurso ainda não está implementado."]
        ];

        $code = (int)($data["errcode"] ?? 404);
        [$title, $message] = $messages[$code] ?? $messages[404];

        http_response_code(array_key_exists($code, $messages) ? $code : 404);

        echo $this->view->render("error", [
            "code" => $code,
            "title" => $title,
            "message" => $message
        ]);
    }

}
