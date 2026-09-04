<?php
include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( true );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_tweb() . 'core.php' );
include ( Constants::getpath_root() . 'helpers.php' );

$nombreUsuario = '';
$rolUsuario = 'operador';
$currentPage = 'descarga_sat';
$esConsultor = esConsultor();

include('view_header.php');
?>

<h4 class="fw-bold text-dark mb-4"><i class="fas fa-cloud-download-alt me-2"></i>Descarga Masiva de CFDI del SAT</h4>

<div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-12">
        <h6 class="fw-bold text-primary"><i class="fas fa-key me-1"></i>E.Firma (Certificado Digital)</h6>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-bold small">Archivo .cer</label>
        <input type="file" class="form-control form-control-sm" id="sat-cer" accept=".cer,.cer.der">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-bold small">Archivo .key</label>
        <input type="file" class="form-control form-control-sm" id="sat-key" accept=".key,.key.der">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-bold small">Contraseña de la llave</label>
        <input type="password" class="form-control form-control-sm" id="sat-password">
      </div>
    </div>

    <hr>

    <div class="row g-3">
      <div class="col-md-12">
        <h6 class="fw-bold text-primary"><i class="fas fa-calendar-alt me-1"></i>Parámetros de Consulta</h6>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-bold small">Fecha Desde</label>
        <input type="date" class="form-control form-control-sm" id="sat-desde">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-bold small">Fecha Hasta</label>
        <input type="date" class="form-control form-control-sm" id="sat-hasta">
      </div>
      <div class="col-md-3">
        <label class="form-label fw-bold small">Tipo de Descarga</label>
        <select class="form-select form-select-sm" id="sat-tipo">
          <option value="emitidos">CFDI Emitidos</option>
          <option value="recibidos">CFDI Recibidos</option>
        </select>
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-primary fw-bold w-100" onclick="solicitarDescarga()" id="btn-solicitar">
          <i class="fas fa-paper-plane me-1"></i>Solicitar al SAT
        </button>
      </div>
    </div>

    <div class="row g-3 mt-2">
      <div class="col-md-4">
        <label class="form-label fw-bold small">RFC Emisor (filtro)</label>
        <input type="text" class="form-control form-control-sm" id="sat-rfc-emisor" maxlength="13" placeholder="Opcional" style="text-transform:uppercase;">
      </div>
      <div class="col-md-4">
        <label class="form-label fw-bold small">RFC Receptor (filtro)</label>
        <input type="text" class="form-control form-control-sm" id="sat-rfc-receptor" maxlength="13" placeholder="Opcional" style="text-transform:uppercase;">
      </div>
    </div>
  </div>
</div>

<!-- Solicitudes realizadas -->
<div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="fw-bold text-primary mb-0"><i class="fas fa-history me-1"></i>Solicitudes Recientes</h6>
      <button class="btn btn-sm btn-outline-secondary" onclick="cargarSolicitudes()"><i class="fas fa-sync me-1"></i>Recargar</button>
    </div>
    <div style="overflow-x:auto;">
      <table id="tabla-solicitudes" class="table table-hover table-sm mb-0" style="font-size:12px;"></table>
    </div>
  </div>
</div>

<!-- Progreso -->
<div id="progreso-container" class="card border-0 shadow-sm mb-3" style="border-radius:12px; display:none;">
  <div class="card-body text-center">
    <div class="spinner-border text-primary mb-2" role="status">
      <span class="visually-hidden">Cargando...</span>
    </div>
    <p class="fw-bold" id="progreso-texto">Procesando...</p>
  </div>
</div>

<!-- Resultado descarga -->
<div id="resultado-container" class="card border-0 shadow-sm mb-3" style="border-radius:12px; display:none;">
  <div class="card-body">
    <div id="resultado-content"></div>
  </div>
</div>

