import {
    showDataForm,
    getBackendUrlApi, 
    getBackendUrl, 
    showToast
} from "./../_shared/functions.js";

import {
    userAuth
} from "./../_shared/globals.js";

// Existing fetch logic for user data...

const formPhoto = document.querySelector("#form-photo");
formPhoto.addEventListener("submit", async (e) => {
    e.preventDefault();
    
    const formData = new FormData(formPhoto);
    
    try {
        const response = await fetch(getBackendUrlApi("users/photo"), {
            method: "POST",
            body: formData,
            headers: {
                token: userAuth.token
            }
        });

        const data = await response.json();

        if (data.error) {
            showToast(data.error.message, "error");
            return;
        }

        showToast("Foto atualizada com sucesso!", "success");
        
        // Update profile image
        const profileImg = document.querySelector("img[data-profile-photo]");
        if (profileImg) {
            profileImg.src = getBackendUrl(data.user.photo);
        }

    } catch (error) {
        console.error("Erro no upload:", error);
        showToast("Erro ao atualizar foto", "error");
    }
});