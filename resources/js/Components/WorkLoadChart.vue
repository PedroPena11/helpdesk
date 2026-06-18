<script setup>
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';
import { computed } from 'vue';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({
  tickets: Array,
  technicians: Array
});


const chartData = computed(() => {
  
  const labels = props.technicians.map(t => t.name);
  
  
  const data = props.technicians.map(tech => {
    return props.tickets.filter(t => t.technician_id === tech.id && t.status === 'en_progreso').length;
  });

  return {
    labels,
    datasets: [
      {
        label: 'Tareas en Progreso 🛠️',
        backgroundColor: '#3b82f6',
        borderRadius: 6,
        data
      }
    ]
  };
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false } 
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: { stepSize: 1 } 
    }
  }
};
</script>

<template>
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
      <h6 class="m-0 fw-bold text-dark">📊 Monitoreo de Carga de Trabajo (En Vivo)</h6>
    </div>
    <div class="card-body">
      <div style="height: 220px; position: relative;">
        <Bar :data="chartData" :options="chartOptions" />
      </div>
    </div>
  </div>
</template>