<script setup>
import { ref } from 'vue';
import axios from 'axios';

const emit = defineEmits(['user-created']);

const userLoading = ref(false);
const userForm = ref({ name: '', email: '', password: '', role: '' });

const handleCreateUser = async () => {
  userLoading.value = true;
  try {
    const response = await axios.post('/api/admin/users', userForm.value);
    alert(response.data.message);

    emit('user-created', userForm.value.role);

    userForm.value = { name: '', email: '', password: '', role: '' };

    const modalBtnClose = document.querySelector('#createUserModal .btn-close');
    if (modalBtnClose) modalBtnClose.click();

  } catch (error) {
    console.error("Error al crear usuario:", error);
    alert(error.response?.data?.message || "Hubo un error al procesar el registro.");
  } finally {
    userLoading.value = false;
  }
};
</script>

<template>
  <div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title fw-bold">👥 Registrar Nuevo Usuario</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-start">
          <form @submit.prevent="handleCreateUser">
            <div class="mb-3">
              <label class="form-label font-medium">Nombre Completo</label>
              <input v-model="userForm.name" type="text" class="form-control" required placeholder="Ej. Juan Pérez">
            </div>
            <div class="mb-3">
              <label class="form-label font-medium">Correo Electrónico</label>
              <input v-model="userForm.email" type="email" class="form-control" required placeholder="correo@ejemplo.com">
            </div>
            <div class="mb-3">
              <label class="form-label font-medium">Contraseña de Seguridad</label>
              <input v-model="userForm.password" type="password" class="form-control" required placeholder="Mínimo 6 caracteres">
            </div>
            <div class="mb-3">
              <label class="form-label font-medium">Rol del Usuario</label>
              <select v-model="userForm.role" class="form-select" required>
                <option value="">-- Seleccionar Rol --</option>
                <option value="client">Cliente (Usuario Regular)</option>
                <option value="agent">Técnico (Soporte)</option>
                <option value="admin">Administrador (Control Total)</option>
              </select>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary" :disabled="userLoading">
                <span v-if="userLoading" class="spinner-border spinner-border-sm me-1"></span>
                Guardar Usuario
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>