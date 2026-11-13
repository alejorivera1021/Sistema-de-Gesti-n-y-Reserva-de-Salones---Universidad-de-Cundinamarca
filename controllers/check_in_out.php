<?php
require_once '../includes/auth_check.php';
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && isset($_POST['reserva_id'])) {
    $reserva_id = intval($_POST['reserva_id']);
    $accion = $_POST['accion'];
    $usuario_id = $_SESSION['user_id'];
    $rol = $_SESSION['user_rol'];
    
    // Verificar que la reserva existe y pertenece al usuario
    $stmt = $pdo->prepare("SELECT * FROM reservas WHERE id = ? AND usuario_id = ? AND estado = 'confirmada'");
    $stmt->execute([$reserva_id, $usuario_id]);
    $reserva = $stmt->fetch();
    
    if (!$reserva) {
        $_SESSION['msg'] = "❌ No se encontró la reserva o no tienes permisos.";
        header("Location: ../views/dashboard_" . $rol . ".php");
        exit;
    }
    
    $fecha_actual = date('Y-m-d H:i:s');
    
    if ($accion === 'check_in') {
        // Marcar entrada
        if ($reserva['check_in'] !== null) {
            $_SESSION['msg'] = "⚠️ Ya habías marcado la entrada a esta reserva.";
        } else {
            $update = $pdo->prepare("UPDATE reservas SET check_in = ? WHERE id = ?");
            $update->execute([$fecha_actual, $reserva_id]);
            $_SESSION['msg'] = "✅ Entrada registrada correctamente.";
        }
    } elseif ($accion === 'check_out') {
        // Marcar salida
        if ($reserva['check_in'] === null) {
            $_SESSION['msg'] = "❌ Debes marcar la entrada antes de registrar la salida.";
        } elseif ($reserva['check_out'] !== null) {
            $_SESSION['msg'] = "⚠️ Ya habías marcado la salida de esta reserva.";
        } else {
            $update = $pdo->prepare("UPDATE reservas SET check_out = ? WHERE id = ?");
            $update->execute([$fecha_actual, $reserva_id]);
            $_SESSION['msg'] = "✅ Salida registrada. ¡Salón desocupado!";
        }
    } else {
        $_SESSION['msg'] = "❌ Acción no válida.";
    }
    
    header("Location: ../views/dashboard_" . $rol . ".php");
    exit;
} else {
    header("Location: ../views/dashboard_" . $_SESSION['user_rol'] . ".php");
    exit;
}
?>