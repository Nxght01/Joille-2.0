import {
    getBackendUrl, getBackendUrlApi, showToast
} from "./../_shared/functions.js";

import {
    userAuth
} from "./../_shared/globals.js";

if(!userAuth){
    window.location.href = getBackendUrl("login");
}
let userInfo = JSON.parse(localStorage.getItem("userAuth"));

const imageUser = document.querySelector("#imageUser");

if (userInfo?.photo) {
    imageUser.src = getBackendUrl(userInfo.photo);
}

const logout = document.querySelector("#logout")
const spanUser = document.querySelector("#span-user")
spanUser.innerHTML = `Olá, ${userInfo.name}`; 

logout.addEventListener("click", async (e) => {
    e.preventDefault();
  localStorage.removeItem("userAuth");
  window.location.href = getBackendUrl("");
})


fetch(getBackendUrlApi("users/token-validate"), {
    method: "GET",
    headers: {
        token: userAuth.token
    }
}).then((response) => {
    response.json().then((data) => {
        if(data.error) {
            showToast(data.error.message);
            setTimeout(() => {
                window.location.href = getBackendUrl("login");
            },3000);
        }
    });
});