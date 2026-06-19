<template>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow custom-navbar">
    <div class="container-fluid px-4">
      <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
        <span class="me-2">🎫</span> Helpdesk OS
      </a>

      <div class="d-flex flex-column align-items-center text-light small ms-3">
        <span class="fw-bold">{{ userData ? userData.name : 'Cargando...' }}</span>
        <span class="badge bg-secondary font-monospace text-capitalize" style="font-size: 0.65rem; margin-top: 2px;">
          {{ role === 'agent' ? 'Técnico' : role }}
        </span>
      </div>

      <div class="collapse navbar-collapse" id="navbarContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0"></ul>
        <div class="d-flex align-items-center gap-2">

          <button v-if="role !== 'agent'" class="btn btn-success btn-sm fw-bold px-3"
            @click="openModal('mainTicketModal')">
            ➕ Nuevo Ticket
          </button>

          <button v-if="role === 'admin'" class="btn btn-outline-info btn-sm fw-bold px-3"
            @click="openModal('createUserModal')">
            👤 <span>Nuevo Usuario</span>
          </button>

          <div class="vr mx-2 text-light opacity-25 d-none d-lg-block" style="height: 24px;"></div>

          <button class="btn btn-outline-danger btn-sm fw-bold px-2" title="Cerrar Sesión" @click="handleLogout">
            🚪 <span class="d-none d-md-inline">Salir</span>
          </button>
        </div>
      </div>
    </div>
  </nav>

  <div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="text-primary fw-bold">🎫 Panel de Incidencias (Helpdesk)</h2>
    </div>

    <div v-if="role === 'admin'" class="alert alert-info border-0 shadow-sm mb-4">
      Conectado como <strong>Administrador</strong>.
    </div>

    <div v-if="role === 'admin' && technicians.length > 0" class="container-fluid mb-4">
      <WorkloadChart :tickets="tickets" :technicians="technicians" />
    </div>

    <div v-if="loading" class="text-center my-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Cargando...</span>
      </div>
    </div>

    <div v-else class="row g-4">

      <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-dark fw-bold">🚀 Incidencias Activas</h5>
            <span class="badge bg-primary rounded-pill">
              {{tickets.filter(t => t.status !== 'resuelto').length}} Pendientes
            </span>
          </div>

          <div class="card-body scrollable-column bg-light-subtle">
            <div class="row row-cols-1 row-cols-md-2 g-3">
              <template v-for="ticket in tickets" :key="ticket.id">
                <div v-if="ticket.status !== 'resuelto'" class="col">
                  <div class="card h-100 border-0 shadow-sm ticket-card" :class="getBorderClass(ticket.priority)">
                    <div class="card-body d-flex flex-column justify-content-between">
                      <div>
                        <div class="d-flex justify-content-between align-items-start mb-2">
                          <h6 class="card-title fw-bold text-dark mb-0 text-truncate" style="max-width: 80%;">
                            {{ ticket.title }}
                          </h6>
                          <span :class="getBadgeClass(ticket.status)">
                            {{ ticket.status === 'en_progreso' ? 'En Progreso' : 'Abierto' }}
                          </span>
                        </div>
                        <p class="card-text text-muted small text-clamp-2 mb-3">
                          {{ ticket.description }}
                        </p>
                      </div>

                      <div class="pt-2 border-top text-muted small">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <span>👤 {{ ticket.client ? ticket.client.name : 'Anónimo' }}</span>
                          <span v-if="ticket.technician_id" class="text-primary fw-bold">
                            ⚙️ {{ ticket.technician ? ticket.technician.name : 'Asignado' }}
                          </span>
                        </div>

                        <div class="mt-3 d-flex justify-content-end gap-2">
                          <button v-if="ticket.status === 'abierto' && role === 'agent'"
                            class="btn btn-primary btn-sm fw-bold" @click="claimTicket(ticket.id)">
                            🛠️ Tomar Tarea
                          </button>
                          <button
                            v-if="ticket.status === 'en_progreso' && role === 'agent' && ticket.technician_id === userData?.id"
                            class="btn btn-success btn-sm fw-bold" @click="completeTicket(ticket.id)">
                            ✓ Finalizar Incidencia
                          </button>
                          <span v-if="ticket.status === 'en_progreso' && ticket.technician_id !== userData?.id"
                            class="badge bg-light text-dark border font-monospace">
                            👨‍💻 En proceso por: {{ ticket.technician?.name }}
                          </span>
                        </div>

                      </div>
                    </div>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
          <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0 text-secondary fw-bold">✅ Historial Resueltos</h5>
          </div>
          <div class="card-body scrollable-column bg-light py-2">
            <div class="list-group list-group-flush">
              <div v-for="ticket in completedTicketsOrdered" :key="ticket.id"
                class="list-group-item bg-white border-0 shadow-sm rounded mb-2 p-3 border-start border-success border-3">
                <div class="fw-bold text-dark text-truncate">{{ ticket.title }}</div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                  <small class="text-muted">👤 {{ ticket.client ? ticket.client.name : 'Cliente' }}</small>
                  <small class="text-success font-monospace" style="font-size: 0.75rem;">Resuelto ✓</small>
                </div>
                <div  class="text-secondary mt-1" style="font-size: 0.9rem;">
                  <small class="text-muted">⚙️ {{ ticket.technician?.name }}</small>
                </div>
                <div v-if="ticket.resolved_at" class="text-secondary mt-1" style="font-size: 0.7rem;">
                  🏁 <strong>Finalizado:</strong> {{ formatDateTime(ticket.resolved_at) }}
                </div>
                <div v-if="ticket.started_at && ticket.resolved_at" class="text-muted small mt-1"
                  style="font-size: 0.7rem;">
                  ⏱️ <strong>Tiempo:</strong> {{ calculateDuration(ticket.started_at, ticket.resolved_at) }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>

  <CreateTicketModal id="mainTicketModal" @ticket-created="(newTicket) => tickets.unshift(newTicket)" />
  <CreateUserModal @user-created="(userRole) => { if (userRole === 'agent') fetchTechnicians(); }" />
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue';
import axios from 'axios';
import WorkloadChart from './WorkloadChart.vue';
import CreateTicketModal from './CreateTicketModal.vue';
import CreateUserModal from './CreateUserModal.vue';
import { Modal } from 'bootstrap';

const tickets = ref([]);
const technicians = ref([]);
const loading = ref(true);
const role = ref('');
const userData = ref(null);

const openModal = (modalId) => {
  const modalElement = document.getElementById(modalId);
  if (modalElement) {
    const modalInstance = new Modal(modalElement);
    modalInstance.show();
  } else {
    console.error(`No se encontró ningún modal con el id: ${modalId}`);
  }
};

const emit = defineEmits(['logout-trigger']);

// RENDIMIENTO COMPUTADO
const completedTicketsOrdered = computed(() => {
  return tickets.value
    .filter(t => t.status === 'resuelto')
    .sort((a, b) => new Date(b.resolved_at || 0) - new Date(a.resolved_at || 0));
});

// ACCIONES API
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
    console.error("Error al cargar los técnicos:", error);
  }
};

