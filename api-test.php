<?php
/**
 * Teste de fumaça da API — executa via CLI: php api-test.php
 * Percorre todas as rotas e confere código HTTP e envelope de resposta.
 *
 * A suíte é AUTOLIMPANTE: no fim ela apaga do banco tudo o que criou (prefixo
 * `ZZ ` e usuários `zz.*`). Isso não é cosmético — enquanto a limpeza apenas
 * inativava os registros, cada execução deixava lixo permanente e as telas de
 * Usuários e Categorias do painel ficaram com dezenas de linhas de teste.
 */

require __DIR__ . "/vendor/autoload.php";

use Source\Core\Connect;

const BASE = "http://localhost/projeto/joille2/api";

$pass = 0;
$fail = 0;
$ctx = [];

function req(string $method, string $path, array $data = [], ?string $token = null, bool $asJson = false): array
{
    $ch = curl_init(BASE . $path);
    $headers = [];

    if ($token) {
        $headers[] = "Authorization: Bearer {$token}";
    }

    if ($data) {
        if ($method === "PUT" || $asJson) {
            $body = $asJson ? json_encode($data) : http_build_query($data);
            $headers[] = $asJson ? "Content-Type: application/json" : "Content-Type: application/x-www-form-urlencoded";
        } else {
            $body = http_build_query($data);
            $headers[] = "Content-Type: application/x-www-form-urlencoded";
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }

    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$code, json_decode($raw, true), $raw];
}

/**
 * POST multipart, para as rotas de upload de imagem.
 * @param string[] $paths caminhos dos arquivos locais a enviar
 */
function upload(string $path, string $field, array $paths, ?string $token = null): array
{
    $ch = curl_init(BASE . $path);
    $headers = $token ? ["Authorization: Bearer {$token}"] : [];

    $body = [];
    foreach (array_values($paths) as $index => $file) {
        // O nome com [] faz o PHP agrupar os arquivos em $_FILES[$field]
        $body["{$field}[{$index}]"] = new CURLFile($file, mime_content_type($file), basename($file));
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$code, json_decode($raw, true), $raw];
}

/** Gera um PNG temporário, para não depender de arquivos versionados. */
function makePng(string $name, int $width, int $height): string
{
    $image = imagecreatetruecolor($width, $height);
    imagefill($image, 0, 0, imagecolorallocate($image, 200, 160, 80));
    imagefilledellipse($image, (int)($width / 2), (int)($height / 2),
        (int)($width / 2), (int)($height / 2), imagecolorallocate($image, 60, 44, 35));

    $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $name . "-" . uniqid() . ".png";
    imagepng($image, $file);
    imagedestroy($image);

    return $file;
}

function check(string $label, string $method, string $path, int $expectCode, ?string $expectStatus, array $data = [], ?string $token = null): ?array
{
    global $pass, $fail;

    [$code, $json, $raw] = req($method, $path, $data, $token);

    $okCode = $code === $expectCode;
    $okStatus = $expectStatus === null || (($json["status"] ?? null) === $expectStatus);

    if ($okCode && $okStatus) {
        $pass++;
        printf("  ok   %-52s %s %d\n", $label, $method, $code);
    } else {
        $fail++;
        printf("  FAIL %-52s %s got %d/%s want %d/%s\n", $label, $method, $code,
            $json["status"] ?? "-", $expectCode, $expectStatus ?? "-");
        printf("       %s\n", substr(str_replace("\n", " ", $raw), 0, 200));
    }

    return $json;
}

echo "\n=== 1. Rotas públicas ===\n";
check("GET /faqs", "GET", "/faqs", 200, "success");
check("GET /faqs-types", "GET", "/faqs-types", 200, "success");
check("GET /categories", "GET", "/categories", 200, "success");
check("GET /services", "GET", "/services", 200, "success");
check("GET /services/paginator/1/2", "GET", "/services/paginator/1/2", 200, "success");
check("GET /services/category/1", "GET", "/services/category/1", 200, "success");
check("GET /rota-inexistente -> 404", "GET", "/rota-inexistente", 404, "not_found");
check("GET /services/abc -> 400", "GET", "/services/abc", 400, "bad_request");
check("GET /services/99999 -> 404", "GET", "/services/99999", 404, "not_found");

echo "\n=== 2. Guarda de autenticação (sem token) ===\n";
check("POST /categories sem token", "POST", "/categories", 401, "unauthorized", ["name" => "X"]);
check("DELETE /services/1 sem token", "DELETE", "/services/1", 401, "unauthorized");
check("GET /users/admin/list sem token", "GET", "/users/admin/list", 401, "unauthorized");
check("GET /users/me sem token", "GET", "/users/me", 401, "unauthorized");

echo "\n=== 3. Login de administrador ===\n";
check("POST /users/login-admin senha errada", "POST", "/users/login-admin", 401, "unauthorized",
    ["email" => "admin@joille.com", "password" => "errada"]);
