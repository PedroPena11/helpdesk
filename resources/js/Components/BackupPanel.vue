<template>
  <div class="card border-0 shadow-sm rounded-3 p-4 mt-4">
    <!-- Encabezado del Módulo -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h5 class="fw-bold text-dark m-0">
          <i class="bi bi-database-gear text-primary me-2"></i>Gestión de Respaldos (PostgreSQL)
        </h5>
        <p class="text-muted small m-0">Genera, descarga y administra copias de seguridad del sistema.</p>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary" @click="fetchBackups" :disabled="loading">
          <i class="bi bi-arrow-clockwise me-1"></i> Actualizar Lista
        </button>
        <button class="btn btn-sm btn-outline-primary" @click="generateBackup('estructura')" :disabled="loading || actionLoading">
          <i class="bi bi-code-slash me-1"></i> Solo Estructura
        </button>
        <button class="btn btn-sm btn-primary fw-bold" @click="generateBackup('completo')" :disabled="loading || actionLoading">
          <span v-if="actionLoading" class="spinner-border spinner-border-sm me-1" role="status"></span>
          <i v-else class="bi bi-database-fill-down me-1"></i> Respaldar Todo
        </button>
      </div>
    </div>

    <!-- Alertas de estado locales -->
    <div v-if="alertMessage" :class="['alert py-2 small mb-3', isError ? 'alert-danger' : 'alert-success']" role="alert">
      <i :class="['bi me-2', isError ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill']"></i>
      {{ alertMessage }}
    </div>

    <!-- Estado de carga -->
    <div v-if="loading" class="text-center py-4">
      <div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>
      <p class="text-muted small m-0">Leyendo almacenamiento local en Windows...</p>
    </div>

    <!-- Tabla de Respaldos Existentes -->
    <div v-else class="table-responsive shadow-sm border rounded-3" style="max-height: 300px; overflow-y: auto;">
      <table class="table table-hover align-middle small m-0">
        <thead class="table-light sticky-top" style="z-index: 1;">
          <tr>
            <th>Nombre del Archivo Backup</th>
            <th>Tamaño</th>
            <th>Fecha de Creación</th>
            <th class="text-end">Acciones de Control</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="backups.length === 0">
            <td colspan="4" class="text-center text-muted py-4">
              <i class="bi bi-folder-x fs-4 d-block mb-2"></i> No se han encontrado archivos de respaldo generados.
            </td>
          </tr>
          <tr v-for="backup in backups" :key="backup.filename">
            <td class="font-monospace fw-bold text-secondary">{{ backup.filename }}</td>
            <td><span class="badge bg-light text-dark border">{{ backup.size }}</span></td>
            <td class="text-muted">{{ backup.date }}</td>
            <td class="text-end">
              <div class="btn-group btn-group-sm">
                <!-- Descarga Directa -->
                <a :href="`/api/admin/backups/download/${backup.filename}`" 
                   class="btn btn-outline-dark" 
                   title="Descargar archivo SQL">
                  <i class="bi bi-download"></i>
                </a>
                <!-- Destrucción de archivo -->
                <button class="btn btn-outline-danger" 
                        @click="deleteBackup(backup.filename)" 
                        title="Eliminar del servidor">
                  <i class="bi bi-trash3-fill"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const backups = ref([]);
const loading = ref(false);
const actionLoading = ref(false);
const alertMessage = ref('');
const isError = ref(false);


const fetchBackups = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/admin/backups');
    backups.value = response.data;
  } catch (error) {
    showAlert('No se pudo recuperar la lista de copias de seguridad.', true);
  } finally {
    loading.value = false;
  }
};


const generateBackup = async (type) => {
  actionLoading.value = true;
  showAlert('Ejecutando volcado criptográfico en PostgreSQL...', false);
  
  try {
    const response = await axios.post('/api/admin/backups', { type });
    showAlert(response.data.message, false);
    await fetchBackups();
  } catch (error) {
    showAlert(error.response?.data?.message || 'Error crítico del sistema operativo al respaldar.', true);
  } finally {
    actionLoading.value = false;
  }
};


const deleteBackup = async (filename) => {
  if (!confirm(`¿Estás seguro de eliminar de forma permanente el respaldo: ${filename}?`)) return;

  try {
    const response = await axios.delete(`/api/admin/backups/${filename}`);
    showAlert(response.data.message, false);
    await fetchBackups();
  } catch (error) {
    const errorDetalle = error.response?.data?.error_puro_windows || 'Error sin detalle.';
    const mensajeBase = error.response?.data?.message || 'Error crítico.';
  }
};


const showAlert = (message, errorStatus) => {
  alertMessage.value = message;
  isError.value = errorStatus;
  if (!errorStatus) {
    setTimeout(() => { alertMessage.value = ''; }, 5000);
  }
};

onMounted(() => {
  fetchBackups();
});
</script>