<script>
function solicitarDescarga() {
  var cer = $('#sat-cer')[0].files[0];
  var key = $('#sat-key')[0].files[0];
  var pass = $('#sat-password').val().trim();
  var desde = $('#sat-desde').val();
  var hasta = $('#sat-hasta').val();
  var tipo = $('#sat-tipo').val();
  var rfcEmisor = $('#sat-rfc-emisor').val().trim();
  var rfcReceptor = $('#sat-rfc-receptor').val().trim();

  if (!cer) { MsgNotify('Seleccione el archivo .cer', 'error'); return; }
  if (!key) { MsgNotify('Seleccione el archivo .key', 'error'); return; }
  if (!pass) { MsgNotify('Ingrese la contraseña de la llave', 'error'); return; }
  if (!desde || !hasta) { MsgNotify('Especifique el rango de fechas', 'error'); return; }

  var formData = new FormData();
  formData.append('action', 'solicitar');
  formData.append('cer_file', cer);
  formData.append('key_file', key);
  formData.append('password', pass);
  formData.append('fecha_desde', desde);
  formData.append('fecha_hasta', hasta);
  formData.append('tipo', tipo);
  formData.append('rfc_emisor', rfcEmisor);
  formData.append('rfc_receptor', rfcReceptor);

  $('#btn-solicitar').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Enviando...');
  mostrarProgreso('Conectando con el SAT...');

  $.ajax({
    url: path.model + 'descarga_sat.php',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function(dat) {
      ocultarProgreso();
      $('#btn-solicitar').prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i>Solicitar al SAT');
      if (!dat.result) {
        MsgNotify(dat.message, 'error');
        mostrarResultado(dat.message, 'danger');
        return;
      }
      MsgNotify(dat.message, 'success');
      mostrarResultado('<strong>Solicitud enviada.</strong><br>Request ID: ' + dat.request_id + '<br>RFC: ' + dat.rfc + '<br><br>De clicking en "Recargar" para verificar el estado de la solicitud.', 'success');
      cargarSolicitudes();
    },
    error: function(xhr, status, error) {
      ocultarProgreso();
      $('#btn-solicitar').prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i>Solicitar al SAT');
      MsgNotify('Error de conexion: ' + error, 'error');
    }
  });
}

function verificarSolicitud(requestId) {
  var cer = $('#sat-cer')[0].files[0];
  var key = $('#sat-key')[0].files[0];
  var pass = $('#sat-password').val().trim();
  if (!cer) { MsgNotify('Seleccione el archivo .cer para verificar', 'error'); return; }
  if (!key) { MsgNotify('Seleccione el archivo .key para verificar', 'error'); return; }
  if (!pass) { MsgNotify('Ingrese la contraseña de la llave', 'error'); return; }

  var formData = new FormData();
  formData.append('action', 'verificar');
  formData.append('cer_file', cer);
  formData.append('key_file', key);
  formData.append('password', pass);
  formData.append('request_id', requestId);

  mostrarProgreso('Verificando solicitud ' + requestId + '...');
  $.ajax({
    url: path.model + 'descarga_sat.php',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function(dat) {
      ocultarProgreso();
      if (!dat.result) { MsgNotify(dat.message, 'error'); mostrarResultado(dat.message, 'danger'); return; }
      MsgNotify(dat.message, dat.estado === '3' ? 'success' : 'info');
      if (dat.estado === '3' && dat.paquetes && dat.paquetes.length > 0) {
        mostrarResultado('<strong>Solicitud completada.</strong><br>CFDIs: ' + dat.num_cfdis + '<br>Paquetes: ' + dat.paquetes.length + '<br><br>' +
          dat.paquetes.map(function(p, i) {
            return '<button class="btn btn-sm btn-success me-2 mb-2" onclick="descargarPaquete(\'' + p + '\')"><i class="fas fa-download me-1"></i>Paquete ' + (i+1) + '</button>';
          }).join(' '), 'success');
      } else {
        mostrarResultado('<strong>Estado: ' + dat.estado_texto + '</strong><br>Intentelo de nuevo en unos minutos.', 'warning');
      }
      cargarSolicitudes();
    },
    error: function(xhr, status, error) {
      ocultarProgreso();
      MsgNotify('Error de conexion: ' + error, 'error');
    }
  });
}

