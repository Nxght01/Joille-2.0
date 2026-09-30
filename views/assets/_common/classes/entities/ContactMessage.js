import Entity from "./Entity.js";

/**
 * Mensagem do formulário de contato (tabela `contacts`).
 *
 * Enviada por visitantes da área pública e lida no painel administrativo.
 */
export default class ContactMessage extends Entity {
    #name;
    #email;
    #subject;
    #message;
    #answered;
    #createdAt;

    constructor({
        id = null,
        name = "",
        email = "",
        subject = null,
        message = "",
        answered = 0,
        created_at = null
    } = {}) {
        super({ id });

        this.name = name;
        this.email = email;
        this.subject = subject;
        this.message = message;
        this.answered = answered;
        this.createdAt = created_at;
    }

    get name() {
        return this.#name;
    }

    set name(value) {
        this.#name = Entity.requireText(value, "O nome");
    }

    get email() {
        return this.#email;
    }

    set email(value) {
        const email = Entity.requireText(value, "O e-mail");

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            throw new TypeError("O e-mail deve ser válido");
        }

        this.#email = email;
    }

    get subject() {
        return this.#subject;
    }

    set subject(value) {
        this.#subject = value === null || value === undefined || String(value).trim() === ""
            ? null
            : String(value).trim();
    }

    get message() {
        return this.#message;
    }

    set message(value) {
        const message = Entity.requireText(value, "A mensagem");

        if (message.length < 10) {
            throw new RangeError("A mensagem deve ter no mínimo 10 caracteres");
        }

        this.#message = message;
    }

    get answered() {
        return this.#answered;
    }

    set answered(value) {
        this.#answered = Entity.toActive(value, 0);
    }

    get isAnswered() {
        return this.#answered === 1;
    }

    get createdAt() {
        return this.#createdAt;
    }

    set createdAt(value) {
        this.#createdAt = value === null || value === undefined || value === "" ? null : String(value);
    }

    /** Data de recebimento em formato brasileiro, para exibição. */
    get formattedDate() {
        if (this.#createdAt === null) {
            return "-";
        }

        // O MySQL devolve "AAAA-MM-DD HH:MM:SS"; o T deixa o Date interpretar
        // como hora local em vez de UTC.
        const date = new Date(this.#createdAt.replace(" ", "T"));

        return Number.isNaN(date.getTime())
            ? this.#createdAt
            : date.toLocaleString("pt-BR", { dateStyle: "short", timeStyle: "short" });
    }

    toJSON() {
        return {
            id: this.id,
            name: this.name,
            email: this.email,
            subject: this.subject,
            message: this.message,
            answered: this.answered
        };
    }
}
