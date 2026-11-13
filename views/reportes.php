<?php 
require_once '../includes/auth_check.php'; 
require_once '../config/db.php';
include '../includes/header.php'; 

// Validar que solo el admin pueda ver reportes
if ($_SESSION['user_rol'] !== 'admin') {
    header("Location: dashboard_" . $_SESSION['user_rol'] . ".php");
    exit;
}

// Filtros
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
$tipo_usuario = $_GET['tipo_usuario'] ?? '';
$bloque = $_GET['bloque'] ?? '';
?>

<h2>📊 Sistema de Reportes - Consultas Multitabla</h2>

<div class="card" style="background:#e8f4fd; border-left:4px solid #3498db;">
    <p style="margin:0;">
        <strong>ℹ️ Nota técnica:</strong> Este módulo demuestra consultas multitabla con JOIN entre 3 tablas: 
        <code>usuarios</code>, <code>reservas</code> y <code>salones</code>.
        Incluye registro de entrada/salida real vs programada.
    </p>
</div>

<!-- FORMULARIO DE FILTROS -->
<div class="card">
    <h3>🔍 Generar Reporte</h3>
    <form method="GET" action="reportes.php">
        <div class="row">
            <div class="col">
                <label><b>Fecha Inicio:</b></label>
                <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>" required>
            </div>
            <div class="col">
                <label><b>Fecha Fin:</b></label>
                <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>" required>
            </div>
            <div class="col">
                <label><b>Tipo de Usuario:</b></label>
                <select name="tipo_usuario">
                    <option value="">Todos</option>
                    <option value="profesor" <?= $tipo_usuario=='profesor'?'selected':'' ?>>👨‍🏫 Profesores</option>
                    <option value="aseo" <?= $tipo_usuario=='aseo'?'selected':'' ?>>🧹 Personal de Aseo</option>
                </select>
            </div>
            <div class="col">
                <label><b>Bloque:</b></label>
                <select name="bloque">
                    <option value="">Todos</option>
                    <option value="Bloque A" <?= $bloque=='Bloque A'?'selected':'' ?>>Bloque A</option>
                    <option value="Bloque B" <?= $bloque=='Bloque B'?'selected':'' ?>>Bloque B</option>
                    <option value="Bloque C" <?= $bloque=='Bloque C'?'selected':'' ?>>Bloque C</option>
                </select>
            </div>
         </div>
        <div style="margin-top:20px; display:flex; gap:10px;">
            <button type="submit" class="btn-generar-reporte">📊 Ver Reporte</button>
            <button type="submit" formaction="generar_pdf.php" formtarget="_blank" class="btn-generar-pdf">📄 Generar PDF</button>
        </div>
    </form>
</div>

<?php
// CONSULTA MULTITABLA CON JOIN DE 3 TABLAS
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

// Estadísticas del reporte
$total_reservas = count($reporte);
$total_profesores = 0;
$total_aseo = 0;
$salieron_antes = 0;
$en_salon = 0;

foreach ($reporte as $r) {
    if ($r['tipo_usuario'] === 'profesor') $total_profesores++;
    if ($r['tipo_usuario'] === 'aseo') $total_aseo++;
    if ($r['estado_asistencia'] === 'Salió antes') $salieron_antes++;
    if ($r['estado_asistencia'] === 'En el salón') $en_salon++;
}
?>

<!-- ESTADÍSTICAS DEL REPORTE -->
<div class="row" style="margin-bottom:20px;">
    <div class="col">
        <div class="card" style="background:#3498db; color:white; text-align:center;">
            <h3 style="color:white; border:none; margin:0;"><?= $total_reservas ?></h3>
            <p style="margin:5px 0 0 0;">Total Reservas</p>
        </div>
    </div>
    <div class="col">
        <div class="card" style="background:#9b59b6; color:white; text-align:center;">
            <h3 style="color:white; border:none; margin:0;"><?= $total_profesores ?></h3>
            <p style="margin:5px 0 0 0;">Reservas de Profesores</p>
        </div>
    </div>
    <div class="col">
        <div class="card" style="background:#f39c12; color:white; text-align:center;">
            <h3 style="color:white; border:none; margin:0;"><?= $total_aseo ?></h3>
            <p style="margin:5px 0 0 0;">Limpiezas de Aseo</p>
        </div>
    </div>
    <div class="col">
        <div class="card" style="background:#e74c3c; color:white; text-align:center;">
            <h3 style="color:white; border:none; margin:0;"><?= $salieron_antes ?></h3>
            <p style="margin:5px 0 0 0;">Salieron Antes</p>
        </div>
    </div>
    <div class="col">
        <div class="card" style="background:#28a745; color:white; text-align:center;">
            <h3 style="color:white; border:none; margin:0;"><?= $en_salon ?></h3>
            <p style="margin:5px 0 0 0;">Actualmente en Salón</p>
        </div>
    </div>
