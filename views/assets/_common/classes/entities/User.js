import Entity from "./Entity.js";

/**
 * Usuário. Tabela `users`, relacionada a `users_types` por `type_id`.
 *
 * A senha é encapsulada em um campo privado SEM getter público: pode ser
 * escrita (para cadastro / troca de senha) e segue no payload de envio, mas
 * nunca pode ser lida de volta a partir do objeto.
 */
export default class User extends Entity {
    static TYPE_ADMIN = 1;
    static TYPE_CLIENT = 2;

    #typeId;
    #name;
    #email;
    #photo;
    #active;
    #password = null;
    #token = null;
    #address = '';
    #phone = '';

    constructor({
        id = null,
        type_id = User.TYPE_CLIENT,
        name = "",
        email = "",
        photo = null,
        active = 1,
        password = null,
        token = null,
        address = '',
        phone = ''
    } = {}) {
        super({ id });

        this.typeId = type_id;
        this.name = name;
        this.email = email;
        this.photo = photo;
        this.active = active;
        this.address = address;
        this.phone = phone;

        if (password) {
            this.password = password;
        }

        if (token) {
            this.token = token;
        }
    }

    get typeId() {
        return this.#typeId;
    }
    get address() { return this.#address; }
    set address(value) {
        if (String(value ?? '').length > 255) throw new RangeError('Endereço muito longo.');
        this.#address = String(value ?? '').trim();
    }
    get phone() { return this.#phone; }
    set phone(value) {
        if (String(value ?? '').length > 30) throw new RangeError('Telefone muito longo.');
        this.#phone = String(value ?? '').trim();
    }

    set typeId(value) {
        const number = Number(value);

        if (![User.TYPE_ADMIN, User.TYPE_CLIENT].includes(number)) {
            throw new RangeError("O perfil deve ser 1 (administrador) ou 2 (cliente)");
        }

        this.#typeId = number;
    }

    get name() {
        return this.#name;
    }

    set name(value) {
        this.#name = Entity.requireText(value, "O nome");
    }

    get email() {
        return this.#email;
    }

    set email(value) {
        const email = Entity.requireText(value, "O e-mail");

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            throw new TypeError("Informe um e-mail válido");
        }

        this.#email = email;
    }

    get photo() {
        return this.#photo;
    }

    set photo(value) {
        this.#photo = value === null || value === undefined || value === "" ? null : String(value);
    }

    get active() {
        return this.#active;
    }

    set active(value) {
        this.#active = Entity.toActive(value);
    }

    get isActive() {
        return this.#active === 1;
    }

    get isAdmin() {
        return this.#typeId === User.TYPE_ADMIN;
    }

    /** Primeiro nome, usado nas saudações da interface. */
    get firstName() {
        return this.#name.split(" ")[0];
    }

    // --- senha: só escrita ---------------------------------------------------

    set password(value) {
        if (typeof value !== "string" || value.length < 8) {
            throw new RangeError("A senha deve ter no mínimo 8 caracteres");
        }

        this.#password = value;
    }

    get hasPassword() {
        return this.#password !== null;
    }

    // --- token ---------------------------------------------------------------

    get token() {
        return this.#token;
    }

    set token(value) {
        this.#token = value === null || value === undefined || value === "" ? null : String(value);
    }

    /** Dados mínimos da sessão gravados no localStorage — sem senha. */
    toSession() {
        return {
            id: this.id,
            name: this.name,
            email: this.email,
            photo: this.photo,
            type_id: this.typeId
        };
    }

    toJSON() {
        const payload = {
            id: this.id,
            type_id: this.typeId,
            name: this.name,
            email: this.email,
            active: this.active,
            address: this.address,
            phone: this.phone
        };

        // A senha só entra no payload quando foi informada nesta operação
        if (this.#password !== null) {
            payload.password = this.#password;
        }

        return payload;
    }
}
