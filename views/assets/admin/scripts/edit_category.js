import CategoryService from "../../_common/classes/services/CategoryService.js";
import Category from "../../_common/classes/entities/Category.js";
import CrudController from "../../_common/prototypes/CrudController.js";
import FormController from "../../_common/prototypes/FormController.js";
import { createToast } from "../../_common/prototypes/Toast.js";
import SessionStorage from "../../_common/scripts/storage.js";
import { escapeHtml, getBackendUrl } from "../../_common/scripts/functions.js";
import { createAuthFailureHandler } from "../../_common/scripts/ui.js";

const session = new SessionStorage("admin");

const categories = new CategoryService();
categories.setAuthToken(session.getToken());

const toast = createToast();

const searchInput = document.querySelector("#searchInput");

const crud = new CrudController({
    service: categories,
    form: new FormController(document.querySelector("#editForm"), {
        rules: {
            name: { required: true, label: "O nome" }
        }
    }),
    toast,
    container: document.querySelector(".categories-container"),
    modal: document.querySelector(".modal"),
    modalTitle: document.querySelector("#modalTitle"),
    idAttribute: "category-id",
    itemClass: "category-item",
    template: (category) => `
        <span data-label="ID">${category.id}</span>
        <span data-label="Nome">${escapeHtml(category.name)}</span>
        <div class="category-actions">
            <button class="btn btn-edit" type="button">Editar</button>
            <button class="btn btn-delete" type="button">Excluir</button>
        </div>
    `,
    labels: {
        loading: "Carregando categorias...",
        empty: "Nenhuma categoria cadastrada.",
        createTitle: "Nova Categoria",
        editTitle: "Edição de Categoria",
        confirmDelete: "Tem certeza que deseja excluir esta categoria?"
    },
    loader: () => categories.listAdmin(),
    toEntity: (values) => new Category(values),
    onAuthFailure: createAuthFailureHandler({ session, toast, loginUrl: getBackendUrl("admin/login") })
});

crud.bind({
    newButton: document.querySelector("#btnNew"),
    cancelButton: document.querySelector(".btn-cancel")
});

await crud.load();

searchInput.addEventListener("keyup", () => {
    const term = searchInput.value.trim().toLowerCase();
    crud.setFilter((category) => term === "" || category.name.toLowerCase().includes(term));
});
