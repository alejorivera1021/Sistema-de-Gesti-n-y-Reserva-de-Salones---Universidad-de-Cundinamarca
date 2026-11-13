<?php 
require_once '../includes/auth_check.php'; 
require_once '../config/db.php';
include '../includes/header.php'; 

$fecha_busqueda = $_GET['fecha'] ?? date('Y-m-d');
?>

<h2>🛠️ Panel de Administrador - Control Total</h2>

<?php if(isset($_SESSION['msg'])): ?>
    <div class="msg"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
<?php endif; ?>

<!-- ESTADÍSTICAS RÁPIDAS -->
<div class="row" style="margin-bottom:25px;">
    <?php
    $stats = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM salones) as total_salones,
            (SELECT COUNT(*) FROM reservas WHERE fecha = CURDATE() AND estado = 'confirmada') as reservas_hoy,
            (SELECT COUNT(*) FROM reservas WHERE tipo_reserva = 'aseo' AND fecha = CURDATE()) as aseo_hoy,
            (SELECT COUNT(*) FROM usuarios WHERE rol = 'profesor') as total_profesores,
            (SELECT COUNT(*) FROM usuarios WHERE rol = 'estudiante') as total_estudiantes,
            (SELECT COUNT(*) FROM usuarios WHERE rol = 'aseo') as total_aseo
    ")->fetch();
    ?>
    
    <div class="col">
        <div class="card" style="background:#3498db; color:white; text-align:center;">
            <h3 style="color:white; border:none; margin:0;"><?= $stats['total_salones'] ?></h3>
            <p style="margin:5px 0 0 0;">Salones Totales</p>
        </div>
    </div>
    <div class="col">
        <div class="card" style="background:#28a745; color:white; text-align:center;">
            <h3 style="color:white; border:none; margin:0;"><?= $stats['reservas_hoy'] ?></h3>
            <p style="margin:5px 0 0 0;">Reservas Hoy</p>
        </div>
    </div>
    <div class="col">
        <div class="card" style="background:#f39c12; color:white; text-align:center;">
            <h3 style="color:white; border:none; margin:0;"><?= $stats['aseo_hoy'] ?></h3>
            <p style="margin:5px 0 0 0;">Limpiezas Hoy</p>
        </div>
    </div>
    <div class="col">
        <div class="card" style="background:#9b59b6; color:white; text-align:center;">
            <h3 style="color:white; border:none; margin:0;"><?= $stats['total_profesores'] ?></h3>
            <p style="margin:5px 0 0 0;">Profesores</p>
        </div>
    </div>
    <div class="col">
        <div class="card" style="background:#1abc9c; color:white; text-align:center;">
            <h3 style="color:white; border:none; margin:0;"><?= $stats['total_estudiantes'] ?></h3>
            <p style="margin:5px 0 0 0;">Estudiantes</p>
        </div>
    </div>
    <div class="col">
        <div class="card" style="background:#e67e22; color:white; text-align:center;">
            <h3 style="color:white; border:none; margin:0;"><?= $stats['total_aseo'] ?></h3>
            <p style="margin:5px 0 0 0;">Personal Aseo</p>
        </div>
    </div>
</div>

<!-- ENLACE A REPORTES -->
<div class="card" style="background:#2c3e50; color:white; text-align:center; margin-bottom:25px;">
    <h3 style="color:white; border:none; margin:0 0 10px 0;">📊 Módulo de Reportes Multitabla</h3>
    <p style="margin:0 0 15px 0;">Genera reportes de uso de aulas con JOIN de 3 tablas y check-in/out</p>
    <a href="reportes.php" style="background:#3498db; color:white; padding:12px 30px; text-decoration:none; border-radius:8px; font-weight:bold; display:inline-block;">
        📈 Ver Reportes Detallados
    </a>
</div>

