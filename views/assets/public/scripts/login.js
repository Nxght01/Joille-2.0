import UserService from "../../_common/classes/services/UserService.js";
import FormController from "../../_common/prototypes/FormController.js";
import { createToast } from "../../_common/prototypes/Toast.js";
import SessionStorage from "../../_common/scripts/storage.js";
import { getBackendUrl } from "../../_common/scripts/functions.js";
import { serviceUnavailableMessage } from "../../_common/scripts/ui.js";

const users = new UserService();
const session = new SessionStorage("user");
const toast = createToast();

const form = new FormController(document.querySelector("#formLogin"), {
    busyLabel: "Entrando...",
    rules: {
        email: { required: true, email: true, label: "O e-mail" },
        password: { required: true, label: "A senha" }
    }
});

form.form.addEventListener("submit", async (event) => {
    event.preventDefault();

    const errors = form.validate();

    if (errors.length > 0) {
        toast.warning(errors[0]);
        return;
    }

    form.setBusy(true);

    try {
        // POST — o backend expõe o login como POST (Users:auth)
        const result = await users.login(form.serialize());

        if (!result.ok) {
            toast.fromResponse(result);
            return;
        }

        const user = result.entity;

        if (!user?.token) {
            toast.error("O servidor não retornou uma sessão válida.");
            return;
        }

        session.saveSession(user.token, user.toSession());
        toast.success(`Olá, ${user.firstName}!`);

        setTimeout(() => {
            window.location.href = getBackendUrl("app/");
        }, 1200);
    } catch (error) {
        console.error("Erro na requisição:", error);
        toast.error(serviceUnavailableMessage());
    } finally {
        form.setBusy(false);
    }
});
