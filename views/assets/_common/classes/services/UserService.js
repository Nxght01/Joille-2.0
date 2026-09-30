import ApiService from "./ApiService.js";
import User from "../entities/User.js";

/**
 * Serviço HTTP da entidade User (recurso `/users`).
 *
 * As rotas de usuário não seguem o CRUD REST puro — o cadastro é
 * `/users/register`, a área administrativa fica sob `/users/admin/...` e o
 * login é **POST**, como exige o backend (`Users:auth`). Por isso os métodos
 * herdados de ApiService são sobrescritos aqui.
 */
export default class UserService extends ApiService {
    constructor() {
        super("/users", User);
    }

    // --- autenticação --------------------------------------------------------

    /** POST — o backend expõe o login como POST, nunca GET. */
    async login(input) {
        return this.toResult(await this.post(`${this.resource}/login`, this.toPayload(input)));
    }

    /** POST — login exclusivo do perfil administrador. */
    async loginAdmin(input) {
        return this.toResult(await this.post(`${this.resource}/login-admin`, this.toPayload(input)));
    }

    async register(input) {
        return this.toResult(await this.post(`${this.resource}/register`, this.toPayload(input)));
    }

    /** Cadastro feito pelo painel: pode criar administrador ou cliente. */
    async registerAdmin(input) {
        return this.toResult(await this.post(`${this.resource}/register-admin`, this.toPayload(input)));
    }

    /** Resposta traz apenas { id }, então não vira entidade. */
    async tokenValidate() {
        return this.toPlainResult(await this.get(`${this.resource}/token-validate`));
    }

    // --- perfil do usuário autenticado --------------------------------------

    async me() {
        return this.toResult(await this.get(`${this.resource}/me`));
    }

    async updateProfile(input) {
        return this.toResult(await this.putForm(`${this.resource}/update`, this.toPayload(input)));
    }

    async updatePassword(input) {
        return this.toPlainResult(await this.putForm(`${this.resource}/password`, this.toPayload(input)));
    }

    /** Upload multipart; a resposta traz apenas { id, photo }. */
    async updatePhoto(input) {
        return this.toPlainResult(await this.post(`${this.resource}/photo`, this.toPayload(input)));
    }

    // --- área administrativa (sobrescrevem o CRUD da base) -------------------

    async list() {
        return this.toResult(await this.get(`${this.resource}/admin/list`));
    }

    async find(id) {
        return this.toResult(await this.get(`${this.resource}/admin/:id`, { id }));
    }

    async create(input) {
        return this.registerAdmin(input);
    }

    async update(id, input) {
        return this.toResult(await this.putForm(`${this.resource}/admin/:id`, this.toPayload(input), { id }));
    }

    async remove(id) {
        return this.toResult(await this.delete(`${this.resource}/admin/:id`, { id }));
    }
}
