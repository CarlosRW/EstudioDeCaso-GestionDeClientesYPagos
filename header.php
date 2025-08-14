<?php
// header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header class="header">
    <div class="header-content">
        <div class="logo">
            Finanzas Seguras S.A.
        </div>
        <?php if (isset($_SESSION['usuario_id'])): ?>
        <div class="user-info">
            <span class="user-name">
                <?= htmlspecialchars($_SESSION['nombre_completo']) ?>
            </span>
            <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
        </div>
        <?php endif; ?>
    </div>
</header>