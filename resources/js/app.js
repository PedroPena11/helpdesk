import './bootstrap';
import { createApp, ref } from 'vue';
import Login from './Components/Auth/Login.vue';
import TicketDashboard from './Components/TicketDashboard.vue';
import axios from 'axios';

const isAuthenticated = ref(false);
const currentUser = ref(null);

const loginSuccess = (data) => {
    currentUser.value = data.user; 
    isAuthenticated.value = true;   

}

const token = localStorage.getItem('auth_token');


const hasValidToken = token && token !== 'null' && token !== 'undefined';

if (hasValidToken) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

createApp({
    components: {
        Login,
        TicketDashboard
    },
    setup() {
        
        const isAuthenticated = ref(hasValidToken);
        const currentUser = ref(null);

       
        const savedUser = localStorage.getItem('user_data');
        if (hasValidToken && savedUser && savedUser !== 'undefined' && savedUser !== 'null') {
            try {
                currentUser.value = JSON.parse(savedUser);
            } catch (e) {
                console.error("Error al leer los datos de usuario corruptos:", e);
            }
        }

       
        const loginSuccess = (user) => {
            currentUser.value = user;
            isAuthenticated.value = true;
        };

        
        const handleLogout = async () => {
            try {
                await axios.post('/api/logout');
            } catch (error) {
                console.error("Error al revocar el token en el servidor:", error);
            } finally {
               
                localStorage.removeItem('auth_token');
                localStorage.removeItem('user_data');
                delete axios.defaults.headers.common['Authorization'];
                isAuthenticated.value = false;
                currentUser.value = null;
            }
        };

        return {
            isAuthenticated,
            currentUser,
            loginSuccess,
            handleLogout
        };
    }
}).mount('#app');