import Entity from './Entity.js';

export default class ServiceRequest extends Entity {
    static statuses = {pending: 'Em análise', in_progress: 'Em execução', completed: 'Entregue', cancelled: 'Cancelada'};
    #serviceId; #description; #quantity; #status; #unitPrice; #notes;
    constructor(data = {}) {
        super({id: data.id ?? null});
        this.serviceId = data.services_id;
        this.description = data.description;
        this.quantity = data.quantity ?? 1;
        this.status = data.status ?? 'pending';
        this.#unitPrice = Number(data.unit_price ?? 0);
        this.preliminaryValue = data.preliminary_value == null ? null : Number(data.preliminary_value);
        this.notes = data.admin_notes ?? '';
        this.serviceName = data.service_name ?? '';
        this.customerName = data.customer_name ?? '';
        this.createdAt = data.created_at ?? '';
    }
    get serviceId() { return this.#serviceId; }
    set serviceId(value) {
        if (!Number.isInteger(Number(value)) || Number(value) < 1) throw new RangeError('Selecione um serviço.');
        this.#serviceId = Number(value);
    }
    get description() { return this.#description; }
    set description(value) {
        const text = String(value ?? '').trim();
        if (text.length < 10 || text.length > 2000) throw new RangeError('Descreva o projeto em 10 a 2000 caracteres.');
        this.#description = text;
    }
    get quantity() { return this.#quantity; }
    set quantity(value) {
        if (!Number.isInteger(Number(value)) || Number(value) < 1 || Number(value) > 100) throw new RangeError('Use uma quantidade entre 1 e 100.');
        this.#quantity = Number(value);
    }
    get status() { return this.#status; }
    set status(value) {
        if (!Object.hasOwn(ServiceRequest.statuses, value)) throw new RangeError('Situação inválida.');
        this.#status = value;
    }
    get notes() { return this.#notes; }
    set notes(value) {
        if (String(value).length > 2000) throw new RangeError('Use até 2000 caracteres na observação.');
        this.#notes = String(value);
    }
    get statusLabel() { return ServiceRequest.statuses[this.status]; }
    get canEdit() { return this.status === 'pending'; }
    get total() { return this.#unitPrice * this.quantity; }
    get formattedTotal() { return this.#unitPrice === 0 ? 'Sob orçamento' : this.total.toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'}); }
    get formattedPreliminaryValue() { return this.preliminaryValue === null ? 'Ainda não definido' : this.preliminaryValue.toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'}); }
    toJSON() { return {services_id: this.serviceId, description: this.description, quantity: this.quantity}; }
}
