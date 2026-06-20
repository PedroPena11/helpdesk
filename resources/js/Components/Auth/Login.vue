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

          <div class="form-floating mb-4">
            <input type="password" v-model="form.password" class="form-control" id="passwordInput"
              placeholder="Contraseña" required :disabled="loading">
            <label for="passwordInput">Contraseña</label>
          </div>

          <button type="submit"
            class="btn btn-primary w-100 py-2.5 fw-bold d-flex justify-content-center align-items-center"
            :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            {{ loading ? 'Verificando seguridad...' : 'Iniciar Sesión' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const form = ref({
  email: '',
  password: ''
});
const loading = ref(false);
const errorMessage = ref('');

const emit = defineEmits(['auth-success']);

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

<style scoped>
.container {
  background-color: #f8f9fa;
}

.btn-primary {
  transition: all 0.2s ease-in-out;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
}
</style>