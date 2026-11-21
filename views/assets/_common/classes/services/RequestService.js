import ApiService from './ApiService.js';
import ServiceRequest from '../entities/ServiceRequest.js';
export default class RequestService extends ApiService {
    constructor() { super('/requests', ServiceRequest); }
    async updateStatus(id, status, admin_notes) {
        return this.toResult(await this.put(`${this.resource}/${id}`, {status, admin_notes}));
    }
}