check("POST /users/login-admin email inválido", "POST", "/users/login-admin", 400, "bad_request",
    ["email" => "nao-e-email", "password" => "admin@123"]);
$login = check("POST /users/login-admin", "POST", "/users/login-admin", 200, "success",
    ["email" => "admin@joille.com", "password" => "admin@123"]);
$adminToken = $login["data"]["token"] ?? null;
echo "  token admin: " . ($adminToken ? "recebido (" . strlen($adminToken) . " chars), type_id=" . $login["data"]["type_id"] : "AUSENTE") . "\n";

echo "\n=== 4. Perfil errado (401 sem sessão x 403 sem permissão) ===\n";
check("GET /users/me com token ADMIN -> 403", "GET", "/users/me", 403, "forbidden", [], $adminToken);
check("POST /categories com token ADMIN -> 201 (perfil certo)", "POST", "/categories", 201, "created",
    ["name" => "ZZ Perfil OK"], $adminToken);

echo "\n=== 4b. Contrato: data:null em resposta de erro ===\n";
[$errCode, $errJson] = req("GET", "/services/abc");
if (array_key_exists("data", $errJson) && $errJson["data"] === null) {
    $pass++;
    echo "  ok   erro 400 devolve \"data\": null\n";
} else {
    $fail++;
    echo "  FAIL erro 400 não devolveu \"data\": null\n";
}

echo "\n=== 5. CRUD de categorias (ADMIN) ===\n";
check("GET /categories/admin/list", "GET", "/categories/admin/list", 200, "success", [], $adminToken);
$cat = check("POST /categories", "POST", "/categories", 201, "created", ["name" => "ZZ Teste Automatizado"], $adminToken);
$catId = $cat["data"]["id"] ?? null;
$ctx["catId"] = $catId;
echo "  categoria criada: id={$catId}\n";
check("POST /categories duplicada -> 400", "POST", "/categories", 400, "bad_request", ["name" => "ZZ Teste Automatizado"], $adminToken);
check("GET /categories/{id}", "GET", "/categories/{$catId}", 200, "success");
check("PUT /categories/{id}", "PUT", "/categories/{$catId}", 200, "success", ["name" => "ZZ Teste Renomeada"], $adminToken);
check("PUT /categories/{id} sem name -> 400", "PUT", "/categories/{$catId}", 400, "bad_request", [], $adminToken);

echo "\n=== 6. CRUD de serviços (ADMIN) ===\n";
check("GET /services/admin/list", "GET", "/services/admin/list", 200, "success", [], $adminToken);
check("POST /services sem campos -> 400", "POST", "/services", 400, "bad_request", ["name" => "só nome"], $adminToken);
$svc = check("POST /services", "POST", "/services", 201, "created", [
    "categories_id" => $catId, "name" => "ZZ Serviço Teste", "description" => "criado pelo teste", "value" => "1234"
], $adminToken);
$svcId = $svc["data"]["id"] ?? null;
$ctx["svcId"] = $svcId;
echo "  serviço criado: id={$svcId}\n";
$one = check("GET /services/{id} (com JOIN)", "GET", "/services/{$svcId}", 200, "success");
echo "  category_name no JOIN: " . ($one["data"]["category_name"] ?? "AUSENTE") . "\n";
check("PUT /services/{id} parcial", "PUT", "/services/{$svcId}", 200, "success", ["value" => "9999"], $adminToken);
$after = req("GET", "/services/{$svcId}")[1];
echo "  value após update parcial: " . ($after["data"]["value"] ?? "?") . " | name preservado: " . ($after["data"]["name"] ?? "?") . "\n";
check("DELETE /categories em uso -> 400", "DELETE", "/categories/{$catId}", 400, "bad_request", [], $adminToken);
check("DELETE /services/{id} (soft)", "DELETE", "/services/{$svcId}", 200, "success", [], $adminToken);
$listAfter = req("GET", "/services")[1];
$stillThere = false;
foreach (($listAfter["data"] ?? []) as $s) { if ($s["id"] == $svcId) { $stillThere = true; } }
echo "  soft delete removeu da listagem pública: " . ($stillThere ? "NÃO (falha)" : "sim") . "\n";
check("DELETE /services/{id} de novo -> 404", "DELETE", "/services/{$svcId}", 404, "not_found", [], $adminToken);

echo "\n=== 7. CRUD de FAQs (ADMIN) ===\n";
check("GET /faqs/admin/list", "GET", "/faqs/admin/list", 200, "success", [], $adminToken);
$faq = check("POST /faqs", "POST", "/faqs", 201, "created",
    ["types_id" => 1, "question" => "ZZ Pergunta Teste?", "answer" => "Resposta teste."], $adminToken);
