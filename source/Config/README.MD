# source/Config/

`Config.php` guarda as constantes globais e é carregado pelo Composer
(`autoload.files`), portanto está disponível em qualquer ponto da aplicação.

| Constante | Uso |
|-----------|-----|
| `CONF_URL_BASE` / `CONF_URL_TEST` | URL raiz do projeto. Usada por `url()`, pelo Router e como `iss` do JWT. |
| `CONF_DB_HOST`, `CONF_DB_PORT`, `CONF_DB_NAME`, `CONF_DB_USER`, `CONF_DB_PASS` | Conexão PDO em `Core\Connect`. |
| `CONF_UPLOAD_DIR`, `CONF_UPLOAD_IMAGE_DIR` | Destino dos uploads em `Support\ImageUploader`. |
| `JWT_SECRET_KEY` | Chave de assinatura HS512 em `Core\JWTToken`. |

## Ao trocar de máquina

Copie `Config.example.php` para `Config.php` antes de executar a aplicação.
`Config.php` é local e ignorado pelo Git. Preencha uma chave JWT própria, gerada
com `php -r "echo bin2hex(random_bytes(64));"`, e ajuste as credenciais do banco.

Ajuste `CONF_URL_BASE` e `CONF_URL_TEST` para o caminho real do projeto no servidor
e replique o mesmo valor em `views/assets/_common/scripts/config.js` — é de lá que
o front-end monta a URL da API.

> O exemplo contém somente um marcador para a chave JWT; substitua-o localmente.
