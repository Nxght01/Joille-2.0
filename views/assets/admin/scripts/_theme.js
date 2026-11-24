import AuthGuard from "../../_common/scripts/auth-guard.js";
import SessionStorage from "../../_common/scripts/storage.js";
import User from "../../_common/classes/entities/User.js";
import UserService from '../../_common/classes/services/UserService.js';
import {createToast} from '../../_common/prototypes/Toast.js';
import {createAuthFailureHandler} from '../../_common/scripts/ui.js';
import { getBackendUrl } from "../../_common/scripts/functions.js";

const session = new SessionStorage("admin");
const guard = new AuthGuard(getBackendUrl("admin/login"), "admin", User.TYPE_ADMIN);

if (!guard.protect()) {
    throw new Error("Administrador não autenticado");
}
const api = new UserService();
api.setAuthToken(session.getToken());
const toast = createToast();
try {
    const result = await api.me();
    if (!result.ok) {
        createAuthFailureHandler({session, toast, loginUrl: getBackendUrl('admin/login')})(result);
        toast.fromResponse(result);
    } else session.saveSession(session.getToken(), result.entity.toSession());
} catch { toast.error('Não foi possível validar sua sessão. Tente novamente.'); }

const stored = session.getUser();
const imageAdmin = document.querySelector("#imageAdmin");
const defaultPhoto = imageAdmin?.src;
imageAdmin?.addEventListener("error", () => {
    if (imageAdmin.src !== defaultPhoto) imageAdmin.src = defaultPhoto;
});
if (imageAdmin && stored?.photo) {
    imageAdmin.src = getBackendUrl(stored.photo);
}

const spanAdmin = document.querySelector("#span-admin");
if (spanAdmin && stored) {
    // A sessão guarda um objeto simples; reidrata como entidade para usar firstName
    spanAdmin.textContent = `Olá, ${User.fromApi(stored).firstName}!`;
}

document.querySelector("#logout")?.addEventListener("click", (event) => {
    event.preventDefault();
    session.clearSession();
    window.location.replace(getBackendUrl("admin/login"));
});
