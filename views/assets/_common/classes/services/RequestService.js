import ApiService from './ApiService.js';
import ServiceRequest from '../entities/ServiceRequest.js';
export default class RequestService extends ApiService {
    constructor() { super('/requests', ServiceRequest); }
    async updateStatus(id, status, admin_notes, preliminary_value = undefined) {
        const payload = {status, admin_notes};
        if (preliminary_value !== undefined) payload.preliminary_value = preliminary_value;
        return this.toResult(await this.put(`${this.resource}/${id}`, payload));
    }
}
