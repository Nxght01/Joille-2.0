import UserService from "../../_common/classes/services/UserService.js";
import User from "../../_common/classes/entities/User.js";
import FormController from "../../_common/prototypes/FormController.js";
import { createToast } from "../../_common/prototypes/Toast.js";
import SessionStorage from "../../_common/scripts/storage.js";
import { getBackendUrl } from "../../_common/scripts/functions.js";
import { serviceUnavailableMessage } from "../../_common/scripts/ui.js";

const users = new UserService();
const session = new SessionStorage("admin");
const toast = createToast();

const form = new FormController(document.querySelector("#formAdmin"), {
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
        // POST — o backend expõe o login administrativo como POST
        const result = await users.loginAdmin(form.serialize());

        if (!result.ok) {
            toast.fromResponse(result);
            return;
        }

        const admin = result.entity;

        if (!admin?.token || admin.typeId !== User.TYPE_ADMIN) {
            toast.error("O servidor não retornou uma sessão válida.");
            return;
        }

        session.saveSession(admin.token, admin.toSession());
        toast.success(`Olá, ${admin.firstName}, bom trabalho!`);

        setTimeout(() => {
            window.location.replace(getBackendUrl("admin/dashboard"));
        }, 1200);
    } catch (error) {
        console.error("Erro na requisição:", error);
        toast.error(serviceUnavailableMessage());
    } finally {
        form.setBusy(false);
    }
});
