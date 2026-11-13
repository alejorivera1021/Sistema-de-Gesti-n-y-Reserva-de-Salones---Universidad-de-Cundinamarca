<?php
/**
 * Componente reutilizable: Tabla de Disponibilidad con botones por bloque
 * Uso: include '../includes/tabla_disponibilidad.php';
 * Requiere: $pdo (conexión BD), $fecha_busqueda
 */

// Consulta para obtener todas las reservas de cada salón
$sql = "SELECT s.*, 
               r.id as res_id, r.hora_inicio, r.hora_fin, r.motivo, r.tipo_reserva,
               u.nombre as user_nombre, u.rol as user_rol
        FROM salones s
        LEFT JOIN reservas r ON s.id = r.salon_id AND r.fecha = ? AND r.estado = 'confirmada'
        LEFT JOIN usuarios u ON r.usuario_id = u.id
        WHERE 1=1";

$params = [$fecha_busqueda];

$sql .= " ORDER BY s.edificio, s.piso, s.codigo, r.hora_inicio ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$todas_reservas = $stmt->fetchAll();

// Agrupar reservas por salón y luego por bloque
$salones_agrupados = [];
foreach ($todas_reservas as $reserva) {
    $salon_id = $reserva['id'];
    if (!isset($salones_agrupados[$salon_id])) {
        $salones_agrupados[$salon_id] = [
            'info' => $reserva,
            'reservas' => []
        ];
    }
    if ($reserva['res_id']) {
        $salones_agrupados[$salon_id]['reservas'][] = $reserva;
    }
}

// Agrupar por bloque
$bloques = [];
foreach ($salones_agrupados as $salon_id => $datos) {
    $bloque = $datos['info']['edificio'];
    if (!isset($bloques[$bloque])) {
        $bloques[$bloque] = [];
    }
    $bloques[$bloque][$salon_id] = $datos;
}

// Contar reservas por bloque para mostrar en los botones
$contadores = [];
foreach ($bloques as $bloque => $salones) {
    $total = 0;
    $ocupados = 0;
    $aseo = 0;
    foreach ($salones as $datos) {
        $total += count($datos['reservas']);
        foreach ($datos['reservas'] as $r) {
            if ($r['tipo_reserva'] === 'aseo') $aseo++;
            else $ocupados++;
        }
    }
    $contadores[$bloque] = ['total' => $total, 'ocupados' => $ocupados, 'aseo' => $aseo];
}
?>

