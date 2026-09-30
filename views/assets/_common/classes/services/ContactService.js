import ApiService from "./ApiService.js";
import ContactMessage from "../entities/ContactMessage.js";

/**
 * Serviço HTTP das mensagens de contato (recurso `/contacts`).
 *
 * O envio (`create`) é público; a listagem, a baixa e a exclusão são do painel
 * administrativo e exigem token de admin. O `create` herdado de ApiService já
 * serve — só a baixa e a listagem precisam de método próprio.
 */
export default class ContactService extends ApiService {
    constructor() {
        super("/contacts", ContactMessage);
    }

    /** Marca a mensagem como respondida (ou volta para pendente). */
    async setAnswered(id, answered = 1) {
        return this.toPlainResult(
            await this.putForm(`${this.resource}/:id`, { answered }, { id })
        );
    }
}