$faqId = $faq["data"]["id"] ?? null;
$ctx["faqId"] = $faqId;
check("GET /faqs/type/1", "GET", "/faqs/type/1", 200, "success");
check("PUT /faqs/{id}", "PUT", "/faqs/{$faqId}", 200, "success", ["answer" => "Resposta editada."], $adminToken);
check("DELETE /faqs/{id}", "DELETE", "/faqs/{$faqId}", 200, "success", [], $adminToken);

$type = check("POST /faqs-types", "POST", "/faqs-types", 201, "created", ["description" => "ZZ Tipo Teste"], $adminToken);
$typeId = $type["data"]["id"] ?? null;
$ctx["typeId"] = $typeId;

echo "\n=== 7b. FAQs: quem pode escrever ===\n";
// Regra do sistema: a área pública LÊ perguntas; só o ADMIN cadastra, edita e
// exclui. Estas verificações existem para que a regra não se perca em uma
// alteração futura — se alguém tirar o authToken(1) de um método do controller
// Faqs, a suíte falha aqui.
$faqPayload = ["types_id" => 1, "question" => "ZZ Pergunta indevida?", "answer" => "x"];

// Visitante anônimo: 401 (sem sessão)
check("visitante: POST /faqs -> 401", "POST", "/faqs", 401, "unauthorized", $faqPayload);
check("visitante: PUT /faqs/1 -> 401", "PUT", "/faqs/1", 401, "unauthorized", ["answer" => "x"]);
check("visitante: DELETE /faqs/1 -> 401", "DELETE", "/faqs/1", 401, "unauthorized");
check("visitante: POST /faqs-types -> 401", "POST", "/faqs-types", 401, "unauthorized", ["description" => "ZZ"]);

// Cliente autenticado: 403 (sessão válida, perfil sem permissão)
$emailFaq = "zz.leitor." . time() . "@joille.com";
req("POST", "/users/register", ["name" => "ZZ Leitor", "email" => $emailFaq, "password" => "senha12345"]);
$tokenLeitor = req("POST", "/users/login", ["email" => $emailFaq, "password" => "senha12345"])[1]["data"]["token"] ?? null;

check("cliente: POST /faqs -> 403", "POST", "/faqs", 403, "forbidden", $faqPayload, $tokenLeitor);
check("cliente: PUT /faqs/1 -> 403", "PUT", "/faqs/1", 403, "forbidden", ["answer" => "x"], $tokenLeitor);
check("cliente: DELETE /faqs/1 -> 403", "DELETE", "/faqs/1", 403, "forbidden", [], $tokenLeitor);
check("cliente: GET /faqs/admin/list -> 403", "GET", "/faqs/admin/list", 403, "forbidden", [], $tokenLeitor);

// O que a área pública PODE: ler as perguntas publicadas e mandar mensagem
check("visitante: GET /faqs (leitura) -> 200", "GET", "/faqs", 200, "success");
check("visitante: GET /faqs-types (leitura) -> 200", "GET", "/faqs-types", 200, "success");

// Nenhuma das tentativas pode ter gravado nada
$depoisTentativas = req("GET", "/faqs/admin/list", [], $adminToken)[1]["data"] ?? [];
$indevidas = array_filter($depoisTentativas, fn($f) => str_contains((string)$f["question"], "indevida"));

if (empty($indevidas)) {
    $pass++;
    echo "  ok   nenhuma pergunta foi criada pelas tentativas indevidas\n";
} else {
    $fail++;
    echo "  FAIL " . count($indevidas) . " pergunta(s) indevida(s) chegaram ao banco\n";
}
check("PUT /faqs-types/{id}", "PUT", "/faqs-types/{$typeId}", 200, "success", ["description" => "ZZ Tipo Editado"], $adminToken);
check("DELETE /faqs-types/{id}", "DELETE", "/faqs-types/{$typeId}", 200, "success", [], $adminToken);

echo "\n=== 8. Ciclo de vida do cliente ===\n";
$email = "zz.teste." . time() . "@joille.com";
check("POST /users/register senha curta -> 400", "POST", "/users/register", 400, "bad_request",
    ["name" => "ZZ Teste", "email" => $email, "password" => "123"]);
check("POST /users/register email inválido -> 400", "POST", "/users/register", 400, "bad_request",
    ["name" => "ZZ Teste", "email" => "invalido", "password" => "senha12345"]);
$reg = check("POST /users/register", "POST", "/users/register", 201, "created",
    ["name" => "ZZ Teste", "email" => $email, "password" => "senha12345"]);
$userId = $reg["data"]["id"] ?? null;
$ctx["userId"] = $userId;
echo "  usuário criado: id={$userId} email={$email}\n";
// E-mail repetido é erro do cliente: ele corrige mandando outro endereço.
// Antes isto devolvia 500, incoerente com o update, que já respondia 400.
check("POST /users/register duplicado -> 400", "POST", "/users/register", 400, "bad_request",
    ["name" => "ZZ Teste", "email" => $email, "password" => "senha12345"]);
