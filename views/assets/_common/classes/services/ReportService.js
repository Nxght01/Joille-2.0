import ApiService from "./ApiService.js";

/**
 * Indicadores e relatórios da área administrativa (recurso `/reports`).
 *
 * `dashboard()` traz os números e as séries que o painel usa nos gráficos.
 * Os métodos `*Pdf()` devolvem o PDF como Blob — o download precisa passar
 * pelo fetch (e não por um link direto) porque o token JWT vai no header
 * `Authorization`, que uma navegação normal do navegador não envia.
 *
 * Nenhuma rota devolve entidade, então o serviço não tem classe de entidade.
 */
export default class ReportService extends ApiService {
    constructor() {
        super("/reports", null);
    }

    async dashboard() {
        return this.toPlainResult(await this.get(`${this.resource}/dashboard`));
    }

    async servicesPdf() {
        return this.toPdfResult(await this.getBlob(`${this.resource}/services/pdf`));
    }

    async usersPdf() {
        return this.toPdfResult(await this.getBlob(`${this.resource}/users/pdf`));
    }

    async faqsPdf() {
        return this.toPdfResult(await this.getBlob(`${this.resource}/faqs/pdf`));
    }

    /**
     * Normaliza a resposta de um relatório para o mesmo formato dos demais:
     * `{ ok, blob, message, code }`. Um Blob significa PDF pronto; um objeto,
     * o envelope de erro da API.
     */
    toPdfResult(response) {
        if (response instanceof Blob) {
            return { ok: true, blob: response, code: 200, message: "Relatório gerado com sucesso." };
        }

        return { ...this.toPlainResult(response), blob: null };
    }
}
