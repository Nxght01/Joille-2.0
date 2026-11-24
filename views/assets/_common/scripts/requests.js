import RequestService from '../classes/services/RequestService.js';
import ServiceService from '../classes/services/ServiceService.js';
import ServiceRequest from '../classes/entities/ServiceRequest.js';
import FormController from '../prototypes/FormController.js';
import {createToast} from '../prototypes/Toast.js';
import SessionStorage from './storage.js';
import {createAuthFailureHandler, showEmpty, showLoading, showListError} from './ui.js';
import {getBackendUrl} from './functions.js';

const admin = document.querySelector('[data-requests-area]').dataset.requestsArea === 'admin';
const session = new SessionStorage(admin ? 'admin' : 'user');
const api = new RequestService();
api.setAuthToken(session.getToken());
const toast = createToast();
const fail = createAuthFailureHandler({session, toast, loginUrl: getBackendUrl(admin ? 'admin/login' : 'login')});
const list = document.querySelector('#request-list');
let editing = null;
let busy = false;
const form = !admin ? new FormController(document.querySelector('#request-form')) : null;
const element = (tag, text, className) => {
    const el = document.createElement(tag);
    if (text !== undefined) el.textContent = text;
    if (className) el.className = className;
    return el;
};

function resetForm() {
    editing = null;
    form.form.reset();
    form.form.elements.services_id.disabled = false;
    document.querySelector('#request-form-title').textContent = 'Solicitar projeto';
    form.form.querySelector('[type=submit]').textContent = 'Enviar briefing';
    document.querySelector('#request-reset').hidden = true;
}

async function act(operation, button) {
    if (busy) return;
    busy = true;
    if (button) button.disabled = true;
    try {
        const result = await operation();
        toast.fromResponse(result);
        if (!result.ok) { fail(result); return; }
        if (form) resetForm();
        await load();
    } catch (error) {
        toast.error(error.message || 'Não foi possível concluir a operação.');
    } finally {
        busy = false;
        if (button) button.disabled = false;
    }
}

function render(request) {
    const card = element('article', undefined, 'request-card');
    card.append(element('h3', `#${request.id} · ${request.serviceName}`));
    card.append(element('p', request.statusLabel, 'request-status'));
    if (admin) card.append(element('p', `Responsável: ${request.customerName}`));
    card.append(element('p', `${request.quantity} unidade(s) da empresa · ${request.formattedTotal}`));
    card.append(element('p', `Valor preliminar do projeto: ${request.formattedPreliminaryValue}`));
    if (request.preliminaryValue !== null) card.append(element('p', 'Estimativa total sujeita à definição do escopo e à proposta final.'));
    card.append(element('p', request.description));
    if (request.notes) card.append(element('p', `Retorno sobre o projeto: ${request.notes}`));
    if (admin) {
        const controls = element('form', undefined, 'request-admin-form');
        const label = element('label', 'Etapa do projeto');
        const select = element('select');
        const allowed = {pending: ['pending','in_progress','cancelled'], in_progress: ['in_progress','completed','cancelled'], completed: ['completed'], cancelled: ['cancelled']};
        allowed[request.status].forEach(status => select.add(new Option(ServiceRequest.statuses[status], status)));
        select.value = request.status;
        label.append(select);
        const noteLabel = element('label', 'Retorno para a empresa');
        const notes = element('textarea'); notes.value = request.notes; notes.maxLength = 2000;
        noteLabel.append(notes);
        const priceLabel = element('label', 'Valor preliminar total (R$)');
        const price = element('input');
        price.type = 'number'; price.min = '0'; price.max = '9999999999.99'; price.step = '0.01';
        price.name = 'preliminary_value'; price.value = request.preliminaryValue ?? '';
        price.placeholder = 'Ainda não definido';
        const priceHelp = element('small', 'Valor total do projeto, sem multiplicar pelas unidades. Deixe vazio para remover a estimativa.');
        priceHelp.id = `estimate-help-${request.id}`; price.setAttribute('aria-describedby', priceHelp.id);
        priceLabel.append(price);
        const save = element('button', 'Atualizar projeto'); save.type = 'submit';
        controls.append(label, priceLabel, priceHelp, noteLabel, save);
        controls.addEventListener('submit', event => { event.preventDefault(); act(() => api.updateStatus(request.id, select.value, notes.value, price.value === '' ? null : price.value), save); });
        card.append(controls);
    } else if (request.canEdit) {
        const actions = element('div', undefined, 'request-actions');
        const edit = element('button', 'Editar'); edit.type = 'button';
        edit.addEventListener('click', () => {
            if (busy) return;
            editing = request;
            form.fill(request.toJSON());
            form.form.elements.services_id.disabled = true;
            document.querySelector('#request-form-title').textContent = `Editar projeto #${request.id}`;
            form.form.querySelector('[type=submit]').textContent = 'Salvar alterações';
            document.querySelector('#request-reset').hidden = false;
            form.form.scrollIntoView({behavior:'smooth'});
        });
        const cancel = element('button', 'Cancelar projeto'); cancel.type = 'button';
        cancel.addEventListener('click', () => {
            if (confirm(`Cancelar o projeto #${request.id}?`)) act(() => api.remove(request.id), cancel);
        });
        actions.append(edit, cancel); card.append(actions);
    }
    list.append(card);
}

async function load() {
    showLoading(list, 'Carregando projetos...');
    try {
        const result = await api.list();
        if (!result.ok) { fail(result); showListError(list, result.message); return; }
        list.replaceChildren();
        if (!result.entities.length) { showEmpty(list, admin ? 'Nenhum projeto recebido até o momento.' : 'Você ainda não enviou um projeto. Escolha uma solução para começar.'); return; }
        result.entities.forEach(render);
    } catch { showListError(list, 'Não foi possível carregar os projetos. Tente atualizar a lista.'); }
}
document.querySelector('#request-reload').addEventListener('click', load);
if (form) {
    document.querySelector('#request-reset').addEventListener('click', resetForm);
    const services = new ServiceService();
    try {
        const result = await services.list();
        if (!result.ok) throw new Error(result.message);
        const select = form.form.elements.services_id;
        select.replaceChildren(new Option('Selecione um serviço', ''));
        result.entities.forEach(service => select.add(new Option(`${service.name} · ${service.formattedValue}`, service.id)));
        const selected = new URLSearchParams(location.search).get('service');
        if (selected) select.value = selected;
    } catch (error) { toast.error(error.message); }
    form.form.addEventListener('submit', event => {
        event.preventDefault();
        try {
            const request = new ServiceRequest({...form.values(), services_id: editing?.serviceId ?? form.form.elements.services_id.value});
            act(() => editing ? api.update(editing.id, request) : api.create(request), form.form.querySelector('[type=submit]'));
        } catch (error) { toast.warning(error.message); }
    });
}
await load();
