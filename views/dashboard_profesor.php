<?php 
require_once '../includes/auth_check.php'; 
require_once '../config/db.php';
include '../includes/header.php'; 

$fecha_busqueda = $_GET['fecha'] ?? date('Y-m-d');
?>

<h2>👨‍🏫 Panel de Profesor - Agendar Salón</h2>

<?php if(isset($_SESSION['msg'])): ?>
    <div class="msg"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
<?php endif; ?>

<!-- FORMULARIO DE RESERVA -->
<div class="card">
    <h3>📅 Nueva Reserva Académica</h3>
    <form action="../controllers/reservar_salon.php" method="POST">
        <label for="salon_id"><b>Seleccionar Salón:</b></label>
        <select name="salon_id" id="salon_id" required>
            <option value="">-- Seleccione un salón --</option>
            <?php
            $sql = "SELECT * FROM salones ORDER BY edificio, piso, codigo";
            $salones = $pdo->query($sql)->fetchAll();
            $b_actual = ''; $p_actual = '';
            foreach($salones as $s) {
                if ($s['edificio'] !== $b_actual) { 
                    $b_actual = $s['edificio']; 
                    echo "<optgroup label='🏢 {$b_actual}'>"; 
                    $p_actual = ''; 
                }
                if ($s['piso'] !== $p_actual) { 
                    if($p_actual !== '') echo "</optgroup>"; 
                    $p_actual = $s['piso']; 
                    $tipo_label = ($s['tipo'] === 'computo') ? '💻' : '📚';
                    echo "<optgroup label='&nbsp;&nbsp;{$tipo_label} Piso {$p_actual}'>"; 
                }
                $tipo_icon = ($s['tipo'] === 'computo') ? '💻' : '';
                echo "<option value='{$s['id']}'>{$s['codigo']} - {$s['nombre']} {$tipo_icon} (Cap: {$s['capacidad']})</option>";
            }
            echo "</optgroup>";
            ?>
        </select>

        <label for="fecha"><b>Fecha:</b></label>
        <input type="date" name="fecha" id="fecha" value="<?= date('Y-m-d') ?>" required>

        <div class="row">
            <div class="col">
                <label for="hora_inicio"><b>Hora Inicio:</b></label>
                <input type="time" name="hora_inicio" id="hora_inicio" required>
            </div>
            <div class="col">
                <label for="hora_fin"><b>Hora Fin:</b></label>
                <input type="time" name="hora_fin" id="hora_fin" required>
            </div>
        </div>

        <label for="motivo"><b>Motivo / Materia:</b></label>
        <input type="text" name="motivo" id="motivo" placeholder="Ej: Clase de Ingeniería de Software - Grupo A" required>

        <button type="submit">📅 Reservar Salón</button>
    </form>
</div>

<!-- MIS RESERVAS CON CHECK-IN/CHECK-OUT -->
<div class="card">
    <h3>📋 Mis Reservas Activas</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Salón</th>
                <th>Fecha</th>
                <th>Horario</th>
                <th>Motivo</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $stmt = $pdo->prepare("SELECT r.*, s.codigo, s.nombre as salon_nombre, s.edificio 
                               FROM reservas r 
                               JOIN salones s ON r.salon_id = s.id 
                               WHERE r.usuario_id = ? AND r.estado = 'confirmada'
                               ORDER BY r.fecha DESC, r.hora_inicio ASC");
        $stmt->execute([$_SESSION['user_id']]);
        $mis_reservas = $stmt->fetchAll();
        
        if (count($mis_reservas) === 0) {
            echo "<tr><td colspan='6' style='text-align:center; color:#888; padding:20px;'>No tienes reservas activas</td></tr>";
        } else {
            foreach($mis_reservas as $r): 
                $puede_check_in = ($r['check_in'] === null);
                $puede_check_out = ($r['check_in'] !== null && $r['check_out'] === null);
                $ya_completada = ($r['check_out'] !== null);
            ?>
                <tr>
                    <td>
                        <b><?= $r['codigo'] ?></b><br>
                        <small><?= $r['salon_nombre'] ?></small><br>
                        <small style="color:#666;"><?= $r['edificio'] ?></small>
                    </td>
                    <td><?= date('d/m/Y', strtotime($r['fecha'])) ?></td>
                    <td><?= $r['hora_inicio'] . ' - ' . $r['hora_fin'] ?></td>
                    <td><?= htmlspecialchars($r['motivo']) ?></td>
                    <td>
                        <?php if ($ya_completada): ?>
                            <span class="badge-status status-completed">✅ Completada</span>
                        <?php elseif ($puede_check_out): ?>
                            <span class="badge-status status-active">🟢 En el salón</span>
                        <?php else: ?>
                            <span class="badge-status status-pending">⏳ Pendiente</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="acciones-cell">
                            <?php if ($puede_check_in): ?>
                                <form method="POST" action="../controllers/check_in_out.php">
                                    <input type="hidden" name="reserva_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="accion" value="check_in">
                                    <button type="submit" class="btn-checkin">🟢 Marcar Entrada</button>
                                </form>
                            <?php elseif ($puede_check_out): ?>
                                <form method="POST" action="../controllers/check_in_out.php">
                                    <input type="hidden" name="reserva_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="accion" value="check_out">
                                    <button type="submit" class="btn-checkout" onclick="return confirm('¿Confirmas que saliste del salón?')">🔴 Marcar Salida</button>
                                </form>
                            <?php else: ?>
                                <span class="btn-disabled">Finalizada</span>
                            <?php endif; ?>
                            
                            <a href="../controllers/cancelar_reserva.php?id=<?= $r['id'] ?>" 
                               class="btn-cancel"
                               onclick="return confirm('¿Seguro que deseas cancelar esta reserva?')">
                               ❌ Cancelar
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach;
        }
        ?>
        </tbody>
    </table>
</div>

<!-- TABLA DE DISPONIBILIDAD POR BLOQUES -->
<div class="card">
    <h3>🔍 Disponibilidad de Salones</h3>
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

<?php include '../includes/footer.php'; ?>