import HttpClientBase from './HttpClientBase.js';

export class HttpUser extends HttpClientBase {
    constructor() {
        super(`http://localhost/joille/api/users/`);

        
    }

    async createUser(userData) {
        return this.post('/', userData);
    }

    }

