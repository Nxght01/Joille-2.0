import {
    getBackendUrl,
    getBackendUrlApi,
    showToast
  } from "../_shared/functions.js";
  
  import {
    userAuth
  } from "../_shared/globals.js";
  
  // Escutador para o envio do formulário
  document.getElementById('edit-form').addEventListener('submit', function(event) {
    event.preventDefault(); // Previne o envio padrão do formulário
  
    const formData = {
        payment_id: document.getElementById('payment_id').value,
        idUser: document.getElementById('idUser').value,
        type: document.getElementById('type').value,
        value: document.getElementById('value').value
    };
  
    // Enviar os dados para o backend
    fetch(getBackendUrlApi('services/add'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'token': userAuth.token // Token de autenticação se necessário
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Serviço adicionado com sucesso!');
            // Opcional: redirecionar ou limpar o formulário
            document.getElementById('edit-form').reset();
        } else {
            showToast('Erro ao adicionar serviço: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showToast('Erro ao se comunicar com o servidor.');
    });
  });
  
  // Listener para o botão de cancelar
  document.getElementById('cancel-btn').addEventListener('click', function() {
    window.location.href = 'admin.html'; // Redirecionar para a página anterior
  });
  