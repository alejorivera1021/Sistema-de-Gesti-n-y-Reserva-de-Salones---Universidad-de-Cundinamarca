<?php
// Ruta corregida para instalación manual de Dompdf
require_once '../vendor/dompdf/autoload.inc.php'; 
require_once '../includes/auth_check.php';
require_once '../config/db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Validar que solo el admin pueda generar reportes
if ($_SESSION['user_rol'] !== 'admin') {
    header("Location: dashboard_" . $_SESSION['user_rol'] . ".php");
    exit;
}

// Obtener filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$tipo_usuario = $_GET['tipo_usuario'] ?? '';
$bloque = $_GET['bloque'] ?? '';

// Consulta multitabla (la misma que en reportes.php)
$sql = "SELECT 
            u.nombre AS usuario_nombre,
            u.email AS usuario_email,
            u.rol AS tipo_usuario,
            s.codigo AS salon_codigo,
            s.nombre AS salon_nombre,
            s.edificio AS bloque,
            s.piso,
            r.fecha,
            r.hora_inicio AS hora_programada_inicio,
            r.hora_fin AS hora_programada_fin,
            r.check_in AS hora_real_entrada,
            r.check_out AS hora_real_salida,
            r.motivo,
            r.tipo_reserva,
            TIMESTAMPDIFF(MINUTE, r.hora_inicio, r.hora_fin) AS duracion_programada_min,
            CASE 
                WHEN r.check_in IS NOT NULL AND r.check_out IS NOT NULL 
                THEN TIMESTAMPDIFF(MINUTE, r.check_in, r.check_out)
                ELSE 0
            END AS duracion_real_min,
            CASE 
                WHEN r.check_out IS NOT NULL AND TIME(r.check_out) < r.hora_fin 
                THEN 'Salió antes'
                WHEN r.check_out IS NOT NULL AND TIME(r.check_out) > r.hora_fin 
                THEN 'Salió después'
                WHEN r.check_out IS NOT NULL 
                THEN 'Salió a tiempo'
                WHEN r.check_in IS NOT NULL 
                THEN 'En el salón'
                ELSE 'No ha entrado'
            END AS estado_asistencia
        FROM reservas r
        INNER JOIN usuarios u ON r.usuario_id = u.id
        INNER JOIN salones s ON r.salon_id = s.id
        WHERE r.fecha BETWEEN ? AND ?
        AND r.estado = 'confirmada'";

$params = [$fecha_inicio, $fecha_fin];

if ($tipo_usuario) {
    $sql .= " AND u.rol = ?";
    $params[] = $tipo_usuario;
}

if ($bloque) {
    $sql .= " AND s.edificio = ?";
    $params[] = $bloque;
}

$sql .= " ORDER BY r.fecha DESC, r.hora_inicio ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reporte = $stmt->fetchAll();

// Estadísticas
$total_reservas = count($reporte);
$total_profesores = 0;
$total_aseo = 0;
$salieron_antes = 0;

foreach ($reporte as $r) {
    if ($r['tipo_usuario'] === 'profesor') $total_profesores++;
    if ($r['tipo_usuario'] === 'aseo') $total_aseo++;
    if ($r['estado_asistencia'] === 'Salió antes') $salieron_antes++;
}

