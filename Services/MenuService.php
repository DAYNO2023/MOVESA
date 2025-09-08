
<?php
function generarMenu() {
    // Obtener página activa
    $pagina = isset($_GET['Pages']) ? $_GET['Pages'] : '';

    // Asegurar sesión y obtener rol (Es_Admin)
    if (session_status() == PHP_SESSION_NONE) {
        @session_start();
    }
    $esAdmin = 0;
    if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario'])) {
        $esAdmin = (int)($_SESSION['usuario']['rol'] ?? 0);
    }

    echo '<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">';

    // Ventas (visible para todos)
    $activeVenta = ($pagina == 'Venta') ? 'active' : '';
    echo '<li class="nav-item">';
    echo '<a href="?Pages=Venta" class="nav-link ' . $activeVenta . '"><i class="fa-solid fa-bag-shopping"></i><p> Ventas</p></a>';
    echo '</li>';

    // Si es admin mostrar el resto del menú
    if ($esAdmin === 1) {
        // Accesos
        $activeUsuario = ($pagina == 'Usuario') ? 'active' : '';
        $menuOpenAcceso = ($pagina == 'Usuario') ? 'menu-open' : '';
        echo '<li class="nav-item ' . $menuOpenAcceso . '" id="EsquemaAcceso">';
        echo '<a href="#" class="nav-link" id="LinkAcceso"><i class="fa-solid fa-lock"></i><p> Accesos<i class="fas fa-angle-left right"></i></p></a>';
        echo '<ul class="nav nav-treeview">';
        echo '<li class="nav-item"><a href="?Pages=Usuario" class="nav-link ' . $activeUsuario . '"><i class="fa fa-minus"></i><p>Usuarios</p></a></li>';
        echo '</ul>';
        echo '</li>';

        // Indicadores
        $activeResumenSucursal = ($pagina == 'Resumen_por_sucursal') ? 'active' : '';
        $menuOpenIndicadores = ($pagina == 'Resumen_por_sucursal') ? 'menu-open' : '';
        echo '<li class="nav-item ' . $menuOpenIndicadores . '" id="EsquemaIndicadores">';
        echo '<a href="#" class="nav-link" id="LinkGeneral"><i class="fa-solid fa-chart-column"></i><p> Indicadores<i class="fas fa-angle-left right"></i></p></a>';
        echo '<ul class="nav nav-treeview">';
        echo '<li class="nav-item"><a href="?Pages=Resumen_por_sucursal" class="nav-link ' . $activeResumenSucursal . '"><i class="fa fa-minus"></i><p>R. por Sucursal</p></a></li>';
        echo '</ul>';
        echo '</li>';

        // Reportes
        $activeReporte = ($pagina == 'Reporte') ? 'active' : '';
        $menuOpenReportes = ($pagina == 'Reporte') ? 'menu-open' : '';
        echo '<li class="nav-item ' . $menuOpenReportes . '" id="EsquemaReportes">';
        echo '<a href="#" class="nav-link" id="LinkReportes"><i class="fa-solid fa-file"></i><p> Reportes<i class="fas fa-angle-left right"></i></p></a>';
        echo '<ul class="nav nav-treeview">';
        echo '<li class="nav-item"><a href="?Pages=Reporte" class="nav-link ' . $activeReporte . '"><i class="fa fa-minus"></i><p>Reporte de Ventas</p></a></li>';
        echo '</ul>';
        echo '</li>';
    }

    echo '</ul>';
}
?>
