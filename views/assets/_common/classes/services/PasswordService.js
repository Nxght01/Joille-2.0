import ApiService from "./ApiService.js";

/**
 * Recuperação de senha por e-mail (recurso `/passwords`).
 *
 * Nenhuma destas rotas exige token — quem esqueceu a senha não consegue logar —
 * e nenhuma devolve entidade, apenas o envelope da API, por isso o serviço é
 * criado sem classe de entidade.
 */
export default class PasswordService extends ApiService {
    constructor() {
        super("/passwords", null);
    }

    /** Dispara o e-mail com o link de recuperação. */
    async forgot(input) {
        return this.toPlainResult(await this.post(`${this.resource}/forgot`, this.toPayload(input)));
    }

    /** Confere o link antes de a tela liberar o formulário de nova senha. */
    async validateToken(token) {
        return this.toPlainResult(await this.get(`${this.resource}/validate/:token`, { token }));
    }

    /** Grava a nova senha e consome o token. */
    async reset(input) {
        return this.toPlainResult(await this.post(`${this.resource}/reset`, this.toPayload(input)));
    }
}
