import ServiceService from "../../_common/classes/services/ServiceService.js";
import SessionStorage from "../../_common/scripts/storage.js";
import { serviceUnavailableMessage, showEmpty, showListError, showLoading } from "../../_common/scripts/ui.js";

const session = new SessionStorage("user");

const services = new ServiceService();
services.setAuthToken(session.getToken());

const servicesContainer = document.querySelector(".services-container");

function renderServices(entities) {
    if (entities.length === 0) {
        showEmpty(servicesContainer, "Nenhum serviço disponível no momento.");
        return;
    }

    servicesContainer.innerHTML = ``;

    entities.forEach((service) => {
        const article = document.createElement("article");
        article.classList.add("service-item");
        article.setAttribute("service-id", service.id);
        article.innerHTML = `
            <span data-label="Nome">${service.name}</span>
            <span data-label="Descrição">${service.description}</span>
            <span data-label="Categoria">${service.categoryName ?? "-"}</span>
            <span data-label="Valor">${service.formattedValue}</span>
        `;
        servicesContainer.appendChild(article);
    });
}

showLoading(servicesContainer, "Carregando serviços...");

try {
    const result = await services.list();

    if (!result.ok) {
        showListError(servicesContainer, result.message);
    } else {
        renderServices(result.entities);
    }
} catch (error) {
    console.error("Erro na requisição:", error);
    showListError(servicesContainer, serviceUnavailableMessage());
}
