import ContactService from "../../_common/classes/services/ContactService.js";
import { createToast } from "../../_common/prototypes/Toast.js";
import SessionStorage from "../../_common/scripts/storage.js";
import { escapeHtml, getBackendUrl } from "../../_common/scripts/functions.js";
import {
    createAuthFailureHandler,
    serviceUnavailableMessage,
    showEmpty,
    showListError,
    showLoading
} from "../../_common/scripts/ui.js";

/**
 * Mensagens recebidas pelo formulário de contato da área pública.
 *
 * A tela não usa CrudController: aqui não há formulário de criação nem edição
 * de campos — só leitura, baixa ("respondida") e exclusão. Manter o fluxo
 * explícito é mais simples do que forçar o protótipo genérico.
 */

const session = new SessionStorage("admin");

const contacts = new ContactService();
contacts.setAuthToken(session.getToken());

const toast = createToast();
const handleAuthFailure = createAuthFailureHandler({
    session,
    toast,
    loginUrl: getBackendUrl("admin/login")
});

const container = document.querySelector("#contactsList");
const searchInput = document.querySelector("#searchInput");
const searchStatus = document.querySelector("#searchStatus");
const modal = document.querySelector("#viewModal");

const detail = {
    from: document.querySelector("#detailFrom"),
    email: document.querySelector("#detailEmail"),
    date: document.querySelector("#detailDate"),
    subject: document.querySelector("#detailSubject"),
    message: document.querySelector("#detailMessage")
};

const btnAnswer = document.querySelector("#btnAnswer");

let messages = [];
let selected = null;

// --- listagem ----------------------------------------------------------------

function visibleMessages() {
    const term = searchInput.value.trim().toLowerCase();
    const status = searchStatus.value;

    return messages.filter((message) => {
        const matchesStatus =
            status === "all" ||
            (status === "answered" && message.isAnswered) ||
            (status === "pending" && !message.isAnswered);

        const haystack = `${message.name} ${message.email} ${message.subject ?? ""}`.toLowerCase();

        return matchesStatus && (term === "" || haystack.includes(term));
    });
}

function render() {
    const visible = visibleMessages();

    if (visible.length === 0) {
        showEmpty(container, "Nenhuma mensagem encontrada.");
        return;
    }

    container.innerHTML = "";

    visible.forEach((message) => {
        const article = document.createElement("article");
        article.classList.add("service-item");
        article.setAttribute("contact-id", message.id);

        if (!message.isAnswered) {
            article.classList.add("is-pending");
        }

        // Nome, e-mail e assunto foram escritos por um visitante anônimo:
        // precisam ser escapados antes de entrar no innerHTML
        article.innerHTML = `
            <span data-label="Recebida">${escapeHtml(message.formattedDate)}</span>
            <span data-label="De">${escapeHtml(message.name)}<br><small>${escapeHtml(message.email)}</small></span>
            <span data-label="Assunto">${escapeHtml(message.subject ?? "-")}</span>
            <span data-label="Situação">${message.isAnswered ? "Respondida" : "Pendente"}</span>
            <div class="service-actions">
                <button class="btn btn-view" type="button">Ler</button>
                <button class="btn btn-delete" type="button">Excluir</button>
            </div>
        `;

        container.appendChild(article);
    });
}

async function load() {
    showLoading(container, "Carregando mensagens...");

    try {
        const result = await contacts.list();

        if (!result.ok) {
            handleAuthFailure(result);
            showListError(container, result.message);
            return;
        }

        messages = result.entities;
        render();
    } catch (error) {
        console.error("Erro na requisição:", error);
        showListError(container, serviceUnavailableMessage());
    }
}

// --- leitura -----------------------------------------------------------------

function openModal(message) {
    selected = message;

    detail.from.textContent = message.name;
    detail.email.textContent = message.email;
    detail.email.href = `mailto:${message.email}?subject=${encodeURIComponent(`Re: ${message.subject ?? "Contato"}`)}`;
    detail.date.textContent = message.formattedDate;
    detail.subject.textContent = message.subject ?? "-";
    // textContent, e não innerHTML: a mensagem é texto vindo de fora
    detail.message.textContent = message.message;

    btnAnswer.hidden = message.isAnswered;
    modal.style.display = "flex";
}

function closeModal() {
    selected = null;
    modal.style.display = "none";
}

async function setAnswered() {
    if (!selected) {
        return;
    }

    btnAnswer.disabled = true;

    try {
        const result = await contacts.setAnswered(selected.id);

        toast.fromResponse(result);

        if (!result.ok) {
            handleAuthFailure(result);
            return;
        }

        closeModal();
        await load();
    } catch (error) {
        console.error("Erro ao atualizar a mensagem:", error);
        toast.error(serviceUnavailableMessage());
    } finally {
        btnAnswer.disabled = false;
    }
}

async function remove(id) {
    if (!confirm("Tem certeza que deseja excluir esta mensagem? A ação não pode ser desfeita.")) {
        return;
    }

    try {
        const result = await contacts.remove(id);

        toast.fromResponse(result);

        if (!result.ok) {
            handleAuthFailure(result);
            return;
        }

        await load();
    } catch (error) {
        console.error("Erro ao excluir a mensagem:", error);
        toast.error(serviceUnavailableMessage());
    }
}

// --- eventos -----------------------------------------------------------------

container.addEventListener("click", (event) => {
    const article = event.target.closest("article");

    if (!article) {
        return;
    }

    const id = Number(article.getAttribute("contact-id"));

    if (event.target.classList.contains("btn-view")) {
        const message = messages.find((item) => item.id === id);

        if (message) {
            openModal(message);
        }
    }

    if (event.target.classList.contains("btn-delete")) {
        remove(id);
    }
});

btnAnswer.addEventListener("click", setAnswered);
document.querySelector("#viewModal .btn-cancel").addEventListener("click", closeModal);

searchInput.addEventListener("keyup", render);
searchStatus.addEventListener("change", render);

await load();