<!-- DISPONIBILIDAD POR BLOQUES -->
<div class="card">
    <h3>🔍 Disponibilidad de Salones (Vista por Bloques)</h3>
    
    <form method="GET" action="" style="margin-bottom: 20px;">
        <div class="row">
            <div class="col">
                <label>Fecha:</label>
                <input type="date" name="fecha" value="<?= $fecha_busqueda ?>">
            </div>
            <div class="col" style="display:flex; align-items:flex-end;">
                <button type="submit" style="margin-top:0;">Filtrar</button>
            </div>
        </div>
    </form>

    <?php include '../includes/tabla_disponibilidad.php'; ?>
</div>

<!-- TABLA DE TODAS LAS RESERVAS -->
<div class="card">
    <h3>📋 Todas las Reservas del Sistema</h3>
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Salón</th>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Fecha</th>
                <th>Horario</th>
                <th>Tipo</th>
                <th>Motivo</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $sql = "SELECT r.*, s.codigo, s.nombre as salon_nombre, s.edificio, 
                       u.nombre as usuario_nombre, u.rol as usuario_rol
                FROM reservas r
                JOIN salones s ON r.salon_id = s.id
                JOIN usuarios u ON r.usuario_id = u.id
                WHERE r.fecha = ? AND r.estado = 'confirmada'
                ORDER BY r.hora_inicio ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fecha_busqueda]);
        $reservas = $stmt->fetchAll();
        
        if (count($reservas) === 0) {
            echo "<tr><td colspan='9' style='text-align:center; color:#888;'>No hay reservas para la fecha seleccionada</td></tr>";
        } else {
            foreach($reservas as $r): 
                $clase_fila = ($r['tipo_reserva'] === 'aseo') ? 'bg-aseo' : '';
            ?>
                <tr class="<?= $clase_fila ?>">
                    <td>#<?= $r['id'] ?></td>
                    <td><b><?= $r['codigo'] ?></b> - <?= $r['salon_nombre'] ?></td>
                    <td><?= $r['usuario_nombre'] ?></td>
                    <td><span class="badge" style="background:#ecf0f1;"><?= ucfirst($r['usuario_rol']) ?></span></td>
                    <td><?= date('d/m/Y', strtotime($r['fecha'])) ?></td>
                    <td><?= $r['hora_inicio'] . ' - ' . $r['hora_fin'] ?></td>
                    <td>
                        <?php if ($r['tipo_reserva'] === 'aseo'): ?>
                            <span class="badge badge-aseo">🧹 Aseo</span>
                        <?php else: ?>
                            <span class="badge badge-success">📚 Académica</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($r['motivo']) ?></td>
                    <td>
                        <a href="../controllers/cancelar_reserva.php?id=<?= $r['id'] ?>" 
                           class="btn-cancelar"
                           onclick="return confirm('¿Seguro que deseas cancelar esta reserva?')">
                           ❌ Cancelar
                        </a>
                    </td>
                </tr>
            <?php endforeach;
        }
        ?>
        </tbody>
    </table>
</div>

<!-- GESTIÓN DE SALONES -->
<div class="card">
    <h3>🏫 Gestión de Salones (Resumen)</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Bloque</th>
                <th>Piso</th>
                <th>Tipo</th>
                <th>Total Salones</th>
                <th>Capacidad Total</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $stmt = $pdo->query("
            SELECT edificio, piso, tipo, COUNT(*) as total, SUM(capacidad) as cap_total
            FROM salones
            GROUP BY edificio, piso, tipo
            ORDER BY edificio, piso
        ");
        foreach($stmt->fetchAll() as $row): ?>
            <tr>
                <td><b><?= $row['edificio'] ?></b></td>
                <td>Piso <?= $row['piso'] ?></td>
                <td><?= $row['tipo'] === 'computo' ? '💻 Cómputo' : '📚 Normal' ?></td>
                <td><?= $row['total'] ?> salones</td>
                <td><?= $row['cap_total'] ?> personas</td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>