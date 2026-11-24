import Entity from "./Entity.js";
import { getBackendUrl } from "../../scripts/functions.js";

/**
 * Serviço — entidade principal do Joille.
 * Tabela `services`, relacionada a `categories` por `categories_id`.
 */
export default class Service extends Entity {
    #categoriesId;
    #name;
    #description;
    #value;
    #active;
    #categoryName;
    #coverImage;
    #imagesCount;

    constructor({
        id = null,
        categories_id = null,
        name = "",
        description = "",
        value = 0,
        active = 1,
        category_name = null,
        cover_image = null,
        images_count = 0
    } = {}) {
        super({ id });

        this.categoriesId = categories_id;
        this.name = name;
        this.description = description;
        this.value = value;
        this.active = active;
        this.categoryName = category_name;
        this.coverImage = cover_image;
        this.imagesCount = images_count;
    }

    get categoriesId() {
        return this.#categoriesId;
    }

    set categoriesId(value) {
        if (value === null || value === undefined || value === "") {
            this.#categoriesId = null;
            return;
        }

        const number = Number(value);

        if (!Number.isInteger(number) || number < 1) {
            throw new RangeError("A categoria do serviço é obrigatória");
        }

        this.#categoriesId = number;
    }

    get name() {
        return this.#name;
    }

    set name(value) {
        this.#name = Entity.requireText(value, "O nome do serviço");
    }

    get description() {
        return this.#description;
    }

    set description(value) {
        this.#description = Entity.requireText(value, "A descrição do serviço");
    }

    get value() {
        return this.#value;
    }

    set value(value) {
        // O banco guarda `value` como varchar, mas no frontend ele é sempre número
        const number = Number(String(value).replace(",", "."));

        if (!Number.isFinite(number) || number < 0) {
            throw new RangeError("O valor deve ser um número não negativo");
        }

        this.#value = number;
    }

    get active() {
        return this.#active;
    }

    set active(value) {
        this.#active = Entity.toActive(value);
    }

    /** Só existe quando o payload vem do JOIN com `categories`. */
    get categoryName() {
        return this.#categoryName;
    }

    set categoryName(value) {
        this.#categoryName = value === null || value === undefined || value === "" ? null : String(value);
    }

    /** Caminho relativo da imagem de capa, como veio do banco. */
    get coverImage() {
        return this.#coverImage;
    }

    set coverImage(value) {
        this.#coverImage = value === null || value === undefined || value === "" ? null : String(value);
    }

    get imagesCount() {
        return this.#imagesCount;
    }

    set imagesCount(value) {
        const number = Number(value);
        this.#imagesCount = Number.isInteger(number) && number >= 0 ? number : 0;
    }

    /** URL absoluta da capa, pronta para o atributo src, ou null quando não há imagem. */
    get coverImageUrl() {
        return this.#coverImage === null ? null : getBackendUrl(this.#coverImage);
    }

    get hasImages() {
        return this.#imagesCount > 0 || this.#coverImage !== null;
    }

    get isActive() {
        return this.#active === 1;
    }

    /** Valor formatado em real brasileiro, para exibição. */
    get formattedValue() {
        if (this.#value === 0) return 'Sob orçamento';
        return this.#value.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
    }

    toJSON() {
        return {
            id: this.id,
            categories_id: this.categoriesId,
            name: this.name,
            description: this.description,
            value: this.value,
            active: this.active
        };
    }
}
