<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
  id: { type: String, default: 'createTicketModal' }
});

const emit = defineEmits(['ticket-created']);

const ticketLoading = ref(false);
const ticketForm = ref({ title: '', description: '', priority: '' });

const handleCreateTicket = async () => {
  ticketLoading.value = true;
  try {
    const response = await axios.post('/api/tickets', ticketForm.value);
    alert(response.data.message);

    if (response.data.ticket) {
      emit('ticket-created', response.data.ticket);
    }

    ticketForm.value = { title: '', description: '', priority: '' };

    const modalBtnClose = document.querySelector(`#${props.id} .btn-close`);
    if (modalBtnClose) modalBtnClose.click();

  } catch (error) {
    console.error("Error al crear el ticket:", error);
    alert(error.response?.data?.message || "Hubo un error al registrar la incidencia.");
  } finally {
    ticketLoading.value = false;
  }
};
</script>

<template>
  <div class="modal fade" :id="id" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content border-0 shadow">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title fw-bold">🎫 Crear Nueva Incidencia</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-start">
          <form @submit.prevent="handleCreateTicket">
            <div class="mb-3">
              <label class="form-label font-medium">Asunto / Título del Problema</label>
              <input v-model="ticketForm.title" type="text" class="form-control" required placeholder="Ej. El switch de red no enciende">
            </div>
            <div class="mb-3">
              <label class="form-label font-medium">Descripción Detallada del Fallo</label>
              <textarea v-model="ticketForm.description" class="form-control" rows="4" required placeholder="Describe detalladamente qué ocurre con el equipo..."></textarea>
            </div>
            <div class="mb-3">
              <label class="form-label font-medium">Nivel de Prioridad</label>
              <select v-model="ticketForm.priority" class="form-select" required>
                <option value="">-- Seleccionar Prioridad --</option>
                <option value="baja">🟢 Baja</option>
                <option value="media">🟡 Media</option>
                <option value="alta">🟠 Alta</option>
                <option value="critica">🔴 Crítica</option>
              </select>
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-success" :disabled="ticketLoading">
                <span v-if="ticketLoading" class="spinner-border spinner-border-sm me-1"></span>
                Reportar Incidencia
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>