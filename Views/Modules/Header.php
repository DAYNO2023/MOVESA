<?php
if (session_status() == PHP_SESSION_NONE) {
  @session_start();
}

$usuarioNombre = 'Invitado';
$usuarioRolTexto = '';
if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario'])) {
  $usuarioNombre = htmlspecialchars($_SESSION['usuario']['nombre'] ?? 'Invitado', ENT_QUOTES, 'UTF-8');
  $esAdmin = (int)($_SESSION['usuario']['rol'] ?? 0);
  $usuarioRolTexto = ($esAdmin === 1) ? 'Administrador' : 'Vendedor';
}

?>
<nav class="main-header navbar navbar-expand navbar-dark" style="background-color:#000">
  <!-- Left navbar links -->
  <ul class="navbar-nav">
    <li class="nav-item">
    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
    </li>
  </ul>
  <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
    <li class="nav-item d-flex align-items-center">
    <span class="mr-2 text-white"><?php echo $usuarioNombre; ?> | <?php echo $usuarioRolTexto; ?></span>
  <a class="nav-link" href="#" data-toggle="modal" data-target="#settingsModal" title="Ajustes">
      <i class="fas fa-cog"></i>
    </a>
    </li>
  </ul>
</nav>

<!-- Modal de Ajustes -->
<div class="modal fade" id="settingsModal" tabindex="-1" role="dialog" aria-labelledby="settingsModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
  <div class="modal-content">
    <div class="modal-header">
    <h5 class="modal-title" id="settingsModalLabel">Cerrar sesión</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
      <span aria-hidden="true">&times;</span>
    </button>
    </div>
    <div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-dismiss="modal">No</button>
  <a href="/MOVESA/Controllers/logout.php" class="btn btn-danger">Si</a>
    </div>
  </div>
  </div>
</div>