check("POST /users/register e-mail com outra caixa -> 400", "POST", "/users/register", 400, "bad_request",
    ["name" => "ZZ Teste", "email" => strtoupper($email), "password" => "senha12345"]);

$ulogin = check("POST /users/login", "POST", "/users/login", 200, "success",
    ["email" => $email, "password" => "senha12345"]);
$userToken = $ulogin["data"]["token"] ?? null;
echo "  token cliente: " . ($userToken ? "recebido, type_id=" . $ulogin["data"]["type_id"] : "AUSENTE") . "\n";

check("GET /users/token-validate", "GET", "/users/token-validate", 200, "success", [], $userToken);
$me = check("GET /users/me", "GET", "/users/me", 200, "success", [], $userToken);
echo "  me: " . ($me["data"]["name"] ?? "?") . " / " . ($me["data"]["email"] ?? "?") . "\n";
check("PUT /users/update", "PUT", "/users/update", 200, "success",
    ["name" => "ZZ Teste Renomeado", "email" => $email], $userToken);
// O "outro" usuário é criado AQUI, e não reaproveitado do banco.
// Antes este teste usava um e-mail real ("antonio@gmail.com"): quando alguém
// editou essa conta pelo painel, o e-mail deixou de existir, o update passou e
// gravou no usuário de teste um endereço que não era dele — o token perdeu a
// referência e as seis verificações seguintes caíram em cascata. Um teste não
// pode depender de dado que outra pessoa edita.
$emailVizinho = "zz.vizinho." . time() . "@joille.com";
req("POST", "/users/register", ["name" => "ZZ Vizinho", "email" => $emailVizinho, "password" => "senha12345"]);

check("PUT /users/update email de outro -> 400", "PUT", "/users/update", 400, "bad_request",
    ["name" => "ZZ", "email" => $emailVizinho], $userToken);
check("PUT /users/password senha atual errada", "PUT", "/users/password", 400, "bad_request",
    ["password" => "errada", "newPassword" => "novasenha1", "confirmNewPassword" => "novasenha1"], $userToken);
check("PUT /users/password não conferem", "PUT", "/users/password", 400, "bad_request",
    ["password" => "senha12345", "newPassword" => "novasenha1", "confirmNewPassword" => "outra12345"], $userToken);
check("PUT /users/password", "PUT", "/users/password", 200, "success",
    ["password" => "senha12345", "newPassword" => "novasenha1", "confirmNewPassword" => "novasenha1"], $userToken);
check("POST /users/login com a senha nova", "POST", "/users/login", 200, "success",
    ["email" => $email, "password" => "novasenha1"]);
check("POST /users/login com a senha antiga -> 401", "POST", "/users/login", 401, "unauthorized",
    ["email" => $email, "password" => "senha12345"]);
check("GET /users/me com token de cliente inativo", "GET", "/users/me", 200, "success", [], $userToken);

echo "\n=== 9. Rotas administrativas de usuário ===\n";
$all = check("GET /users/admin/list", "GET", "/users/admin/list", 200, "success", [], $adminToken);
$leak = false;
foreach (($all["data"] ?? []) as $u) { if (array_key_exists("password", $u)) { $leak = true; } }
echo "  hash de senha exposto na listagem: " . ($leak ? "SIM (falha)" : "não") . "\n";
check("GET /users/admin/{id}", "GET", "/users/admin/{$userId}", 200, "success", [], $adminToken);
check("PUT /users/admin/{id}", "PUT", "/users/admin/{$userId}", 200, "success",
    ["name" => "ZZ Alterado pelo Admin", "email" => $email, "type_id" => 2, "active" => 1], $adminToken);
check("DELETE /users/admin/{id} (soft)", "DELETE", "/users/admin/{$userId}", 200, "success", [], $adminToken);
check("POST /users/login após desativar -> 401", "POST", "/users/login", 401, "unauthorized",
    ["email" => $email, "password" => "novasenha1"]);

echo "\n=== 9b. Etapa 05 — CREATE pelas telas e perfil sem permissão ===\n";
$email05 = "zz.e05." . time() . "@joille.com";
req("POST", "/users/register", ["name" => "ZZ E05", "email" => $email05, "password" => "senha12345"]);
$cli05 = req("POST", "/users/login", ["email" => $email05, "password" => "senha12345"])[1];
$userToken05 = $cli05["data"]["token"];
$userId05 = $cli05["data"]["id"];

check("CLIENTE em rota de ADMIN -> 403", "GET", "/services/admin/list", 403, "forbidden", [], $userToken05);
check("CLIENTE tentando criar categoria -> 403", "POST", "/categories", 403, "forbidden", ["name" => "ZZ nope"], $userToken05);

