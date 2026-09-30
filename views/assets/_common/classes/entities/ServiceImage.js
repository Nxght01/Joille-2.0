import Entity from "./Entity.js";
import { getBackendUrl } from "../../scripts/functions.js";

/**
 * Imagem de um serviço (tabela `service_images`).
 *
 * Cada serviço tem N imagens e no máximo uma capa. O backend devolve `path`
 * (relativo) e `url` (absoluta); quando só vem o path, `url` é derivada aqui.
 */
export default class ServiceImage extends Entity {
    #servicesId;
    #path;
    #url;
    #caption;
    #cover;

    constructor({
        id = null,
        services_id = null,
        path = "",
        url = null,
        caption = null,
        cover = 0
    } = {}) {
        super({ id });

        this.servicesId = services_id;
        this.path = path;
        this.url = url;
        this.caption = caption;
        this.cover = cover;
    }

    get servicesId() {
        return this.#servicesId;
    }

    set servicesId(value) {
        if (value === null || value === undefined || value === "") {
            this.#servicesId = null;
            return;
        }

        const number = Number(value);

        if (!Number.isInteger(number) || number < 1) {
            throw new RangeError("A imagem precisa estar vinculada a um serviço");
        }

        this.#servicesId = number;
    }

    get path() {
        return this.#path;
    }

    set path(value) {
        this.#path = Entity.requireText(value, "O caminho da imagem");
    }

    /** URL absoluta, pronta para o atributo src. */
    get url() {
        return this.#url;
    }

    set url(value) {
        this.#url = value === null || value === undefined || value === ""
            ? getBackendUrl(this.#path)
            : String(value);
    }

    get caption() {
        return this.#caption;
    }

    set caption(value) {
        this.#caption = value === null || value === undefined || String(value).trim() === ""
            ? null
            : String(value).trim();
    }

    get cover() {
        return this.#cover;
    }

    set cover(value) {
        this.#cover = Entity.toActive(value, 0);
    }

    get isCover() {
        return this.#cover === 1;
    }

    /** Texto alternativo: a legenda, quando existe, ou um rótulo genérico. */
    get alt() {
        return this.#caption ?? "Imagem do serviço";
    }

    toJSON() {
        return {
            id: this.id,
            services_id: this.servicesId,
            path: this.path,
            caption: this.caption,
            cover: this.cover
        };
    }
}
