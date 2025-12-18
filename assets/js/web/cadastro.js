import { HttpUser } from "../classes/HttpUser.js";

import {
  getBackendUrl,
  getBackendUrlApi,
  getFirstName,
  showToast,
} from "./../_shared/functions.js";

const formRegister = document.querySelector("#formRegister");
// const api = new HttpUser();

formRegister.addEventListener("submit", async (event) => {
  event.preventDefault();
  try {
    const response = await fetch(getBackendUrlApi("users/"), {
      method: "POST",
      body: new FormData(formRegister),
    });
    const data = await response.json();
    if (data.type === "success") {
      showToast(data.message);
      setTimeout(() => {
        window.location.href = getBackendUrl("/login");
      }, 500);
    } else {
      if (data.type === "error") {
        showToast(data.message);
      }
    }
  } catch (error) {
    console.log("Erro na requisição:", error);
    showToast("Erro ao processar cadastro. Tente novamente.");
  }
});
