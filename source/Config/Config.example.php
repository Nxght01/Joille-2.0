<?php

const CONF_URL_BASE = "http://localhost/projeto/joille2"; // URL base do site, geralmente localhost em desenvolvimento
const CONF_URL_TEST = "http://localhost/projeto/joille2"; // URL base do site, geralmente localhost em desenvolvimento

const CONF_DB_HOST = "localhost";
const CONF_DB_NAME = "joille";
const CONF_DB_USER = "root";
const CONF_DB_PORT = 3306;
const CONF_DB_PASS = "";

// Diretório de upload de imagens, relativo à raiz do projeto
const CONF_UPLOAD_DIR = "uploads";
const CONF_UPLOAD_IMAGE_DIR = "images";

// Chave secreta para criação do token JWT, deve ser uma string complexa e única para cada aplicação
// para gerar a sua acesse: https://jwtsecrets.com/
const JWT_SECRET_KEY = "SUBSTITUA_POR_UMA_CHAVE_ALEATORIA_GERADA_LOCALMENTE";

// --- Envio de e-mail (PHPMailer / SMTP) --------------------------------------
//
// CONF_MAIL_MODE define para onde vão os e-mails:
//
//   "capture" — MODO DE TESTE. Nada sai para a internet: cada mensagem
//               é gravada como HTML em storage/mail/ e pode ser aberta no
//               navegador em <URL_BASE>/storage/mail/. É assim que se testa o
//               "esqueci minha senha" e o formulário de contato pelo frontend
//               sem precisar de conta de e-mail nenhuma.
//
//   "smtp"    — PRODUÇÃO/APRESENTAÇÃO. Envia de verdade, e aí CONF_MAIL_USER e
//               CONF_MAIL_PASSWD precisam estar preenchidos.
//
// Para configurar a credencial local: execute scripts/configure-gmail.ps1.
// CONF_MAIL_PASSWD é uma "Senha de app" de 16 caracteres (Conta Google > Segurança >
// pesquisar "Senhas de app"), NUNCA a senha real da conta.
const CONF_MAIL_MODE = "smtp";

const CONF_MAIL_HOST = "smtp.gmail.com";
const CONF_MAIL_PORT = 587;
const CONF_MAIL_USER = "joilleifsul@gmail.com";
define("CONF_MAIL_PASSWD", getenv("JOILLE_MAIL_PASSWORD") ?: (is_file(__DIR__ . "/Mail.secret.php") ? (string) require __DIR__ . "/Mail.secret.php" : ""));
const CONF_MAIL_SECURITY = "tls"; // tls (587) ou ssl (465)
const CONF_MAIL_SENDER_NAME = "Joille";
const CONF_MAIL_SENDER_MAIL = "joilleifsul@gmail.com";
// Caixa que recebe as mensagens do formulário de contato da área pública
const CONF_MAIL_SUPPORT = "joilleifsul@gmail.com";

// Pasta onde o modo "capture" grava os e-mails, relativa à raiz do projeto
const CONF_MAIL_CAPTURE_DIR = "storage/mail";

// Validade (em minutos) do link de recuperação de senha
const CONF_PASSWORD_RESET_EXPIRE = 60;

// --- Dados institucionais (usados nos e-mails e nos relatórios em PDF) -------
const CONF_COMPANY_NAME = "Joille";
const CONF_COMPANY_SLOGAN = "Sistemas, redes e integração para empresas joalheiras";
const CONF_COMPANY_ADDRESS = "Av. Presidente Vargas, 1234 - Centro, Sapucaia do Sul/RS";
const CONF_COMPANY_PHONE = "(51) 3000-0000";