const claimTicket = async (id) => {
  try {
    const response = await axios.post(`/api/tickets/${id}/claim`);
    const index = tickets.value.findIndex(t => t.id === id);
    if (index !== -1) tickets.value[index] = response.data;
  } catch (error) {
    console.error("Error al reclamar el ticket:", error);
  }
};

const completeTicket = async (ticketId) => {
  try {
    const response = await axios.put(`/api/tickets/${ticketId}/complete`);
    alert(response.data.message);
    const index = tickets.value.findIndex(t => t.id === ticketId);
    if (index !== -1) {
      tickets.value[index].status = 'resuelto';
      tickets.value[index].resolved_at = new Date().toISOString();
    }
  } catch (error) {
    console.error("Error al completar el ticket:", error);
  }
};

// WEBSOCKETS (LARAVEL ECHO)
const listenForTickets = () => {
  window.Echo.channel('tickets-channel')
    .listen('.ticket.created', (e) => {
      if (role.value === 'admin' || role.value === 'agent' || (role.value === 'client' && e.ticket.client_id === userData.value?.id)) {
        tickets.value.unshift(e.ticket);
      }
    })
    .listen('.ticket.updated', (e) => {
      const index = tickets.value.findIndex(t => t.id === e.ticket.id);
      if (index !== -1) tickets.value[index] = e.ticket;
    })
    .listen('.ticket.completed', (e) => {
      if (role.value === 'agent') {
        tickets.value = tickets.value.filter(t => t.id !== e.ticket.id);
      } else {
        const index = tickets.value.findIndex(t => t.id === e.ticket.id);
        if (index !== -1) tickets.value[index] = e.ticket;
      }
    });
};

// HELPERS
const calculateDuration = (started, resolved) => {
  if (!started || !resolved) return 'N/A';
  const diffMins = Math.round((new Date(resolved) - new Date(started)) / 60000);
  return diffMins < 60 ? `${diffMins} min` : `${Math.floor(diffMins / 60)}h ${diffMins % 60}m`;
};

const formatDateTime = (dateString) => {
  if (!dateString) return '';
  return new Intl.DateTimeFormat('es-VE', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit', hour12: true
  }).format(new Date(dateString));
};

const getBorderClass = (priority) => {
  const p = priority?.toLowerCase() || '';
  if (p === 'crítica' || p === 'critica') return 'border-priority-critica';
  if (p === 'alta') return 'border-priority-alta';
  if (p === 'media') return 'border-priority-media';
  return 'border-priority-baja';
};

const getBadgeClass = (status) => {
  return status === 'en_progreso'
    ? 'badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill'
    : 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill';
};

const handleLogout = () => emit('logout-trigger');

// LIFECYCLE
onMounted(() => {
  const rawData = localStorage.getItem('user_data');
  if (rawData) {
    userData.value = JSON.parse(rawData);
    role.value = userData.value.role;
  }
  fetchTickets();
  listenForTickets();
  fetchTechnicians();
});

onUnmounted(() => {
  window.Echo.leaveChannel("tickets-channel");
});
</script>

<style scoped>
.scrollable-column {
  max-height: 75vh;
  overflow-y: auto;
  padding-right: 8px;
}

.text-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.ticket-card {
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.ticket-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
}

.border-priority-critica {
  border-left: 4px solid #dc3545 !important;
}

.border-priority-alta {
  border-left: 4px solid #fd7e14 !important;
}

.border-priority-media {
  border-left: 4px solid #ffc107 !important;
}

.border-priority-baja {
  border-left: 4px solid #0dcaf0 !important;
}

.custom-navbar {
  position: sticky;
  top: 0;
  z-index: 1030;
  backdrop-filter: blur(8px);
  background-color: #1e293b !important;
}
</style>