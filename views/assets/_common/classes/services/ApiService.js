import HttpClientBase from "../HttpClientBase.js";

/**
 * Base dos serviços HTTP (POO clássica — herança em dois níveis):
 *
 *      HttpClientBase  ->  ApiService  ->  ServiceService / CategoryService / ...
 *
 * HttpClientBase cuida do transporte (URL base, cabeçalhos, Bearer, fetch).
 * ApiService acrescenta o que é específico da API do Joille: o recurso REST,
 * a conversão do payload em entidades e o contrato de resposta
 * { code, type, status, message, data }.
 *
 * Um serviço NUNCA manipula HTML: ele devolve dados: quem desenha é a tela.
 */
export default class ApiService extends HttpClientBase {
    #resource;
    #entityClass;

    /**
     * @param {string} resource    caminho do recurso, ex.: "/services"
     * @param {Function|null} entityClass classe de entidade usada na conversão.
     *        `null` para recursos que não têm entidade própria (relatórios,
     *        recuperação de senha): nesse caso só o envelope é normalizado.
     */
    constructor(resource, entityClass = null) {
        super();

        if (typeof resource !== "string" || !resource.startsWith("/")) {
            throw new TypeError('O recurso deve começar com "/", ex.: "/services"');
        }

        this.#resource = resource;
        this.#entityClass = entityClass;
    }

    get resource() {
        return this.#resource;
    }

    get entityClass() {
        return this.#entityClass;
    }

    /**
     * Normaliza o envelope da API e anexa as entidades já convertidas.
     * `entities` para listas, `entity` para registro único.
     */
    toResult(response) {
        const ok = response?.type === "success";

        // Recurso sem entidade: só o envelope interessa
        if (!this.#entityClass) {
            return this.toPlainResult(response);
        }

        if (!ok) {
            return { ...response, ok, entities: [], entity: null };
        }

        if (Array.isArray(response.data)) {
            return { ...response, ok, entities: this.#entityClass.collectionFromApi(response.data), entity: null };
        }

        // Rotas paginadas devolvem { page, perPage, total, data: [...] }
        if (Array.isArray(response.data?.data)) {
            return {
                ...response,
                ok,
                entities: this.#entityClass.collectionFromApi(response.data.data),
                entity: null
            };
        }

        return {
            ...response,
            ok,
            entities: [],
            entity: response.data ? this.#entityClass.fromApi(response.data) : null
        };
    }

    /**
     * Para respostas cujo `data` não representa a entidade completa
     * (ex.: { id, photo }), mantém o envelope e só acrescenta `ok`.
     */
    toPlainResult(response) {
        return { ...response, ok: response?.type === "success" };
    }

    /** Aceita entidade, FormData ou objeto simples e devolve algo enviável. */
    toPayload(input) {
        if (input instanceof FormData ||
            (typeof HTMLFormElement !== "undefined" && input instanceof HTMLFormElement)) {
            return input;
        }

        if (typeof input?.toFormData === "function") {
            return input.toFormData();
        }

        return this.toFormData(input ?? {});
    }

    // --- CRUD padrão ---------------------------------------------------------

    async list(params = {}) {
        return this.toResult(await this.get(this.#resource, params));
    }

    async find(id) {
        return this.toResult(await this.get(`${this.#resource}/:id`, { id }));
    }

    async create(input) {
        return this.toResult(await this.post(this.#resource, this.toPayload(input)));
    }

    async update(id, input) {
        return this.toResult(await this.putForm(`${this.#resource}/:id`, this.toPayload(input), { id }));
    }

    async remove(id) {
        return this.toResult(await this.delete(`${this.#resource}/:id`, { id }));
    }
}