$catNew = check("POST /categories (criar pela tela)", "POST", "/categories", 201, "created", ["name" => "ZZ Cat Tela"], $adminToken);
$catNewId = $catNew["data"]["id"];
$svcNew = check("POST /services (criar pela tela)", "POST", "/services", 201, "created",
    ["categories_id" => $catNewId, "name" => "ZZ Svc Tela", "description" => "desc", "value" => "500"], $adminToken);
$svcNewId = $svcNew["data"]["id"];
$faqNew = check("POST /faqs (criar pela tela)", "POST", "/faqs", 201, "created",
    ["types_id" => 1, "question" => "ZZ Pergunta Tela?", "answer" => "resposta"], $adminToken);
check("POST /users/register-admin (criar pela tela)", "POST", "/users/register-admin", 201, "created",
    ["name" => "ZZ Admin Tela", "email" => "zz.adm." . time() . "@joille.com", "password" => "senha12345", "type_id" => 1], $adminToken);

check("DELETE /faqs criada", "DELETE", "/faqs/" . $faqNew["data"]["id"], 200, "success", [], $adminToken);
check("DELETE /services criado", "DELETE", "/services/{$svcNewId}", 200, "success", [], $adminToken);
check("DELETE /categories após inativar o serviço", "DELETE", "/categories/{$catNewId}", 200, "success", [], $adminToken);
check("DELETE cliente da Etapa 05", "DELETE", "/users/admin/{$userId05}", 200, "success", [], $adminToken);
check("token de cliente inativado -> 401", "GET", "/users/me", 401, "unauthorized", [], $userToken05);

echo "\n=== 10. Token adulterado ===\n";
check("GET /users/admin/list token inválido", "GET", "/users/admin/list", 401, "unauthorized", [], "abc.def.ghi");
check("GET /users/admin/list token truncado", "GET", "/users/admin/list", 401, "unauthorized", [], substr((string)$adminToken, 0, -6));

echo "\n=== 11. Rotas no formato do enunciado (Etapa 03) ===\n";
check("GET /services/list", "GET", "/services/list", 200, "success");
check("GET /services/list/paginator/1/2", "GET", "/services/list/paginator/1/2", 200, "success");
check("GET /services/list/99999 -> 404", "GET", "/services/list/99999", 404, "not_found");
check("GET /categories/list", "GET", "/categories/list", 200, "success");
check("GET /categories/list/paginator/1/2", "GET", "/categories/list/paginator/1/2", 200, "success");
check("GET /categories/list/99999 -> 404", "GET", "/categories/list/99999", 404, "not_found");

// A rota literal "/list" não pode ser engolida pela curinga "/{id}"
$listEnvelope = req("GET", "/services/list")[1];
if (($listEnvelope["message"] ?? "") === "Lista de Serviços") {
    $pass++;
    echo "  ok   /services/list caiu em listAll (e não em listById)\n";
} else {
    $fail++;
    echo "  FAIL /services/list caiu na rota errada: " . ($listEnvelope["message"] ?? "-") . "\n";
}

echo "\n=== 12. Imagens da entidade principal (Etapa 07) ===\n";
$catImg = check("POST /categories (para as imagens)", "POST", "/categories", 201, "created",
    ["name" => "ZZ Cat Imagens"], $adminToken);
$svcImg = check("POST /services (para as imagens)", "POST", "/services", 201, "created", [
    "categories_id" => $catImg["data"]["id"], "name" => "ZZ Svc Imagens",
    "description" => "com imagens", "value" => "150"
], $adminToken);
$svcImgId = $svcImg["data"]["id"];

check("GET /service-images/{id} vazio", "GET", "/service-images/{$svcImgId}", 200, "success");
check("POST /service-images sem token -> 401", "POST", "/service-images/{$svcImgId}", 401, "unauthorized");
check("POST /service-images sem arquivo -> 400", "POST", "/service-images/{$svcImgId}", 400, "bad_request",
    ["caption" => "sem arquivo"], $adminToken);
check("POST /service-images serviço inexistente -> 404", "POST", "/service-images/999999", 404, "not_found",
    [], $adminToken);

// Duas imagens de verdade, geradas na hora, para exercitar o input `multiple`
$files = [makePng("zz-img-a", 900, 600), makePng("zz-img-b", 400, 400)];
[$upCode, $upJson] = upload("/service-images/{$svcImgId}", "images", $files, $adminToken);

if ($upCode === 201 && count($upJson["data"] ?? []) === 2) {
    $pass++;
    echo "  ok   POST /service-images com 2 arquivos                     POST 201\n";
} else {
    $fail++;
    echo "  FAIL POST /service-images com 2 arquivos: got {$upCode} / "
        . count($upJson["data"] ?? []) . " imagens\n";
    echo "       " . substr(json_encode($upJson), 0, 200) . "\n";
}

array_map("unlink", $files);

