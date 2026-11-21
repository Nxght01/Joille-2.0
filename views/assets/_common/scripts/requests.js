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
    document.querySelector('#request-form-title').textContent = 'Nova solicitação';
    form.form.querySelector('[type=submit]').textContent = 'Enviar solicitação';
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
    if (admin) card.append(element('p', `Cliente: ${request.customerName}`));
    card.append(element('p', `${request.quantity} peça(s) · Estimativa: ${request.formattedTotal}`));
    card.append(element('p', request.description));
    if (request.notes) card.append(element('p', `Retorno da equipe: ${request.notes}`));
    if (admin) {
        const controls = element('form', undefined, 'request-admin-form');
        const label = element('label', 'Situação do atendimento');
        const select = element('select');
        const allowed = {pending: ['pending','in_progress','cancelled'], in_progress: ['in_progress','completed','cancelled'], completed: ['completed'], cancelled: ['cancelled']};
        allowed[request.status].forEach(status => select.add(new Option(ServiceRequest.statuses[status], status)));
        select.value = request.status;
        label.append(select);
        const noteLabel = element('label', 'Observação para o cliente');
        const notes = element('textarea'); notes.value = request.notes; notes.maxLength = 2000;
        noteLabel.append(notes);
        const save = element('button', 'Salvar atendimento'); save.type = 'submit';
        controls.append(label, noteLabel, save);
        controls.addEventListener('submit', event => { event.preventDefault(); act(() => api.updateStatus(request.id, select.value, notes.value), save); });
        card.append(controls);
    } else if (request.canEdit) {
        const actions = element('div', undefined, 'request-actions');
        const edit = element('button', 'Editar'); edit.type = 'button';
        edit.addEventListener('click', () => {
            if (busy) return;
            editing = request;
            form.fill(request.toJSON());
            form.form.elements.services_id.disabled = true;
            document.querySelector('#request-form-title').textContent = `Editar solicitação #${request.id}`;
            form.form.querySelector('[type=submit]').textContent = 'Salvar alterações';
            document.querySelector('#request-reset').hidden = false;
            form.form.scrollIntoView({behavior:'smooth'});
        });
        const cancel = element('button', 'Cancelar solicitação'); cancel.type = 'button';
        cancel.addEventListener('click', () => {
            if (confirm(`Cancelar a solicitação #${request.id}?`)) act(() => api.remove(request.id), cancel);
        });
        actions.append(edit, cancel); card.append(actions);
    }
    list.append(card);
}

async function load() {
    showLoading(list, 'Carregando solicitações...');
    try {
        const result = await api.list();
        if (!result.ok) { fail(result); showListError(list, result.message); return; }
        list.replaceChildren();
        if (!result.entities.length) { showEmpty(list, 'Nenhuma solicitação registrada.'); return; }
        result.entities.forEach(render);
    } catch { showListError(list, 'Não foi possível carregar as solicitações. Tente atualizar a lista.'); }
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
