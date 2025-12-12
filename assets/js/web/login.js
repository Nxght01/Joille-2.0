import {
    HttpUser
} from '../classes/HttpUser.js';

import {
    getBackendUrl,
    getBackendUrlApi,
    getFirstName,
    showToast
} from "./../_shared/functions.js";

// Create an HttpUser instance to handle API calls
const api = new HttpUser();

const formLogin = document.querySelector("#formLogin");
formLogin.addEventListener("submit", async (e) => {
    e.preventDefault();
    try {
        const response = await api.post('/login', new FormData(formLogin));
        console.log(response);
        if (response.type == "error" || response.type == "warning") {
            showToast(response.message, response.type);
            return;
        }
        showToast(`Olá, ${getFirstName(response.user.name)} como vai!`);
        setTimeout(() => {
            window.location.href = getBackendUrl("app");
        }, 3000);
    } catch (error) {
        console.error('Erro na requisição:', error);
        showToast('Erro ao fazer login', 'error');
    }
});