$imgs = check("GET /service-images/{id} com 2", "GET", "/service-images/{$svcImgId}", 200, "success");
$imgList = $imgs["data"] ?? [];
$covers = array_filter($imgList, fn($i) => (int)$i["cover"] === 1);
echo "  primeira imagem virou capa automaticamente: " . (count($covers) === 1 ? "sim" : "NÃO (falha)") . "\n";
count($covers) === 1 ? $pass++ : $fail++;

// O arquivo tem de estar realmente acessível pela URL devolvida
$firstUrl = $imgList[0]["url"] ?? "";
$headers = @get_headers($firstUrl);
if ($headers && str_contains($headers[0], "200")) {
    $pass++;
    echo "  ok   imagem acessível na URL devolvida\n";
} else {
    $fail++;
    echo "  FAIL imagem não acessível em {$firstUrl}\n";
}

// Redimensionamento: a imagem de 900px foi reduzida ao limite configurado
$dimensions = @getimagesize($firstUrl);
echo "  dimensões gravadas: " . ($dimensions ? "{$dimensions[0]}x{$dimensions[1]}" : "?") . "\n";

$second = $imgList[1]["id"] ?? 0;
check("PUT capa para a segunda imagem", "PUT", "/service-images/{$svcImgId}/cover/{$second}", 200, "success",
    [], $adminToken);
check("PUT capa de imagem de outro serviço -> 404", "PUT", "/service-images/999999/cover/{$second}", 404,
    "not_found", [], $adminToken);

$afterCover = req("GET", "/service-images/{$svcImgId}")[1]["data"] ?? [];
$coverId = ($afterCover[0]["cover"] ?? 0) == 1 ? $afterCover[0]["id"] : null;
echo "  capa trocada e listada primeiro: " . ($coverId == $second ? "sim" : "NÃO (falha)") . "\n";
$coverId == $second ? $pass++ : $fail++;

// A capa aparece junto do serviço, para a listagem da área pública
$svcWithCover = req("GET", "/services/{$svcImgId}")[1]["data"] ?? [];
echo "  cover_image no payload do serviço: " . (!empty($svcWithCover["cover_image"]) ? "sim" : "NÃO (falha)")
    . " | images_count=" . ($svcWithCover["images_count"] ?? "?") . "\n";
!empty($svcWithCover["cover_image"]) ? $pass++ : $fail++;

check("DELETE imagem sem token -> 401", "DELETE", "/service-images/{$svcImgId}/{$second}", 401, "unauthorized");
check("DELETE imagem", "DELETE", "/service-images/{$svcImgId}/{$second}", 200, "success", [], $adminToken);
check("DELETE a mesma imagem -> 404", "DELETE", "/service-images/{$svcImgId}/{$second}", 404, "not_found",
    [], $adminToken);

$afterDelete = req("GET", "/service-images/{$svcImgId}")[1]["data"] ?? [];
echo "  capa reatribuída após excluir a capa: "
    . ((count($afterDelete) === 1 && (int)$afterDelete[0]["cover"] === 1) ? "sim" : "NÃO (falha)") . "\n";
(count($afterDelete) === 1 && (int)$afterDelete[0]["cover"] === 1) ? $pass++ : $fail++;

check("DELETE a última imagem", "DELETE", "/service-images/{$svcImgId}/" . $afterDelete[0]["id"],
    200, "success", [], $adminToken);

echo "\n=== 13. Recuperação de senha (Etapa 07.1) ===\n";
check("POST /passwords/forgot sem e-mail -> 400", "POST", "/passwords/forgot", 400, "bad_request", []);
check("POST /passwords/forgot e-mail inválido -> 400", "POST", "/passwords/forgot", 400, "bad_request",
    ["email" => "nao-e-email"]);
// E-mail desconhecido responde 200 de propósito: revelar o contrário permitiria
// descobrir quais e-mails têm conta
check("POST /passwords/forgot e-mail inexistente -> 200", "POST", "/passwords/forgot", 200, "success",
    ["email" => "zz.ninguem." . time() . "@joille.com"]);
check("GET /passwords/validate/token-falso -> 404", "GET", "/passwords/validate/tokenfalso123", 404, "not_found");
check("POST /passwords/reset sem campos -> 400", "POST", "/passwords/reset", 400, "bad_request", []);
check("POST /passwords/reset senhas diferentes -> 400", "POST", "/passwords/reset", 400, "bad_request",
    ["token" => "x", "password" => "senha12345", "confirmPassword" => "outra12345"]);
check("POST /passwords/reset senha curta -> 400", "POST", "/passwords/reset", 400, "bad_request",
    ["token" => "x", "password" => "curta", "confirmPassword" => "curta"]);
check("POST /passwords/reset token falso -> 404", "POST", "/passwords/reset", 404, "not_found",
    ["token" => "tokenfalso123", "password" => "senha12345", "confirmPassword" => "senha12345"]);

