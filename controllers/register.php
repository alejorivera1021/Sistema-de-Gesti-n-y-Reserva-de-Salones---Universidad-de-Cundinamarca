<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Obtener y limpiar datos del formulario
    $nombre = trim($_POST['nombre']);
    $codigo = trim($_POST['codigo']);
    $email = trim($_POST['email']);
    $rol = trim($_POST['rol']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    // 2. Array para almacenar errores de validación
    $errors = [];

    // 3. Validar dominio de correo institucional
    $dominio_permitido = '@ucundinamarca.edu.co';
    if (substr($email, -strlen($dominio_permitido)) !== $dominio_permitido) {
        $errors[] = "El correo debe ser institucional (@ucundinamarca.edu.co)";
    }

    // 4. Validar que el correo no exista ya en la base de datos
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "El correo electrónico ya está registrado en el sistema";
    }

    // 5. Validar que el código no exista ya en la base de datos
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE codigo = ?");
    $stmt->execute([$codigo]);
    if ($stmt->fetch()) {
        $errors[] = "El código estudiantil/laboral ya está registrado";
    }

    // 6. Validar longitud de la contraseña
    if (strlen($password) < 6) {
        $errors[] = "La contraseña debe tener al menos 6 caracteres";
    }

    // 7. Validar que las contraseñas coincidan
    if ($password !== $password_confirm) {
        $errors[] = "Las contraseñas no coinciden";
    }

    // 8. Validar que el rol sea uno de los permitidos para registro público
    // (Nota: 'admin' no se incluye por seguridad)
    if (!in_array($rol, ['estudiante', 'profesor', 'aseo'])) {
        $errors[] = "Rol de usuario no válido";
    }

    // 9. Si hay errores, guardarlos en sesión y redirigir al login
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>• ", $errors);
        header("Location: ../views/login.php");
        exit;
    }

    // 10. Hashear la contraseña de forma segura con bcrypt
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // 11. Asignar departamento por defecto si es personal de aseo
    $departamento = ($rol === 'aseo') ? 'Servicios Generales' : null;

    // 12. Intentar insertar el nuevo usuario en la base de datos
    try {
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (codigo, nombre, email, password, rol, departamento, activo) 
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        
        $stmt->execute([
            $codigo,
            $nombre,
            $email,
            $password_hash,
            $rol,
            $departamento
        ]);

        // 13. Registro exitoso: mensaje de éxito y redirección
        $_SESSION['success'] = "✅ ¡Registro exitoso! Ahora puedes iniciar sesión con tu correo institucional.";
        header("Location: ../views/login.php");
        exit;

    } catch (PDOException $e) {
        // 14. Manejo de errores de base de datos
        $_SESSION['error'] = "Error al registrar el usuario. Por favor, intenta de nuevo más tarde.";
        // Opcional: registrar el error real en un log para debugging
        // error_log($e->getMessage());
        header("Location: ../views/login.php");
        exit;
    }
} else {
    // Si alguien intenta acceder a este archivo directamente sin enviar el formulario POST
    header("Location: ../views/login.php");
    exit;
}
?>