import Entity from "./Entity.js";

/**
 * Categoria de serviço. Tabela `categories`.
 */
export default class Category extends Entity {
    #name;
    #active;

    constructor({ id = null, name = "", active = 1 } = {}) {
        super({ id });

        this.name = name;
        this.active = active;
    }

    get name() {
        return this.#name;
    }

    set name(value) {
        this.#name = Entity.requireText(value, "O nome da categoria");
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
        return this.#name;
    }

    toJSON() {
        return {
            id: this.id,
            name: this.name,
            active: this.active
        };
    }
}
