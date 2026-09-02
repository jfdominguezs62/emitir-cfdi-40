<?php 
include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( true );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_tweb() . 'core.php' );
include ( Constants::getpath_root() . 'helpers.php' );

$nombreUsuario = '';
$rolUsuario = 'operador';
$currentPage = 'reportes';

include('view_header.php');
?>

<div class="container-fluid">
<h4 class="fw-bold text-dark mb-4"><i class="fas fa-chart-bar me-2"></i>Reportes de CFDI</h4>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
  <div class="card-body">
    <div class="row g-3 align-items-end">
      <div class="col-md-2">
        <label class="form-label fw-bold small">Agrupar por</label>
        <select class="form-select form-select-sm" id="rpt-tipo">
          <option value="dia">Día</option>
          <option value="mes" selected>Mes</option>
          <option value="anio">Año</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label fw-bold small">Desde</label>
        <input type="date" class="form-control form-control-sm" id="rpt-desde">
      </div>
      <div class="col-md-2">
        <label class="form-label fw-bold small">Hasta</label>
        <input type="date" class="form-control form-control-sm" id="rpt-hasta">
      </div>
      <div class="col-md-2">
        <label class="form-label fw-bold small">Estado</label>
        <select class="form-select form-select-sm" id="rpt-estado">
          <option value="">Todos</option>
          <option value="VIGENTE">Vigente</option>
          <option value="CANCELADO">Cancelado</option>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="rpt-usar-cierre">
          <label class="form-check-label fw-bold small" for="rpt-usar-cierre">Usar fecha real de cierre</label>
        </div>
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary fw-bold btn-sm w-100" onclick="generarReporte()">
          <i class="fas fa-search me-1"></i>Generar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Resumen -->
<div class="row g-3 mb-4" id="rpt-resumen" style="display:none;">
  <div class="col-md-2">
    <div class="card border-0 shadow-sm text-center" style="border-radius:12px;">
      <div class="card-body py-3">
        <div class="text-muted small">Total CFDI</div>
        <div class="fs-3 fw-bold text-primary" id="rpt-total-cfdi">0</div>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card border-0 shadow-sm text-center" style="border-radius:12px;">
      <div class="card-body py-3">
        <div class="text-muted small">Vigentes</div>
        <div class="fs-3 fw-bold text-success" id="rpt-total-vigentes">0</div>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card border-0 shadow-sm text-center" style="border-radius:12px;">
      <div class="card-body py-3">
        <div class="text-muted small">Cancelados</div>
        <div class="fs-3 fw-bold text-danger" id="rpt-total-cancelados">0</div>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card border-0 shadow-sm text-center" style="border-radius:12px;">
      <div class="card-body py-3">
        <div class="text-muted small">Sin Verificar</div>
        <div class="fs-3 fw-bold text-secondary" id="rpt-total-sinver">0</div>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card border-0 shadow-sm text-center" style="border-radius:12px;">
      <div class="card-body py-3">
        <div class="text-muted small">Total IVA</div>
        <div class="fs-3 fw-bold text-info" id="rpt-total-iva">$0</div>
      </div>
    </div>
  </div>
  <div class="col-md-2">
    <div class="card border-0 shadow-sm text-center" style="border-radius:12px;">
      <div class="card-body py-3">
        <div class="text-muted small">Importe Total</div>
        <div class="fs-3 fw-bold text-dark" id="rpt-total-importe">$0</div>
      </div>
    </div>
  </div>
</div>

<!-- Grafica de barras -->
<div class="card border-0 shadow-sm mb-4" id="rpt-chart-card" style="border-radius:12px; display:none;">
  <div class="card-body">
    <h6 class="fw-bold mb-3">Distribución por período</h6>
    <div id="rpt-chart" style="display:flex; align-items:flex-end; gap:8px; height:200px; overflow-x:auto;"></div>
  </div>
</div>

<!-- Tabla detalle -->
<div class="card border-0 shadow-sm" id="rpt-table-card" style="border-radius:12px; display:none;">
  <div class="card-body">
    <h6 class="fw-bold mb-3">Detalle por período</h6>
    <div style="overflow-x:auto;">
      <table class="table table-hover table-sm" id="rpt-tabla" style="font-size:13px;">
        <thead class="table-light">
          <tr>
            <th>Período</th>
            <th class="text-center">CFDI</th>
            <th class="text-center">Vigentes</th>
            <th class="text-center">Cancelados</th>
            <th class="text-center">Sin Verificar</th>
            <th class="text-center">Globales</th>
            <th class="text-end">Subtotal</th>
            <th class="text-end">Descuento</th>
            <th class="text-end">IVA</th>
            <th class="text-end">Total</th>
            <th class="text-center">Detalle</th>
          </tr>
        </thead>
        <tbody id="rpt-tbody"></tbody>
        <tfoot id="rpt-tfoot" class="fw-bold table-light"></tfoot>
      </table>
    </div>
  </div>
