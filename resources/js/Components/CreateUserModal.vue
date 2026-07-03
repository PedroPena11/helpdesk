<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const emit = defineEmits(['user-created']);

const userLoading = ref(false);
const usersTableLoading = ref(false);
const systemUsers = ref([]);

const userForm = ref({ name: '', email: '', password: '', role: '' });


const loadSystemUsers = async () => {
  usersTableLoading.value = true;
  try {
    const token = localStorage.getItem('access_token');
    const response = await axios.get('/api/admin/users', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    systemUsers.value = response.data;
  } catch (error) {
    console.error("Error al mapear usuarios del sistema:", error);
  } finally {
    usersTableLoading.value = false;
  }
};


const handleCreateUser = async () => {
  userLoading.value = true;
  try {
    const token = localStorage.getItem('access_token');
    const response = await axios.post('/api/admin/users', userForm.value, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    
    alert(response.data.message);

    
    emit('user-created', userForm.value.role);

    
    userForm.value = { name: '', email: '', password: '', role: '' };

    
    await loadSystemUsers();

  } catch (error) {
    console.error("Error al crear usuario:", error);
    alert(error.response?.data?.message || "Hubo un error al procesar el registro.");
  } finally {
    userLoading.value = false;
  }
};


const handleDeleteUser = async (id) => {
  if (!confirm('¿Estás seguro de que deseas purgar a este usuario del sistema perimetral?')) return;

  try {
    const token = localStorage.getItem('access_token');
    const response = await axios.delete(`/api/admin/users/${id}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    
    alert(response.data.message);
    await loadSystemUsers(); 
  } catch (error) {
    console.error("Error al eliminar usuario:", error);
    alert(error.response?.data?.message || "No se pudo eliminar al usuario.");
  }
};


onMounted(() => {
  loadSystemUsers();
});
</script>

<template>
  <div class="modal fade" id="createUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content border-0 shadow-lg rounded-4">
        
        <div class="modal-header bg-primary text-white py-3">
          <h5 class="modal-title fw-bold">👥 Consola de Gestión de Usuarios</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body text-start p-4">
          
          <h6 class="fw-bold text-primary mb-3"><i class="bi bi-person-plus-fill me-1"></i> Registrar Nuevo Usuario</h6>
          <form @submit.prevent="handleCreateUser" class="row g-3 mb-4 p-3 bg-light rounded-3 border">
            <div class="col-md-6">
              <label class="form-label font-medium small fw-bold text-secondary">Nombre Completo</label>
              <input v-model="userForm.name" type="text" class="form-control bg-white" required placeholder="Ej. Juan Pérez" :disabled="userLoading">
            </div>
            <div class="col-md-6">
              <label class="form-label font-medium small fw-bold text-secondary">Correo Electrónico</label>
              <input v-model="userForm.email" type="email" class="form-control bg-white" required placeholder="correo@ejemplo.com" :disabled="userLoading">
            </div>
            <div class="col-md-6">
              <label class="form-label font-medium small fw-bold text-secondary">Contraseña de Seguridad</label>
              <input v-model="userForm.password" type="password" class="form-control bg-white" required placeholder="Mínimo 6 caracteres" :disabled="userLoading">
            </div>
            <div class="col-md-6">
              <label class="form-label font-medium small fw-bold text-secondary">Rol del Usuario</label>
              <select v-model="userForm.role" class="form-select bg-white" required :disabled="userLoading">
                <option value="">-- Seleccionar Rol --</option>
                <option value="client">Cliente (Usuario Regular)</option>
                <option value="agent">Técnico (Soporte)</option>
                <option value="admin">Administrador (Control Total)</option>
              </select>
            </div>
            <div class="col-12 d-flex justify-content-end gap-2 mt-3">
              <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm" :disabled="userLoading">
                <span v-if="userLoading" class="spinner-border spinner-border-sm me-1"></span>
                Guardar e Inyectar
              </button>
            </div>
          </form>

          <hr class="my-4 opacity-25">

          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold text-secondary mb-0"><i class="bi bi-list-task me-1"></i> Usuarios Registrados en el Sistema</h6>
            <button type="button" class="btn btn-sm btn-outline-secondary border-0" @click="loadSystemUsers" :disabled="usersTableLoading">
              <span v-if="usersTableLoading" class="spinner-border spinner-border-sm me-1"></span>
              🔄 Actualizar Vista
            </button>
          </div>

          <div class="table-responsive border rounded-3 bg-white mb-2" style="max-height: 250px; overflow-y: auto;">
            <table class="table table-hover align-middle mb-0 small">
              <thead class="table-light sticky-top">
                <tr>
                  <th>Nombre</th>
                  <th>Correo</th>
                  <th>Rol</th>
                  <th>Seguridad</th>
                  <th class="text-center">Acción</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="systemUsers.length === 0 && !usersTableLoading">
                  <td colspan="5" class="text-center text-muted py-3">No hay usuarios registrados en el sistema.</td>
                </tr>
                <tr v-for="user in systemUsers" :key="user.id">
                  <td class="fw-semibold text-dark">{{ user.name }}</td>
                  <td class="text-muted">{{ user.email }}</td>
                  <td>
                    <span :class="['badge font-monospace', user.role === 'admin' ? 'bg-danger-subtle text-danger' : user.role === 'agent' ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary']">
                      {{ user.role === 'agent' ? 'Técnico' : user.role }}
                    </span>
                  </td>
                  <td>
                    <span :class="['badge rounded-pill shadow-sm', user.setup_completed ? 'bg-success' : 'bg-warning text-dark']" style="font-size: 0.7rem;">
                      {{ user.setup_completed ? 'Cripto OK ✓' : 'Pendiente ⚠️' }}
                    </span>
                  </td>
                  <td class="text-center">
                    <button class="btn btn-sm btn-outline-danger border-0 px-2 py-1 rounded-2" title="Eliminar Usuario" @click="handleDeleteUser(user.id)">
                      🗑️
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>