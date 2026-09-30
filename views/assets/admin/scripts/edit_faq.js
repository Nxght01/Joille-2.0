import FaqService from "../../_common/classes/services/FaqService.js";
import FaqTypeService from "../../_common/classes/services/FaqTypeService.js";
import Faq from "../../_common/classes/entities/Faq.js";
import CrudController from "../../_common/prototypes/CrudController.js";
import FormController from "../../_common/prototypes/FormController.js";
import { createToast } from "../../_common/prototypes/Toast.js";
import SessionStorage from "../../_common/scripts/storage.js";
import { escapeHtml, getBackendUrl, showDataSelect } from "../../_common/scripts/functions.js";
import { createAuthFailureHandler, serviceUnavailableMessage } from "../../_common/scripts/ui.js";

const session = new SessionStorage("admin");
const token = session.getToken();

const faqs = new FaqService();
const faqTypes = new FaqTypeService();
[faqs, faqTypes].forEach((client) => client.setAuthToken(token));

const toast = createToast();

const searchInput = document.querySelector("#searchInput");
const searchTypes = document.querySelector("#searchTypes");

// Guardado para traduzir types_id em descrição na listagem
let types = [];

function typeLabel(typesId) {
    return types.find((type) => type.id === typesId)?.description ?? "-";
}

const crud = new CrudController({
    service: faqs,
    form: new FormController(document.querySelector("#editForm"), {
        rules: {
            question: { required: true, label: "A pergunta" },
            answer: { required: true, label: "A resposta" },
            types_id: { required: true, label: "O tipo" }
        }
    }),
    toast,
    container: document.querySelector(".faqs-container"),
    modal: document.querySelector(".modal"),
    modalTitle: document.querySelector("#modalTitle"),
    idAttribute: "faq-id",
    itemClass: "service-item",
    template: (faq) => `
        <span data-label="ID">${faq.id}</span>
        <span data-label="Pergunta">${escapeHtml(faq.resume())}${faq.isActive ? "" : " (inativa)"}</span>
        <span data-label="Tipo">${escapeHtml(typeLabel(faq.typesId))}</span>
        <div class="service-actions">
            <button class="btn btn-edit" type="button">Editar</button>
            <button class="btn btn-delete" type="button">Excluir</button>
        </div>
    `,
    labels: {
        loading: "Carregando perguntas...",
        empty: "Nenhuma pergunta cadastrada.",
        createTitle: "Nova Pergunta",
        editTitle: "Edição de Pergunta",
        confirmDelete: "Tem certeza que deseja inativar esta pergunta?"
    },
    loader: () => faqs.listAdmin(),
    toEntity: (values) => new Faq(values),
    onAuthFailure: createAuthFailureHandler({ session, toast, loginUrl: getBackendUrl("admin/login") })
});

crud.bind({
    newButton: document.querySelector("#btnNew"),
    cancelButton: document.querySelector(".btn-cancel")
});

try {
    const result = await faqTypes.list();

    if (result.ok) {
        types = result.entities;
        showDataSelect(types, document.querySelector("#types_id"));
        showDataSelect(types, searchTypes);
    } else {
        toast.fromResponse(result);
    }
} catch (error) {
    console.error("Erro na requisição:", error);
    toast.error(serviceUnavailableMessage());
}

await crud.load();

function applyFilters() {
    const term = searchInput.value.trim().toLowerCase();
    const type = searchTypes.value;

    crud.setFilter(
        (faq) =>
            (type === "all" || String(faq.typesId) === String(type)) &&
            (term === "" || faq.question.toLowerCase().includes(term))
    );
}

searchInput.addEventListener("keyup", applyFilters);
searchTypes.addEventListener("change", applyFilters);