</div>

<!-- Modal detalle -->
<div class="modal fade" id="modalDetalle" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-bold" id="modalDetalleTitle">Detalle</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="overflow-x:auto;">
        <table class="table table-hover table-sm" style="font-size:12px;">
          <thead class="table-light">
            <tr>
              <th>UUID</th>
              <th>Serie</th>
              <th>Folio</th>
              <th>Fecha</th>
              <th>RFC Emisor</th>
              <th>RFC Receptor</th>
              <th>Nombre Receptor</th>
              <th class="text-end">Subtotal</th>
              <th class="text-end">IVA</th>
              <th class="text-end">Total</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody id="modal-detalle-body"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

</div>

<style>
.rpt-bar { display:flex; flex-direction:column; align-items:center; min-width:60px; }
.rpt-bar-fill { width:40px; border-radius:4px 4px 0 0; transition: height 0.3s; }
.rpt-bar-label { font-size:11px; margin-top:4px; text-align:center; }
.rpt-bar-value { font-size:10px; font-weight:bold; margin-bottom:2px; }
</style>

<script>
var rptData = [];

function fmtMoney(v) {
  v = parseFloat(v) || 0;
  return '$' + v.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function generarReporte() {
  var tipo      = $('#rpt-tipo').val();
  var desde     = $('#rpt-desde').val();
  var hasta     = $('#rpt-hasta').val();
  var estado    = $('#rpt-estado').val();
  var usarCierre = $('#rpt-usar-cierre').is(':checked') ? 1 : 0;

  MsgServer(path.model + 'reportes.php', function(dat) {
    if (!dat.result) { MsgNotify(dat.message, 'error'); return; }
    rptData = dat.data;
    mostrarResumen(dat.totales);
    mostrarTabla(dat.data);
    mostrarChart(dat.data);
  }, { action: 'reporte_periodo', tipo: tipo, desde: desde, hasta: hasta, estado: estado, usar_cierre: usarCierre });
}

function mostrarResumen(t) {
  $('#rpt-resumen').show();
  $('#rpt-total-cfdi').text(t.cfdi);
  $('#rpt-total-vigentes').text(t.vigentes);
  $('#rpt-total-cancelados').text(t.cancelados);
  $('#rpt-total-sinver').text(t.sin_verificar);
  $('#rpt-total-iva').text(fmtMoney(t.iva));
  $('#rpt-total-importe').text(fmtMoney(t.importe));
}

function mostrarTabla(data) {
  $('#rpt-table-card').show();
  var html = '';
  data.forEach(function(r) {
    html += '<tr>';
    html += '<td class="fw-bold">' + r.periodo + '</td>';
    html += '<td class="text-center">' + r.total_cfdi + '</td>';
    html += '<td class="text-center text-success">' + r.vigentes + '</td>';
    html += '<td class="text-center text-danger">' + r.cancelados + '</td>';
    html += '<td class="text-center text-secondary">' + r.sin_verificar + '</td>';
    html += '<td class="text-center text-warning">' + r.globales + '</td>';
    html += '<td class="text-end">' + fmtMoney(r.total_subtotal) + '</td>';
    html += '<td class="text-end">' + fmtMoney(r.total_descuento) + '</td>';
    html += '<td class="text-end">' + fmtMoney(r.total_iva) + '</td>';
    html += '<td class="text-end fw-bold">' + fmtMoney(r.total_total) + '</td>';
    html += '<td class="text-center"><button class="btn btn-outline-primary btn-sm" onclick="verDetalle(\'' + r.periodo + '\')"><i class="fas fa-eye"></i></button></td>';
    html += '</tr>';
  });
  $('#rpt-tbody').html(html);

  var tot = rptData.reduce(function(a, r) {
    a.cfdi += r.total_cfdi; a.vigentes += r.vigentes; a.cancelados += r.cancelados;
    a.sinver += r.sin_verificar; a.sub += r.total_subtotal; a.desc += r.total_descuento;
    a.iva += r.total_iva; a.total += r.total_total; return a;
  }, {cfdi:0,vigentes:0,cancelados:0,sinver:0,sub:0,desc:0,iva:0,total:0});

  $('#rpt-tfoot').html('<tr>'
    + '<td>Totales</td>'
    + '<td class="text-center">' + tot.cfdi + '</td>'
    + '<td class="text-center text-success">' + tot.vigentes + '</td>'
    + '<td class="text-center text-danger">' + tot.cancelados + '</td>'
    + '<td class="text-center text-secondary">' + tot.sinver + '</td>'
    + '<td></td>'
    + '<td class="text-end">' + fmtMoney(tot.sub) + '</td>'
    + '<td class="text-end">' + fmtMoney(tot.desc) + '</td>'
    + '<td class="text-end">' + fmtMoney(tot.iva) + '</td>'
    + '<td class="text-end">' + fmtMoney(tot.total) + '</td>'
    + '<td></td>'
    + '</tr>');
}

function mostrarChart(data) {
  if (!data.length) { $('#rpt-chart-card').hide(); return; }
  $('#rpt-chart-card').show();
  var maxTotal = Math.max.apply(null, data.map(function(r){ return r.total_cfdi; }));
  if (maxTotal === 0) maxTotal = 1;
  var html = '';
  data.forEach(function(r) {
    var h = Math.round((r.total_cfdi / maxTotal) * 170);
    var hV = Math.round((r.vigentes / maxTotal) * 170);
    var hC = Math.round((r.cancelados / maxTotal) * 170);
    html += '<div class="rpt-bar">';
    html += '<div class="rpt-bar-value">' + r.total_cfdi + '</div>';
    html += '<div style="display:flex; align-items:flex-end; gap:2px;">';
    html += '<div class="rpt-bar-fill" style="height:' + hV + 'px; background:#198754;" title="Vigentes: ' + r.vigentes + '"></div>';
    html += '<div class="rpt-bar-fill" style="height:' + hC + 'px; background:#dc3545;" title="Cancelados: ' + r.cancelados + '"></div>';
    html += '</div>';
    html += '<div class="rpt-bar-label">' + r.periodo + '</div>';
    html += '</div>';
  });
  html += '<div class="ms-3 align-self-end mb-1" style="font-size:11px;">';
  html += '<span class="me-2"><span style="display:inline-block;width:12px;height:12px;background:#198754;border-radius:2px;"></span> Vigentes</span>';
  html += '<span><span style="display:inline-block;width:12px;height:12px;background:#dc3545;border-radius:2px;"></span> Cancelados</span>';
  html += '</div>';
  $('#rpt-chart').html(html);
}

function verDetalle(periodo) {
  var tipo   = $('#rpt-tipo').val();
  var estado = $('#rpt-estado').val();
  var usarCierre = $('#rpt-usar-cierre').is(':checked') ? 1 : 0;
  $('#modalDetalleTitle').text('Detalle: ' + periodo);
  $('#modal-detalle-body').html('<tr><td colspan="11" class="text-center">Cargando...</td></tr>');
  $('#modalDetalle').modal('show');

  MsgServer(path.model + 'reportes.php', function(dat) {
    if (!dat.result || !dat.data.length) {
      $('#modal-detalle-body').html('<tr><td colspan="11" class="text-center text-muted">Sin registros</td></tr>');
      return;
    }
    var html = '';
    dat.data.forEach(function(r) {
      var estadoBadge = '';
      if (r.estado === 'VIGENTE') estadoBadge = '<span class="badge bg-success">Vigente</span>';
      else if (r.estado === 'CANCELADO') estadoBadge = '<span class="badge bg-danger">Cancelado</span>';
      else estadoBadge = '<span class="badge bg-secondary">' + (r.estado || '-') + '</span>';

      html += '<tr>';
      html += '<td style="font-size:11px;">' + r.uuid + '</td>';
      html += '<td>' + r.serie + '</td>';
      html += '<td>' + r.folio + '</td>';
      html += '<td>' + r.fecha + '</td>';
      html += '<td>' + r.emisor_rfc + '</td>';
      html += '<td>' + r.receptor_rfc + '</td>';
      html += '<td>' + r.receptor_nombre + '</td>';
      html += '<td class="text-end">' + fmtMoney(r.subtotal) + '</td>';
      html += '<td class="text-end">' + fmtMoney(r.iva_neto) + '</td>';
      html += '<td class="text-end fw-bold">' + fmtMoney(r.total) + '</td>';
      html += '<td class="text-center">' + estadoBadge + '</td>';
      html += '</tr>';
    });
    $('#modal-detalle-body').html(html);
  }, { action: 'reporte_detalle', periodo: periodo, tipo: tipo, estado: estado, usar_cierre: usarCierre });
}
</script>

</div>
