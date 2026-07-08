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
                <!-- 🌟 NUEVO: Acción de Restauración -->
                <button class="btn btn-outline-warning text-dark" 
                        @click="openRestoreConfirmation(backup.filename)" 
                        title="Restaurar base de datos"
                        :disabled="restoreLoading">
                  <i class="bi bi-arrow-counterclockwise fw-bold"></i>
                </button>
                <!-- Descarga Directa -->
                <a @click="downloadBackup(backup.filename)" 
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

    <!-- 🌟 NUEVO: MODAL DE CONFIRMACIÓN PARA RESTAURAR -->
    <div class="modal fade" id="restoreConfirmModal" tabindex="-1" aria-hidden="true" ref="restoreModalRef">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-warning text-dark">
            <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i> Confirmar Restauración</h5>
            <button type="button" class="btn-close" @click="closeRestoreModal"></button>
          </div>
          <div class="modal-body text-start">
            <p class="small text-secondary">Estás a punto de sobreescribir el estado actual del sistema utilizando el script SQL:</p>
            <div class="alert alert-light border fw-bold font-monospace text-center text-dark py-2 small mb-3">
              {{ selectedBackup }}
            </div>
            
            <!-- CASO DE USO EXIGIDO: CHECKBOX DE LIMPIEZA -->
            <div class="form-check form-switch p-3 bg-light rounded border border-danger-subtle">
              <input class="form-check-input ms-0 me-2" type="checkbox" id="checkClearDb" v-model="restoreForm.clear_db">
              <label class="form-check-label text-danger fw-bold small" for="checkClearDb">
                ⚠️ Limpiar/Vaciar BD antes de restaurar
              </label>
              <div class="text-muted mt-1" style="font-size: 0.75rem; margin-left: 0.25rem;">
                Eliminará el esquema público (DROP SCHEMA) y recreará las tablas limpias para evitar colisiones estables de llaves primarias duplicadas.
              </div>
            </div>
          </div>
          <div class="modal-footer bg-light py-2">
            <button type="button" class="btn btn-sm btn-secondary" @click="closeRestoreModal" :disabled="restoreLoading">Cancelar</button>
            <button type="button" class="btn btn-sm btn-danger fw-bold" :disabled="restoreLoading" @click="executeRestore">
              <span v-if="restoreLoading" class="spinner-border spinner-border-sm me-1"></span>
              <i v-else class="bi bi-lightning-charge-fill me-1"></i> Ejecutar Restore
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';

const backups = ref([]);
const loading = ref(false);
const actionLoading = ref(false);
const alertMessage = ref('');
const isError = ref(false);

const selectedBackup = ref('');
const restoreLoading = ref(false);
const restoreModalRef = ref(null);
let bootstrapRestoreModal = null;
const restoreForm = ref({
  clear_db: false
});

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


const openRestoreConfirmation = (filename) => {
  selectedBackup.value = filename;
  restoreForm.value.clear_db = false;
  
  if (restoreModalRef.value && !bootstrapRestoreModal) {
    bootstrapRestoreModal = new Modal(restoreModalRef.value);
  }
  bootstrapRestoreModal?.show();
};

const closeRestoreModal = () => {
  if (bootstrapRestoreModal) {
    bootstrapRestoreModal.hide();
  }
};

const executeRestore = async () => {
  restoreLoading.value = true;
  showAlert('Iniciando subproceso de reconstrucción en PostgreSQL...', false);
  
  try {
    const response = await axios.post('/api/admin/backups/restore', {
      filename: selectedBackup.value,
      clear_db: restoreForm.value.clear_db
    });
    
    showAlert(response.data.message || 'Base de datos restaurada con éxito.', false);
    closeRestoreModal();
    await fetchBackups();
  } catch (error) {
    const errorMsg = error.response?.data?.error || 'Error desconocido al procesar el script SQL.';
    showAlert(`Fallo crítico en Restore: ${errorMsg}`, true);
    closeRestoreModal();
  } finally {
    restoreLoading.value = false;
  }
};

const deleteBackup = async (filename) => {
  if (!confirm(`¿Estás seguro de eliminar de forma permanente el respaldo: ${filename}?`)) return;

  try {
    const response = await axios.delete(`/api/admin/backups/${filename}`);
    showAlert(response.data.message, false);
    await fetchBackups();
  } catch (error) {
    showAlert(error.response?.data?.message || 'Error crítico al eliminar.', true);
  }
};

const downloadBackup = async (filename) => {
  try {
    const response = await axios.get(`/api/admin/backups/download/${filename}`, {
      responseType: 'blob' 
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    
    link.remove();
    window.URL.revokeObjectURL(url);

    showAlert('Archivo descargado con éxito.', false);
  } catch (error) {
    console.error(error);
    showAlert('Error al descargar el archivo de respaldo.', true);
  }
};

const showAlert = (message, errorStatus) => {
  alertMessage.value = message;
  isError.value = errorStatus;
  if (!errorStatus) {
    setTimeout(() => { alertMessage.value = ''; }, 6000);
  }
};

onMounted(() => {
  fetchBackups();
});
</script>