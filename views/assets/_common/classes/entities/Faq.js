import Entity from "./Entity.js";

/**
 * Pergunta frequente. Tabela `questions`, relacionada a `types` por `types_id`.
 */
export default class Faq extends Entity {
    #typesId;
    #question;
    #answer;
    #active;

    constructor({ id = null, types_id = null, question = "", answer = "", active = 1 } = {}) {
        super({ id });

        this.typesId = types_id;
        this.question = question;
        this.answer = answer;
        this.active = active;
    }

    get typesId() {
        return this.#typesId;
    }

    set typesId(value) {
        if (value === null || value === undefined || value === "") {
            this.#typesId = null;
            return;
        }

        const number = Number(value);

        if (!Number.isInteger(number) || number < 1) {
            throw new RangeError("O tipo da pergunta é obrigatório");
        }

        this.#typesId = number;
    }

    get question() {
        return this.#question;
    }

    set question(value) {
        this.#question = Entity.requireText(value, "A pergunta");
    }

    get answer() {
        return this.#answer;
    }

    set answer(value) {
        this.#answer = Entity.requireText(value, "A resposta");
    }

    get active() {
        return this.#active;
    }

    set active(value) {
        this.#active = Entity.toActive(value);
    }

    get isActive() {
        return this.#active === 1;
    }

    /** Prévia da pergunta para listagens estreitas. */
    resume(limit = 80) {
        return this.#question.length > limit
            ? `${this.#question.slice(0, limit)}...`
            : this.#question;
    }

    toJSON() {
        return {
            id: this.id,
            types_id: this.typesId,
            question: this.question,
            answer: this.answer,
            active: this.active
        };
    }
}
