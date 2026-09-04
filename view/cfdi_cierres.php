<?php 
include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( true );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_tweb() . 'core.php' );
include ( Constants::getpath_root() . 'helpers.php' );

$nombreUsuario = '';
$rolUsuario = 'operador';
$currentPage = 'cfdi_cierres';
$esConsultor = esConsultor();

include('view_header.php');
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.25.0/dist/bootstrap-table.min.css">

<div class="container-fluid">
<h4 class="fw-bold text-dark mb-4"><i class="fas fa-calendar-check me-2"></i>Cierres de CFDI</h4>

<p class="text-muted small mb-3">Fechas de cierre reales de los CFDI timbrados. Puede editar la fecha de cierre de cada registro.</p>

<div class="card border-0 shadow-sm" style="border-radius:12px;">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <span class="fw-bold" id="total-cierres">0 registros</span>
      <div class="d-flex gap-2 align-items-center">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="filtro-diferentes" onchange="filtrarCierres()">
          <label class="form-check-label fw-bold small" for="filtro-diferentes">Solo diferentes</label>
        </div>
        <?php if (!$esConsultor) { ?>
        <button class="btn btn-sm btn-outline-warning fw-bold" onclick="poblarCierres()"><i class="fas fa-database me-1"></i>Poblar desde XML</button>
        <?php } ?>
        <button class="btn btn-sm btn-primary fw-bold" onclick="cargarCierres()"><i class="fas fa-sync me-1"></i>Recargar</button>
      </div>
    </div>
    <div style="overflow-x:auto;">
      <table id="tabla-cierres" class="table table-hover table-sm" style="font-size:13px;"></table>
    </div>
  </div>
</div>
</div>

<style>
#tabla-cierres tr[style*="background-color: rgb(255, 243, 205)"] td,
#tabla-cierres tr[style*="background-color: #fff3cd"] td {
  background-color: #fff3cd !important;
}
#tabla-cierres tr[style*="color: rgb(220, 53, 69)"] td,
#tabla-cierres tr[style*="color: #dc3545"] td {
  color: #dc3545 !important;
}
</style>

<script>
var cierresData = [];