// A resposta precisa ser IGUAL para e-mail existente e inexistente, senão dá para
// descobrir quais endereços têm conta no sistema
$zzMail = "zz.recup." . time() . "@joille.com";
req("POST", "/users/register", ["name" => "ZZ Recuperação", "email" => $zzMail, "password" => "senha12345"]);

$known = req("POST", "/passwords/forgot", ["email" => $zzMail])[1];
$unknown = req("POST", "/passwords/forgot", ["email" => "zz.nao.existe." . time() . "@joille.com"])[1];

if (($known["message"] ?? "") === ($unknown["message"] ?? "-")) {
    $pass++;
    echo "  ok   resposta idêntica para e-mail existente e inexistente\n";
} else {
    $fail++;
    echo "  FAIL a resposta revela se o e-mail existe\n";
}

// Fluxo completo: só é testável de ponta a ponta no modo "capture", que grava o
// e-mail em disco em vez de enviar
$captureDir = __DIR__ . "/storage/mail";
$before = glob("{$captureDir}/*.html") ?: [];

req("POST", "/passwords/forgot", ["email" => $zzMail]);

$after = glob("{$captureDir}/*.html") ?: [];
$novos = array_values(array_diff($after, $before));

if (count($novos) === 1) {
    $pass++;
    echo "  ok   e-mail gravado em storage/mail/ (modo capture)\n";

    // O link do e-mail tem de funcionar de verdade
    $html = (string)file_get_contents($novos[0]);
    preg_match('~redefinir-senha\?token=([a-f0-9]{64})~', $html, $found);
    $token = $found[1] ?? "";

    check("GET /passwords/validate/{token do e-mail}", "GET", "/passwords/validate/{$token}",
        200, "success");
    check("POST /passwords/reset com o token do e-mail", "POST", "/passwords/reset", 200, "success",
        ["token" => $token, "password" => "novaSenha123", "confirmPassword" => "novaSenha123"]);
    check("POST /users/login com a senha redefinida", "POST", "/users/login", 200, "success",
        ["email" => $zzMail, "password" => "novaSenha123"]);
    check("POST /passwords/reset reusando o token -> 404", "POST", "/passwords/reset", 404, "not_found",
        ["token" => $token, "password" => "outra123456", "confirmPassword" => "outra123456"]);

    // O e-mail de aviso de senha alterada também é gravado
    $depois = glob("{$captureDir}/*.html") ?: [];
    $aviso = count($depois) > count($after);
    $aviso ? $pass++ : $fail++;
    echo "  " . ($aviso ? "ok  " : "FAIL") . " aviso de senha alterada enviado ao titular\n";

    // Limpa só os arquivos criados por este teste
    foreach (array_diff($depois, $before) as $file) {
        @unlink($file);
    }
} else {
    // Em CONF_MAIL_MODE = "smtp" não há arquivo para inspecionar: o fluxo completo
    // depende de uma conta real e é verificado manualmente
    echo "  --   modo \"smtp\" ativo: fluxo completo do e-mail não é testável aqui\n";
}

echo "\n=== 14. Formulário de contato ===\n";
check("POST /contacts sem campos -> 400", "POST", "/contacts", 400, "bad_request", []);
check("POST /contacts e-mail inválido -> 400", "POST", "/contacts", 400, "bad_request",
    ["name" => "ZZ Visitante", "email" => "nao-e-email", "message" => "mensagem com tamanho suficiente"]);
check("POST /contacts mensagem curta -> 400", "POST", "/contacts", 400, "bad_request",
    ["name" => "ZZ Visitante", "email" => "zz.visitante@joille.com", "message" => "curta"]);
$contact = check("POST /contacts (público)", "POST", "/contacts", 201, "created", [
    "name" => "ZZ Visitante", "email" => "zz.visitante@joille.com",
    "subject" => "ZZ Assunto", "message" => "Mensagem de teste automatizado do formulário."
]);
$contactId = $contact["data"]["id"] ?? null;
echo "  mensagem gravada: id={$contactId} | e-mail entregue: "
    . (($contact["data"]["mail_delivered"] ?? false) ? "sim" : "não (SMTP não configurado)") . "\n";

// A mensagem tem de sobreviver mesmo se o e-mail falhar: é o dado do visitante
$stored = req("GET", "/contacts", [], $adminToken)[1]["data"] ?? [];
$found = array_filter($stored, fn($row) => (int)$row["id"] === (int)$contactId);
$found ? $pass++ : $fail++;
echo "  " . ($found ? "ok  " : "FAIL") . " mensagem gravada aparece na listagem do painel\n";

check("GET /contacts sem token -> 401", "GET", "/contacts", 401, "unauthorized");
check("GET /contacts (admin)", "GET", "/contacts", 200, "success", [], $adminToken);
check("PUT /contacts/{id} marcar respondida", "PUT", "/contacts/{$contactId}", 200, "success",
    ["answered" => 1], $adminToken);
