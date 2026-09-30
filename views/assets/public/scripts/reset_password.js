import PasswordService from "../../_common/classes/services/PasswordService.js";
import FormController from "../../_common/prototypes/FormController.js";
import { createToast } from "../../_common/prototypes/Toast.js";
import { getBackendUrl } from "../../_common/scripts/functions.js";
import { serviceUnavailableMessage } from "../../_common/scripts/ui.js";

/**
 * Tela de nova senha, aberta pelo link enviado por e-mail.
 *
 * O token vem na query string. Ele é validado ANTES de o formulário aparecer,
 * para que ninguém digite uma senha nova em um link já expirado ou usado.
 */

const passwords = new PasswordService();
const toast = createToast();

const checking = document.querySelector("#checking");
const invalidLink = document.querySelector("#invalidLink");
const accountEmail = document.querySelector("#accountEmail");

const form = new FormController(document.querySelector("#formReset"), {
    busyLabel: "Salvando...",
    rules: {
        password: { required: true, minLength: 8, label: "A nova senha" },
        confirmPassword: { required: true, matches: "password", label: "A confirmação da senha" }
    }
});

const token = new URLSearchParams(window.location.search).get("token");

function showInvalid(message) {
    checking.hidden = true;
    form.form.hidden = true;
    invalidLink.hidden = false;
    invalidLink.textContent = message;
}

function showForm(data) {
    checking.hidden = true;
    invalidLink.hidden = true;
    form.form.hidden = false;

    if (accountEmail) {
        accountEmail.textContent = data?.email ?? "";
    }
}

// --- validação do link -------------------------------------------------------

if (!token) {
    showInvalid("Link inválido: o endereço não traz o token de recuperação. Solicite um novo link.");
} else {
    try {
        const result = await passwords.validateToken(token);

        if (result.ok) {
            showForm(result.data);
        } else {
            showInvalid(result.message);
        }
    } catch (error) {
        console.error("Erro ao validar o link:", error);
        showInvalid(serviceUnavailableMessage());
    }
}

// --- troca da senha ----------------------------------------------------------

form.form.addEventListener("submit", async (event) => {
    event.preventDefault();

    const errors = form.validate();

    if (errors.length > 0) {
        toast.warning(errors[0]);
        return;
    }

    form.setBusy(true);

    try {
        const result = await passwords.reset({ ...form.values(), token });

        toast.fromResponse(result);

        if (!result.ok) {
            // Token consumido ou expirado no meio do caminho: o formulário sai
            // de cena para não induzir a uma segunda tentativa inútil.
            if ([404, 409].includes(Number(result.code))) {
                showInvalid(result.message);
            }
            return;
        }

        form.form.hidden = true;
        checking.hidden = false;
        checking.textContent = "Senha alterada! Redirecionando para o login...";

        setTimeout(() => {
            window.location.href = getBackendUrl("login");
        }, 2500);
    } catch (error) {
        console.error("Erro ao redefinir a senha:", error);
        toast.error(serviceUnavailableMessage());
    } finally {
        form.setBusy(false);
    }
});
