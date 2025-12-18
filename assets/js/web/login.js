import {
    HttpUser
} from '../classes/HttpUser.js';

import {
    getBackendUrl,
    getBackendUrlApi,
    getFirstName,
    showToast
} from "./../_shared/functions.js";

// Create an HttpUser instance to handle API calls
//const api = new HttpUser();

const formLogin = document.querySelector("#formLogin");
formLogin.addEventListener("submit", async (e) => {
    e.preventDefault();
  
    const response = await fetch(getBackendUrlApi("users/login"), {
      method: "POST",
      body: new FormData(formLogin),
    });
  
    const data = await response.json();
  
    if (data.type === "success") {
      localStorage.setItem("userAuth", JSON.stringify(data.user));
      showToast(data.message);
      setTimeout(() => {
        window.location.href = getBackendUrl("app/");
      }, 1500);
    }else{
      if(data.type === "error"){
        showToast(data.message);
      }
    }
  
    
  });