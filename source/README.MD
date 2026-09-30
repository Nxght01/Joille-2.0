# source/

Código PHP da aplicação, autocarregado via PSR-4 sob o namespace `Source\` (ver `composer.json`).

| Pasta        | Namespace              | Responsabilidade |
|--------------|------------------------|------------------|
| `Config/`    | —                      | Constantes de configuração (banco, URL base, chave JWT). Carregado por `autoload.files`. |
| `Support/`   | `Source\Support`       | Funções auxiliares globais (`url()`) e serviços de apoio (upload de imagem). |
| `Core/`      | `Source\Core`          | Infraestrutura: conexão PDO, Model abstrato (CRUD) e token JWT. |
| `Models/`    | `Source\Models`        | Entidades do domínio — apenas atributos, getters/setters e consultas específicas. |
| `Controller/`| `Source\Controller`    | Controladores da API REST (`api/index.php`). |
| `App/`       | `Source\App`           | Controladores da aplicação MVC que renderizam as views Plates (`themes/`). |

## Fluxo de uma requisição da API

```
api/index.php  ->  Source\Controller\<X>  ->  Source\Models\<Y>  ->  Source\Core\Model  ->  Source\Core\Connect
                          |
                          +-- Source\Controller\Api::authToken()  ->  Source\Core\JWTToken
```

## Fluxo de uma requisição da área web

```
index.php  ->  Source\App\{Web|App|Admin}  ->  League\Plates  ->  themes/{web|app|adm}/
```
