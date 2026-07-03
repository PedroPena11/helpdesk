import './bootstrap';
import { createApp, ref } from 'vue';
import Login from './Components/Auth/Login.vue';
import TicketDashboard from './Components/TicketDashboard.vue';
import axios from 'axios';
import SecuritySetup from './Components/SecuritySetup.vue';
import AuditoriaPanel from './Components/AuditoriaPanel.vue';
import BackupPanel from './Components/BackupPanel.vue';
import PreguntasPanel from './Components/PreguntasPanel.vue';


const token = localStorage.getItem('access_token'); 
const hasValidToken = token && token !== 'null' && token !== 'undefined';

if (hasValidToken) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}


let isExpelling = false;

axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response && error.response.status === 401) {
            const errorData = error.response.data;

            if (errorData.error === 'session_expired' || errorData.error === 'session_concurrent') {
                if (isExpelling) return new Promise(() => { });
                isExpelling = true;

                localStorage.clear();
                delete axios.defaults.headers.common['Authorization'];

                alert(errorData.message);
                window.location.href = '/';

                return new Promise(() => { });
            }
        }
        return Promise.reject(error);
    }
);

createApp({
    components: {
        Login,
        TicketDashboard,
        SecuritySetup,
        AuditoriaPanel,
        BackupPanel,
        PreguntasPanel,

    },
    setup() {
        const tokenExists = localStorage.getItem('access_token');
        const hasValidToken = tokenExists && tokenExists !== 'null' && tokenExists !== 'undefined';

        const isAuthenticated = ref(hasValidToken);
        const currentUser = ref(null);

        const mustCompleteSetup = ref(false);


        let warningTimer = null;
        let logoutTimer = null;

        const TIME_TO_WARNING = 1 * 60 * 1000;
        const TIME_TO_LOGOUT = 2 * 60 * 1000;


        const forceInactivityLogout = () => {
            localStorage.clear();
            delete axios.defaults.headers.common['Authorization'];
            isAuthenticated.value = false;
            currentUser.value = null;
            alert("Tu sesión ha sido cerrada automáticamente por inactividad.");
            window.location.href = '/';
        };


        const resetInactivityTimers = () => {

            if (!isAuthenticated.value) return;


            clearTimeout(warningTimer);
            clearTimeout(logoutTimer);


            warningTimer = setTimeout(() => {
                alert("⚠️ Alerta de Seguridad: Tu sesión va a expirar en 1 minuto por inactividad. Mueve el mouse o interactúa para mantenerla activa.");
            }, TIME_TO_WARNING);


            logoutTimer = setTimeout(() => {
                forceInactivityLogout();
            }, TIME_TO_LOGOUT);
        };


        const startTrackingActivity = () => {
            const activityEvents = ['mousemove', 'keypress', 'click', 'scroll', 'touchstart'];

            activityEvents.forEach(event => {
                window.addEventListener(event, resetInactivityTimers);
            });


            resetInactivityTimers();
        };

        const stopTrackingActivity = () => {
            const activityEvents = ['mousemove', 'keypress', 'click', 'scroll', 'touchstart'];
            activityEvents.forEach(event => {
                window.removeEventListener(event, resetInactivityTimers);
            });
            clearTimeout(warningTimer);
            clearTimeout(logoutTimer);
        };

       
        const savedUser = localStorage.getItem('user_data');
        if (hasValidToken && savedUser && savedUser !== 'undefined' && savedUser !== 'null') {
            try {
                const parsedUser = JSON.parse(savedUser);
                currentUser.value = parsedUser;

                
              

                
                mustCompleteSetup.value = !parsedUser.setup_completed;

                if (parsedUser.setup_completed) {
                    
                    setTimeout(() => startTrackingActivity(), 500);
                } else {
                    console.log("-> ATENCIÓN: Este usuario debería estar bloqueado en el setup.");
                }
            } catch (e) {
                console.error("Error al parsear el usuario guardado:", e);
                localStorage.clear();
                isAuthenticated.value = false;
            }
        }


        const loginSuccess = (userData) => {
            currentUser.value = userData.user;
            isAuthenticated.value = true;

            localStorage.setItem('access_token', userData.access_token);
            localStorage.setItem('user_data', JSON.stringify(userData.user));
            axios.defaults.headers.common['Authorization'] = `Bearer ${userData.access_token}`;

           
            if (!userData.user.setup_completed) {
                mustCompleteSetup.value = true;
            } else {
                mustCompleteSetup.value = false;
                startTrackingActivity();
            }
        };

        
        const securitySetupSuccess = (updatedUser) => {
            currentUser.value = updatedUser;
            mustCompleteSetup.value = false; 

            localStorage.setItem('user_data', JSON.stringify(updatedUser));
            startTrackingActivity();
        };


        const handleLogout = async () => {
            try {
                stopTrackingActivity();
                await axios.post('/api/logout');
            } catch (error) {
                console.error("Error al revocar el token en el servidor:", error);
            } finally {
                localStorage.clear();
                delete axios.defaults.headers.common['Authorization'];
                isAuthenticated.value = false;
                currentUser.value = null;
                window.location.href = '/';
            }
        };

        return {
            isAuthenticated,
            currentUser,
            mustCompleteSetup,
            loginSuccess,
            handleLogout,
            securitySetupSuccess
        };



    }
}).mount('#app');