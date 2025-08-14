<?php
require_once 'conexion.php';
verificarAutenticacion();

$mensaje = '';
$tipo_mensaje = '';
$errores = [];
$prestamos = [];

// Obtener préstamos activos del cliente
try {
    $stmt = $pdo->prepare("SELECT id_prestamo, monto_total, fecha_inicio FROM prestamos WHERE id_cliente = ? AND estado = 'Activo'");
    $stmt->execute([$_SESSION['usuario_id']]);
    $prestamos = $stmt->fetchAll();
} catch (Exception $e) {
    $mensaje = "Error al cargar los préstamos: " . $e->getMessage();
    $tipo_mensaje = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_prestamo = intval($_POST['id_prestamo'] ?? 0);
    $monto_pagado = floatval($_POST['monto_pagado'] ?? 0);
    $fecha_pago = sanitizar($_POST['fecha_pago'] ?? '');
    $numero_deposito = sanitizar($_POST['numero_deposito'] ?? '');
    
    // Validaciones
    if ($id_prestamo <= 0) {
        $errores['id_prestamo'] = 'Debe seleccionar un préstamo válido';
    } else {
        // Verificar que el préstamo pertenece al cliente autenticado
        try {
            $stmt = $pdo->prepare("SELECT id_prestamo FROM prestamos WHERE id_prestamo = ? AND id_cliente = ? AND estado = 'Activo'");
            $stmt->execute([$id_prestamo, $_SESSION['usuario_id']]);
            if (!$stmt->fetch()) {
                $errores['id_prestamo'] = 'El préstamo seleccionado no es válido o no le pertenece';
            }
        } catch (Exception $e) {
            $errores['id_prestamo'] = 'Error al verificar el préstamo';
        }
    }
    
    if ($monto_pagado <= 0) {
        $errores['monto_pagado'] = 'El monto debe ser mayor a 0';
    } elseif ($monto_pagado > 50000) {
        $errores['monto_pagado'] = 'El monto no puede ser mayor a ₡50,000';
    }
    
    if (empty($fecha_pago)) {
        $errores['fecha_pago'] = 'La fecha de pago es obligatoria';
    } else {
        $fecha_actual = new DateTime();
        $fecha_pago_obj = DateTime::createFromFormat('Y-m-d', $fecha_pago);
        if (!$fecha_pago_obj) {
            $errores['fecha_pago'] = 'Formato de fecha inválido';
        } elseif ($fecha_pago_obj > $fecha_actual) {
            $errores['fecha_pago'] = 'La fecha no puede ser futura';
        }
    }
    
    if (strlen($numero_deposito) < 3) {
        $errores['numero_deposito'] = 'El número de depósito debe tener al menos 3 caracteres';
    } else {
        // Verificar que el número de depósito no esté duplicado
        try {
            $stmt = $pdo->prepare("SELECT id_pago FROM pagos WHERE numero_deposito = ?");
            $stmt->execute([$numero_deposito]);
            if ($stmt->fetch()) {
                $errores['numero_deposito'] = 'Este número de depósito ya ha sido registrado';
            }
        } catch (Exception $e) {
            $errores['numero_deposito'] = 'Error al verificar el número de depósito';
        }
    }
    
    if (empty($errores)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO pagos (id_prestamo, monto_pagado, fecha_pago, numero_deposito) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$id_prestamo, $monto_pagado, $fecha_pago, $numero_deposito])) {
                $mensaje = 'Pago registrado correctamente';
                $tipo_mensaje = 'success';
                
                // Limpiar formulario después del éxito
                $_POST = [];
            } else {
                $mensaje = 'Error al registrar el pago';
                $tipo_mensaje = 'error';
            }
        } catch (Exception $e) {
            $mensaje = 'Error de conexión: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportar Pago - Finanzas Seguras S.A.</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'nav.php'; ?>
    
    <div class="container">
        <div class="card fade-in">
            <h1 class="card-title">Reportar Pago de Crédito</h1>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo_mensaje ?>">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($prestamos)): ?>
                <div class="alert alert-info">
                    No tienes préstamos activos para reportar pagos.
                    <br><br>
                    <a href="dashboard.php" class="btn btn-primary">Volver al Dashboard</a>
                </div>
            <?php else: ?>
                <form id="pagoForm" method="POST" action="">
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="id_prestamo">Préstamo:</label>
                                <select id="id_prestamo" 
                                        name="id_prestamo" 
                                        class="form-control <?= isset($errores['id_prestamo']) ? 'error' : '' ?>"
                                        required>
                                    <option value="">Seleccione un préstamo</option>
                                    <?php foreach ($prestamos as $prestamo): ?>
                                        <option value="<?= $prestamo['id_prestamo'] ?>" 
                                                <?= (($_POST['id_prestamo'] ?? '') == $prestamo['id_prestamo']) ? 'selected' : '' ?>>
                                            Préstamo #<?= $prestamo['id_prestamo'] ?> - 
                                            ₡<?= number_format($prestamo['monto_total'], 2) ?> 
                                            (<?= date('d/m/Y', strtotime($prestamo['fecha_inicio'])) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errores['id_prestamo'])): ?>
                                    <span class="error-message"><?= $errores['id_prestamo'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="monto_pagado">Monto Pagado (₡):</label>
                                <input type="number" 
                                       id="monto_pagado" 
                                       name="monto_pagado" 
                                       class="form-control <?= isset($errores['monto_pagado']) ? 'error' : '' ?>" 
                                       step="0.01"
                                       min="0.01"
                                       max="50000"
                                       value="<?= htmlspecialchars($_POST['monto_pagado'] ?? '') ?>"
                                       placeholder="Ej: 500.00"
                                       required>
                                <?php if (isset($errores['monto_pagado'])): ?>
                                    <span class="error-message"><?= $errores['monto_pagado'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="fecha_pago">Fecha del Pago:</label>
                                <input type="date" 
                                       id="fecha_pago" 
                                       name="fecha_pago" 
                                       class="form-control <?= isset($errores['fecha_pago']) ? 'error' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['fecha_pago'] ?? date('Y-m-d')) ?>"
                                       max="<?= date('Y-m-d') ?>"
                                       required>
                                <?php if (isset($errores['fecha_pago'])): ?>
                                    <span class="error-message"><?= $errores['fecha_pago'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="numero_deposito">Número de Depósito:</label>
                                <input type="text" 
                                       id="numero_deposito" 
                                       name="numero_deposito" 
                                       class="form-control <?= isset($errores['numero_deposito']) ? 'error' : '' ?>" 
                                       value="<?= htmlspecialchars($_POST['numero_deposito'] ?? '') ?>"
                                       placeholder="Ej: DEP123456"
                                       required>
                                <?php if (isset($errores['numero_deposito'])): ?>
                                    <span class="error-message"><?= $errores['numero_deposito'] ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información del préstamo seleccionado -->
                    <div id="prestamoInfo" class="card" style="background: #f8fafc; display: none; margin: 20px 0;">
                        <h4>Información del Préstamo</h4>
                        <div id="prestamoDetalles"></div>
                    </div>
                    
                    <div class="form-row" style="margin-top: 30px;">
                        <div class="form-col">
                            <button type="submit" class="btn btn-success" id="pagoBtn">
                                <span id="pagoText">Registrar Pago</span>
                                <span id="pagoSpinner" class="loading" style="display: none;"></span>
                            </button>
                        </div>
                        <div class="form-col">
                            <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Mostrar información del préstamo al seleccionar
        $('#id_prestamo').on('change', function() {
            const prestamoId = $(this).val();
            if (prestamoId) {
                const selectedOption = $(this).find('option:selected');
                const texto = selectedOption.text();
                
                $('#prestamoDetalles').html(`
                    <p><strong>Préstamo seleccionado:</strong> ${texto}</p>
                    <p><em>Solo puedes reportar pagos de tus propios préstamos.</em></p>
                `);
                $('#prestamoInfo').slideDown();
            } else {
                $('#prestamoInfo').slideUp();
            }
        });
        
        // Validación del formulario
        $('#pagoForm').on('submit', function(e) {
            let valid = true;
            
            // Limpiar errores previos
            $('.error-message').not('.server-error').text('');
            $('.form-control').removeClass('error');
            
            // Validar préstamo
            const prestamo = $('#id_prestamo').val();
            if (!prestamo) {
                mostrarError('id_prestamo', 'Debe seleccionar un préstamo');
                valid = false;
            }
            
            // Validar monto
            const monto = parseFloat($('#monto_pagado').val());
            if (isNaN(monto) || monto <= 0) {
                mostrarError('monto_pagado', 'El monto debe ser mayor a 0');
                valid = false;
            } else if (monto > 50000) {
                mostrarError('monto_pagado', 'El monto no puede ser mayor a ₡50,000');
                valid = false;
            }
            
            // Validar fecha
            const fecha = $('#fecha_pago').val();
            if (!fecha) {
                mostrarError('fecha_pago', 'La fecha es obligatoria');
                valid = false;
            } else {
                const fechaSeleccionada = new Date(fecha);
                const fechaHoy = new Date();
                if (fechaSeleccionada > fechaHoy) {
                    mostrarError('fecha_pago', 'La fecha no puede ser futura');
                    valid = false;
                }
            }
            
            // Validar número de depósito
            const numeroDeposito = $('#numero_deposito').val().trim();
            if (numeroDeposito.length < 3) {
                mostrarError('numero_deposito', 'El número de depósito debe tener al menos 3 caracteres');
                valid = false;
            }
            
            if (valid) {
                // Mostrar loading
                $('#pagoBtn').prop('disabled', true);
                $('#pagoText').hide();
                $('#pagoSpinner').show();
            } else {
                e.preventDefault();
            }
        });
        
        // Formatear monto mientras se escribe
        $('#monto_pagado').on('input', function() {
            let value = $(this).val();
            // Permitir solo números y punto decimal
            value = value.replace(/[^0-9.]/g, '');
            // Permitir solo un punto decimal
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            // Limitar decimales a 2
            if (parts[1] && parts[1].length > 2) {
                value = parts[0] + '.' + parts[1].substring(0, 2);
            }
            $(this).val(value);
        });
        
        // Convertir número de depósito a mayúsculas
        $('#numero_deposito').on('input', function() {
            $(this).val($(this).val().toUpperCase());
        });
        
        // Limpiar errores al escribir
        $('.form-control').on('input change', function() {
            $(this).removeClass('error');
            const fieldName = $(this).attr('name');
            $('#' + fieldName + 'Error').text('');
        });
        
        function mostrarError(campo, mensaje) {
            $('#' + campo).addClass('error');
            const errorSpan = $('#' + campo).siblings('.error-message');
            if (errorSpan.length === 0) {
                $('#' + campo).after('<span class="error-message" id="' + campo + 'Error">' + mensaje + '</span>');
            } else {
                errorSpan.text(mensaje);
            }
        }
    });
    </script>
</body>
</html>