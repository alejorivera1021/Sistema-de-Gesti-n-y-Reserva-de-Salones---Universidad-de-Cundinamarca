<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Salones - <?php echo ucfirst($_SESSION['user_rol'] ?? 'Login'); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo">🏫 Gestión de Salones</div>
        <div class="user-info">
            <span>Hola, <?php echo htmlspecialchars($_SESSION['user_name']); ?> (<?php echo ucfirst($_SESSION['user_rol']); ?>)</span>
            <a href="../controllers/logout.php" class="btn-logout">Cerrar Sesión</a>
        </div>
    </nav>
    <div class="container">