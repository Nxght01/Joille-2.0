import ServiceService from "../../_common/classes/services/ServiceService.js";
import CategoryService from "../../_common/classes/services/CategoryService.js";
import ServiceImageService from "../../_common/classes/services/ServiceImageService.js";
import Service from "../../_common/classes/entities/Service.js";
import CrudController from "../../_common/prototypes/CrudController.js";
import FormController from "../../_common/prototypes/FormController.js";
import ImageGallery from "../../_common/prototypes/ImageGallery.js";
import { createToast } from "../../_common/prototypes/Toast.js";
import SessionStorage from "../../_common/scripts/storage.js";
import { escapeHtml, getBackendUrl, showDataSelect } from "../../_common/scripts/functions.js";
import { createAuthFailureHandler, serviceUnavailableMessage } from "../../_common/scripts/ui.js";

const session = new SessionStorage("admin");
const token = session.getToken();

const services = new ServiceService();
const categories = new CategoryService();
const serviceImages = new ServiceImageService();
[services, categories, serviceImages].forEach((client) => client.setAuthToken(token));

const toast = createToast();
const onAuthFailure = createAuthFailureHandler({
    session,
    toast,
    loginUrl: getBackendUrl("admin/login")
});

const searchInput = document.querySelector("#searchInput");
const searchCategories = document.querySelector("#searchCategories");
const galleryHint = document.querySelector("#galleryHint");

// Galeria das N imagens do serviço em edição (Etapa 07)
const gallery = new ImageGallery({
    service: serviceImages,
    toast,
    container: document.querySelector("#galleryGrid"),
    input: document.querySelector("#galleryInput"),
    uploadButton: document.querySelector("#galleryUpload"),
    preview: document.querySelector("#galleryPreview"),
    counter: document.querySelector("#galleryCount"),
    onAuthFailure,
    onChange: () => crud.load()
}).bind();

const crud = new CrudController({
    service: services,
    form: new FormController(document.querySelector("#editForm"), {
        rules: {
            name: { required: true, label: "O nome" },
            description: { required: true, label: "A descrição" },
            value: { required: true, label: "O valor" },
            categories_id: { required: true, label: "A categoria" }
        }
    }),
    toast,
    container: document.querySelector(".services-container"),
    modal: document.querySelector(".modal"),
    modalTitle: document.querySelector("#modalTitle"),
    idAttribute: "service-id",
    itemClass: "service-item",
    template: (service) => `
        <span data-label="ID">${service.id}</span>
        <span data-label="Imagem">${
            service.coverImageUrl
                ? `<img class="list-thumb" src="${encodeURI(service.coverImageUrl)}"
                        alt="Capa de ${escapeHtml(service.name)}" loading="lazy">`
                : `<span class="list-thumb list-thumb-empty">sem foto</span>`
        }</span>
        <span data-label="Nome">${escapeHtml(service.name)}${service.isActive ? "" : " (inativo)"}</span>
        <span data-label="Categoria">${escapeHtml(service.categoryName ?? "-")}</span>
        <span data-label="Imagens">${service.imagesCount}</span>
        <div class="service-actions">
            <button class="btn btn-edit" type="button">Editar</button>
            <button class="btn btn-delete" type="button">Excluir</button>
        </div>
    `,
    labels: {
        loading: "Carregando serviços...",
        empty: "Nenhum serviço cadastrado.",
        createTitle: "Novo Serviço",
        editTitle: "Edição de Serviço",
        confirmDelete: "Tem certeza que deseja inativar este serviço?"
    },
    // Listagem administrativa: mostra também os inativos
    loader: () => services.listAdmin(),
    // Converte os valores do formulário na entidade (validação nos setters)
    toEntity: (values) => new Service(values),
    onAuthFailure,
    // O upload exige um serviço já gravado, então a galeria só é liberada na edição
    onOpen: (service) => {
        galleryHint.hidden = service !== null;
        gallery.setServiceId(service?.id ?? null);
    },
    onClose: () => gallery.setServiceId(null)
});

crud.bind({
    newButton: document.querySelector("#btnNew"),
    cancelButton: document.querySelector(".btn-cancel")
});

// Preenche os selects de categoria
try {
    const result = await categories.listAdmin();

    if (result.ok) {
        showDataSelect(result.entities, document.querySelector("#categories_id"));
        showDataSelect(result.entities, searchCategories);
    } else {
        toast.fromResponse(result);
    }
} catch (error) {
    console.error("Erro na requisição:", error);
    toast.error(serviceUnavailableMessage());
}

await crud.load();

// Filtros aplicados sobre a coleção de entidades já carregada
function applyFilters() {
    const term = searchInput.value.trim().toLowerCase();
    const category = searchCategories.value;

    crud.setFilter(
        (service) =>
            (category === "all" || String(service.categoriesId) === String(category)) &&
            (term === "" || service.name.toLowerCase().includes(term))
    );
}

searchInput.addEventListener("keyup", applyFilters);
searchCategories.addEventListener("change", applyFilters);
