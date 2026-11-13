<?php
require_once '../includes/auth_check.php';
require_once '../config/db.php';

if (isset($_GET['id'])) {
    $reserva_id = intval($_GET['id']);
    $usuario_id = $_SESSION['user_id'];
    $rol = $_SESSION['user_rol'];
    
    // Validar que la reserva existe y pertenece al usuario (o es admin)
    $stmt = $pdo->prepare("SELECT * FROM reservas WHERE id = ? AND estado = 'confirmada'");
    $stmt->execute([$reserva_id]);
    $reserva = $stmt->fetch();
    
    if ($reserva) {
        // Si es admin, puede cancelar cualquier reserva. Si no, solo las suyas.
        if ($rol === 'admin' || $reserva['usuario_id'] == $usuario_id) {
            $update = $pdo->prepare("UPDATE reservas SET estado = 'cancelada' WHERE id = ?");
            if ($update->execute([$reserva_id])) {
                $_SESSION['msg'] = "✅ Reserva cancelada exitosamente.";
            } else {
                $_SESSION['msg'] = "❌ Error al cancelar la reserva.";
            }
        } else {
            $_SESSION['msg'] = "❌ No tienes permisos para cancelar esta reserva.";
        }
    } else {
        $_SESSION['msg'] = " La reserva no existe o ya fue cancelada.";
    }
}

// Redirigir al dashboard correspondiente
header("Location: ../views/dashboard_" . $rol . ".php");
exit;
?>