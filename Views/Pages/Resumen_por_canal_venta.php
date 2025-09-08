<!-- Ejemplo de tabla con Bootstrap y DataTables -->
<link rel="stylesheet" href="../Views/Resources/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

<div class="container mt-4">
  <div class="card">
    <div class="card-header bg-warning text-brown font-weight-bold">
      Indicador Motos - Resumen por Canales de Venta
    </div>
    <div class="card-body p-0">
      <table id="tablaCanales" class="table table-bordered table-striped">
        <thead class="thead-light">
          <tr>
            <th>CANAL</th>
            <th>DIA</th>
            <th>SEMANA</th>
            <th>MES</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>CD</td>
            <td>3</td>
            <td>36</td>
            <td>594</td>
          </tr>
          <tr>
            <td>TELEVENTAS</td>
            <td>0</td>
            <td>0</td>
            <td>5</td>
          </tr>
          <tr>
            <td>CI</td>
            <td>1</td>
            <td>26</td>
            <td>288</td>
          </tr>
          <tr>
            <td>OTRAS VENTAS</td>
            <td>0</td>
            <td>0</td>
            <td>5</td>
          </tr>
          <tr class="bg-warning font-weight-bold">
            <td>MOVESA</td>
            <td>4</td>
            <td>62</td>
            <td>892</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Scripts necesarios para DataTables -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="../Views/Resources/plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../Views/Resources/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script>
  $(document).ready(function() {
    $('#tablaCanales').DataTable({
      paging: false,
      searching: false,
      info: false,
      ordering: false
    });
  });
</script>