import { getBackendUrl } from "./functions.js";

/**
 * Estados de lista exigidos pela Etapa 05 (carregando, vazio, erro).
 * As mensagens de sucesso/aviso/erro ficam a cargo do Toast prototípico.
 */

function renderState(container, message, variant) {
    if (!container) {
        return;
    }

    container.innerHTML = `<p class="list-state list-state-${variant}">${message}</p>`;
}

export function showLoading(container, message = "Carregando...") {
    renderState(container, message, "loading");
}

export function showEmpty(container, message = "Nenhum registro encontrado.") {
    renderState(container, message, "empty");
}

export function showListError(container, message = "Não foi possível carregar os dados.") {
    renderState(container, message, "error");
}

/**
 * Mensagem para falhas de rede / serviço indisponível. O HttpClientBase lança
 * uma exceção quando o fetch não completa, e nesse caso não existe envelope
 * da API para ler.
 */
export function serviceUnavailableMessage() {
    return "Serviço indisponível no momento. Verifique sua conexão e tente novamente.";
}

/**
 * Devolve o tratador de 401/403 usado pelas telas autenticadas.
 *
 *  401 -> sem sessão válida: limpa o armazenamento e manda para o login.
 *  403 -> sessão válida, mas sem permissão: o toast da resposta já informou.
 */
export function createAuthFailureHandler({ session, toast, loginUrl = getBackendUrl("login") }) {
    return (result) => {
        if (Number(result?.code) !== 401) {
            return false;
        }

        session?.clearSession();
        toast?.error("Sua sessão expirou. Faça login novamente.");

        setTimeout(() => {
            window.location.href = loginUrl;
        }, 2000);

        return true;
    };
}