function fmtMoney(v) {
  v = parseFloat(v) || 0;
  return '$' + v.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function fmtFecha(v) {
  if (!v) return '-';
  return v.substring(0, 10) + ' ' + v.substring(11, 19);
}

function cargarCierres() {
  MsgServer(path.model + 'cfdi_cierres.php', function(dat) {
    if (!dat.result) { MsgNotify(dat.message, 'error'); return; }
    cierresData = dat.data;
    filtrarCierres();
    $('#total-cierres').text(dat.total + ' registros');
  }, { action: 'listar' });
}

function filtrarCierres() {
  var soloDiferentes = $('#filtro-diferentes').is(':checked');
  var data = cierresData;
  if (soloDiferentes) {
    data = cierresData.filter(function(r) {
      var t = r.fecha_timbrado ? r.fecha_timbrado.substring(0, 10) : '';
      var c = r.fecharealcierre ? r.fecharealcierre.substring(0, 10) : '';
      return t && c && t !== c;
    });
  }
  initTabla(data);
  $('#total-cierres').text(data.length + ' de ' + cierresData.length + ' registros');
}

function initTabla(data) {
  var $tbl = $('#tabla-cierres');
  $tbl.bootstrapTable('destroy');
  $tbl.bootstrapTable({
    data: data,
    classes: 'table table-hover',
    theadClasses: 'table-light',
    locale: 'es-ES',
    sortable: true,
    search: true,
    pagination: true,
    pageSize: 25,
    pageList: [10, 25, 50, 100],
    showColumns: false,
    uniqueId: 'id',
    sortName: 'fecha_timbrado',
    sortOrder: 'asc',
    rowStyle: function(row) {
      var css = {};
      var t = row.fecha_timbrado ? row.fecha_timbrado.substring(0, 10) : '';
      var c = row.fecharealcierre ? row.fecharealcierre.substring(0, 10) : '';
      if (t && c && t !== c) {
        css['background-color'] = '#fff3cd';
      }
      if (row.estado === 'CANCELADO') {
        css['color'] = '#dc3545';
      }
      return Object.keys(css).length ? { css: css } : {};
    },
    columns: [
      { field: 'uuid', title: 'UUID', width: 280, sortable: true, formatter: function(v) { return '<span style="font-size:11px;">' + (v || '') + '</span>'; } },
      { field: 'fecha_timbrado', title: 'Fecha Timbrado', width: 160, sortable: true, formatter: function(v) { return fmtFecha(v); } },
      { field: 'fecharealcierre', title: 'Fecha Real Cierre', width: 160, sortable: true, formatter: function(v, r) {
        var val = v ? v.substring(0, 10) : '';
        if (USER_ROL === 'consultor') {
          return '<span class="fw-bold">' + val + '</span>';
        }
        return '<input type="date" class="form-control form-control-sm" value="' + val + '" id="fecha-' + r.id + '" style="width:140px;">';
      }},
      { field: 'emisor_rfc', title: 'RFC Emisor', width: 120, sortable: true },
      { field: 'receptor_rfc', title: 'RFC Receptor', width: 120, sortable: true },
      { field: 'receptor_nombre', title: 'Nombre Receptor', width: 200, sortable: true },
      { field: 'total', title: 'Total', width: 100, align: 'right', sortable: true, sorter: function(a, b) { return parseFloat(a) - parseFloat(b); }, formatter: function(v) { return fmtMoney(v); } },
      { field: 'estado', title: 'Estado SAT', width: 120, align: 'center', sortable: true, formatter: function(v) {
        if (!v) return '<span class="badge bg-secondary">Sin verificar</span>';
        if (v === 'VIGENTE') return '<span class="badge bg-success">Vigente</span>';
        if (v === 'CANCELADO') return '<span class="badge bg-danger">Cancelado</span>';
        if (v === 'No Encontrado') return '<span class="badge bg-info text-dark">No encontrado</span>';
        return '<span class="badge bg-warning text-dark">' + v + '</span>';
      }},
      { field: 'acciones', title: '<i class="fas fa-cog"></i>', width: 120, align: 'center', formatter: function(v, r) {
        if (USER_ROL === 'consultor') return '';
        return '<button class="btn btn-sm btn-outline-success me-1" onclick="guardarFecha(' + r.id + ')" title="Guardar fecha"><i class="fas fa-save"></i></button>'
             + '<button class="btn btn-sm btn-outline-danger" onclick="eliminarCierre(' + r.id + ')" title="Eliminar"><i class="fas fa-trash"></i></button>';
      }}
    ]
  });
}

function guardarFecha(id) {
  var fecha = $('#fecha-' + id).val();
  MsgServer(path.model + 'cfdi_cierres.php', function(dat) {
    if (dat.result) {
      MsgNotify(dat.message, 'success');
    } else {
      MsgNotify(dat.message, 'error');
    }
  }, { action: 'guardar', id: id, fecharealcierre: fecha });
}

function eliminarCierre(id) {
  if (!confirm('¿Eliminar este registro de cierres?')) return;
  MsgServer(path.model + 'cfdi_cierres.php', function(dat) {
    if (dat.result) {
      MsgNotify(dat.message, 'success');
      cargarCierres();
    } else {
      MsgNotify(dat.message, 'error');
    }
  }, { action: 'eliminar', id: id });
}

function poblarCierres() {
  if (!confirm('¿Poblar cierres con los datos de xml_importados? Solo se agregan UUIDs nuevos.')) return;
  MsgServer(path.model + 'cfdi_cierres.php', function(dat) {
    if (dat.result) {
      MsgNotify(dat.message, 'success');
      cargarCierres();
    } else {
      MsgNotify(dat.message, 'error');
    }
  }, { action: 'poblar' });
}

$(function() { cargarCierres(); });
</script>

<?php include('view_footer.php'); ?>
