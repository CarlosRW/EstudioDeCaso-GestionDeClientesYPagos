<?php
// nav.php
if (!isset($_SESSION['usuario_id'])) {
    return;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="nav">
    <ul>
        <li>
            <a href="dashboard.php" <?= $current_page === 'dashboard.php' ? 'class="active"' : '' ?>>
                Dashboard
            </a>
        </li>
        <li>
            <a href="actualizar_datos.php" <?= $current_page === 'actualizar_datos.php' ? 'class="active"' : '' ?>>
                Actualizar Datos
            </a>
        </li>
        <li>
            <a href="reportar_pago.php" <?= $current_page === 'reportar_pago.php' ? 'class="active"' : '' ?>>
                Reportar Pago
            </a>
        </li>
        <li>
            <a href="historial_pagos.php" <?= $current_page === 'historial_pagos.php' ? 'class="active"' : '' ?>>
                Historial de Pagos
            </a>
        </li>
    </ul>
</nav>