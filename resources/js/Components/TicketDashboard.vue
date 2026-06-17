<template>

  <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow custom-navbar">
    <div class="container-fluid px-4">
      <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
        <span class="me-2">🎫</span> Helpdesk OS
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
        <span class="navbar-toggler-line"></span>
      </button>

      <div class="d-flex flex-column align-items-center text-light small ms-3">
        <span class="fw-bold">
          {{ userData ? userData.name : 'Cargando...' }}
        </span>

        <span class="badge bg-secondary font-monospace text-capitalize" style="font-size: 0.65rem; margin-top: 2px;">
          {{ role === 'agent' ? 'Técnico' : role }}
        </span>
      </div>

      <div class="collapse navbar-collapse" id="navbarContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0"></ul>

        <div class="d-flex align-items-center gap-2">

          <button v-if="role !== 'agent'" class="btn btn-success btn-sm fw-bold px-3 d-flex align-items-center gap-1"
            @click="openTicketModal">
            ➕ <span>Nuevo Ticket</span>
          </button>

          <button v-if="role === 'admin'"
            class="btn btn-outline-info btn-sm fw-bold px-3 d-flex align-items-center gap-1" @click="openUserModal">
            👤 <span>Nuevo Usuario</span>
          </button>

          <div class="vr mx-2 text-light opacity-25 d-none d-lg-block" style="height: 24px;"></div>


          <button class="btn btn-outline-danger btn-sm fw-bold d-flex align-items-center gap-1 px-2"
            title="Cerrar Sesión" @click="handleLogout">
            🚪 <span class="d-none d-md-inline">Salir</span>
          </button>

        </div>
      </div>
    </div>
  </nav>

  <div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="text-primary fw-bold">🎫 Panel de Incidencias (Helpdesk)</h2>

      <div>
        <button v-if="role === 'admin'" class="btn btn-outline-primary me-2" @click="openUserModal">
          👥 Nuevo Usuario
        </button>

        <button v-if="role !== 'agent'" class="btn btn-success" data-bs-toggle="modal"
          data-bs-target="#createTicketModal">
          + Nuevo Ticket

        </button>
        <div class="modal fade" id="createTicketModal" tabindex="-1" aria-labelledby="createTicketModalLabel"
          aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
              <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold" id="createTicketModalLabel">🎫 Crear Nueva Incidencia</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                  aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form @submit.prevent="handleCreateTicket">
                  <div class="mb-3">
                    <label class="form-label font-medium">Asunto / Título del Problema</label>
                    <input v-model="ticketForm.title" type="text" class="form-control" required
                      placeholder="Ej. El switch de red no enciende">
                  </div>
                  <div class="mb-3">
                    <label class="form-label font-medium">Descripción Detallada del Fallo</label>
                    <textarea v-model="ticketForm.description" class="form-control" rows="4" required
                      placeholder="Describe detalladamente qué ocurre con el equipo..."></textarea>
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
      </div>
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
      <div class="container-fluid px-4 py-3">

        <div class="row g-4">

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

                            <div v-if="role === 'admin' && ticket.status?.toLowerCase() === 'abierto'" class="mt-2">
                              <select class="form-select form-select-sm" @change="assignTechnician(ticket.id, $event)">
                                <option value="">Asignar técnico...</option>
                                <option v-for="tech in technicians" :key="tech.id" :value="tech.id">
                                  {{ tech.name }}
                                </option>
                              </select>
                            </div>

                            <div v-if="role === 'agent' && ticket.status === 'en_progreso'" class="mt-2 text-end">
                              <button class="btn btn-sm btn-success w-100 fw-bold" @click="completeTicket(ticket.id)">
                                ✓ Marcar como Resuelto
                              </button>
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

              <div class="card-body scrollable-column bg-light text-center py-2">
                <div class="list-group list-group-flush">

                 

                        <div v-for="ticket in completedTicketsOrdered" :key="ticket.id"
                          class="list-group-item bg-white border-0 shadow-sm rounded mb-2 p-3 text-start border-start border-success border-3 animate-fade-in">

                          <div class="fw-bold text-dark text-truncate">{{ ticket.title }}</div>

                          <div class="d-flex justify-content-between align-items-center mt-1">
                            <small class="text-muted">👤 {{ ticket.client ? ticket.client.name : 'Cliente' }}</small>
                            <small class="text-success font-monospace" style="font-size: 0.75rem;">Resuelto ✓</small>
                          </div>

                          <div v-if="ticket.resolved_at" class="text-secondary mt-1" style="font-size: 0.7rem;">
                            🏁 <strong>Finalizado:</strong> {{ formatDateTime(ticket.resolved_at) }}
                          </div>

                        </div>

                        <div v-if="completedTicketsOrdered.length === 0" class="text-muted py-5 small">
                          No hay tareas completadas en este ciclo.
                        </div>

                      

                  <div v-if="!tickets.some(t => t.status === 'resuelto')" class="text-muted py-5 small">
                    No hay tareas completadas en este ciclo.
                  </div>

                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

      <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel"
        aria-hidden="true" ref="createUserModalRef">
        <div class="modal-dialog">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title fw-bold" id="createUserModalLabel">👥 Registrar Nuevo Usuario</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form @submit.prevent="handleCreateUser">
                <div class="mb-3">
                  <label class="form-label font-medium">Nombre Completo</label>
                  <input v-model="userForm.name" type="text" class="form-control" required placeholder="Ej. Juan Pérez">
                </div>
                <div class="mb-3">
                  <label class="form-label font-medium">Correo Electrónico</label>
                  <input v-model="userForm.email" type="email" class="form-control" required
                    placeholder="correo@ejemplo.com">
                </div>
                <div class="mb-3">
                  <label class="form-label font-medium">Contraseña de Seguridad</label>
                  <input v-model="userForm.password" type="password" class="form-control" required
                    placeholder="Mínimo 6 caracteres">
                </div>
                <div class="mb-3">
                  <label class="form-label font-medium">Rol del Usuario</label>
                  <select v-model="userForm.role" class="form-select" required>
                    <option value="">-- Seleccionar Rol --</option>
                    <option value="client">Cliente (Usuario Regular)</option>
                    <option value="agent">Técnico (Soporte)</option>
                    <option value="admin">Administrador (Control Total)</option>
                  </select>
                </div>
                <div class="d-flex justify-content-end gap-2 mt-4">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  <button type="submit" class="btn btn-primary" :disabled="userLoading">
                    <span v-if="userLoading" class="spinner-border spinner-border-sm me-1"></span>
                    Guardar Usuario
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="modal fade" id="createTicketModal" tabindex="-1" aria-labelledby="createTicketModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
              <h5 class="modal-title fw-bold" id="createTicketModalLabel">🎫 Crear Nueva Incidencia</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <p class="text-muted">Formulario en desarrollo...</p>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, computed, onUnmounted } from 'vue';
