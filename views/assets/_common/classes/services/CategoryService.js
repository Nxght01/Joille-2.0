import ApiService from "./ApiService.js";
import Category from "../entities/Category.js";

/**
 * Serviço HTTP da entidade Category (recurso `/categories`).
 */
export default class CategoryService extends ApiService {
    constructor() {
        super("/categories", Category);
    }

    /** Listagem administrativa: inclui as categorias inativas. */
    async listAdmin() {
        return this.toResult(await this.get(`${this.resource}/admin/list`));
    }
}
