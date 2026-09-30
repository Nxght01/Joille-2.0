import ApiService from "./ApiService.js";
import Service from "../entities/Service.js";

/**
 * Serviço HTTP da entidade Service (recurso `/services`).
 *
 * O nome repetido ("Service" + "Service") é proposital: segue o padrão
 * <Entidade>Service pedido no enunciado, aplicado à entidade Serviço.
 *
 * Herda list / find / create / update / remove de ApiService e acrescenta
 * apenas as rotas específicas deste recurso.
 */
export default class ServiceService extends ApiService {
    constructor() {
        super("/services", Service);
    }

    /** Listagem administrativa: inclui os serviços inativos. */
    async listAdmin() {
        return this.toResult(await this.get(`${this.resource}/admin/list`));
    }

    async listByCategory(categoryId) {
        return this.toResult(await this.get(`${this.resource}/category/:id`, { id: categoryId }));
    }

    async listPaginated(page = 1, perPage = 10) {
        return this.toResult(
            await this.get(`${this.resource}/paginator/:page/:per_page`, { page, per_page: perPage })
        );
    }
}
