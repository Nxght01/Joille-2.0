import UserService from "../../_common/classes/services/UserService.js";
import User from "../../_common/classes/entities/User.js";
import { createToast } from "../../_common/prototypes/Toast.js";
import AuthGuard from "../../_common/scripts/auth-guard.js";
import SessionStorage from "../../_common/scripts/storage.js";
import { getBackendUrl } from "../../_common/scripts/functions.js";

const session = new SessionStorage("user");
const guard = new AuthGuard(getBackendUrl("login"), "user", User.TYPE_CLIENT);

if (!guard.protect()) {
    throw new Error("Usuário não autenticado");
}

const toast = createToast();
const stored = session.getUser();
const user = User.fromApi(stored);

const users = new UserService();
users.setAuthToken(session.getToken());

const imageUser = document.querySelector("#imageUser");
const defaultPhoto = imageUser?.src;
imageUser?.addEventListener("error", () => {
    if (imageUser.src !== defaultPhoto) imageUser.src = defaultPhoto;
});
if (imageUser && user.photo) {
    imageUser.src = getBackendUrl(user.photo);
}

const spanUser = document.querySelector("#span-user");
if (spanUser) {
    spanUser.textContent = `Olá, ${user.firstName}!`;
}

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
