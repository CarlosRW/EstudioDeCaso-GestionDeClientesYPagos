<?php
require_once 'conexion.php';
verificarAutenticacion();

// Parámetros de búsqueda
$fecha_inicio = sanitizar($_GET['fecha_inicio'] ?? '');
$fecha_fin = sanitizar($_GET['fecha_fin'] ?? '');
$prestamo_id = intval($_GET['prestamo_id'] ?? 0);

$pagos = [];
$prestamos = [];

try {
    // Obtener préstamos del cliente para el filtro
    $stmt = $pdo->prepare("SELECT id_prestamo, monto_total, fecha_inicio FROM prestamos WHERE id_cliente = ? ORDER BY fecha_inicio DESC");
    $stmt->execute([$_SESSION['usuario_id']]);
    $prestamos = $stmt->fetchAll();
    
    // Construir consulta de pagos con filtros
    $sql = "
        SELECT p.*, pr.monto_total, pr.fecha_inicio 
        FROM pagos p 
        INNER JOIN prestamos pr ON p.id_prestamo = pr.id_prestamo 
        WHERE pr.id_cliente = ?
    ";
    $params = [$_SESSION['usuario_id']];
    
    if ($fecha_inicio) {
        $sql .= " AND p.fecha_pago >= ?";
        $params[] = $fecha_inicio;
    }
    
    if ($fecha_fin) {
        $sql .= " AND p.fecha_pago <= ?";
        $params[] = $fecha_fin;
    }
    
    if ($prestamo_id > 0) {
        $sql .= " AND p.id_prestamo = ?";
        $params[] = $prestamo_id;
    }
    
    $sql .= " ORDER BY p.fecha_pago DESC, p.id_pago DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pagos = $stmt->fetchAll();
    
    // Calcular totales
    $total_pagado = array_sum(array_column($pagos, 'monto_pagado'));
    $total_pagos = count($pagos);
    
} catch (Exception $e) {
    $error = "Error al cargar el historial: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Pagos - Finanzas Seguras S.A.</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'nav.php'; ?>
    
    <div class="container">
        <div class="card fade-in">
            <h1 class="card-title">Historial de Pagos</h1>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Filtros de búsqueda -->
            <div class="search-container">
                <h3>Filtros de Búsqueda</h3>
                <form method="GET" action="" id="filtrosForm">
                    <div class="search-row">
                        <div class="search-group">
                            <label for="fecha_inicio">Fecha Inicio:</label>
                            <input type="date" 
                                   id="fecha_inicio" 
                                   name="fecha_inicio" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($fecha_inicio) ?>">
                        </div>
                        
                        <div class="search-group">
                            <label for="fecha_fin">Fecha Fin:</label>
                            <input type="date" 
                                   id="fecha_fin" 
                                   name="fecha_fin" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($fecha_fin) ?>">
                        </div>
                        
                        <div class="search-group">
                            <label for="prestamo_id">Préstamo:</label>
                            <select id="prestamo_id" name="prestamo_id" class="form-control">
                                <option value="">Todos los préstamos</option>
                                <?php foreach ($prestamos as $prestamo): ?>
                                    <option value="<?= $prestamo['id_prestamo'] ?>" 
                                            <?= $prestamo_id == $prestamo['id_prestamo'] ? 'selected' : '' ?>>
                                        Préstamo #<?= $prestamo['id_prestamo'] ?> - 
                                        ₡<?= number_format($prestamo['monto_total'], 2) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="search-group">
                            <button type="submit" class="btn btn-primary">Buscar</button>
                            <a href="historial_pagos.php" class="btn btn-secondary">Limpiar</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Resumen -->
            <div class="form-row">
                <div class="form-col">
                    <div class="card summary-card" style="background: linear-gradient(135deg, #27ae60, #229954); color: white; text-align: center;">
                        <h3>Total de Pagos</h3>
                        <h2><?= $total_pagos ?></h2>
                        <p>Pagos encontrados</p>
                    </div>
                </div>
                <div class="form-col">
                    <div class="card summary-card" style="background: linear-gradient(135deg, #0F4C75, #3282B8); color: white; text-align: center;">
                        <h3>Monto Total</h3>
                        <h2>₡<?= number_format($total_pagado, 2) ?></h2>
                        <p>Total pagado</p>
                    </div>
                </div>
            </div>
            
            <!-- Tabla de pagos -->
            <?php if (empty($pagos)): ?>
                <div class="alert alert-info">
                    <?php if ($fecha_inicio || $fecha_fin || $prestamo_id): ?>
                        No se encontraron pagos con los filtros aplicados.
                    <?php else: ?>
                        No tienes pagos registrados aún.
                        <br><br>
                        <a href="reportar_pago.php" class="btn btn-primary">Registrar Primer Pago</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="card no-hover">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2>Pagos Registrados</h2>
                        <div>
                            <a href="reportar_pago.php" class="btn btn-success">Nuevo Pago</a>
                        </div>
                    </div>
                    
                    <div class="table-container">
                        <table class="table" id="tablaPagos">
                            <thead>
                                <tr>
                                    <th>Fecha de Pago</th>
                                    <th>Préstamo</th>
                                    <th>Monto Pagado</th>
                                    <th>Número de Depósito</th>
                                    <th>Monto del Préstamo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pagos as $pago): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($pago['fecha_pago'])) ?></td>
                                    <td>#<?= $pago['id_prestamo'] ?></td>
                                    <td class="monto">₡<?= number_format($pago['monto_pagado'], 2) ?></td>
                                    <td><?= htmlspecialchars($pago['numero_deposito']) ?></td>
                                    <td>₡<?= number_format($pago['monto_total'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr style="background: #f8fafc; font-weight: bold;">
                                    <td colspan="2">TOTAL</td>
                                    <td class="monto">₡<?= number_format($total_pagado, 2) ?></td>
                                    <td colspan="2"><?= $total_pagos ?> pagos</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Establecer fecha máxima como hoy
        const hoy = new Date().toISOString().split('T')[0];
        $('#fecha_inicio, #fecha_fin').attr('max', hoy);
        
        // Validar que fecha inicio sea menor que fecha fin
        $('#fecha_inicio, #fecha_fin').on('change', function() {
            const fechaInicio = $('#fecha_inicio').val();
            const fechaFin = $('#fecha_fin').val();
            
            if (fechaInicio && fechaFin && fechaInicio > fechaFin) {
                alert('La fecha de inicio no puede ser mayor que la fecha de fin');
                $(this).val('');
            }
        });
        
        // Auto-submit al cambiar filtros
        $('#prestamo_id').on('change', function() {
            $('#filtrosForm').submit();
        });
    });
    </script>
</body>
</html>