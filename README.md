# 🎫 Sistema de Gestión de Incidencias en Tiempo Real

¡Bienvenido! Este es un sistema de mesa de ayuda (Helpdesk) moderno diseñado para la creación, seguimiento y asignación de tickets de soporte técnico en tiempo real. El proyecto está construido bajo una arquitectura SPA (Single Page Application) desacoplada, utilizando tecnologías de última generación para garantizar velocidad, consistencia de datos y reactividad.

## 🚀 Características Clave

* **⚡ Conexión en Tiempo Real:** Integración completa de WebSockets que permite a los operadores y administradores ver la creación y asignación de tickets al instante sin recargar la página.
* **👥 Control de Accesos por Roles:** Flujos de trabajo diferenciados para **Administradores** (gestión total y asignación), **Técnicos** (resolución de tareas asignadas) y **Clientes** (reporte de incidencias).
* **🔒 Seguridad y Persistencia:** Sistema protegido con tokens criptográficos (Laravel Sanctum) y base de datos relacional robusta con restricciones de integridad y ENUMs nativos.

---

## 🛠️ Stack Tecnológico

### Backend (API REST)
* **Framework:** Laravel 12
* **Base de Datos:** PostgreSQL 
* **Autenticación:** Laravel Sanctum (Tokens SPA)
* **Eventos & WebSockets:** Laravel Reverb / Echo Channels

### Frontend (SPA)
* **Framework:** Vue 3 (Composition API con `<script setup>`)
* **Empaquetador:** Vite
* **Estilos:** Bootstrap 5 (Control de Modales asíncronos vía JavaScript/DOM)
* **Cliente HTTP:** Axios

---

## 🔧 Instalación y Configuración Local

### Prerrequisitos
* PHP 8.2 o superior
* Node.js & NPM
* Servidor PostgreSQL corriendo

### 1. Clonar y Configurar el Backend
```bash
# Clonar el repositorio
git clone [https://github.com/PedroPena11/helpdesk]

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
