import SessionStorage from "../../_common/scripts/storage.js";
import User from "../../_common/classes/entities/User.js";
import { getBackendUrl } from "../../_common/scripts/functions.js";

/**
 * Exibe o estado autenticado no menu público: com sessão ativa, os links de
 * Login e Cadastro dão lugar à saudação, ao atalho para a área restrita e ao
 * logout.
 */

const session = new SessionStorage("user");
const stored = session.getUser();
const authenticated = Boolean(session.getToken() && stored);

document.querySelectorAll("[data-auth-guest]").forEach((el) => {
    el.hidden = authenticated;
});

document.querySelectorAll("[data-auth-user]").forEach((el) => {
    el.hidden = !authenticated;
});

const greeting = document.querySelector("#nav-greeting");

if (authenticated && greeting) {
    // A sessão guarda um objeto simples; reidrata como entidade para usar firstName
    greeting.textContent = `Olá, ${User.fromApi(stored).firstName}`;
}

document.querySelector("#nav-logout")?.addEventListener("click", (event) => {
    event.preventDefault();
    session.clearSession();
    window.location.href = getBackendUrl();
});
