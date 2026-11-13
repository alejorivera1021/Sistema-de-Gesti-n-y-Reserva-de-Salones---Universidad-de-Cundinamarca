<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Validar dominio de correo (opcional - descomenta si quieres forzarlo)
    /*
    $dominio_permitido = '@ucundinamarca.edu.co';
    if (substr($email, -strlen($dominio_permitido)) !== $dominio_permitido) {
        $_SESSION['error'] = "Solo se permiten correos institucionales @ucundinamarca.edu.co";
        header("Location: ../views/login.php");
        exit;
    }
    */

    try {
        $stmt = $pdo->prepare("SELECT id, nombre, password, rol FROM usuarios WHERE email = ? AND activo = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['user_rol'] = $user['rol'];

            header("Location: ../views/dashboard_" . $user['rol'] . ".php");
            exit;
        } else {
            $_SESSION['error'] = "Credenciales incorrectas o usuario inactivo.";
            header("Location: ../views/login.php");
            exit;
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error en el servidor: " . $e->getMessage();
        header("Location: ../views/login.php");
        exit;
    }
}
?>