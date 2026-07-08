# 🎫 Sistema de Gestión de Incidencias en Tiempo Real (Helpdesk)

¡Bienvenido! Este es un sistema de mesa de ayuda (Helpdesk) moderno diseñado para la creación, seguimiento y asignación de tickets de soporte técnico en tiempo real. El proyecto está construido bajo una arquitectura SPA (Single Page Application) desacoplada, utilizando tecnologías de última generación para garantizar velocidad, consistencia de datos, auditoría forense y alta seguridad transaccional.

---

## 🚀 Características Clave 


### 1. Control de Accesos, Sesiones y Seguridad en Sistemas
* **Autenticación Blindada:** Flujos de trabajo diferenciados con inicio de sesión, registro y modificación de contraseñas bajo hashing criptográfico nativo en el backend.
* **Doble Factor de Recuperación:** Sistema modularizado mediante el **CRUD Completo de Preguntas de Seguridad** (Agregar, Modificar, Eliminar y Consultar), asociación de respuestas por entidad de usuario y simulación de pasarelas de Correo.
* **Políticas de Sesión Única y Protección:** Middleware personalizado que valida fechas de expiración, controla el tiempo de inactividad global (Temporizador automático de sesiones) y revoca credenciales concurrentes mediante tokens persistentes y únicos por sesión activa (`Sanctum / Sessions`).

### 2. Conexión en Tiempo Real y Gestión Operativa
* **⚡ Sincronización vía WebSockets:** Integración completa de canales que permite a los operadores, técnicos y administradores visualizar la creación, asignación y resolución de incidencias al instante sin refrescar la ventana.
* **Flujos por Roles:** Paneles interactivos adaptados según los niveles de privilegios para **Administradores** (gestión integral y asignación masiva), **Técnicos** (resolución de tareas y diagnóstico) y **Clientes** (reporte y consulta de incidencias).

### 3. Panel de Respaldos Avanzado (PostgreSQL Engine)
Módulo administrativo local que interactúa directamente con los esquemas relacionales de la base de datos:
* **Generación Selectiva de Backups:** 
  * *Solo Estructura:* Extrae exclusivamente la definición lógica de esquemas, tablas, restricciones de integridad, llaves primarias y foráneas.
  * *Backup Completo:* Realiza un volcado masivo que empaqueta la arquitectura relacional junto con la totalidad de los datos históricos del sistema.
* **Aislamiento Físico (Sandboxing):** Los archivos `.sql` generados se almacenan fuera del alcance del servidor web público mediante el driver de almacenamiento encapsulado de Laravel (`Storage::disk('backups')`).
* **Descarga Segura (Flujos Binarios):** La API transfiere los datos en formato binario en bruto (`Blob`). El cliente en Vue 3 procesa los bytes en memoria volátil e inicializa la descarga local sin exponer jamás las rutas físicas o absolutas del servidor.
* **Depuración Controlada:** Capacidad del administrador para eliminar archivos de respaldo del disco del servidor previa confirmación interactiva.

### 4. Auditoría Activa e Inmutable (SIEM Local)
* **Registro de Actividad (Trazas):** Cada vez que un administrador genera un respaldo, elimina un archivo o altera datos sensibles, el backend intercepta la acción de forma automática.
* **Trazabilidad Forense:** Registro imborrable en la tabla `auditorias` que captura el ID del usuario operativo, el tipo de transacción (`BACKUP_GENERATED`, etc.), estampa de tiempo, dirección IP de red y cabeceras del agente de usuario (*User Agent*).

---

## 🛠️ Stack Tecnológico

### Backend (API REST)
* **Framework:** Laravel 12
* **Base de Datos:** PostgreSQL 16 (Configuración de Seguridad y Restricciones de Integridad Nativas)
* **Autenticación:** Laravel Sanctum (Tokens SPA)
* **Eventos & WebSockets:** Laravel Reverb / Echo Channels

### Frontend (SPA)
* **Framework:** Vue 3 (Composition API con `<script setup>`)
* **Empaquetador:** Vite
* **Estilos:** Bootstrap 5 (Control de Modales asíncronos vía JavaScript/DOM) + Bootstrap Icons
* **Cliente HTTP:** Axios (Manejo de Respuestas Tipo `Blob`)

---

## 🔧 Instalación y Configuración Local

### Prerrequisitos
* PHP 8.2 o superior
* Node.js & NPM
* Servidor PostgreSQL corriendo

### 1. Clonar y Configurar el Backend
```bash
# Clonar el repositorio
git clone [https://github.com/PedroPena11/helpdesk.git](https://github.com/PedroPena11/helpdesk.git)

cd helpdesk

# Instalar dependencias de PHP
composer install

# Crear archivo de configuración
cp .env.example .env
```

1. Ejecuta las migraciones y levanta el servidor:

```bash 
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

2. Levantar el servidor de WebSockets

En una terminal dedicada, ejecuta

```bash 
php artisan reverb:start
```

3. Configurar el Frontend

En otra terminal, instala las dependencias de JavaScript y arranca el entorno de desarrollo de Vite:

```bash 
npm install
npm run dev
```

## 🛡️ Autor

### Pedro Peña - Desarrollador 