<style>
.bloque-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 15px 20px;
    margin-bottom: 10px;
    border: 2px solid #e1e8ed;
    border-radius: 10px;
    background: white;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 1rem;
    font-weight: 600;
    color: #2c3e50;
}
.bloque-btn:hover {
    border-color: #3498db;
    background: #f8f9fa;
    transform: translateX(5px);
}
.bloque-btn.activo {
    border-color: #3498db;
    background: #3498db;
    color: white;
}
.bloque-btn .icono-bloque {
    font-size: 1.5rem;
    margin-right: 10px;
}
.bloque-btn .info-bloque {
    flex: 1;
    text-align: left;
}
.bloque-btn .badge-contador {
    background: rgba(0,0,0,0.1);
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 0.85rem;
}
.bloque-btn.activo .badge-contador {
    background: rgba(255,255,255,0.3);
}
.bloque-contenido {
    display: none;
    animation: slideDown 0.3s ease-out;
    margin-bottom: 20px;
}
.bloque-contenido.activo {
    display: block;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.fila-salon {
    cursor: pointer;
    transition: background-color 0.2s;
}
.fila-salon:hover {
    background-color: #f8f9fa !important;
}
.fila-reservas {
    display: none;
    background-color: #f8f9fa;
}
.fila-reservas.activa {
    display: table-row;
}
.icono-expandir {
    transition: transform 0.3s;
    display: inline-block;
}
.icono-expandir.rotado {
    transform: rotate(180deg);
}
.badge-multiple {
    background: #6c757d;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.75rem;
    margin-left: 5px;
}
.piso-separador {
    background: #e9ecef;
    font-weight: bold;
    color: #495057;
    padding: 8px 15px;
}
</style>

<!-- Botones de bloques -->
<div class="bloques-container">
    <?php foreach ($bloques as $bloque => $salones): 
        $icono = ($bloque === 'Bloque C') ? '💻' : '🏢';
        $contador = $contadores[$bloque];
        $total_salones = count($salones);
    ?>
        <button class="bloque-btn" onclick="toggleBloque('<?= $bloque ?>')" id="btn-<?= $bloque ?>">
            <span class="icono-bloque"><?= $icono ?></span>
            <span class="info-bloque"><?= $bloque ?> (<?= $total_salones ?> salones)</span>
            <span class="badge-contador">
                <?= $contador['ocupados'] ?> ocupados · <?= $contador['aseo'] ?> en aseo
            </span>
        </button>
        
        <div class="bloque-contenido" id="contenido-<?= $bloque ?>">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;"></th>
                        <th>Código</th>
                        <th>Salón</th>
                        <th>Horario(s)</th>
                        <th>Estado</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                $piso_actual = '';
                foreach ($salones as $salon_id => $datos): 
                    $salon = $datos['info'];
                    $reservas = $datos['reservas'];
                    $total_reservas = count($reservas);
                    
                    // Separador de piso
                    if ($salon['piso'] !== $piso_actual) {
                        $piso_actual = $salon['piso'];
                        $tipo_piso = ($salon['tipo'] === 'computo') ? '💻 Laboratorios de Cómputo' : '📚 Salones Normales';
                        echo "<tr class='piso-separador'><td colspan='6'>{$tipo_piso} - Piso {$piso_actual}</td></tr>";
                    }
                    
                    // Determinar estado general del salón
                    if ($total_reservas === 0) {
                        $estado_general = '🟢 Disponible';
                        $clase_general = '';
                        $horarios_texto = 'Libre todo el día';
                        $detalle_texto = '-';
                    } else {
                        $primera_reserva = $reservas[0];
                        if ($primera_reserva['tipo_reserva'] === 'aseo') {
                            $estado_general = '🧹 EN ASEO';
                            $clase_general = 'bg-aseo';
                        } else {
                            $estado_general = '🔴 Ocupado';
                            $clase_general = 'bg-ocupado';
                        }
                        
                        if ($total_reservas === 1) {
                            $horarios_texto = $primera_reserva['hora_inicio'] . ' - ' . $primera_reserva['hora_fin'];
                            $detalle_texto = '<b>' . $primera_reserva['user_nombre'] . '</b><br><small>' . htmlspecialchars($primera_reserva['motivo']) . '</small>';
                        } else {
                            $horarios_texto = $total_reservas . ' reservas <span class="badge-multiple">Ver todas</span>';
                            $detalle_texto = 'Click para expandir';
                        }
                    }
                ?>
                    <tr class="fila-salon <?= $clase_general ?>" onclick="toggleReservas(<?= $salon_id ?>)">
                        <td>
                            <?php if ($total_reservas > 1): ?>
                                <span class="icono-expandir" id="icon-<?= $salon_id ?>">▼</span>
                            <?php else: ?>
                                <span style="visibility: hidden;">▼</span>
                            <?php endif; ?>
                        </td>
                        <td><b><?= $salon['codigo'] ?></b></td>
                        <td><?= $salon['nombre'] ?></td>
                        <td><?= $horarios_texto ?></td>
                        <td><strong><?= $estado_general ?></strong></td>
                        <td><?= $detalle_texto ?></td>
                    </tr>
                    
                    <?php if ($total_reservas > 1): ?>
                        <?php foreach ($reservas as $reserva): 
                            $clase_reserva = ($reserva['tipo_reserva'] === 'aseo') ? 'bg-aseo' : 'bg-ocupado';
                            $estado_reserva = ($reserva['tipo_reserva'] === 'aseo') ? '🧹 EN ASEO' : '🔴 Ocupado';
                        ?>
                            <tr class="fila-reservas <?= $clase_reserva ?>" id="reservas-<?= $salon_id ?>">
                                <td></td>
                                <td colspan="2" style="padding-left: 40px;">
                                    <small>↳ <?= $reserva['hora_inicio'] ?> - <?= $reserva['hora_fin'] ?></small>
                                </td>
                                <td colspan="2"><?= $estado_reserva ?></td>
                                <td>
                                    <b><?= $reserva['user_nombre'] ?></b> 
                                    <span class="badge" style="background:#ecf0f1; font-size:0.7rem;"><?= ucfirst($reserva['user_rol']) ?></span>
                                    <br><small><?= htmlspecialchars($reserva['motivo']) ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>

<script>
function toggleBloque(bloque) {
    const contenido = document.getElementById('contenido-' + bloque);
    const btn = document.getElementById('btn-' + bloque);
    const estaActivo = contenido.classList.contains('activo');
    
    // Cerrar todos los bloques
    document.querySelectorAll('.bloque-contenido').forEach(el => el.classList.remove('activo'));
    document.querySelectorAll('.bloque-btn').forEach(el => el.classList.remove('activo'));
    
    // Abrir el seleccionado si no estaba activo
    if (!estaActivo) {
        contenido.classList.add('activo');
        btn.classList.add('activo');
    }
}

function toggleReservas(salonId) {
    const filasReservas = document.querySelectorAll('#reservas-' + salonId);
    const icono = document.getElementById('icon-' + salonId);
    
    if (filasReservas.length > 0) {
        filasReservas.forEach(fila => {
            fila.classList.toggle('activa');
        });
        
        if (icono) {
            icono.classList.toggle('rotado');
        }
    }
}
</script>