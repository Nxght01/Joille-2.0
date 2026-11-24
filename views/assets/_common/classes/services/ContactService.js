import ApiService from "./ApiService.js";
import ContactMessage from "../entities/ContactMessage.js";

/**
 * Serviço HTTP das mensagens de contato (recurso `/contacts`).
 *
 * O envio (`create`) é público; a listagem, a baixa e a exclusão são do painel
 * administrativo e exigem token de admin. O POST retorna uma confirmação de
 * recebimento e envio, não uma ContactMessage completa.
 */
export default class ContactService extends ApiService {
    constructor() {
        super("/contacts", ContactMessage);
    }

    async create(input) {
        return this.toPlainResult(await this.post(this.resource, this.toPayload(input)));
    }

    /** Marca a mensagem como respondida (ou volta para pendente). */
    async setAnswered(id, answered = 1) {
        return this.toPlainResult(
            await this.putForm(`${this.resource}/:id`, { answered }, { id })
        );
    }
}
