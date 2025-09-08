<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MOVESA</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../Views/Resources/plugins/fontawesome-free/css/all.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../Views/Resources/dist/css/adminlte.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="../Views/Resources/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../Views/Resources/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../Views/Resources/plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
</head>
<body class="hold-transition  sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<!-- Site wrapper -->
<div class="wrapper">
  <!-- HEADER -->
  <?php include "Modules/Header.php"?>
  <!-- /.HEADER -->

  <!-- Main Sidebar Container -->
  <?php include "Modules/Menu.php"?>
  <br>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">

     <!-- Main content -->
     <section class="content">
      <div class="container-fluid">
      <?php 
        /*include '../Services/validarAcceso.php';
        if (isset($_GET["Pages"])) {
          $pages = array("factura", "inventario", "cliente", "usuario", "roles", 
                         "empleados", "comprareporte", "ventareporte", "vehiculoreporte", 
                         "empleadoreporte", "dashboards", "dashboardsInicio", "compravehiculo", "apartado");

          if (in_array($_GET["Pages"], $pages)) {
            include "Pages/" . $_GET["Pages"] . ".php";
          }
        }*/

          if (isset($_GET["Pages"])) {
            $pages = array(
                "Almacen", "inventario", "Cliente", "Usuario", "roles",
                "empleados", "comprareporte", "ventareporte", "vehiculoreporte",
                "Empleado", "dashboards", "dashboardsInicio", "compravehiculo", "apartado",
                "ventavehiculo", "modelos", "inicio", "prueba","Resumen_por_canal_venta","Resumen_por_tipo_venta_sucursal",
                "Resumen_por_tipo_venta_canal","Resumen_por_tipo_venta","Resumen_por_sucursal",
                "Venta","Reporte","Import"
            );
            if (in_array($_GET["Pages"], $pages)) {
                include "Pages/" . $_GET["Pages"] . ".php";
            } else {
                echo "<p>Página no encontrada.</p>";
            }
        } else {
            include "Pages/Venta.php"; // Página por defecto
        }
      ?>
      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->

    <?php include "Modules/Footer.php"; ?>

  </div>
  <!-- /.content-wrapper -->


  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="../Views/Resources/plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="../Views/Resources/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="../Views/Resources/dist/js/adminlte.min.js"></script>
<!-- AdminLTE for demo purposes -->
<script src="../Views/Resources/dist/js/demo.js"></script>
<!-- DataTables  & Plugins -->
<script src="../Views/Resources/plugins/datatables/jquery.dataTables.min.js"></script>
<!-- DataTables  & Plugins -->
<script src="../Views/Resources/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../Views/Resources/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../Views/Resources/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../Views/Resources/plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../Views/Resources/plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="../Views/Resources/plugins/jszip/jszip.min.js"></script>
<script src="../Views/Resources/plugins/pdfmake/pdfmake.min.js"></script>
<script src="../Views/Resources/plugins/pdfmake/vfs_fonts.js"></script>
<script src="../Views/Resources/plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="../Views/Resources/plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="../Views/Resources/plugins/datatables-buttons/js/buttons.colVis.min.js"></script>



</body>
</html>
