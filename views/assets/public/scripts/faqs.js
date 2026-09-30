import FaqService from "../../_common/classes/services/FaqService.js";
import Accordion from "../../_common/prototypes/Accordion.js";
import { createToast } from "../../_common/prototypes/Toast.js";
import { escapeHtml } from "../../_common/scripts/functions.js";
import { serviceUnavailableMessage, showEmpty, showListError, showLoading } from "../../_common/scripts/ui.js";

/**
 * Perguntas frequentes da área pública.
 *
 * A lista vem da API (`GET /faqs`, só as publicadas) e é exibida em uma sanfona
 * acessível: cada pergunta é um <button> com `aria-expanded`, e o ícone de "+"
 * gira 45° virando um "×" quando a resposta abre.
 *
 * A página não é mais renderizada pelo PHP: o conteúdo é o mesmo da API que o
 * app mobile consome, então publicar uma pergunta no painel reflete nos dois
 * lugares sem duplicar consulta.
 */

const faqs = new FaqService();
const toast = createToast();

const list = document.querySelector("#list-faqs");
const state = document.querySelector("#faqs-state");

const accordion = new Accordion({ container: list }).bind();

/** Ícone de "+" que o CSS gira em 45° para virar "×" quando a resposta abre. */
const PLUS_ICON = `
    <svg class="faq-icon" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"
         fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <line x1="12" y1="5" x2="12" y2="19"></line>
        <line x1="5" y1="12" x2="19" y2="12"></line>
    </svg>
`;

function render(entities) {
    list.innerHTML = "";

    if (entities.length === 0) {
        showEmpty(state, "Nenhuma pergunta publicada no momento.");
        return;
    }

    state.innerHTML = "";

    entities.forEach((faq) => {
        const item = document.createElement("faq-item");

        item.innerHTML = `
            <dt>
                <button type="button" class="faq-question" aria-expanded="false">
                    <span>${escapeHtml(faq.question)}</span>
                    ${PLUS_ICON}
                </button>
            </dt>
            <dd class="faq-answer">
                <p>${escapeHtml(faq.answer)}</p>
            </dd>
        `;

        list.appendChild(item);
    });
}

async function load() {
    showLoading(state, "Carregando perguntas...");

    try {
        const result = await faqs.list();

        if (!result.ok) {
            showListError(state, result.message);
            toast.fromResponse(result);
            return;
        }

        render(result.entities);
    } catch (error) {
        console.error("Erro ao carregar as perguntas:", error);
        showListError(state, serviceUnavailableMessage());
    }
}

await load();
