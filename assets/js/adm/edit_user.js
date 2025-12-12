import {
    getBackendUrl,
    getBackendUrlApi,
    showToast,
    showDataForm
  } from "../_shared/functions.js";
  
  import {
    userAuth
  } from "../_shared/globals.js";
  
  
  //import {
   // HttpServiceUser
  //} from '../classes/HttpServiceUser.js';

  import {
    HttpUser
  } from "../classes/HttpUser.js";
  
  
  const api = new HttpUser();
  
  const modal = document.querySelector('.modal');
  const usersContainer = document.querySelector('.users-container');
  const editForm = document.querySelector('#editForm');
  const searchInput = document.querySelector('#searchInput');
  
  // função para abrir modal
  function openModal() {
    modal.style.display = 'flex';
  }
  
  // função para fechar modal
  function closeModal() {
    modal.style.display = 'none';
  }
  
  document.querySelector(".btn-cancel").addEventListener('click', () => {
    closeModal();
  });
  
  function renderUsers(listUsers) {
    usersContainer.innerHTML = ``;
    listUsers.forEach(user => {
      const userArticle = document.createElement('article');
      userArticle.setAttribute("user-id", user.id);
      userArticle.classList.add('user-item');
      userArticle.innerHTML = `
        <span data-label="ID">${user.id}</span>
        <span data-label="Name">${user.name}</span>
        <span data-label="Email">${user.email}</span>
        <div class="user-actions">
          <button class="btn btn-edit">Editar</button>
          <button class="btn btn-delete">Excluir</button>
        </div>
      `;
      usersContainer.appendChild(userArticle);
    });
  }
  
  //Carregar todos os usuários
  async function loadUsers() {
    try {
      const listUsers = await api.listUsers();
      renderUsers(listUsers);
    } catch (error) {
      console.error('Erro na requisição:', error);
      showToast('Erro ao carregar usuários', 'error');
   }
  }
  
  // Carregar usuários inicialmente
  loadUsers();
  
  // Evento de clique em um botão de edição
  usersContainer.addEventListener('click', async (event) => {
    const target = event.target;
    
    if (target.classList.contains('btn-edit')) {
      const userId = target.closest('article').getAttribute('user-id');
      try {
        const user = await api.getUserById(userId);
        showDataForm(user);
        openModal();
      } catch (error) {
        console.error('Erro na requisição:', error);
        showToast('Erro ao carregar dados do usuário', 'error');
      }
    }
  
    // Adicionar lógica de exclusão
    if (target.classList.contains('btn-delete')) {
      const userId = target.closest('article').getAttribute('user-id');
      if (confirm('Tem certeza que deseja excluir este usuário?')) {
        try {
          const response = await api.deleteUser(userId);
          showToast(response.message, response.type);
          
          if (response.type === 'success') {
            target.closest('article').remove();
          }
        } catch (error) {
          console.error('Erro na requisição:', error);
          showToast('Erro ao excluir usuário', 'error');
        }
      }
    }
  });
  
  // Submissão do formulário de edição
  editForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(editForm);
  
    try {
      const userUpdate = await api.updateUser(formData);
      showToast(userUpdate.message, userUpdate.type);
  
      if (userUpdate.type === "success") {
        const userArticle = document.querySelector(`article[user-id="${userUpdate.data.id}"]`);
        
        if (userArticle) {
          userArticle.querySelector('span[data-label="Name"]').textContent = userUpdate.data.name;
          userArticle.querySelector('span[data-label="Email"]').textContent = userUpdate.data.email;
        }
  
        closeModal();
      }
    } catch (error) {
      console.error('Erro na requisição:', error);
      showToast('Erro ao atualizar usuário', 'error');
    }
  });
  
  // Busca de usuários
  searchInput.addEventListener('keyup', async () => {
    const searchTerm = searchInput.value.trim();
    
    if (searchTerm.length < 3) {
      return;
    }
  
    try {
      const usersListSearch = await api.searchUsers(searchTerm);
      renderUsers(usersListSearch);
    } catch (error) {
      console.error('Erro na requisição:', error);
      showToast('Erro ao buscar usuários', 'error');
    }
  });