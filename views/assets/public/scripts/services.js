import ServiceService from "../../_common/classes/services/ServiceService.js";
import CategoryService from "../../_common/classes/services/CategoryService.js";
import ServiceImageService from "../../_common/classes/services/ServiceImageService.js";
import ImageGallery from "../../_common/prototypes/ImageGallery.js";
import { createToast } from "../../_common/prototypes/Toast.js";
import { escapeHtml, showDataSelect } from "../../_common/scripts/functions.js";
import { serviceUnavailableMessage, showEmpty, showListError, showLoading } from "../../_common/scripts/ui.js";

const services = new ServiceService();
const categories = new CategoryService();
const serviceImages = new ServiceImageService();
const toast = createToast();

const servicesList = document.querySelector(".services-list");
const filterCategories = document.querySelector("#filterCategories");
const detailModal = document.querySelector("#serviceModal");
const detailTitle = document.querySelector("#serviceModalTitle");
const detailDescription = document.querySelector("#serviceModalDescription");
const detailValue = document.querySelector("#serviceModalValue");

/**
 * A mesma galeria prototípica do painel, aqui em modo somente leitura: o
 * visitante vê todas as imagens do serviço, sem os botões de capa e exclusão.
 */
const gallery = new ImageGallery({
    service: serviceImages,
    toast,
    container: document.querySelector("#serviceModalGallery"),
    readOnly: true,
    labels: { empty: "Este serviço ainda não tem fotos." }
});

let loaded = [];

function renderServices(entities) {
    loaded = entities;

    if (entities.length === 0) {
        showEmpty(servicesList, "Nenhum serviço disponível no momento.");
        return;
    }

    servicesList.innerHTML = ``;

    entities.forEach((service) => {
        const card = document.createElement("article");
        card.classList.add("service-card");
        card.setAttribute("service-id", service.id);
        const name = escapeHtml(service.name);

        card.innerHTML = `
            <div class="service-cover">
                ${service.coverImageUrl
                    ? `<img src="${encodeURI(service.coverImageUrl)}" alt="Foto do serviço ${name}" loading="lazy">`
                    : `<span class="service-cover-empty">sem foto</span>`}
            </div>
            <h2>${name}</h2>
            <p>${escapeHtml(service.description)}</p>
            <span class="service-category">${escapeHtml(service.categoryName ?? "")}</span>
            <strong class="service-value">${service.formattedValue}</strong>
            <button type="button" class="btn btn-gallery" ${service.hasImages ? "" : "disabled"}>
                ${service.hasImages ? `Ver fotos (${service.imagesCount})` : "Sem fotos"}
            </button>
        `;
        servicesList.appendChild(card);
    });
}

// --- galeria de um serviço ---------------------------------------------------

async function openGallery(service) {
    detailTitle.textContent = service.name;
    detailDescription.textContent = service.description;
    detailValue.textContent = service.formattedValue;
    detailModal.style.display = "flex";

    await gallery.setServiceId(service.id);
}

function closeGallery() {
    detailModal.style.display = "none";
    gallery.setServiceId(null);
}

servicesList.addEventListener("click", (event) => {
    if (!event.target.classList.contains("btn-gallery")) {
        return;
    }

    const card = event.target.closest("article");
    const service = loaded.find((item) => item.id === Number(card?.getAttribute("service-id")));

    if (service) {
        openGallery(service);
    }
});

document.querySelector("#serviceModal .btn-cancel")?.addEventListener("click", closeGallery);

async function loadServices(categoryId = "all") {
    showLoading(servicesList, "Carregando serviços...");

    try {
        const result =
            categoryId === "all"
                ? await services.list()
                : await services.listByCategory(categoryId);

        if (!result.ok) {
            showListError(servicesList, result.message);
            return;
        }

        renderServices(result.entities);
    } catch (error) {
        console.error("Erro na requisição:", error);
        showListError(servicesList, serviceUnavailableMessage());
    }
}

try {
    const result = await categories.list();

    if (result.ok) {
        showDataSelect(result.entities, filterCategories);
    } else {
        toast.fromResponse(result);
    }
} catch (error) {
    console.error("Erro na requisição:", error);
}

await loadServices();

filterCategories?.addEventListener("change", () => loadServices(filterCategories.value));