</div>

<!-- TABLA DE REPORTE -->
<div class="card">
    <h3>📋 Reporte de Uso de Aulas con Check-In/Out (<?= date('d/m/Y', strtotime($fecha_inicio)) ?> - <?= date('d/m/Y', strtotime($fecha_fin)) ?>)</h3>
    
    <?php if ($total_reservas === 0): ?>
        <p style="text-align:center; color:#888; padding:20px;">No hay registros para los filtros seleccionados.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Salón</th>
                    <th>Horario Programado</th>
                    <th>Entrada Real</th>
                    <th>Salida Real</th>
                    <th>Duración Real</th>
                    <th>Estado</th>
                    <th>Motivo</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($reporte as $r): 
                $clase_fila = ($r['tipo_reserva'] === 'aseo') ? 'bg-aseo' : '';
                
                $estado_color = '#6c757d';
                if ($r['estado_asistencia'] === 'Salió antes') $estado_color = '#e74c3c';
                elseif ($r['estado_asistencia'] === 'Salió después') $estado_color = '#f39c12';
                elseif ($r['estado_asistencia'] === 'Salió a tiempo') $estado_color = '#28a745';
                elseif ($r['estado_asistencia'] === 'En el salón') $estado_color = '#3498db';
            ?>
                <tr class="<?= $clase_fila ?>">
                    <td><?= date('d/m/Y', strtotime($r['fecha'])) ?></td>
                    <td>
                        <b><?= htmlspecialchars($r['usuario_nombre']) ?></b><br>
                        <small style="color:#666;"><?= htmlspecialchars($r['usuario_email']) ?></small>
                    </td>
                    <td>
                        <?php if ($r['tipo_usuario'] === 'profesor'): ?>
                            <span class="badge" style="background:#9b59b6; color:white;">👨‍🏫 Profesor</span>
                        <?php else: ?>
                            <span class="badge badge-aseo">🧹 Aseo</span>
                        <?php endif; ?>
                    </td>
                    <td><b><?= htmlspecialchars($r['salon_codigo']) ?></b><br><small><?= htmlspecialchars($r['salon_nombre']) ?></small></td>
                    <td>
                        <small>📅 <?= $r['hora_programada_inicio'] ?> - <?= $r['hora_programada_fin'] ?></small><br>
                        <small style="color:#888;">(<?= $r['duracion_programada_min'] ?> min)</small>
                    </td>
                    <td>
                        <?php if ($r['hora_real_entrada']): ?>
                            <strong style="color:#28a745;">🟢 <?= date('H:i', strtotime($r['hora_real_entrada'])) ?></strong>
                        <?php else: ?>
                            <span style="color:#999;">⏳ Pendiente</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($r['hora_real_salida']): ?>
                            <strong style="color:#dc3545;">🔴 <?= date('H:i', strtotime($r['hora_real_salida'])) ?></strong>
                        <?php else: ?>
                            <span style="color:#999;">⏳ Pendiente</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($r['duracion_real_min'] > 0): ?>
                            <?= $r['duracion_real_min'] ?> min
                        <?php else: ?>
                            <span style="color:#999;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge" style="background:<?= $estado_color ?>; color:white;"><?= htmlspecialchars($r['estado_asistencia']) ?></span>
                    </td>
                    <td><?= htmlspecialchars($r['motivo']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- BOTÓN PARA VOLVER -->
<div style="text-align:center; margin-top:20px;">
    <a href="dashboard_admin.php" class="btn-cancelar" style="text-decoration:none; padding:10px 20px;">← Volver al Dashboard</a>
</div>

<?php include '../includes/footer.php'; ?>