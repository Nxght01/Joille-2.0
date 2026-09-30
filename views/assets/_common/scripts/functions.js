import { URL_BASE, API_BASE_URL } from "./config.js";

// Retorno da URL do backend
export function getBackendUrl(path = "") {
  return `${URL_BASE}/${path.replace(/^\//, "")}`;
}

// Retorno da URL da API
export function getBackendUrlApi(path = "") {
  return `${API_BASE_URL}/${path.replace(/^\//, "")}`;
}

/**
 * Preenche um <select> a partir de uma coleção de entidades.
 * As entidades expõem o getter `label` (Category.name, FaqType.description).
 */
export function showDataSelect(entities, select) {
  if (!select || !Array.isArray(entities)) {
    return;
  }

  entities.forEach((entity) => {
    const option = document.createElement("option");
    option.value = entity.id;
    option.textContent = entity.label ?? entity.name ?? entity.description;
    select.appendChild(option);
  });
}

// Função que recebe o nome completo de uma pessoa e retorna só o primeiro nome
export function getFirstName(fullName) {
  return String(fullName ?? "").split(" ")[0];
}

/**
 * Escapa um texto para uso dentro de HTML.
 *
 * Obrigatório sempre que um valor vindo do usuário é interpolado em um template
 * de `innerHTML` — o caso mais crítico são as mensagens de contato, escritas por
 * visitantes anônimos e exibidas no painel administrativo. Também protege
 * atributos: as aspas são escapadas, então `alt="${escapeHtml(v)}"` é seguro.
 */
export function escapeHtml(value) {
  if (value === null || value === undefined) {
    return "";
  }

  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}
