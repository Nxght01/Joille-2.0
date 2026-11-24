import UserService from "../../_common/classes/services/UserService.js";
import User from "../../_common/classes/entities/User.js";
import { createToast } from "../../_common/prototypes/Toast.js";
import AuthGuard from "../../_common/scripts/auth-guard.js";
import SessionStorage from "../../_common/scripts/storage.js";
import { getBackendUrl } from "../../_common/scripts/functions.js";
import { renderProfile } from "../../_common/scripts/profile-display.js";

const session = new SessionStorage("user");
const guard = new AuthGuard(getBackendUrl("login"), "user", User.TYPE_CLIENT);

if (!guard.protect()) {
    throw new Error("Usuário não autenticado");
}

const toast = createToast();
renderProfile(session.getUser());

// O evento storage sincroniza a foto e o nome em outras abas já abertas.
window.addEventListener("storage", event => {
    if (event.key === "joille_user") renderProfile(session.getUser());
});

const users = new UserService();
users.setAuthToken(session.getToken());

document.querySelector("#logout")?.addEventListener("click", (event) => {
    event.preventDefault();
    session.clearSession();
    window.location.href = getBackendUrl();
});

// Revalida o token a cada carregamento da área restrita
try {
    const result = await users.tokenValidate();

    if (!result.ok) {
        toast.fromResponse(result);

        setTimeout(() => {
            session.clearSession();
            window.location.href = getBackendUrl("login");
        }, 2500);
    }
} catch (error) {
    console.error("Erro na validação do token:", error);
}
