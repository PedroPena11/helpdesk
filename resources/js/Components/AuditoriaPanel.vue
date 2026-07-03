<template>
    <div class="card border-0 shadow-sm rounded-3 p-4 mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark m-0">
                <i class="bi bi-journal-text text-primary me-2"></i>Trazas de Auditoría del Sistema
            </h5>
            <button class="btn btn-sm btn-outline-secondary" @click="cargarTrazas" :disabled="loading">
                <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
            </button>
        </div>

        <div v-if="loading" class="text-center py-4">
            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
        </div>

        <!-- Contenedor con altura fija y scroll vertical para mantener el Dashboard compacto -->
        <div v-else class="table-responsive shadow-sm border rounded-3" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-hover align-middle small m-0">
                <!-- table-sticky hace que los encabezados se queden fijos arriba al hacer scroll -->
                <thead class="table-light sticky-top" style="z-index: 1;">
                    <tr>
                        <th>Fecha / Hora</th>
                        <th>Operación</th>
                        <th>Usuario</th>
                        <th>Descripción</th>
                        <th>Dirección IP</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="traza in trazas" :key="traza.id">
                        <td class="text-muted" style="white-space: nowrap;">{{ formatFecha(traza.created_at) }}</td>
                        <td>
                            <span :class="badgeClass(traza.operacion)">{{ traza.operacion }}</span>
                        </td>
                        <td>
                            <span v-if="traza.user" class="fw-bold">{{ traza.user.name }} <br><small
                                    class="text-muted">{{ traza.user.email }}</small></span>
                            <span v-else class="text-danger-subtle font-monospace">Sistema Anónimo</span>
                        </td>
                        <td class="text-dark">{{ traza.detalles }}</td>
                        <td><code class="text-secondary">{{ traza.ip_address || 'Desconocida' }}</code></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const trazas = ref([]);
const loading = ref(false);

const cargarTrazas = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/api/admin/auditorias');
        trazas.value = response.data;
    } catch (error) {
        console.error("Error al cargar la bitácora de auditoría:", error);
    } finally {
        loading.value = false;
    }
};

const badgeClass = (operacion) => {
    if (operacion.includes('EXITOSO')) return 'badge bg-success-subtle text-success px-2 py-1 rounded';
    if (operacion.includes('FALLIDO') || operacion.includes('CONCURRENTE')) return 'badge bg-danger-subtle text-danger px-2 py-1 rounded';
    return 'badge bg-warning-subtle text-warning px-2 py-1 rounded';
};

const formatFecha = (fechaStr) => {
    return new Date(fechaStr).toLocaleString('es-ES', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit'
    });
};

onMounted(() => {
    cargarTrazas();
});
</script>