check("PUT /contacts/{id} answered inválido -> 400", "PUT", "/contacts/{$contactId}", 400, "bad_request",
    ["answered" => 7], $adminToken);
check("DELETE /contacts/{id}", "DELETE", "/contacts/{$contactId}", 200, "success", [], $adminToken);
check("DELETE /contacts/{id} de novo -> 404", "DELETE", "/contacts/{$contactId}", 404, "not_found",
    [], $adminToken);

echo "\n=== 15. Indicadores e relatórios em PDF ===\n";
check("GET /reports/dashboard sem token -> 401", "GET", "/reports/dashboard", 401, "unauthorized");
$dash = check("GET /reports/dashboard", "GET", "/reports/dashboard", 200, "success", [], $adminToken);

$expectedKeys = ["totals", "services_by_category", "users_by_type", "faqs_by_type"];
$missing = array_diff($expectedKeys, array_keys($dash["data"] ?? []));
if (empty($missing)) {
    $pass++;
    echo "  ok   dashboard traz todas as séries dos gráficos\n";
    echo "       totais: " . json_encode($dash["data"]["totals"]) . "\n";
} else {
    $fail++;
    echo "  FAIL dashboard sem as chaves: " . implode(", ", $missing) . "\n";
}

check("GET /reports/services/pdf sem token -> 401", "GET", "/reports/services/pdf", 401, "unauthorized");

foreach (["services", "users", "faqs"] as $report) {
    [$pdfCode, , $pdfRaw] = req("GET", "/reports/{$report}/pdf", [], $adminToken);

    // %PDF- é a assinatura do formato; garante que não veio HTML de erro
    if ($pdfCode === 200 && str_starts_with($pdfRaw, "%PDF-")) {
        $pass++;
        printf("  ok   GET /reports/%s/pdf (%d KB)\n", $report, (int)(strlen($pdfRaw) / 1024));
    } else {
        $fail++;
        printf("  FAIL GET /reports/%s/pdf: got %d, início: %s\n",
            $report, $pdfCode, substr($pdfRaw, 0, 80));
    }
}

echo "\n=== 16. Limpeza dos registros de teste ===\n";

// O Web Service só faz exclusão LÓGICA (active = 0), de propósito — mas isso não
// serve para limpar depois de testar: as linhas ficariam no banco para sempre,
// aparecendo nas telas do painel. A limpeza vai direto ao banco, o que uma suíte
// de teste pode fazer (é ela que criou os registros).
//
// A ordem segue as chaves estrangeiras: filhos antes dos pais.
$purgeStatements = [
    "DELETE FROM service_images WHERE services_id IN (SELECT id FROM services WHERE name LIKE 'ZZ %')",
    "DELETE FROM services   WHERE name LIKE 'ZZ %'",
    "DELETE FROM categories  WHERE name LIKE 'ZZ %'",
    "DELETE FROM questions   WHERE question LIKE 'ZZ %'",
    "DELETE FROM types       WHERE description LIKE 'ZZ %'",
    "DELETE FROM password_resets WHERE users_id IN (SELECT id FROM users WHERE email LIKE 'zz.%')",
    "DELETE FROM contacts    WHERE contactEmaill LIKE 'zz.%'",
    // O admin@joille.com não casa com o padrão e é preservado
    "DELETE FROM users       WHERE email LIKE 'zz.%'",
];

try {
    $connection = Connect::getInstance();
    $removed = 0;

    foreach ($purgeStatements as $sql) {
        $removed += $connection->exec($sql);
    }

    echo "  {$removed} linhas de teste apagadas do banco\n";

    // Confirma que nada com prefixo de teste sobrou
    $leftover = 0;
    foreach ([
        "SELECT COUNT(*) FROM users      WHERE email LIKE 'zz.%'",
        "SELECT COUNT(*) FROM categories WHERE name LIKE 'ZZ %'",
        "SELECT COUNT(*) FROM services   WHERE name LIKE 'ZZ %'",
        "SELECT COUNT(*) FROM questions  WHERE question LIKE 'ZZ %'",
        "SELECT COUNT(*) FROM types      WHERE description LIKE 'ZZ %'",
    ] as $sql) {
        $leftover += (int)$connection->query($sql)->fetchColumn();
    }

    if ($leftover === 0) {
        $pass++;
        echo "  ok   banco limpo: nenhum registro de teste sobrou\n";
    } else {
        $fail++;
        echo "  FAIL {$leftover} registro(s) de teste ficaram no banco\n";
    }
} catch (Throwable $exception) {
    $fail++;
    echo "  FAIL não foi possível limpar o banco: {$exception->getMessage()}\n";
}

printf("\n===== %d passaram, %d falharam =====\n", $pass, $fail);
file_put_contents(__DIR__ . "/api-test-ctx.json", json_encode($ctx));
exit($fail > 0 ? 1 : 0);
