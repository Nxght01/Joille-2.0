import UserService from "../../_common/classes/services/UserService.js";
import FormController from "../../_common/prototypes/FormController.js";
import { createToast } from "../../_common/prototypes/Toast.js";
import SessionStorage from "../../_common/scripts/storage.js";
import { createAuthFailureHandler, serviceUnavailableMessage } from "../../_common/scripts/ui.js";

const session = new SessionStorage("user");

const users = new UserService();
users.setAuthToken(session.getToken());

const toast = createToast();
const handleAuthFailure = createAuthFailureHandler({ session, toast });

const form = new FormController(document.querySelector("#form-password"), {
    rules: {
        password: { required: true, label: "A senha atual" },
        newPassword: { required: true, minLength: 8, label: "A nova senha" },
        confirmNewPassword: { required: true, matches: "newPassword", label: "A confirmação da senha" }
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
        const result = await users.updatePassword(form.serialize());

        toast.fromResponse(result);

        if (!result.ok) {
            handleAuthFailure(result);
            return;
        }

        form.clear();
    } catch (error) {
        console.error("Erro ao atualizar senha:", error);
        toast.error(serviceUnavailableMessage());
    } finally {
        form.setBusy(false);
    }
});
