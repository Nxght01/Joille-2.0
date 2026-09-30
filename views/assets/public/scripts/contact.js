import ContactService from "../../_common/classes/services/ContactService.js";
import ContactMessage from "../../_common/classes/entities/ContactMessage.js";
import FormController from "../../_common/prototypes/FormController.js";
import { createToast } from "../../_common/prototypes/Toast.js";
import { serviceUnavailableMessage } from "../../_common/scripts/ui.js";

/**
 * Formulário de contato da área pública.
 *
 * A mensagem vai para a API do próprio sistema (`POST /contacts`), que a grava
 * no banco, encaminha por e-mail ao suporte (PHPMailer) e manda um recibo ao
 * visitante. Nenhum serviço externo é usado.
 */

const contacts = new ContactService();
const toast = createToast();

const form = new FormController(document.querySelector("#contactForm"), {
    busyLabel: "Enviando...",
    rules: {
        name: { required: true, label: "O nome" },
        email: { required: true, email: true, label: "O e-mail" },
        message: { required: true, minLength: 10, label: "A mensagem" }
    }
});

const mensagemStatus = document.querySelector("#mensagemStatus");

function showStatus(message, variant) {
    if (!mensagemStatus) {
        return;
    }

    mensagemStatus.className = `message ${variant}`;
    mensagemStatus.style.display = "block";
    mensagemStatus.textContent = message;
    mensagemStatus.scrollIntoView({ behavior: "smooth", block: "nearest" });
}

form.form.addEventListener("submit", async (event) => {
    event.preventDefault();

    // Campo honeypot: preenchido significa robô, então nada é enviado
    if (form.field("botcheck")) {
        return;
    }

    const errors = form.validate();

    if (errors.length > 0) {
        toast.warning(errors[0]);
        showStatus(`✗ ${errors[0]}`, "error");
        return;
    }

    let message;

    // As validações dos setters da entidade viram mensagens para o usuário
    try {
        message = new ContactMessage(form.values());
    } catch (error) {
        toast.warning(error.message);
        showStatus(`✗ ${error.message}`, "error");
        return;
    }

    form.setBusy(true);

    if (mensagemStatus) {
        mensagemStatus.style.display = "none";
    }

    try {
        const result = await contacts.create(message);

        toast.fromResponse(result);

        if (!result.ok) {
            showStatus(`✗ ${result.message}`, "error");
            return;
        }

        showStatus(`✓ ${result.message}`, "success");
        form.clear();
    } catch (error) {
        console.error("Erro ao enviar:", error);
        toast.error(serviceUnavailableMessage());
        showStatus(`✗ ${serviceUnavailableMessage()}`, "error");
    } finally {
        form.setBusy(false);
    }
});
