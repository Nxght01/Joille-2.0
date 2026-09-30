import ApiService from "./ApiService.js";
import Faq from "../entities/Faq.js";

/**
 * Serviço HTTP da entidade Faq (recurso `/faqs`).
 */
export default class FaqService extends ApiService {
    constructor() {
        super("/faqs", Faq);
    }

    /** Listagem administrativa: inclui as perguntas inativas. */
    async listAdmin() {
        return this.toResult(await this.get(`${this.resource}/admin/list`));
    }

    async listByType(typeId) {
        return this.toResult(await this.get(`${this.resource}/type/:id`, { id: typeId }));
    }
}
