<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Establecer Nueva Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card border-0 shadow-lg rounded-4 p-4" style="width: 100%; max-width: 420px;">
            <div class="card-body text-center">
                <div class="badge bg-primary p-3 rounded-circle mb-3">
                    <i class="bi bi-shield-lock-fill fs-3 text-white"></i>
                </div>
                <h3 class="fw-bold text-dark mb-2">Nueva Contraseña</h3>
                <p class="text-muted small mb-4">Ingresa tus nuevas credenciales de acceso para el sistema.</p>

                <form id="resetPasswordForm" class="text-start">
                    @csrf

                    <input type="hidden" id="token" name="token" value="{{ $token }}">
                    <input type="hidden" id="email" name="email" value="{{ $email }}">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Correo Electrónico</label>
                        <input type="email" class="form-control bg-light" value="{{ $email }}" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Nueva Contraseña</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Mínimo 8 caracteres" required minlength="8">
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">Confirmar Nueva Contraseña</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Repite tu contraseña" required>
                    </div>

                    <div id="errorAlert" class="alert alert-danger d-none small py-2" role="alert"></div>

                    <button type="submit" id="submitBtn" class="btn btn-primary w-100 py-2.5 fw-bold shadow-sm">
                        Actualizar Contraseña
                    </button>
                </form>
                <script>
                    document.getElementById('resetPasswordForm').addEventListener('submit', async function(e) {
                        e.preventDefault();

                        const submitBtn = document.getElementById('submitBtn');
                        const errorAlert = document.getElementById('errorAlert');

                        submitBtn.disabled = true;
                        submitBtn.innerText = 'Actualizando...';
                        errorAlert.classList.add('d-none');

                        const payload = {
                            token: document.getElementById('token').value,
                            email: document.getElementById('email').value,
                            password: document.getElementById('password').value,
                            password_confirmation: document.getElementById('password_confirmation').value
                        };

                        try {
                            const response = await fetch('/api/reset-password', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                                },
                                body: JSON.stringify(payload)
                            });

                            const data = await response.json();

                            if (response.ok) {
                                
                                submitBtn.classList.replace('btn-primary', 'btn-success');
                                submitBtn.innerText = '¡Contraseña cambiada! Redirigiendo...';

                                setTimeout(() => {
                                    window.location.href = 'http://127.0.0.1:8000/'; 
                                }, 2000);
                            } else {
                                throw new Error(data.message || 'Error al procesar el cambio.');
                            }

                        } catch (error) {
                            errorAlert.innerText = error.message;
                            errorAlert.classList.remove('d-none');
                            submitBtn.disabled = false;
                            submitBtn.innerText = 'Actualizar Contraseña';
                        }
                    });
                </script>
            </div>
        </div>
    </div>
</body>

</html>