import {
    showDataForm,
    getBackendUrlApi,
    getBackendUrl,
    showToast
} from "./../_shared/functions.js";

import {
    userAuth
} from "./../_shared/globals.js";

/**
 * Carregar dados do usuário ao abrir a página
 */
document.addEventListener("DOMContentLoaded", async () => {
    try {
        const response = await fetch(getBackendUrlApi("users/user"), {
            method: "GET",
            headers: {
                token: userAuth.token
            }
        });

        const data = await response.json();

        if (data.type === "success") {
            document.querySelector("#name").value = data.user.name;
            document.querySelector("#email").value = data.user.email;

            const profileImg = document.querySelector("img[data-profile-photo]");
            if (profileImg && data.user.photo) {
                profileImg.src = getBackendUrl(data.user.photo);
            }
        }
    } catch (error) {
        console.error("Erro ao carregar dados:", error);
    }
});

/**
 * Atualizar nome e email
 */
const formProfile = document.querySelector("#profile");

formProfile.addEventListener("submit", async (e) => {
    e.preventDefault();

    const formData = new FormData(formProfile);

    try {
        const response = await fetch(
            getBackendUrlApi("users/users/profile"),
            {
                method: "POST",
                body: formData,
                headers: {
                    token: userAuth.token
                }
            }
        );

        const data = await response.json();

        if (data.type === "error") {
            showToast(data.message, "error");
            return;
        }

        showToast(data.message, "success");

        // Atualiza localStorage
        userAuth.name = data.user.name;
        userAuth.email = data.user.email;
        localStorage.setItem("userAuth", JSON.stringify(userAuth));

    } catch (error) {
        console.error("Erro ao atualizar perfil:", error);
        showToast("Erro ao atualizar perfil", "error");
    }
});

/**
 * Atualizar foto do perfil
 */
const formPhoto = document.querySelector("#form-photo");

formPhoto.addEventListener("submit", async (e) => {
    e.preventDefault();

    const formData = new FormData(formPhoto);

    try {
        const response = await fetch(
            getBackendUrlApi("users/users/photo"),
            {
                method: "POST",
                body: formData,
                headers: {
                    token: userAuth.token
                }
            }
        );

        const data = await response.json();

        if (data.type === "error") {
            showToast(data.message, "error");
            return;
        }

        showToast(data.message, "success");

        
        userAuth.photo = data.user.photo;
        localStorage.setItem("userAuth", JSON.stringify(userAuth));

       
        const profileImg = document.querySelector("img[data-profile-photo]");
        if (profileImg && userAuth.photo) {
            profileImg.src = getBackendUrl(userAuth.photo);
        }

    
        document.querySelector("#photo").value = "";

    } catch (error) {
        console.error("Erro no upload:", error);
        showToast("Erro ao atualizar foto", "error");
    }
});
