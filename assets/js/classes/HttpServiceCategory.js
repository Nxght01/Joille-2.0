import HttpClientBase from './HttpClientBase.js';

export class HttpServiceCategory extends HttpClientBase {
    constructor() {
        super(`http://localhost/joille/api/categories`);
    }

    async getAllCategories() {
        return this.get('/list');
    }

}