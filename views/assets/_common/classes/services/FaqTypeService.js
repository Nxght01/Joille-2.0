import ApiService from "./ApiService.js";
import FaqType from "../entities/FaqType.js";

/**
 * Serviço HTTP da entidade FaqType (recurso `/faqs-types`).
 * Usa o CRUD herdado de ApiService sem acrescentar rotas.
 */
export default class FaqTypeService extends ApiService {
    constructor() {
        super("/faqs-types", FaqType);
    }
}
