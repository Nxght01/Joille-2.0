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
    #caption;
    #cover;

    constructor({
        id = null,
        services_id = null,
        path = "",
        caption = null,
        cover = 0
    } = {}) {
        super({ id });

        this.servicesId = services_id;
        this.path = path;
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
        const path = Entity.requireText(value, "O caminho da imagem");
        if (!/^[\w/-]+\.(?:jpe?g|png)$/i.test(path) || path.startsWith("/")) {
            throw new TypeError("Caminho de imagem inválido");
        }
        this.#path = path;
    }

    /** Deriva a URL do caminho; ignora URLs externas recebidas no payload. */
    get url() {
        return getBackendUrl(this.#path);
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
