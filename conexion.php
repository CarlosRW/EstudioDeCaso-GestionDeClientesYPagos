<?php
// conexion.php
$host = "localhost";
$dbname = "finanzas_seguras";
$user = "root";
$pass = "Skyy231005.";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Conectar a la base de datos
function conectarBD() {
    global $pdo;
    return $pdo;
}

// Función para iniciar sesión segura
function iniciarSesion() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Función para verificar si el usuario está autenticado
function verificarAutenticacion() {
    iniciarSesion();
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: login.php');
        exit();
    }
}

// Función para sanitizar datos de entrada
function sanitizar($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Función para validar email
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Función para generar hash de contraseña
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Función para verificar contraseña
function verificarPassword($password, $hash) {
    return password_verify($password, $hash);
}
?>