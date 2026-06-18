<script setup>
import { Bar } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';
import { computed } from 'vue';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({
  tickets: Array,
  technicians: Array
});

// Propiedad computada que procesa cuántos tickets activos ('en_progreso') tiene cada técnico
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
  scales: {
    y: { beginAtZero: true, ticks: { stepSize: 1 } }
  }
};
</script>

<template>
  <div style="height: 250px;">
    <Bar :data="chartData" :options="chartOptions" />
  </div>
</template>