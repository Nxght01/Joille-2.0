# source/Models/

Entidades do domínio. Cada model estende `Source\Core\Model` e contém **apenas**:

1. os atributos privados tipados, em `camelCase`;
2. o construtor, que preenche os atributos e configura `$table`, `$primaryKey` e `$fillable`;
3. os getters e setters — é por eles que o `Model` lê e grava os valores;
4. consultas específicas que o CRUD genérico não cobre (JOINs, verificação de unicidade).

O CRUD genérico (`insert`, `updateById`, `selectById`, `selectAll`, `selectPaginator`,
`deleteById`, `softDeleteById`) fica todo em `Core\Model` — não reescreva SQL de CRUD aqui.

## Mapa

| Model                 | Tabela      | Observações |
|-----------------------|-------------|-------------|
| `User`                | `users`           | Sobrescreve `insert()` (hash da senha + e-mail único, com `hasEmailConflict()` para o controlador escolher 400 x 500) e adiciona `login()`, `permissionVerify()`, `updatePassword()`, `resetPassword()`, `replacePhoto()`, `emailInUse()`, `selectActiveByEmail()`, `statsByType()`, `selectAllForReport()`. |
| `Service`             | `services`        | `selectAllWithCategory()` / `selectByIdWithCategory()` fazem o JOIN com `categories` e trazem a capa (`cover_image`) e o total de imagens; `statsByCategory()` e `selectAllForReport()` alimentam gráficos e PDF. |
| `ServiceImage`        | `service_images`  | N imagens por serviço, com uma capa. `markAsCover()` garante capa única; `coversByService()` evita uma consulta por serviço. |
| `Category`            | `categories`      | `hasServices()` impede excluir categoria em uso. |
| `Faqs\Question`       | `questions`       | Pergunta do FAQ; `statsByType()` e `selectAllForReport()` para gráfico e PDF. |
| `Faqs\Type`           | `types`           | Tipo/categoria da pergunta. |
| `PasswordReset`       | `password_resets` | Token de uso único do "esqueci minha senha": `issueFor()`, `findValid()`, `consume()`, `invalidateFor()`. |
| `Contact`             | `contacts`        | Mensagens do formulário público. Única com colunas em camelCase no schema (ver abaixo). |
| `Store\Product`       | `products`        | Model pronto; **a tabela ainda não existe** no banco e não há rotas expostas. |

## Nomenclatura

O atributo é sempre `camelCase` e a coluna sempre `snake_case`; a conversão é
automática. Exemplo: o atributo `categoriesId` grava na coluna `categories_id`.

**Exceção:** a tabela `contacts` já nasceu com as colunas `contactName` e
`contactEmaill` em camelCase. `Contact` desliga a conversão só para essas duas,
sobrescrevendo `camelToSnake()` e `snakeToCamel()` — em vez de renomear colunas com
dados e sair do ER original.
