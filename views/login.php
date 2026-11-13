<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestión de Salones U.Cundinamarca</title>
    <link rel="stylesheet" href="../assets/css/login-style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="university-logo">🏫</div>
                <h1>Sistema de Gestión de Salones</h1>
                <p class="subtitle">Universidad de Cundinamarca</p>
            </div>

            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
            <?php endif; ?>

            <!-- Toggle Buttons -->
            <div class="toggle-container">
                <button class="toggle-btn active" onclick="showLogin()">Iniciar Sesión</button>
                <button class="toggle-btn" onclick="showRegister()">Registrarse</button>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="auth-form" action="../controllers/login_process.php" method="POST">
                <div class="form-group">
                    <label for="login_email">
                        <span class="icon">📧</span>
                        Correo Institucional
                    </label>
                    <input type="email" id="login_email" name="email" placeholder="usuario@ucundinamarca.edu.co" required>
                </div>

                <div class="form-group">
                    <label for="login_password">
                        <span class="icon">🔒</span>
                        Contraseña
                    </label>
                    <input type="password" id="login_password" name="password" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn-login">
                    <span class="icon">🚪</span>
                    Ingresar al Sistema
                </button>

                <div class="demo-accounts">
                    <p><strong>Cuentas de prueba:</strong></p>
                    <p>‍🏫 Profesor: ana@ucundinamarca.edu.co</p>
                    <p>👨‍🎓 Estudiante: juan@ucundinamarca.edu.co</p>
                    <p>🧹 Aseo: aseo@ucundinamarca.edu.co</p>
                    <p>🔑 Contraseña: 123456</p>
                </div>
            </form>

            <!-- Register Form -->
            <form id="registerForm" class="auth-form hidden" action="../controllers/register.php" method="POST">
                <div class="form-group">
                    <label for="register_nombre">
                        <span class="icon">👤</span>
                        Nombre Completo
                    </label>
                    <input type="text" id="register_nombre" name="nombre" placeholder="Ej: María Fernanda López" required>
                </div>

                <div class="form-group">
                    <label for="register_codigo">
                        <span class="icon">🆔</span>
                        Código Estudiantil/Laboral
                    </label>
                    <input type="text" id="register_codigo" name="codigo" placeholder="Ej: 20231001" required>
                </div>

                <div class="form-group">
                    <label for="register_email">
                        <span class="icon">📧</span>
                        Correo Institucional
                    </label>
                    <input type="email" id="register_email" name="email" placeholder="nombre@ucundinamarca.edu.co" required>
                    <small class="help-text">Solo se permiten correos @ucundinamarca.edu.co</small>
                </div>

               <div class="form-group">
    <label for="register_rol">
        <span class="icon">👥</span>
        Tipo de Usuario
    </label>
    <select id="register_rol" name="rol" required>
        <option value="">Seleccione su rol...</option>
        <option value="estudiante">👨‍🎓 Estudiante</option>
        <option value="profesor">👨‍🏫 Profesor</option>
        <option value="aseo">🧹 Personal de Aseo</option>
    </select>
</div>

                <div class="form-group">
                    <label for="register_password">
                        <span class="icon">🔒</span>
                        Contraseña
                    </label>
                    <input type="password" id="register_password" name="password" placeholder="Mínimo 6 caracteres" minlength="6" required>
                </div>

                <div class="form-group">
                    <label for="register_password_confirm">
                        <span class="icon">🔒</span>
                        Confirmar Contraseña
                    </label>
                    <input type="password" id="register_password_confirm" name="password_confirm" placeholder="Repita la contraseña" required>
                </div>

                <button type="submit" class="btn-register">
                    <span class="icon">✅</span>
                    Crear Cuenta
                </button>
            </form>

            <div class="login-footer">
                <p>&copy; 2026 Universidad de Cundinamarca - Ingeniería de Software</p>
            </div>
        </div>
    </div>

    <script>
        function showLogin() {
            document.getElementById('loginForm').classList.remove('hidden');
            document.getElementById('registerForm').classList.add('hidden');
            document.querySelectorAll('.toggle-btn')[0].classList.add('active');
            document.querySelectorAll('.toggle-btn')[1].classList.remove('active');
        }

        function showRegister() {
            document.getElementById('loginForm').classList.add('hidden');
            document.getElementById('registerForm').classList.remove('hidden');
            document.querySelectorAll('.toggle-btn')[0].classList.remove('active');
            document.querySelectorAll('.toggle-btn')[1].classList.add('active');
        }
    </script>
</body>
</html>