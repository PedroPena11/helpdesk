import './bootstrap';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

import TicketDashboard from './Components/TicketDashboard.vue';
import { createApp } from 'vue';

const app = createApp({});
app.component('ticket-dashboard',TicketDashboard);

app.mount('#app');