function descargarPaquete(paqueteId) {
  var cer = $('#sat-cer')[0].files[0];
  var key = $('#sat-key')[0].files[0];
  var pass = $('#sat-password').val().trim();
  if (!cer) { MsgNotify('Seleccione el archivo .cer para descargar', 'error'); return; }
  if (!key) { MsgNotify('Seleccione el archivo .key para descargar', 'error'); return; }
  if (!pass) { MsgNotify('Ingrese la contraseña de la llave', 'error'); return; }

  var formData = new FormData();
  formData.append('action', 'descargar');
  formData.append('cer_file', cer);
  formData.append('key_file', key);
  formData.append('password', pass);
  formData.append('paquete_id', paqueteId);

  mostrarProgreso('Descargando paquete...');
  $.ajax({
    url: path.model + 'descarga_sat.php',
    type: 'POST',
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function(dat) {
      ocultarProgreso();
      if (!dat.result) { MsgNotify(dat.message, 'error'); mostrarResultado(dat.message, 'danger'); return; }
      MsgNotify(dat.message, 'success');
      mostrarResultado('<strong>Paquete procesado.</strong><br>CFDIs guardados en BD: <span class="text-success fw-bold">' + dat.guardados + '</span><br>CFDIs omitidos (duplicados): <span class="text-warning fw-bold">' + dat.omitidos + '</span><br><br>Puede visualizarlos en <a href="xml_import.php">Importar XML</a>.', 'success');
      cargarSolicitudes();
    },
    error: function(xhr, status, error) {
      ocultarProgreso();
      MsgNotify('Error de conexion: ' + error, 'error');
    }
  });
}

function mostrarProgreso(texto) {
  $('#progreso-texto').text(texto);
  $('#progreso-container').show();
}

function ocultarProgreso() {
  $('#progreso-container').hide();
}

function mostrarResultado(html, tipo) {
  var cls = 'alert-' + (tipo || 'info');
  $('#resultado-content').html('<div class="alert ' + cls + ' mb-0">' + html + '</div>');
  $('#resultado-container').show();
}

function cargarSolicitudes() {
  MsgServer(path.model + 'descarga_sat.php', function(dat) {
    if (!dat.result) return;
    initTabla(dat.data);
  }, { action: 'listar_paquetes' });
}

function initTabla(data) {
  var $tbl = $('#tabla-solicitudes');
  $tbl.bootstrapTable('destroy');
  $tbl.bootstrapTable({
    data: data,
    classes: 'table table-hover',
    theadClasses: 'table-light',
    locale: 'es-ES',
    search: true,
    pagination: true,
    pageSize: 10,
    columns: [
      { field: 'id', title: '#', width: 50, align: 'center' },
      { field: 'rfc', title: 'RFC', width: 130 },
      { field: 'request_id', title: 'Request ID', width: 250, formatter: function(v) { return '<span style="font-size:10px;">' + (v || '') + '</span>'; } },
      { field: 'fecha_desde', title: 'Desde', width: 100 },
      { field: 'fecha_hasta', title: 'Hasta', width: 100 },
      { field: 'tipo', title: 'Tipo', width: 100 },
      { field: 'estado', title: 'Estado', width: 120, align: 'center', formatter: function(v) {
        var cls = 'secondary';
        if (v === 'completado') cls = 'success';
        else if (v === 'solicitado') cls = 'warning';
        else if (v === 'error') cls = 'danger';
        return '<span class="badge bg-' + cls + '">' + (v || '') + '</span>';
      }},
      { field: 'created_at', title: 'Fecha Solicitud', width: 150 },
      { field: 'acciones', title: '<i class="fas fa-cog"></i>', width: 150, align: 'center', formatter: function(v, r) {
        return '<button class="btn btn-sm btn-outline-primary me-1" onclick="verificarSolicitud(\'' + r.request_id + '\')" title="Verificar estado"><i class="fas fa-sync"></i></button>';
      }}
    ]
  });
}

$(function() {
  cargarSolicitudes();
});
</script>

</div>

<?php include('view_footer.php'); ?>
