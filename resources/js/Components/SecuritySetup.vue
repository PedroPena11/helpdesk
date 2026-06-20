<template>
    <div class="container d-flex justify-content-center align-items-center min-vh-75 my-5">
        <div class="card shadow-lg border-0 rounded-4 p-4" style="max-width: 450px; width: 100%;">

            <div class="text-center mb-4">
                <div class="bg-primary bg-gradient text-white d-inline-block p-3 rounded-circle shadow-sm mb-3">
                    <i class="bi bi-shield-lock-fill fs-2"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">Configuración de Seguridad</h4>
                <p class="text-muted small px-3">Por favor, establece una pregunta de seguridad para proteger y validar
                    tu cuenta perimetralmente.</p>
            </div>

            <div v-if="errorMessage"
                class="alert alert-danger d-flex align-items-center small py-2 rounded-3 mb-3 animate__animated animate__fadeIn"
                role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div>{{ errorMessage }}</div>
            </div>

            <form @submit.prevent="handleSubmit">

                <div class="mb-3 text-start">
                    <label class="form-label fw-semibold text-secondary small">Selecciona una pregunta de
                        seguridad</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i
                                class="bi bi-question-circle-fill"></i></span>
                        <select v-model="form.security_question_id" class="form-select border-start-0 bg-white" required
                            :disabled="loading">
                            <option value="" disabled selected>-- Elige una pregunta --</option>
                            <option v-for="q in questionsList" :key="q.id" :value="q.id">
                                {{ q.question }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mb-4 text-start">
                    <label class="form-label fw-semibold text-secondary small">Tu respuesta secreta</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i
                                class="bi bi-key-fill"></i></span>
                        <input type="text" v-model="form.answer" class="form-control border-start-0"
                            placeholder="Escribe tu respuesta aquí..." required minlength="3" autocomplete="off"
                            :disabled="loading" />
                    </div>
                    <div class="form-text text-muted extra-small mt-1">
                        ⚠️ Nota: Recuerda bien esta respuesta, te será solicitada para recuperar accesos.
                    </div>
                </div>

                <button type="submit"
                    class="btn btn-primary bg-gradient w-100 py-2 fw-semibold rounded-3 shadow-sm d-flex justify-content-center align-items-center"
                    :disabled="loading || !form.security_question_id || !form.answer">
                    <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"
                        aria-hidden="true"></span>
                    <i v-else class="bi bi-check-circle-fill me-2"></i>
                    {{ loading ? 'Guardando configuración...' : 'Finalizar y Entrar' }}
                </button>

            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';


const emit = defineEmits(['setup-success']);

const form = ref({
    security_question_id: '',
    answer: ''
});

const questionsList = ref([]);
const loading = ref(false);
const errorMessage = ref('');


onMounted(async () => {
    try {
        const response = await axios.get('/api/security-questions');
        questionsList.value = response.data;
    } catch (error) {
        console.error("Error al cargar preguntas:", error);
        errorMessage.value = 'No se pudieron cargar las preguntas desde el servidor.';
    }
});

const handleSubmit = async () => {
  loading.value = true;
  errorMessage.value = '';
  try {
   
    const token = localStorage.getItem('access_token');
    
    if (token) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
    }

    
    const response = await axios.post('/api/user/security-setup', form.value);
    
    
    emit('setup-success', response.data.user);
  } catch (error) {
    console.error("Error al guardar:", error);
    if (error.response && error.response.status === 401) {
      errorMessage.value = 'Tu sesión expiró o no tienes autorización perimetral.';
    } else if (error.response && error.response.data && error.response.data.message) {
      errorMessage.value = error.response.data.message;
    } else {
      errorMessage.value = 'Ocurrió un error al procesar la solicitud.';
    }
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.min-vh-75 {
    min-vh: 75vh;
}

.extra-small {
    font-size: 0.75rem;
}

.card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
}
</style>