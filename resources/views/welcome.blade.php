<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HelpDesk Professional</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
</head>

<body class="bg-light m-0 p-0">

    <div id="app" class="bg-light min-vh-100">

        <div v-if="!isAuthenticated">
            <login v-on:auth-success="loginSuccess"></login>
        </div>

        <div v-else-if="mustCompleteSetup">
            <security-setup v-on:setup-success="securitySetupSuccess"></security-setup>
        </div>

        <div v-else>
            <ticket-dashboard :user-data="currentUser" @logout-trigger="handleLogout"></ticket-dashboard>
        </div>

    </div>

</body>

</html>