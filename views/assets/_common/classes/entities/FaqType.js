import Entity from "./Entity.js";

/**
 * Tipo de pergunta frequente. Tabela `types`.
 */
export default class FaqType extends Entity {
    #description;
    #active;

    constructor({ id = null, description = "", active = 1 } = {}) {
        super({ id });

        this.description = description;
        this.active = active;
    }

    get description() {
        return this.#description;
    }

    set description(value) {
        this.#description = Entity.requireText(value, "A descrição do tipo");
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

    /** Rótulo usado nos <select> das telas. */
    get label() {
        return this.#description;
    }

    toJSON() {
        return {
            id: this.id,
            description: this.description,
            active: this.active
        };
    }
}
