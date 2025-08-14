<?php
require_once 'conexion.php';

iniciarSesion();

// Si ya está autenticado, redirigir
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = sanitizar($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($usuario) || empty($password)) {
        $error = 'Por favor complete todos los campos';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id_cliente, usuario, contrasena, nombre, apellidos FROM clientes WHERE usuario = ?");
            $stmt->execute([$usuario]);
            $cliente = $stmt->fetch();
            
            if ($cliente) {
                // Verificar si la contraseña está hasheada o es texto plano
                $password_valid = false;
                
                // Si la contraseña en BD comienza con $2y$ es un hash bcrypt
                if (substr($cliente['contrasena'], 0, 4) === '$2y$') {
                    $password_valid = password_verify($password, $cliente['contrasena']);
                } else {
                    // Comparación directa para contraseñas en texto plano (temporal)
                    $password_valid = ($password === $cliente['contrasena']);
                    
                    // Actualizar a hash si es válida (migración automática)
                    if ($password_valid) {
                        $new_hash = password_hash($password, PASSWORD_DEFAULT);
                        $update_stmt = $pdo->prepare("UPDATE clientes SET contrasena = ? WHERE id_cliente = ?");
                        $update_stmt->execute([$new_hash, $cliente['id_cliente']]);
                    }
                }
                
                if ($password_valid) {
                    $_SESSION['usuario_id'] = $cliente['id_cliente'];
                    $_SESSION['usuario'] = $cliente['usuario'];
                    $_SESSION['nombre_completo'] = $cliente['nombre'] . ' ' . $cliente['apellidos'];
                    
                    // Log para debugging (remover en producción)
                    error_log("Login exitoso para usuario: " . $usuario);
                    
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error = 'Usuario o contraseña incorrectos';
                    error_log("Login fallido para usuario: " . $usuario . " - Contraseña incorrecta");
                }
            } else {
                $error = 'Usuario o contraseña incorrectos';
                error_log("Login fallido - Usuario no encontrado: " . $usuario);
            }
        } catch (Exception $e) {
            $error = 'Error de conexión. Intente nuevamente';
            error_log("Error en login: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Finanzas Seguras S.A.</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="login-container">
        <div class="login-card fade-in">
            <h1 class="login-title">Finanzas Seguras S.A.</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form id="loginForm" method="POST" action="">
                <div class="form-group">
                    <label for="usuario">Usuario:</label>
                    <input type="text" 
                           id="usuario" 
                           name="usuario" 
                           class="form-control" 
                           value="<?= htmlspecialchars($usuario ?? '') ?>"
                           required>
                    <span class="error-message" id="usuarioError"></span>
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           required>
                    <span class="error-message" id="passwordError"></span>
                </div>
                
                <button type="submit" class="btn btn-primary" id="loginBtn">
                    <span id="loginText">Iniciar Sesión</span>
                    <span id="loginSpinner" class="loading" style="display: none;"></span>
                </button>
            </form>
            
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 14px; color: #718096;">
                <strong>Usuario de prueba:</strong><br>
                Usuario: juanp<br>
                Contraseña: password123
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('#loginForm').on('submit', function(e) {
            // Limpiar errores previos
            $('.error-message').text('');
            $('.form-control').removeClass('error');
            
            let valid = true;
            
            // Validar usuario
            const usuario = $('#usuario').val().trim();
            if (usuario.length < 3) {
                $('#usuarioError').text('El usuario debe tener al menos 3 caracteres');
                $('#usuario').addClass('error');
                valid = false;
            }
            
            // Validar contraseña
            const password = $('#password').val();
            if (password.length < 6) {
                $('#passwordError').text('La contraseña debe tener al menos 6 caracteres');
                $('#password').addClass('error');
                valid = false;
            }
            
            if (valid) {
                // Mostrar loading
                $('#loginBtn').prop('disabled', true);
                $('#loginText').hide();
                $('#loginSpinner').show();
            } else {
                e.preventDefault();
            }
        });
        
        // Limpiar errores al escribir
        $('.form-control').on('input', function() {
            $(this).removeClass('error');
            $(this).siblings('.error-message').text('');
        });
    });
    </script>
</body>
</html>