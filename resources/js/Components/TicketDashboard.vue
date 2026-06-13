<template>
  <div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="text-primary fw-bold">🎫 Panel de Incidencias (Helpdesk)</h2>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createTicketModal">
        + Nuevo Ticket
      </button>
    </div>

    <div v-if="loading" class="text-center my-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
    </div>

    <div v-else class="row">
      <div v-for="ticket in tickets" :key="ticket.id" class="col-md-4 mb-4">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-header d-flex justify-content-between align-items-center bg-light">
            <span :class="priorityClass(ticket.priority)" class="badge text-uppercase">
              {{ ticket.priority }}
            </span>
            <span :class="statusClass(ticket.status)" class="badge rounded-pill">
              {{ ticket.status }}
            </span>
          </div>
          <div class="card-body">
            <h5 class="card-title fw-bold text-dark">{{ ticket.title }}</h5>
            <p class="card-text text-muted text-truncate-3">{{ ticket.description }}</p>
          </div>
          <div class="card-footer bg-transparent border-0 text-muted small d-flex justify-content-between">
            <span>👤 {{ ticket.client ? ticket.client.name : 'Cliente Anonimo' }}</span>
            <span>📅 {{ formatDate(ticket.created_at) }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'; 
import axios from 'axios';

const tickets = ref([]);
const loading = ref(true);

const fetchTickets = async () => {
  try {
    const response = await axios.get('/api/tickets');
    tickets.value = response.data;
  } catch (error) {
    console.error("Error al cargar los tickets:", error);
  } finally {
    loading.value = false;
  }
};

const listenForTickets = () => {
  window.Echo.channel('tickets-channel') 
    .listen('.ticket.created', (e) => {  
      console.log("¡Nuevo ticket recibido por WebSockets!", e.ticket);
      
     
      tickets.value.unshift(e.ticket);
    });
};


const priorityClass = (priority) => {
  return {
    'bg-danger text-white': priority === 'critica' || priority === 'alta',
    'bg-warning text-dark': priority === 'media',
    'bg-info text-dark': priority === 'baja',
  };
};

const statusClass = (status) => {
  return {
    'bg-secondary': status === 'abierto',
    'bg-primary': status === 'en_progreso',
    'bg-success': status === 'resuelto',
  };
};

const formatDate = (dateString) => {
  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return new Date(dateString).toLocaleDateString('es-ES', options);
};

onMounted(() => {
  fetchTickets();
  listenForTickets();
});

onUnmounted(()=>{
  window.Echo.leaveChannel("tickets-channel");
})

</script>

<style scoped>
.text-truncate-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;  
  overflow: hidden;
}
</style>