import axios from 'axios';
import * as bootstrap from 'bootstrap';

// ==========================================
// ESTADOS REACTIVOS Y VARIABLES GLOBALES
// ==========================================
const tickets = ref([]);
const technicians = ref([]);
const loading = ref(true);

const ticketLoading = ref(false);

//Creacion de Tickets

const ticketForm = ref({
  title: '',
  description: '',
  priority: ''
});


const createTicketModalRef = ref(null);
let instanceTicketModal = null;


const role = ref('');
const userData = ref(null);


const userLoading = ref(false);
const userForm = ref({
  name: '',
  email: '',
  password: '',
  role: ''
});


const createUserModalRef = ref(null);
let instanceUserModal = null;


// ==========================================
// FLUJO DE GESTIÓN DE USUARIOS (ADMIN)
// ==========================================


const openUserModal = () => {
  if (!instanceUserModal && createUserModalRef.value) {
    instanceUserModal = new bootstrap.Modal(createUserModalRef.value);
  }
  if (instanceUserModal) {
    instanceUserModal.show();
  }
};


const handleCreateUser = async () => {
  userLoading.value = true;
  try {
    const response = await axios.post('/api/admin/users', userForm.value);
    alert(response.data.message);


    if (userForm.value.role === 'agent') {
      fetchTechnicians();
    }


    userForm.value = { name: '', email: '', password: '', role: '' };


    if (instanceUserModal) {
      instanceUserModal.hide();
    }

  } catch (error) {
    console.error("Error al crear usuario:", error);
    alert(error.response?.data?.message || "Hubo un error al procesar el registro.");
  } finally {
    userLoading.value = false;
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


// ==========================================
//  FLUJO DE GESTIÓN DE TICKETS
// ==========================================


const completedTicketsOrdered = computed(() => {
  return tickets.value
    .filter(t => t.status === 'resuelto')
    .sort((a, b) => {
      if (!a.resolved_at) return 1;
      if (!b.resolved_at) return -1;

      return new Date(b.resolved_at) - new Date(a.resolved_at);

    });
});


// Trae todos los tickets del sistema
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

const completeTicket = async (ticketId) => {
  try {
    const response = await axios.put(`/api/tickets/${ticketId}/complete`);
    alert(response.data.message);

    const index = tickets.value.findIndex(t => t.id === ticketId);
    if (index !== -1) {
      tickets.value[index].status = 'resuelto';
    }
  } catch (error) {
    console.error("Error al completar el ticket:", error);
    alert(error.response?.data?.message || "No se pudo actualizar el estado.");
  }
}

// Asigna un técnico específico a una incidencia
const assignTechnician = async (ticketId, event) => {
  const selectedTechId = event.target.value;
  if (!selectedTechId) return;

  const currentTech = technicians.value.find(t => t.id == selectedTechId);

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

    tickets.value[index].technician = {
      name: currentTech ? currentTech.name : 'Tecnico'
    };

  } catch (error) {
    console.error("Error al asignar técnico:", error);
    alert(error.response?.data?.message || "Error al procesar la asignación.");
  }
};


const handleCreateTicket = async () => {
  ticketLoading.value = true;
  try {
    const response = await axios.post('/api/tickets', ticketForm.value);

    console.log("Respuesta server: ", response.data);

    alert(response.data.message);

    if (response.data.ticket) {
      tickets.value.unshift(response.data.ticket);
    }

    ticketForm.value = { title: '', description: '', priority: '' };

    const modalElement = document.getElementById('createTicketModal');
    const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
    if (modal) modal.hide();

  } catch (error) {
    console.error("Error al crear el ticket:", error);
    alert(error.response?.data?.message || "Hubo un error al registrar la incidencia.");
  } finally {
    ticketLoading.value = false;
  }
};



// ==========================================
// WEBSOCKETS (LARAVEL ECHO)
// ==========================================
const listenForTickets = () => {
  window.Echo.channel('tickets-channel')
    .listen('.ticket.created', (e) => {
      console.log("Ticket recibido por WS:", e.ticket);


      if (role.value === 'admin') {
        tickets.value.unshift(e.ticket);
      } else if (role.value === 'client' && userData.value && e.ticket.client_id === userData.value.id) {
        tickets.value.unshift(e.ticket);
      }
    })


    .listen('.ticket.assigned', (e) => {
      console.log("Asignación recibida por WS:", e.ticket);

      if (role.value === 'agent') {

        if (userData.value && e.ticket.technician_id === userData.value.id) {
          const exists = tickets.value.some(t => t.id === e.ticket.id);
          if (!exists) tickets.value.unshift(e.ticket);
        } else {

          tickets.value = tickets.value.filter(t => t.id !== e.ticket.id);
        }
      } else if (role.value === 'admin' || (role.value === 'client' && userData.value && e.ticket.client_id === userData.value.id)) {
        const index = tickets.value.findIndex(t => t.id === e.ticket.id);
        if (index !== -1) {
          tickets.value[index] = e.ticket;
        }
      }
    })

    .listen('.ticket.completed', (e) => {
      console.log("Ticket completado recibido por WS:", e.ticket);

      if (role.value === 'agent') {
        tickets.value = tickets.value.filter(t => t.id !== e.ticket.id);
      } else {
        const index = tickets.value.findIndex(t => t.id === e.ticket.id);
        if (index !== -1) {
          tickets.value[index] = e.ticket;
        }
      }
    });

};


// ==========================================
// HELPERS Y ESTILOS VISUALES
// ==========================================

const formatDateTime = (dateString) => {
  if (!dateString) return '';

  const date = new Date(dateString);


  return new Intl.DateTimeFormat('es-VE', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  }).format(date);
};


const getBorderClass = (priority) => {
  if (!priority) return 'border-start border-secondary border-3';
  const p = priority.toLowerCase();
  if (p === 'crítica' || p === 'critica') return 'border-priority-critica';
  if (p === 'alta') return 'border-priority-alta';
  if (p === 'media') return 'border-priority-media';
  return 'border-priority-baja';
};

const getBadgeClass = (status) => {
  if (status === 'en_progreso') return 'badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill';
  return 'badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill';
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

const emit = defineEmits(['logout-trigger']);


const handleLogout = () => {

  emit('logout-trigger');
};


// ==========================================
//  CICLO DE VIDA DEL COMPONENTE
// ==========================================



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

onUnmounted(() => {
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


.scrollable-column::-webkit-scrollbar {
  width: 6px;
}

.scrollable-column::-webkit-scrollbar-track {
  background: transparent;
}

.scrollable-column::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
  border-radius: 10px;
}

.scrollable-column::-webkit-scrollbar-thumb:hover {
  background-color: #94a3b8;
}

.custom-navbar {
  position: sticky;
  top: 0;
  z-index: 1030;
  backdrop-filter: blur(8px);
  background-color: #1e293b !important;
}


.container-fluid {
  margin-top: 10px;
}
</style>