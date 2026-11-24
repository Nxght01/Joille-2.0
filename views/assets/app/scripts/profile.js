import { bindImagePreview } from "../../_common/scripts/image-preview.js";
import UserService from "../../_common/classes/services/UserService.js";
import User from "../../_common/classes/entities/User.js";
import { validateImages } from "../../_common/scripts/image-validation.js";
import FormController from "../../_common/prototypes/FormController.js";
import { createToast } from "../../_common/prototypes/Toast.js";
import SessionStorage from "../../_common/scripts/storage.js";
import { getBackendUrl } from "../../_common/scripts/functions.js";
import { createAuthFailureHandler, serviceUnavailableMessage } from "../../_common/scripts/ui.js";
import { renderProfile } from "../../_common/scripts/profile-display.js";

const scope = document.querySelector('[data-profile-scope]')?.dataset.profileScope ?? 'user';
const session = new SessionStorage(scope);

const users = new UserService();
users.setAuthToken(session.getToken());

const toast = createToast();
const handleAuthFailure = createAuthFailureHandler({ session, toast, loginUrl: getBackendUrl(scope === 'admin' ? 'admin/login' : 'login') });

const photoInput = document.querySelector("#photo");
const photoStatus = document.querySelector("#photo-status");
let uploadingPhoto = false;
const clearPreview = bindImagePreview(photoInput, document.querySelector("#photoPreview"),
    files => validateImages(files, 1), message => toast.warning(message));

const profileForm = new FormController(document.querySelector("#profile"), {
    rules: {
        name: { required: true, label: "O nome" },
        email: { required: true, email: true, label: "O e-mail" }
    }
});

const photoForm = new FormController(document.querySelector("#form-photo"), {
    busyLabel: "Enviando..."
});

/** Guarda no localStorage os dados mínimos da sessão, sem senha. */
function persist(changes) {
    const profile = { ...session.getUser(), ...changes };
    session.saveSession(session.getToken(), profile);
    renderProfile(profile);
}

// --- carregar o perfil -------------------------------------------------------

try {
    const result = await users.me();

    if (result.ok) {
        persist(result.entity.toSession());
        profileForm.fill({ name: result.entity.name, email: result.entity.email, address: result.entity.address, phone: result.entity.phone });

    } else {
        handleAuthFailure(result);
        toast.fromResponse(result);
    }
} catch (error) {
    console.error("Erro ao carregar dados:", error);
    toast.error(serviceUnavailableMessage());
}

// --- atualizar nome e e-mail -------------------------------------------------

profileForm.form.addEventListener("submit", async (event) => {
    event.preventDefault();

    const errors = profileForm.validate();

    if (errors.length > 0) {
        toast.warning(errors[0]);
        return;
    }

    let user;

    try {
        user = new User({ ...profileForm.values(), type_id: scope === 'admin' ? User.TYPE_ADMIN : User.TYPE_CLIENT });
    } catch (error) {
        toast.warning(error.message);
        return;
    }

    profileForm.setBusy(true);

    try {
        const result = await users.updateProfile(user);

        toast.fromResponse(result);

        if (!result.ok) {
            handleAuthFailure(result);
            return;
        }

        persist({ name: result.entity.name, email: result.entity.email });
    } catch (error) {
        console.error("Erro ao atualizar perfil:", error);
        toast.error(serviceUnavailableMessage());
    } finally {
        profileForm.setBusy(false);
    }
});

// --- atualizar a foto --------------------------------------------------------

photoForm.form.addEventListener("submit", async (event) => {
    event.preventDefault();

    if (uploadingPhoto) return;
    const error = validateImages(photoInput.files, 1);
    if (error) {
        toast.warning(error);
        return;
    }
    const payload = photoForm.serialize();
    if (photoStatus) photoStatus.hidden = true;
    uploadingPhoto = true;
    photoInput.disabled = true;
    photoForm.setBusy(true);

    try {
        const result = await users.updatePhoto(payload);

        toast.fromResponse(result);

        if (!result.ok) {
            handleAuthFailure(result);
            return;
        }

        const photo = result.data?.photo;
        if (!photo) {
            toast.error("Não foi possível carregar a nova foto. Tente novamente.");
            return;
        }
        persist({ photo, photoVersion: Date.now() });

        photoInput.value = "";
        clearPreview();
        if (photoStatus) {
            photoStatus.textContent = "Foto atualizada.";
            photoStatus.hidden = false;
        }
    } catch (error) {
        console.error("Erro no upload:", error);
        toast.error(serviceUnavailableMessage());
    } finally {
        uploadingPhoto = false;
        photoInput.disabled = false;
        photoForm.setBusy(false);
    }
});
