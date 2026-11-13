<?php
require_once '../includes/auth_check.php';
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $salon_id = intval($_POST['salon_id']);
    $fecha = $_POST['fecha'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = $_POST['hora_fin'];
    $motivo = trim($_POST['motivo']);
    $usuario_id = $_SESSION['user_id'];
    
    // Lógica clave para el rol Aseo
    $tipo_reserva = ($_SESSION['user_rol'] === 'aseo') ? 'aseo' : 'academica';

    // Validación básica de horas
    if ($hora_inicio >= $hora_fin) {
        $_SESSION['msg'] = "❌ La hora de inicio debe ser menor a la hora de fin.";
        header("Location: ../views/dashboard_" . $_SESSION['user_rol'] . ".php");
        exit;
    }

    // CONSULTA BLINDADA DE CONFLICTOS (Overlap Logic)
    // Busca cualquier reserva confirmada que se cruce con el nuevo horario
    $sql_conflicto = "SELECT COUNT(*) as total FROM reservas 
                      WHERE salon_id = :salon_id 
                      AND fecha = :fecha 
                      AND estado = 'confirmada'
                      AND (
                          (hora_inicio < :hora_fin AND hora_fin > :hora_inicio)
                      )";
                      
    $stmt = $pdo->prepare($sql_conflicto);
    $stmt->execute([
        ':salon_id' => $salon_id,
        ':fecha' => $fecha,
        ':hora_fin' => $hora_fin,
        ':hora_inicio' => $hora_inicio
    ]);
    
    $conflicto = $stmt->fetch()['total'];

    if ($conflicto > 0) {
        $_SESSION['msg'] = "❌ ¡CONFLICTO! El salón ya está ocupado o en proceso de aseo en ese horario.";
    } else {
        // Insertar reserva
        $insert = $pdo->prepare("INSERT INTO reservas (salon_id, usuario_id, fecha, hora_inicio, hora_fin, motivo, tipo_reserva, estado) 
                                 VALUES (:salon_id, :usuario_id, :fecha, :hora_inicio, :hora_fin, :motivo, :tipo_reserva, 'confirmada')");
        $exito = $insert->execute([
            ':salon_id' => $salon_id,
            ':usuario_id' => $usuario_id,
            ':fecha' => $fecha,
            ':hora_inicio' => $hora_inicio,
            ':hora_fin' => $hora_fin,
            ':motivo' => $motivo,
            ':tipo_reserva' => $tipo_reserva
        ]);

        if ($exito) {
            $_SESSION['msg'] = "✅ Reserva de tipo <b>$tipo_reserva</b> confirmada exitosamente.";
        } else {
            $_SESSION['msg'] = "❌ Error al guardar en la base de datos.";
        }
    }
    
    header("Location: ../views/dashboard_" . $_SESSION['user_rol'] . ".php");
    exit;
}
?>