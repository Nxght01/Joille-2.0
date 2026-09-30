import ApiService from "./ApiService.js";
import ServiceImage from "../entities/ServiceImage.js";

/**
 * Serviço HTTP das imagens da entidade principal (recurso `/service-images`).
 *
 * Todas as rotas são aninhadas em um serviço (`/service-images/{serviceId}`),
 * então o CRUD genérico de ApiService não serve e os métodos são próprios.
 * A listagem é pública; enviar, definir capa e excluir exigem token de admin.
 */
export default class ServiceImageService extends ApiService {
    constructor() {
        super("/service-images", ServiceImage);
    }

    /** Imagens de um serviço, capa primeiro. */
    async listByService(serviceId) {
        return this.toResult(await this.get(`${this.resource}/:id`, { id: serviceId }));
    }

    /**
     * Upload de 1..N imagens. Aceita um <form>, um FormData ou uma FileList —
     * em todos os casos os arquivos vão no campo `images[]`.
     */
    async upload(serviceId, input) {
        return this.toResult(
            await this.post(`${this.resource}/:id`, this.toUploadPayload(input), { id: serviceId })
        );
    }

    async setCover(serviceId, imageId) {
        return this.toPlainResult(
            await this.putForm(`${this.resource}/:service/cover/:image`, {}, {
                service: serviceId,
                image: imageId
            })
        );
    }

    async remove(serviceId, imageId) {
        return this.toPlainResult(
            await this.delete(`${this.resource}/:service/:image`, {
                service: serviceId,
                image: imageId
            })
        );
    }

    /** Normaliza qualquer entrada em um FormData com o campo `images[]`. */
    toUploadPayload(input) {
        if (input instanceof FormData) {
            return input;
        }

        if (typeof HTMLFormElement !== "undefined" && input instanceof HTMLFormElement) {
            return new FormData(input);
        }

        const formData = new FormData();
        const files = input instanceof File ? [input] : Array.from(input ?? []);

        files.forEach((file) => formData.append("images[]", file));

        return formData;
    }
}
