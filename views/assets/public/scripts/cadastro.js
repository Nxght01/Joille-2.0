import UserService from "../../_common/classes/services/UserService.js";
import User from "../../_common/classes/entities/User.js";
import FormController from "../../_common/prototypes/FormController.js";
import { createToast } from "../../_common/prototypes/Toast.js";
import { getBackendUrl } from "../../_common/scripts/functions.js";
import { serviceUnavailableMessage } from "../../_common/scripts/ui.js";

const users = new UserService();
const toast = createToast();

const form = new FormController(document.querySelector("#formRegister"), {
    busyLabel: "Cadastrando...",
    rules: {
        name: { required: true, label: "O nome" },
        email: { required: true, email: true, label: "O e-mail" },
        password: { required: true, minLength: 8, label: "A senha" }
    }
});

form.form.addEventListener("submit", async (event) => {
    event.preventDefault();

    const errors = form.validate();

    if (errors.length > 0) {
        toast.warning(errors[0]);
        return;
    }

    let user;

    // As validações dos setters da entidade viram mensagens para o usuário
    try {
        user = new User({ ...form.values(), type_id: User.TYPE_CLIENT });
    } catch (error) {
        toast.warning(error.message);
        return;
    }

    form.setBusy(true);

    try {
        const result = await users.register(user);

        toast.fromResponse(result);

        if (!result.ok) {
            return;
        }

        setTimeout(() => {
            window.location.href = getBackendUrl("login");
        }, 1200);
    } catch (error) {
        console.error("Erro na requisição:", error);
        toast.error(serviceUnavailableMessage());
    } finally {
        form.setBusy(false);
    }
});
