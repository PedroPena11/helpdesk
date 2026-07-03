<template>
  <div class="container d-flex justify-content-center align-items-center min-vh-100">
    <div class="card shadow-lg border-0 rounded-3" style="width: 100%; max-width: 420px;">
      <div class="card-body p-5">
        <div class="text-center mb-4">
          <div class="badge bg-primary p-3 rounded-circle mb-3">
            <i class="bi bi-shield-lock-fill fs-3 text-white"></i>
          </div>
          <h3 class="fw-bold text-dark">Panel de Soporte</h3>
          <p class="text-muted small">Introduce tus credenciales</p>
        </div>

        <div v-if="errorMessage" class="alert alert-danger d-flex align-items-center py-2 small" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>
          <div>{{ errorMessage }}</div>
        </div>

        <form @submit.prevent="handleLogin">
          <div class="form-floating mb-3">
            <input type="email" v-model="form.email" class="form-control" id="emailInput"
              placeholder="nombre@ejemplo.com" required :disabled="loading">
            <label for="emailInput">Correo Electrónico</label>
          </div>

          <div class="form-floating mb-2">
            <input type="password" v-model="form.password" class="form-control" id="passwordInput"
              placeholder="Contraseña" required :disabled="loading">
            <label for="passwordInput">Contraseña</label>
          </div>

          <div class="mb-4 text-end">
            <button type="button" class="btn btn-link p-0 small text-decoration-none" data-bs-toggle="modal"
              data-bs-target="#recoveryModal" @click="resetRecoveryState">
              ¿Olvidaste tu contraseña?
            </button>
          </div>

          <button type="submit"
            class="btn btn-primary w-100 py-2.5 fw-bold d-flex justify-content-center align-items-center"
            :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            {{ loading ? 'Verificando seguridad...' : 'Iniciar Sesión' }}
          </button>
        </form>

        <div class="modal fade" id="recoveryModal" tabindex="-1" aria-labelledby="recoveryModalLabel"
          aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">

              <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold" id="recoveryModalLabel">
                  <i class="bi bi-shield-exclamation me-2 text-warning"></i>Recuperación de Cuenta
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"
                  id="closeRecoveryModal"></button>
              </div>

              <div class="modal-body p-4 text-start">

                <div v-if="recoveryAlert"
                  :class="['alert py-2 rounded-3 small mb-3', isRecoveryError ? 'alert-danger' : 'alert-success']">
                  {{ recoveryAlert }}
                </div>

                <div v-if="recoveryStep === 1">
                  <p class="text-muted small">Ingresa tu correo electrónico institucional para buscar tus métodos de
                    recuperación activos en PostgreSQL.</p>
                  <div class="mb-4">
                    <label class="form-label small fw-bold text-secondary">Correo Electrónico</label>
                    <input type="email" v-model="recoveryEmail" class="form-control" placeholder="correo@ejemplo.com"
                      required :disabled="recoveryLoading">
                  </div>

                  <div class="row g-2">
                    <div class="col-6">
                      <button type="button" class="btn btn-outline-primary w-100 py-2 small fw-bold"
                        @click="initiateEmailRecovery" :disabled="recoveryLoading || !recoveryEmail">
                        📧 Enviar Correo
                      </button>
                    </div>
                    <div class="col-6">
                      <button type="button" class="btn btn-dark w-100 py-2 small fw-bold" @click="fetchSecurityQuestion"
                        :disabled="recoveryLoading || !recoveryEmail">
                        ❓ Usar Pregunta
                      </button>
                    </div>
                  </div>
                </div>

                <div v-if="recoveryStep === 2">
                  <div class="bg-light p-3 rounded-3 mb-3 border border-primary-subtle">
                    <span class="text-uppercase font-monospace text-primary d-block small fw-bold mb-1">Pregunta del Sistema:</span>
                    <strong class="text-dark">{{ challengerQuestion }}</strong>
                  </div>

                  <form @submit.prevent="handleQuestionReset">
                    <div class="mb-3">
                      <label class="form-label small fw-bold text-secondary">Tu Respuesta Secreta</label>
                      <input type="text" v-model="questionAnswer" class="form-control"
                        placeholder="Escribe tu respuesta..." required autocomplete="off" :disabled="recoveryLoading">
                    </div>
                    <hr class="opacity-25">
                    <div class="mb-3">
                      <label class="form-label small fw-bold text-secondary">Nueva Contraseña</label>
                      <input type="password" v-model="newPasswordForm.password" class="form-control"
                        placeholder="Mínimo 8 caracteres" required minlength="8" :disabled="recoveryLoading">
                    </div>
                    <div class="mb-4">
                      <label class="form-label small fw-bold text-secondary">Confirmar Nueva Contraseña</label>
                      <input type="password" v-model="newPasswordForm.password_confirmation" class="form-control"
                        placeholder="Repite la contraseña" required :disabled="recoveryLoading">
                    </div>

                    <div class="d-flex gap-2">
                      <button type="button" class="btn btn-light border small fw-bold px-3" @click="recoveryStep = 1"
                        :disabled="recoveryLoading">Atrás</button>
                      <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" :disabled="recoveryLoading">
                        <span v-if="recoveryLoading" class="spinner-border spinner-border-sm me-1"></span>
                        Reestablecer Contraseña
                      </button>
                    </div>
                  </form>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';


