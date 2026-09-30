/**
 * Classe base de todas as entidades do frontend (POO clássica).
 *
 * Concentra o que toda entidade tem em comum:
 *  - o campo `id`, encapsulado em campo privado (#id) e validado no setter;
 *  - a conversão payload da API -> objeto JavaScript (fromApi / collectionFromApi);
 *  - a conversão objeto JavaScript -> payload da API (toJSON / toFormData).
 *
 * As subclasses herdam tudo isso e implementam apenas o seu próprio toJSON().
 */
export default class Entity {
    #id;

    constructor({ id = null } = {}) {
        this.id = id; // passa pelo setter, portanto é validado
    }

    get id() {
        return this.#id;
    }

    set id(value) {
        if (value === null || value === undefined || value === "") {
            this.#id = null;
            return;
        }

        const number = Number(value);

        if (!Number.isInteger(number) || number < 1) {
            throw new RangeError("O id deve ser um número inteiro positivo");
        }

        this.#id = number;
    }

    /** Um registro que ainda não existe no banco. */
    get isNew() {
        return this.#id === null;
    }

    /**
     * `this` em um método estático é a própria subclasse que foi chamada,
     * então Service.fromApi() devolve um Service e Category.fromApi() um Category.
     */
    static fromApi(payload) {
        return new this(payload ?? {});
    }

    static collectionFromApi(payloads) {
        return (Array.isArray(payloads) ? payloads : []).map((payload) => this.fromApi(payload));
    }

    toJSON() {
        throw new Error(`${this.constructor.name} precisa implementar toJSON()`);
    }

    /**
     * Payload pronto para enviar à API. Campos nulos são omitidos, o que
     * permite o update parcial aceito pelo backend.
     */
    toFormData() {
        const formData = new FormData();

        Object.entries(this.toJSON()).forEach(([key, value]) => {
            if (value !== null && value !== undefined) {
                formData.append(key, value);
            }
        });

        return formData;
    }

    /** Normaliza o `active` (0/1) usado pela exclusão lógica do backend. */
    static toActive(value, fallback = 1) {
        if (value === null || value === undefined || value === "") {
            return fallback;
        }

        const number = Number(value);

        if (![0, 1].includes(number)) {
            throw new RangeError("O campo active deve ser 0 ou 1");
        }

        return number;
    }

    /** Valida um texto obrigatório e devolve-o sem espaços nas pontas. */
    static requireText(value, label) {
        if (typeof value !== "string" || value.trim() === "") {
            throw new TypeError(`${label} é obrigatório`);
        }

        return value.trim();
    }
}
