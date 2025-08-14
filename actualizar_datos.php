<?php
require_once 'conexion.php';
verificarAutenticacion();

$mensaje = '';
$tipo_mensaje = '';
$errores = [];

// Obtener datos actuales del cliente
try {
    $pdo = conectarBD();
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id_cliente = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $cliente = $stmt->fetch();
} catch (Exception $e) {
    $mensaje = "Error al cargar los datos: " . $e->getMessage();
    $tipo_mensaje = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar y validar datos
    $datos = [
        'identificacion' => sanitizar($_POST['identificacion'] ?? ''),
        'apellidos' => sanitizar($_POST['apellidos'] ?? ''),
        'nombre' => sanitizar($_POST['nombre'] ?? ''),
        'telefono_personal' => sanitizar($_POST['telefono_personal'] ?? ''),
        'direccion_personal' => sanitizar($_POST['direccion_personal'] ?? ''),
        'email' => sanitizar($_POST['email'] ?? ''),
        'lugar_trabajo' => sanitizar($_POST['lugar_trabajo'] ?? ''),
        'direccion_trabajo' => sanitizar($_POST['direccion_trabajo'] ?? ''),
        'telefono_trabajo' => sanitizar($_POST['telefono_trabajo'] ?? '')
    ];
    
    // Validaciones
    if (strlen($datos['identificacion']) < 8) {
        $errores['identificacion'] = 'La identificación debe tener al menos 8 caracteres';
    }
    
    if (strlen($datos['apellidos']) < 2) {
        $errores['apellidos'] = 'Los apellidos son obligatorios';
    }
    
    if (strlen($datos['nombre']) < 2) {
        $errores['nombre'] = 'El nombre es obligatorio';
    }
    
    if (!validarEmail($datos['email'])) {
        $errores['email'] = 'El email no tiene un formato válido';
    }
    
    if (empty($errores)) {
        try {
            // Verificar si la identificación ya existe (diferente al usuario actual)
            $stmt = $pdo->prepare("SELECT id_cliente FROM clientes WHERE identificacion = ? AND id_cliente != ?");
            $stmt->execute([$datos['identificacion'], $_SESSION['usuario_id']]);
            if ($stmt->fetch()) {
                $errores['identificacion'] = 'Esta identificación ya está registrada';
            }
            
            // Verificar si el email ya existe (diferente al usuario actual)
            $stmt = $pdo->prepare("SELECT id_cliente FROM clientes WHERE email = ? AND id_cliente != ?");
            $stmt->execute([$datos['email'], $_SESSION['usuario_id']]);
            if ($stmt->fetch()) {
                $errores['email'] = 'Este email ya está registrado';
            }
            
            if (empty($errores)) {
                // Actualizar datos
                $stmt = $pdo->prepare("
                    UPDATE clientes SET 
                        identificacion = ?, apellidos = ?, nombre = ?, 
                        telefono_personal = ?, direccion_personal = ?, email = ?,
                        lugar_trabajo = ?, direccion_trabajo = ?, telefono_trabajo = ?
                    WHERE id_cliente = ?
                ");
                
                if ($stmt->execute([
                    $datos['identificacion'], $datos['apellidos'], $datos['nombre'],
                    $datos['telefono_personal'], $datos['direccion_personal'], $datos['email'],
                    $datos['lugar_trabajo'], $datos['direccion_trabajo'], $datos['telefono_trabajo'],
                    $_SESSION['usuario_id']
                ])) {
                    // Actualizar nombre en sesión
                    $_SESSION['nombre_completo'] = $datos['nombre'] . ' ' . $datos['apellidos'];
                    
                    $mensaje = 'Datos actualizados correctamente';
                    $tipo_mensaje = 'success';
                    
                    // Recargar datos actualizados
                    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id_cliente = ?");
                    $stmt->execute([$_SESSION['usuario_id']]);
                    $cliente = $stmt->fetch();
                } else {
                    $mensaje = 'Error al actualizar los datos';
                    $tipo_mensaje = 'error';
                }
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
    <title>Actualizar Datos - Finanzas Seguras S.A.</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include 'header.php'; ?>
    <?php include 'nav.php'; ?>
    
    <div class="container">
        <div class="card fade-in">
            <h1 class="card-title">Actualizar Datos Personales</h1>
            
            <?php if ($mensaje): ?>
                <div class="alert alert-<?= $tipo_mensaje ?>">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>
            
            <form id="actualizarForm" method="POST" action="">
                <!-- Datos Personales -->
                <h3 style="margin-bottom: 20px; color: #4a5568;">Información Personal</h3>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="identificacion">Número de Identificación:</label>
                            <input type="text" 
                                   id="identificacion" 
                                   name="identificacion" 
                                   class="form-control <?= isset($errores['identificacion']) ? 'error' : '' ?>" 
                                   value="<?= htmlspecialchars($cliente['identificacion'] ?? '') ?>"
                                   required>
                            <?php if (isset($errores['identificacion'])): ?>
                                <span class="error-message"><?= $errores['identificacion'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="email">Correo Electrónico:</label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-control <?= isset($errores['email']) ? 'error' : '' ?>" 
                                   value="<?= htmlspecialchars($cliente['email'] ?? '') ?>"
                                   required>
                            <?php if (isset($errores['email'])): ?>
                                <span class="error-message"><?= $errores['email'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="nombre">Nombre:</label>
                            <input type="text" 
                                   id="nombre" 
                                   name="nombre" 
                                   class="form-control <?= isset($errores['nombre']) ? 'error' : '' ?>" 
                                   value="<?= htmlspecialchars($cliente['nombre'] ?? '') ?>"
                                   required>
                            <?php if (isset($errores['nombre'])): ?>
                                <span class="error-message"><?= $errores['nombre'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="apellidos">Apellidos:</label>
                            <input type="text" 
                                   id="apellidos" 
                                   name="apellidos" 
                                   class="form-control <?= isset($errores['apellidos']) ? 'error' : '' ?>" 
                                   value="<?= htmlspecialchars($cliente['apellidos'] ?? '') ?>"
                                   required>
                            <?php if (isset($errores['apellidos'])): ?>
                                <span class="error-message"><?= $errores['apellidos'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="telefono_personal">Teléfono Personal:</label>
                            <input type="tel" 
                                   id="telefono_personal" 
                                   name="telefono_personal" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($cliente['telefono_personal'] ?? '') ?>"
                                   placeholder="8888-8888">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="direccion_personal">Dirección Personal:</label>
                            <input type="text" 
                                   id="direccion_personal" 
                                   name="direccion_personal" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($cliente['direccion_personal'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                
                <!-- Información Laboral -->
                <h3 style="margin: 30px 0 20px 0; color: #4a5568;">Información Laboral</h3>
                
                <div class="form-row">
                    <div class="form-col">
                        <div class="form-group">
                            <label for="lugar_trabajo">Lugar de Trabajo:</label>
                            <input type="text" 
                                   id="lugar_trabajo" 
                                   name="lugar_trabajo" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($cliente['lugar_trabajo'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-col">
                        <div class="form-group">
                            <label for="telefono_trabajo">Teléfono del Trabajo:</label>
                            <input type="tel" 
                                   id="telefono_trabajo" 
                                   name="telefono_trabajo" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($cliente['telefono_trabajo'] ?? '') ?>"
                                   placeholder="2222-2222">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="direccion_trabajo">Dirección del Trabajo:</label>
                    <input type="text" 
                           id="direccion_trabajo" 
                           name="direccion_trabajo" 
                           class="form-control" 
                           value="<?= htmlspecialchars($cliente['direccion_trabajo'] ?? '') ?>">
                </div>
                
                <div class="form-row" style="margin-top: 30px;">
                    <div class="form-col">
                        <button type="submit" class="btn btn-primary" id="guardarBtn">
                            <span id="guardarText">Guardar Cambios</span>
                            <span id="guardarSpinner" class="loading" style="display: none;"></span>
                        </button>
                    </div>
                    <div class="form-col">
                        <a href="dashboard.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Validación en tiempo real
        $('#actualizarForm').on('submit', function(e) {
            let valid = true;
            
            // Limpiar errores previos
            $('.error-message').not('.server-error').text('');
            $('.form-control').removeClass('error');
            
            // Validar identificación
            const identificacion = $('#identificacion').val().trim();
            if (identificacion.length < 8) {
                mostrarError('identificacion', 'La identificación debe tener al menos 8 caracteres');
                valid = false;
            }
            
            // Validar nombre
            const nombre = $('#nombre').val().trim();
            if (nombre.length < 2) {
                mostrarError('nombre', 'El nombre es obligatorio');
                valid = false;
            }
            
            // Validar apellidos
            const apellidos = $('#apellidos').val().trim();
            if (apellidos.length < 2) {
                mostrarError('apellidos', 'Los apellidos son obligatorios');
                valid = false;
            }
            
            // Validar email
            const email = $('#email').val().trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                mostrarError('email', 'El email no tiene un formato válido');
                valid = false;
            }
            
            // Validar teléfonos si se proporcionan
            const telefonoPersonal = $('#telefono_personal').val().trim();
            if (telefonoPersonal && !validarTelefono(telefonoPersonal)) {
                mostrarError('telefono_personal', 'Formato de teléfono inválido (ej: 8888-8888)');
                valid = false;
            }
            
            const telefonoTrabajo = $('#telefono_trabajo').val().trim();
            if (telefonoTrabajo && !validarTelefono(telefonoTrabajo)) {
                mostrarError('telefono_trabajo', 'Formato de teléfono inválido (ej: 2222-2222)');
                valid = false;
            }
            
            if (valid) {
                // Mostrar loading
                $('#guardarBtn').prop('disabled', true);
                $('#guardarText').hide();
                $('#guardarSpinner').show();
            } else {
                e.preventDefault();
            }
        });
        
        // Formatear teléfonos automáticamente
        $('#telefono_personal, #telefono_trabajo').on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            if (value.length >= 4) {
                value = value.substring(0, 4) + '-' + value.substring(4, 8);
            }
            $(this).val(value);
        });
        
        // Limpiar errores al escribir
        $('.form-control').on('input', function() {
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
        
        function validarTelefono(telefono) {
            const telefonoRegex = /^\d{4}-\d{4}$/;
            return telefonoRegex.test(telefono);
        }
    });
    </script>
</body>
</html>