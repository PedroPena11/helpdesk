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
        
        <login v-if="!isAuthenticated" @@auth-success="loginSuccess"></login>

        <div v-else>
            
            <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm py-3 mb-4">
                <div class="container d-flex justify-content-between align-items-center">
                    
                    <a class="navbar-brand fw-bold d-flex align-items-center" href="#">
                        <i class="bi bi-headset me-2 text-primary"></i> HelpDesk Professional
                    </a>

                    <div class="d-flex align-items-center">
                        <span class="text-white-50 me-3 small d-none d-sm-inline fw-semibold">
                            <i class="bi bi-person-fill me-1 text-primary"></i> @{{ currentUser?.name }}
                        </span>
                        
                        <button @@click="handleLogout" class="btn btn-outline-danger btn-sm fw-bold">
                            <i class="bi bi-box-arrow-right me-1"></i> Cerrar Sesión
                        </button>
                    </div>

                </div>
            </nav>

            <ticket-dashboard></ticket-dashboard>

        </div>
    </div>

</body>
</html>