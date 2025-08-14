<?php
require_once 'conexion.php';
verificarAutenticacion();

try {
    // Obtener información del cliente
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id_cliente = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $cliente = $stmt->fetch();
    
    // Obtener préstamos activos
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_prestamos, SUM(monto_total) as monto_total FROM prestamos WHERE id_cliente = ? AND estado = 'Activo'");
    $stmt->execute([$_SESSION['usuario_id']]);
    $prestamos_info = $stmt->fetch();
    
    // Obtener total de pagos realizados
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_pagos, SUM(p.monto_pagado) as total_pagado 
        FROM pagos p 
        INNER JOIN prestamos pr ON p.id_prestamo = pr.id_prestamo 
        WHERE pr.id_cliente = ?
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $pagos_info = $stmt->fetch();
    
    // Obtener últimos pagos
    $stmt = $pdo->prepare("
        SELECT p.*, pr.monto_total 
        FROM pagos p 
        INNER JOIN prestamos pr ON p.id_prestamo = pr.id_prestamo 
        WHERE pr.id_cliente = ? 
        ORDER BY p.fecha_pago DESC 
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $ultimos_pagos = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Error al cargar los datos: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Finanzas Seguras S.A.</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'nav.php'; ?>
    
    <div class="container">
        <div class="card fade-in">
            <h1 class="card-title">¡Bienvenido, <?= htmlspecialchars($_SESSION['nombre_completo']) ?>!</h1>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
    </div>

    <script>
    $(document).ready(function() {
        // Animaciones al cargar
        $('.card').each(function(index) {
            $(this).delay(100 * index).animate({
                opacity: 1,
                transform: 'translateY(0)'
            }, 500);
        });
    });
    </script>
</body>
</html>        <?php endif; ?>
            
            <!-- Resumen de cuentas -->
            <div class="form-row">
                <div class="form-col">
                    <div class="card" style="background: linear-gradient(135deg, #3282b8, #2c5587); color: white; text-align: center;">
                        <h3>Préstamos Activos</h3>
                        <h2><?= $prestamos_info['total_prestamos'] ?? 0 ?></h2>
                        <p>Total: ₡<?= number_format($prestamos_info['monto_total'] ?? 0, 2) ?></p>
                    </div>
                </div>
                
                <div class="form-col">
                    <div class="card" style="background: linear-gradient(135deg, #2aa865, #2f855a); color: white; text-align: center;">
                        <h3>Pagos Realizados</h3>
                        <h2><?= $pagos_info['total_pagos'] ?? 0 ?></h2>
                        <p>Total: ₡<?= number_format($pagos_info['total_pagado'] ?? 0, 2) ?></p>
                    </div>
                </div>
                
                <div class="form-col">
                    <div class="card" style="background: linear-gradient(135deg, #3182ce, #2c5282); color: white; text-align: center;">
                        <h3>Saldo Pendiente</h3>
                        <h2>₡<?= number_format(($prestamos_info['monto_total'] ?? 0) - ($pagos_info['total_pagado'] ?? 0), 2) ?></h2>
                        <p>Por pagar</p>
                    </div>
                </div>
            </div>
            
            <!-- Últimos pagos -->
            <?php if (!empty($ultimos_pagos)): ?>
            <div class="card">
                <h2 class="card-title">Últimos Pagos Realizados</h2>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Monto Pagado</th>
                                <th>Número de Depósito</th>
                                <th>Préstamo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimos_pagos as $pago): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($pago['fecha_pago'])) ?></td>
                                <td>₡<?= number_format($pago['monto_pagado'], 2) ?></td>
                                <td><?= htmlspecialchars($pago['numero_deposito']) ?></td>
                                <td>#<?= $pago['id_prestamo'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="text-align: center; margin-top: 20px;">
                    <a href="historial_pagos.php" class="btn btn-primary">Ver Historial Completo</a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Acciones rápidas -->
            <div class="card">
                <h2 class="card-title">Acciones Rápidas</h2>
                <div class="form-row">
                    <div class="form-col">
                        <a href="actualizar_datos.php" class="btn btn-primary" style="width: 100%;">
                            Actualizar Datos Personales
                        </a>
                    </div>
                    <div class="form-col">
                        <a href="reportar_pago.php" class="btn btn-success" style="width: 100%;">
                            Reportar Nuevo Pago
                        </a>
                    </div>
                    <div class="form-col">
                        <a href="historial_pagos.php" class="btn btn-secondary" style="width: 100%;">
                            Ver Historial de Pagos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    