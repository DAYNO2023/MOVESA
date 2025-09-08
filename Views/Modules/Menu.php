<aside class="main-sidebar sidebar-dark-primary elevation-4" style="background-color:#000">
    <!-- Brand Logo -->
    <a href="../Views/Resources/index3.html" class="brand-link" style="background-color:#000">
      <img src="../Views/Resources\dist\img\MOVESA-fondo-rojo.png" alt="grupo rac" class="brand-image elevation-3">
      <span class="brand-text font-weight-light" style="color:white">MOVESA</span>
    </a>
    
    <?php
      /*if (session_status() == PHP_SESSION_NONE) {
          session_start();
      }*/
      //include '../config.php'; // Asegúrate de incluir la conexión a la base de datos.
      include '../Services/MenuService.php';
      //$nombreCompleto = isset($_SESSION['nombre_completo']) ? $_SESSION['nombre_completo'] : 'Usuario invitado';
    ?>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <?php generarMenu(); ?>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>