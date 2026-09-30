import PasswordService from "../../_common/classes/services/PasswordService.js";
import FormController from "../../_common/prototypes/FormController.js";
import { createToast } from "../../_common/prototypes/Toast.js";
import { serviceUnavailableMessage } from "../../_common/scripts/ui.js";

/**
 * Tela "esqueci minha senha": pede o e-mail e dispara o envio do link.
 *
 * A API responde com a mesma mensagem exista ou não a conta, para não revelar
 * quais e-mails estão cadastrados — a tela apenas repete o que ela devolveu.
 */

const passwords = new PasswordService();
const toast = createToast();

const form = new FormController(document.querySelector("#formForgot"), {
    busyLabel: "Enviando...",
    rules: {
        email: { required: true, email: true, label: "O e-mail" }
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
        const result = await passwords.forgot(form.values());

        toast.fromResponse(result);

        if (result.ok) {
            form.clear();
        }
    } catch (error) {
        console.error("Erro ao solicitar a recuperação:", error);
        toast.error(serviceUnavailableMessage());
    } finally {
        form.setBusy(false);
    }
});
