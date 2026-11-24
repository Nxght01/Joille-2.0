import ServiceService from "../../_common/classes/services/ServiceService.js";
import SessionStorage from "../../_common/scripts/storage.js";
import {getBackendUrl} from '../../_common/scripts/functions.js';
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
        for (const [label, value] of [['Nome', service.name], ['Descrição', service.description], ['Categoria', service.categoryName ?? '-'], ['Valor', service.formattedValue]]) {
            const span = document.createElement(label === 'Nome' ? 'h2' : 'span');
            span.dataset.label = label;
            span.textContent = value;
            if (label === 'Nome' && service.coverImageUrl) {
                const cover = document.createElement('img');
                cover.className = 'service-thumbnail';
                cover.src = service.coverImageUrl;
                cover.alt = '';
                cover.loading = 'lazy';
                cover.width = 192;
                cover.height = 128;
                span.prepend(cover);
            }
            article.append(span);
        }
        const request = document.createElement('a');
        request.href = getBackendUrl(`app/solicitacoes?service=${service.id}`);
        request.textContent = 'Solicitar projeto';
        request.setAttribute('aria-label', `Solicitar projeto: ${service.name}`);
        article.append(request);
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
