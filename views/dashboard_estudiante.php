<?php 
require_once '../includes/auth_check.php'; 
require_once '../config/db.php';
include '../includes/header.php'; 

$fecha_busqueda = $_GET['fecha'] ?? date('Y-m-d');
?>

<h2>👨‍🎓 Panel de Estudiante - Consultar Disponibilidad</h2>

<div class="card">
    <h3>🔍 Buscar Salones Disponibles</h3>
    <form method="GET" action="">
        <div class="row">
            <div class="col">
                <label for="fecha"><b>Fecha:</b></label>
                <input type="date" name="fecha" id="fecha" value="<?= $fecha_busqueda ?>" required>
            </div>
            <div class="col" style="display:flex; align-items:flex-end;">
                <button type="submit">🔎 Buscar Disponibilidad</button>
            </div>
        </div>
    </form>
</div>

<?php include '../includes/tabla_disponibilidad.php'; ?>

<div class="card" style="background:#e8f4fd;">
    <h4>ℹ️ Leyenda de Estados</h4>
    <p>
        <span style="color:#28a745;">🟢 Verde</span> = Salón disponible &nbsp;|&nbsp;
        <span style="color:#dc3545;">🔴 Rojo</span> = Salón ocupado (reserva académica) &nbsp;|&nbsp;
        <span style="color:#856404;">🧹 Amarillo</span> = Salón en proceso de aseo
    </p>
    <p><small>💡 Haz clic en un bloque para ver sus salones. Haz clic en un salón con múltiples reservas para expandir detalles.</small></p>
</div>

<?php include '../includes/footer.php'; ?>