const emit = defineEmits(['auth-success']);

const form = ref({
  email: '',
  password: ''
});
const loading = ref(false);
const errorMessage = ref('');


const recoveryStep = ref(1);
const recoveryLoading = ref(false);
const recoveryEmail = ref('');
const recoveryAlert = ref('');
const isRecoveryError = ref(false);

const challengerQuestion = ref('');
const questionAnswer = ref('');
const newPasswordForm = ref({
  password: '',
  password_confirmation: ''
});

const resetRecoveryState = () => {
  recoveryStep.value = 1;
  recoveryEmail.value = '';
  recoveryAlert.value = '';
  isRecoveryError.value = false;
  challengerQuestion.value = '';
  questionAnswer.value = '';
  newPasswordForm.value = { password: '', password_confirmation: '' };
};


const initiateEmailRecovery = async () => {
  recoveryLoading.value = true;
  recoveryAlert.value = '';
  try {
    const response = await axios.post('/api/forgot-password', { email: recoveryEmail.value });
    isRecoveryError.value = false;
    recoveryAlert.value = response.data.message;
    recoveryEmail.value = '';
  } catch (error) {
    isRecoveryError.value = true;
    recoveryAlert.value = error.response?.data?.message || 'Error al procesar la solicitud de correo.';
  } finally {
    recoveryLoading.value = false;
  }
};


const fetchSecurityQuestion = async () => {
  recoveryLoading.value = true;
  recoveryAlert.value = '';
  try {
    const response = await axios.post('/api/recovery/get-question', { email: recoveryEmail.value });
    challengerQuestion.value = response.data.question;
    isRecoveryError.value = false;
    recoveryStep.value = 2; 
  } catch (error) {
    isRecoveryError.value = true;
    recoveryAlert.value = error.response?.data?.message || 'El usuario no posee preguntas registradas.';
  } finally {
    recoveryLoading.value = false;
  }
};


const handleQuestionReset = async () => {
  if (newPasswordForm.value.password !== newPasswordForm.value.password_confirmation) {
    isRecoveryError.value = true;
    recoveryAlert.value = 'La confirmación de la contraseña no coincide.';
    return;
  }

  recoveryLoading.value = true;
  recoveryAlert.value = '';
  try {
    const payload = {
      email: recoveryEmail.value,
      answer: questionAnswer.value,
      password: newPasswordForm.value.password,
      password_confirmation: newPasswordForm.value.password_confirmation
    };

    const response = await axios.post('/api/recovery/reset-password', payload);
    isRecoveryError.value = false;
    recoveryAlert.value = response.data.message;
    
    setTimeout(() => {
      const closeBtn = document.getElementById('closeRecoveryModal');
      if (closeBtn) closeBtn.click();
      resetRecoveryState();
    }, 2500);

  } catch (error) {
    isRecoveryError.value = true;
    recoveryAlert.value = error.response?.data?.message || 'Respuesta incorrecta o error de validación.';
  } finally {
    recoveryLoading.value = false;
  }
};


const handleLogin = async () => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const response = await axios.post('/api/login', form.value, {
      headers: { 'Accept': 'application/json' }
    });

    console.log("Acceso autorizado criptográficamente.");
    emit('auth-success', response.data);

  } catch (error) {
    console.error("Fallo en la autenticación:", error);
    if (error.response && error.response.data) {
      errorMessage.value = error.response.data.message || 'Credenciales inválidas.';
    } else {
      errorMessage.value = 'No se pudo conectar con el servidor de seguridad.';
    }
  } finally {
    loading.value = false;
  }
};
</script>