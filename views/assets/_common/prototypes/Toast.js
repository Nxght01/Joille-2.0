/**
 * Toast — POO PROTOTÍPICA (Object.create).
 *
 * Não existe `class` aqui. Os métodos moram uma única vez em `toastPrototype`;
 * cada toast criado por `createToast()` é um objeto novo que apenas *delega*
 * ao mesmo protótipo. Dois toasts criados pela fábrica compartilham
 * exatamente as mesmas funções:
 *
 *     Object.getPrototypeOf(a) === Object.getPrototypeOf(b)   // true
 *     a.show === b.show                                       // true
 *
 * O que é próprio de cada objeto: `selector`, `duration` e `logger`.
 * O que é compartilhado: show, success, warning, error e fromResponse.
 */

export const TOAST_TYPES = Object.freeze(["success", "warning", "error"]);

export const toastPrototype = {
    /** Resolve o contêiner só na hora de exibir, para não depender do DOM na importação. */
    resolveContainer() {
        const container =
            document.querySelector(this.selector) ??
            document.querySelector("#toast-container");

        if (container) {
            return container;
        }

        // Cria um contêiner quando a página não declarou nenhum
        const created = document.createElement("div");
        created.className = "toast-container";
        document.body.appendChild(created);
        return created;
    },

    show(message, type = "success") {
        const variant = TOAST_TYPES.includes(type) ? type : "error";
        const text = String(message ?? "").trim();

        if (text === "") {
            return null;
        }

        const container = this.resolveContainer();
        const toast = document.createElement("div");
        toast.className = `toast toast-${variant}`;
        toast.setAttribute("role", variant === "error" ? "alert" : "status");
        toast.textContent = text;

        container.appendChild(toast);

        requestAnimationFrame(() => toast.classList.add("show"));

        setTimeout(() => {
            toast.classList.remove("show");
            setTimeout(() => toast.remove(), 400);
        }, this.duration);

        return toast;
    },

    success(message) {
        return this.show(message, "success");
    },

    warning(message) {
        return this.show(message, "warning");
    },

    error(message) {
        return this.show(message, "error");
    },

    /**
     * Exibe a mensagem de uma resposta da API.
     *
     * - funciona com respostas COM ou SEM `data`;
     * - nunca usa a `data` inteira como mensagem;
     * - resposta inesperada não é silenciada: mostra um aviso e registra o
     *   objeto original no console para depuração.
     */
    fromResponse(response, fallback = "Não foi possível concluir a operação.") {
        if (!response || typeof response !== "object") {
            this.logger.error("Resposta inesperada da API:", response);
            return this.error(fallback);
        }

        const message = typeof response.message === "string" && response.message.trim() !== ""
            ? response.message
            : null;

        if (message === null) {
            this.logger.error("Resposta da API sem a chave 'message':", response);
            return this.error(fallback);
        }

        return this.show(message, this.variantFor(response));
    },

    /** Traduz o envelope da API para uma das três aparências do toast. */
    variantFor(response) {
        if (response.type === "success") {
            return "success";
        }

        const code = Number(response.code);

        // 400 é erro de preenchimento: o usuário consegue corrigir -> amarelo
        if (response.type === "warning" || code === 400) {
            return "warning";
        }

        return "error";
    }
};

/**
 * Fábrica: cria um objeto que delega ao protótipo compartilhado.
 */
export function createToast({ selector = ".toast-container", duration = 3000, logger = console } = {}) {
    const toast = Object.create(toastPrototype);

    toast.selector = selector;
    toast.duration = duration;
    toast.logger = logger;

    return toast;
}

export default createToast;
