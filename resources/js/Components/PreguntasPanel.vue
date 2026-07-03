<template>
  <div class="container-fluid mt-4">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0"><i class="bi bi-shield-lock-fill me-2"></i> Gestión de Preguntas de Seguridad</h5>
        <button class="btn btn-success btn-sm" @click="openModal()">
          <i class="bi bi-plus-circle me-1"></i> Nueva Pregunta
        </button>
      </div>

      <div class="card-body">
        
        <div v-if="alert.message" :class="['alert', alert.isError ? 'alert-danger' : 'alert-success', 'alert-dismissible', 'fade', 'show']" role="alert">
          {{ alert.message }}
          <button type="button" class="btn-close" @click="alert.message = ''"></button>
        </div>

       
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th style="width: 10%">ID</th>
                <th style="width: 70%">Pregunta de Seguridad</th>
                <th style="width: 20%" class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="q in questions" :key="q.id">
                <td><span class="badge bg-secondary text-wrap"># {{ q.id }}</span></td>
                <td class="text-secondary fw-semibold">{{ q.question }}</td>
                <td class="text-end">
                  <button class="btn btn-outline-primary btn-sm me-2" @click="openModal(q)" title="Editar">
                    <i class="bi bi-pencil-square"></i>
                  </button>
                  <button class="btn btn-outline-danger btn-sm" @click="deleteQuestion(q.id)" title="Eliminar">
                    <i class="bi bi-trash3-fill"></i>
                  </button>
                </td>
              </tr>
              <tr v-if="questions.length === 0">
                <td colspan="3" class="text-center text-muted py-4">No hay preguntas registradas.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    
    <div class="modal fade" id="questionModal" tabindex="-1" aria-hidden="true" ref="modalRef">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-light">
            <h5 class="modal-title fw-bold">
              {{ isEditing ? 'Modificar Pregunta de Seguridad' : 'Agregar Nueva Pregunta' }}
            </h5>
            <button type="button" class="btn-close" @click="closeModal"></button>
          </div>
          <form @submit.prevent="saveQuestion">
            <div class="modal-body">
              <div class="mb-3">
                <label for="inputQuestion" class="form-label fw-semibold">Enunciado de la Pregunta</label>
                <input 
                  type="text" 
                  id="inputQuestion" 
                  class="form-control" 
                  v-model="form.question" 
                  placeholder="Ej. ¿Cuál fue el nombre de tu primera mascota?" 
                  required
                />
              </div>
            </div>
            <div class="modal-footer bg-light">
              <button type="button" class="btn btn-secondary" @click="closeModal">Cancelar</button>
              <button type="submit" class="btn btn-primary" :disabled="loading">
                <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
                {{ isEditing ? 'Guardar Cambios' : 'Registrar Pregunta' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';

interface Question {
  id: number;
  question: string;
}


const questions = ref<Question[]>([]);
const modalRef = ref<HTMLElement | null>(null);
let bootstrapModal: Modal | null = null;

const isEditing = ref(false);
const loading = ref(false);
const currentId = ref<number | null>(null);

const form = ref({
  question: ''
});

const alert = ref({
  message: '',
  isError: false
});


onMounted(() => {
  if (modalRef.value) {
    bootstrapModal = new Modal(modalRef.value);
  }
  fetchQuestions();
});


const showAlert = (msg: string, err = false) => {
  alert.value = { message: msg, isError: err };
  if (!err) setTimeout(() => alert.value.message = '', 4000);
};


const fetchQuestions = async () => {
  try {
    const response = await axios.get('/api/preguntas-seguridad');
    questions.value = response.data;
  } catch (error: any) {
    showAlert('Error al cargar la lista de preguntas.', true);
  }
};


const openModal = (questionData: Question | null = null) => {
  if (questionData) {
    isEditing.value = true;
    currentId.value = questionData.id;
    form.value.question = questionData.question;
  } else {
    isEditing.value = false;
    currentId.value = null;
    form.value.question = '';
  }
  bootstrapModal?.show();
};

const closeModal = () => {
  bootstrapModal?.hide();
};


const saveQuestion = async () => {
  loading.value = true;
  try {
    if (isEditing.value && currentId.value) {
      
      await axios.put(`/api/preguntas-seguridad/${currentId.value}`, form.value);
      showAlert('Pregunta de seguridad actualizada.');
    } else {
      
      await axios.post('/api/preguntas-seguridad', form.value);
      showAlert('Nueva pregunta agregada con éxito.');
    }
    fetchQuestions();
    closeModal();
  } catch (error: any) {
    const errorMsg = error.response?.data?.message || 'Error al procesar la solicitud.';
    showAlert(errorMsg, true);
  } finally {
    loading.value = false;
  }
};


const deleteQuestion = async (id: number) => {
  if (!confirm('¿Estás seguro de que deseas eliminar permanentemente esta pregunta?')) return;
  
  try {
    await axios.delete(`/api/preguntas-seguridad/${id}`);
    showAlert('Pregunta eliminada del sistema.');
    fetchQuestions();
  } catch (error: any) {
    showAlert('No se pudo eliminar la pregunta. Verifique si está en uso.', true);
  }
};
</script>