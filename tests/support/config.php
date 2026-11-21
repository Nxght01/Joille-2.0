<?php
// Configuração exclusiva do banco descartável iniciado por start-env.ps1.
const CONF_URL_BASE = 'http://127.0.0.1:8086';
const CONF_URL_TEST = CONF_URL_BASE;
const CONF_DB_HOST = '127.0.0.1';
const CONF_DB_PORT = 33316;
const CONF_DB_NAME = 'joille_test';
const CONF_DB_USER = 'root';
const CONF_DB_PASS = '';
const JWT_SECRET_KEY = 'isolated-tests-only-this-key-is-never-used-by-the-installed-application-2026';
const CONF_UPLOAD_DIR = 'uploads/acceptance';
const CONF_UPLOAD_IMAGE_DIR = 'images';
const CONF_MAIL_MODE = 'capture';
const CONF_MAIL_CAPTURE_DIR = '.runtime/mail';
const CONF_MAIL_HOST = '';
const CONF_MAIL_PORT = 587;
const CONF_MAIL_USER = '';
const CONF_MAIL_PASSWD = '';
const CONF_MAIL_SECURITY = 'tls';
const CONF_MAIL_SENDER_NAME = 'Joille';
const CONF_MAIL_SENDER_MAIL = 'nao-responda@joille.test';
const CONF_MAIL_SUPPORT = 'suporte@joille.test';
const CONF_PASSWORD_RESET_EXPIRE = 60;
const CONF_COMPANY_NAME = 'Joille';
const CONF_COMPANY_SLOGAN = 'Gestão para joalherias e bijuterias';
const CONF_COMPANY_ADDRESS = 'R. Campos Sáles, 512 - São José, Guaporé/RS';
const CONF_COMPANY_PHONE = '(51) 3000-0000';
