import UserService from "../../_common/classes/services/UserService.js";
import User from "../../_common/classes/entities/User.js";
import CrudController from "../../_common/prototypes/CrudController.js";
import FormController from "../../_common/prototypes/FormController.js";
import { createToast } from "../../_common/prototypes/Toast.js";
import SessionStorage from "../../_common/scripts/storage.js";
import { escapeHtml, getBackendUrl } from "../../_common/scripts/functions.js";
import { createAuthFailureHandler } from "../../_common/scripts/ui.js";

const session = new SessionStorage("admin");

const users = new UserService();
users.setAuthToken(session.getToken());

const toast = createToast();

const searchInput = document.querySelector("#searchInput");

const crud = new CrudController({
    service: users,
    form: new FormController(document.querySelector("#editForm"), {
        rules: {
            name: { required: true, label: "O nome" },
            email: { required: true, email: true, label: "O e-mail" },
            password: { minLength: 8, label: "A senha" }
        }
    }),
    toast,
    container: document.querySelector(".users-container"),
    modal: document.querySelector(".modal"),
    modalTitle: document.querySelector("#modalTitle"),
    idAttribute: "user-id",
    itemClass: "user-item",
    // A coluna Perfil é o que distingue um administrador de um cliente na
    // listagem: sem ela não há como saber quem tem acesso ao painel.
    template: (user) => `
        <span data-label="ID">${user.id}</span>
        <span data-label="Nome">${escapeHtml(user.name)}</span>
        <span data-label="Email">${escapeHtml(user.email)}</span>
        <span data-label="Perfil">
            <span class="badge ${user.isAdmin ? "badge-admin" : "badge-client"}">
                ${user.isAdmin ? "Administrador" : "Cliente"}
            </span>
        </span>
        <div class="user-actions">
            <button class="btn btn-edit" type="button">Editar</button>
            <button class="btn btn-delete" type="button">Excluir</button>
        </div>
    `,
    labels: {
        loading: "Carregando usuários...",
        empty: "Nenhum usuário encontrado.",
        createTitle: "Novo Usuário",
        editTitle: "Edição de Usuário",
        confirmDelete: "Tem certeza que deseja excluir este usuário?"
    },
    toEntity: (values) => {
        // A senha só é obrigatória no cadastro; na edição, em branco = manter
        if (!values.id && !values.password) {
            throw new RangeError("A senha é obrigatória para cadastrar um novo usuário");
        }

        return new User(values);
    },
    onAuthFailure: createAuthFailureHandler({ session, toast, loginUrl: getBackendUrl("admin/login") })
});

crud.bind({
    newButton: document.querySelector("#btnNew"),
    cancelButton: document.querySelector(".btn-cancel")
});

await crud.load();

searchInput.addEventListener("keyup", () => {
    const term = searchInput.value.trim().toLowerCase();

    crud.setFilter(
        (user) =>
            term === "" ||
            user.name.toLowerCase().includes(term) ||
            user.email.toLowerCase().includes(term)
    );
});