// Generar HTML para el PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 20px;
        }
        .header p {
            color: #666;
            margin: 5px 0;
        }
        .info-box {
            background: #f8f9fa;
            padding: 10px;
            border-left: 4px solid #3498db;
            margin-bottom: 20px;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        .stat-box {
            background: #3498db;
            color: white;
            padding: 10px;
            text-align: center;
            border-radius: 5px;
            flex: 1;
            margin: 0 5px;
        }
        .stat-box h3 {
            margin: 0;
            font-size: 24px;
        }
        .stat-box p {
            margin: 5px 0 0 0;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background: #3498db;
            color: white;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #ddd;
            font-size: 9px;
        }
        tr:nth-child(even) {
            background: #f8f9fa;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
            color: white;
        }
        .badge-profesor { background: #9b59b6; }
        .badge-aseo { background: #f39c12; }
        .badge-antes { background: #e74c3c; }
        .badge-despues { background: #f39c12; }
        .badge-tiempo { background: #28a745; }
        .badge-salon { background: #3498db; }
        .badge-pendiente { background: #6c757d; }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Reporte de Uso de Aulas</h1>
        <p>Sistema de Gestión de Salones - Universidad de Cundinamarca</p>
        <p>Generado el ' . date('d/m/Y H:i') . ' por ' . $_SESSION['user_name'] . '</p>
    </div>

    <div class="info-box">
        <strong>Filtros aplicados:</strong><br>
        📅 Período: ' . date('d/m/Y', strtotime($fecha_inicio)) . ' - ' . date('d/m/Y', strtotime($fecha_fin)) . '<br>
        👥 Tipo de usuario: ' . ($tipo_usuario ? ucfirst($tipo_usuario) : 'Todos') . '<br>
        🏢 Bloque: ' . ($bloque ? $bloque : 'Todos') . '
    </div>

    <div class="stats">
        <div class="stat-box">
            <h3>' . $total_reservas . '</h3>
            <p>Total Reservas</p>
        </div>
        <div class="stat-box" style="background:#9b59b6;">
            <h3>' . $total_profesores . '</h3>
            <p>Profesores</p>
        </div>
        <div class="stat-box" style="background:#f39c12;">
            <h3>' . $total_aseo . '</h3>
            <p>Aseo</p>
        </div>
        <div class="stat-box" style="background:#e74c3c;">
            <h3>' . $salieron_antes . '</h3>
            <p>Salieron Antes</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Salón</th>
                <th>Horario</th>
                <th>Entrada</th>
                <th>Salida</th>
                <th>Duración</th>
                <th>Estado</th>
                <th>Motivo</th>
            </tr>
        </thead>
        <tbody>';

foreach ($reporte as $r) {
    $badge_class = 'badge-pendiente';
    if ($r['estado_asistencia'] === 'Salió antes') $badge_class = 'badge-antes';
    elseif ($r['estado_asistencia'] === 'Salió después') $badge_class = 'badge-despues';
    elseif ($r['estado_asistencia'] === 'Salió a tiempo') $badge_class = 'badge-tiempo';
    elseif ($r['estado_asistencia'] === 'En el salón') $badge_class = 'badge-salon';

    $rol_badge = $r['tipo_usuario'] === 'profesor' ? 'badge-profesor' : 'badge-aseo';
    $rol_text = $r['tipo_usuario'] === 'profesor' ? '👨‍🏫 Profesor' : '🧹 Aseo';

    $html .= '
            <tr>
                <td>' . date('d/m/Y', strtotime($r['fecha'])) . '</td>
                <td><b>' . htmlspecialchars($r['usuario_nombre']) . '</b><br><small>' . htmlspecialchars($r['usuario_email']) . '</small></td>
                <td><span class="badge ' . $rol_badge . '">' . $rol_text . '</span></td>
                <td><b>' . $r['salon_codigo'] . '</b><br><small>' . $r['salon_nombre'] . '</small></td>
                <td>' . $r['hora_programada_inicio'] . ' - ' . $r['hora_programada_fin'] . '</td>
                <td>' . ($r['hora_real_entrada'] ? date('H:i', strtotime($r['hora_real_entrada'])) : '⏳') . '</td>
                <td>' . ($r['hora_real_salida'] ? date('H:i', strtotime($r['hora_real_salida'])) : '⏳') . '</td>
                <td>' . ($r['duracion_real_min'] > 0 ? $r['duracion_real_min'] . ' min' : '-') . '</td>
                <td><span class="badge ' . $badge_class . '">' . $r['estado_asistencia'] . '</span></td>
                <td>' . htmlspecialchars($r['motivo']) . '</td>
            </tr>';
}

$html .= '
        </tbody>
    </table>

    <div class="footer">
        <p>Universidad de Cundinamarca - Sistema de Gestión de Salones v1.0</p>
        <p>Reporte generado automáticamente el ' . date('d/m/Y H:i:s') . '</p>
    </div>
</body>
</html>';

// Configurar Dompdf
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape'); // Horizontal para mejor visualización
$dompdf->render();

// Descargar el PDF
$dompdf->stream('reporte_salones_' . date('Y-m-d') . '.pdf', [
    'Attachment' => true
]);
exit;
?>