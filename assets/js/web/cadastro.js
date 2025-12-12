import {
    HttpUser
} from '../classes/HttpUser.js';

import {
    getBackendUrl,
    getBackendUrlApi,
    getFirstName,
    showToast
} from "./../_shared/functions.js";

const formRegister = document.querySelector("#formRegister");
const api = new HttpUser();

formRegister.addEventListener("submit", async (e) => {
    e.preventDefault();
    try {
        const users = await api.createUser(new FormData(formRegister));
        showToast(users.message);
    } catch (error) {
        console.error('Erro na requisição:', error);
    }
});