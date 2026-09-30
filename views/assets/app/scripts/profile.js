import UserService from "../../_common/classes/services/UserService.js";
import User from "../../_common/classes/entities/User.js";
import { validateImages } from "../../_common/scripts/image-validation.js";
import FormController from "../../_common/prototypes/FormController.js";
import { createToast } from "../../_common/prototypes/Toast.js";
import SessionStorage from "../../_common/scripts/storage.js";
import { getBackendUrl } from "../../_common/scripts/functions.js";
import { createAuthFailureHandler, serviceUnavailableMessage } from "../../_common/scripts/ui.js";

const session = new SessionStorage("user");

const users = new UserService();
users.setAuthToken(session.getToken());

const toast = createToast();
const handleAuthFailure = createAuthFailureHandler({ session, toast });

const profileImg = document.querySelector("img[data-profile-photo]");
const photoInput = document.querySelector("#photo");
let uploadingPhoto = false;

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
    session.saveSession(session.getToken(), { ...session.getUser(), ...changes });
}

// --- carregar o perfil -------------------------------------------------------

try {
    const result = await users.me();

    if (result.ok) {
        persist({ photo: result.entity.photo });
        profileForm.fill({ name: result.entity.name, email: result.entity.email });

        if (profileImg && result.entity.photo) {
            profileImg.src = getBackendUrl(result.entity.photo);
        }
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
        user = new User({ ...profileForm.values(), type_id: User.TYPE_CLIENT });
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

        const photo = result.data?.photo ?? null;
        persist({ photo });

        if (profileImg && photo) {
            profileImg.src = getBackendUrl(photo);
        }

        document.querySelector("#photo").value = "";
    } catch (error) {
        console.error("Erro no upload:", error);
        toast.error(serviceUnavailableMessage());
    } finally {
        uploadingPhoto = false;
        photoInput.disabled = false;
        photoForm.setBusy(false);
    }
});
