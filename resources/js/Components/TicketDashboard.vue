<template>
  <div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="text-primary fw-bold"> Panel de Incidencias (Helpdesk)</h2>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createTicketModal">
        + Nuevo Ticket
      </button>
    </div>

    <div v-if="role === 'admin'" class="alert alert-info border-0 shadow-sm mb-4">
      Conectado como <strong>Administrador</strong>.
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

            <div v-if="role === 'admin' && ticket.status.toLowerCase() === 'abierto'" class="mt-3 pt-3 border-top">
              <label class="form-label small fw-bold text-secondary">Asignar Técnico:</label>
              <select 
                @change="assignTechnician(ticket.id, $event)" 
                class="form-select form-select-sm"
              >
                <option value="">-- Seleccionar Técnico --</option>
                <option v-for="tech in technicians" :key="tech.id" :value="tech.id">
                  {{ tech.name }}
                </option>
              </select>
            </div>
          </div>
          <div class="card-footer bg-transparent border-0 text-muted small d-flex justify-content-between">
            <span>👤 {{ ticket.client ? ticket.client.name : 'Cliente Anónimo' }}</span>
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


const role = ref('');
const userData = ref(null);
const technicians = ref([]);

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


const fetchTechnicians = async () => {
  try {
    const response = await axios.get('/api/admin/technicians');
    technicians.value = response.data;
  } catch (error) {
    console.error("Error al cargar los técnicos en el frontend:", error);
  }
};

const assignTechnician = async (ticketId, event) => {
  const selectedTechId = event.target.value;
  if (!selectedTechId) return;

  try {
    const response = await axios.post(`/api/admin/tickets/${ticketId}/assign`, {
      technician_id: selectedTechId
    });

    console.log("Asignación exitosa:", response.data.message);

    const index = tickets.value.findIndex(t => t.id === ticketId);
    if (index !== -1) {
      tickets.value[index].technician_id = selectedTechId;
      tickets.value[index].status = 'en_progreso';
    }

  } catch (error) {
    console.error("Error al asignar técnico:", error);
    alert(error.response?.data?.message || "Error al procesar la asignación.");
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
  const rawData = localStorage.getItem('user_data');
  if (rawData) {
    userData.value = JSON.parse(rawData);
    role.value = userData.value.role; 
  }

  fetchTickets();
  listenForTickets();

  if (role.value === 'admin') {
    fetchTechnicians();
  }
});

onUnmounted(()=>{
  window.Echo.leaveChannel("tickets-channel");
});
</script>

<style scoped>
.text-truncate-3 {
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;  
  overflow: hidden;
}
</style>