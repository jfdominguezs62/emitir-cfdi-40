<?php 
include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( true );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_tweb() . 'core.php' );
include ( Constants::getpath_root() . 'helpers.php' );

$nombreUsuario = '';
$rolUsuario = 'operador';
include('view_header.php');

$esConsultor = esConsultor();
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-table@1.25.0/dist/bootstrap-table.min.css">

<style>
.col-item {
  display: flex;
  align-items: center;
  padding: 6px 10px;
  margin-bottom: 4px;
  background: #fff;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  cursor: grab;
  transition: all 0.15s;
  user-select: none;
}
.col-item:active { cursor: grabbing; }
.col-item:hover { border-color: #1565c0; background: #e3f2fd; }
.col-item.dragging { opacity: 0.4; border-style: dashed; }
.col-item.drag-over { border-color: #0d47a1; background: #bbdefb; box-shadow: 0 0 0 2px rgba(13,71,161,0.25); }
.col-item .col-drag { color: #adb5bd; margin-right: 8px; font-size: 12px; }
.col-item .col-check { margin-right: 8px; }
.col-item .col-title { flex: 1; font-size: 13px; }
.col-item .col-badge { font-size: 11px; opacity: 0.6; }

.fixed-table-header .th-inner { cursor: grab; }
.fixed-table-header .th-inner:active { cursor: grabbing; }

#tabla-xml-container { overflow-x: auto; -webkit-overflow-scrolling: touch; }

#tabla-xml tr.row-cancelado td { color: #dc3545 !important; }
#tabla-xml tr.row-vigente td { color: #198754 !important; }
#tabla-xml tr.row-global td { background-color: #fff3cd !important; }
#tabla-xml tr.row-global.row-cancelado td { background-color: #fff3cd !important; color: #dc3545 !important; }
#tabla-xml tr.row-global.row-vigente td { background-color: #fff3cd !important; color: #198754 !important; }
</style>

<h4 class="fw-bold text-dark mb-4"><i class="fas fa-file-import me-2"></i>Importar XML de Carpeta</h4>

<?php if (!$esConsultor) { ?>
<div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
  <div class="card-body">
    <div class="row g-2 align-items-end">
      <div class="col-md-12">
        <label class="form-label fw-bold">Seleccionar carpeta</label>
        <div class="d-flex gap-2">
          <div class="flex-grow-1">
            <input type="file" id="folder-input" webkitdirectory multiple style="display:none;" onchange="onFolderSelected(this)">
            <button class="btn btn-primary fw-bold" onclick="$('#folder-input').click()" type="button">
              <i class="fas fa-folder-open me-1"></i>Examinar carpeta...
            </button>
            <span class="ms-2 text-muted small" id="folder-name">Ninguna carpeta seleccionada</span>
          </div>
          <button class="btn btn-success fw-bold" onclick="leerXml()" id="btn-leer" style="white-space:nowrap;">
            <i class="fas fa-sync-alt me-1"></i>Cargar XML
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
<?php } ?>

<!-- Filtros -->
<div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
  <div class="card-body py-2">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label fw-bold small">Buscar</label>
        <input type="text" class="form-control form-control-sm" id="filtro-buscar" placeholder="RFC, nombre, UUID..." oninput="aplicarFiltros()">
      </div>
      <div class="col-md-2">
        <label class="form-label fw-bold small">Fecha desde</label>
        <input type="date" class="form-control form-control-sm" id="filtro-fecha-desde" onchange="aplicarFiltros()">
      </div>
      <div class="col-md-2">
        <label class="form-label fw-bold small">Fecha hasta</label>
        <input type="date" class="form-control form-control-sm" id="filtro-fecha-hasta" onchange="aplicarFiltros()">
      </div>
      <div class="col-md-2">
        <label class="form-label fw-bold small">Tipo comprobante</label>
        <select class="form-select form-select-sm" id="filtro-tipo" onchange="aplicarFiltros()">
          <option value="">Todos</option>
          <option value="I">Ingreso</option>
          <option value="E">Egreso</option>
          <option value="T">Traslado</option>
          <option value="N">Nómina</option>
          <option value="P">Pago</option>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="filtro-canceladas" onchange="aplicarFiltros()">
          <label class="form-check-label fw-bold small" for="filtro-canceladas">Solo canceladas</label>
        </div>
      </div>
      <div class="col-md-3 d-flex gap-1">
        <button class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltros()"><i class="fas fa-times me-1"></i>Limpiar</button>
        <button class="btn btn-outline-success btn-sm" onclick="exportarExcel()"><i class="fas fa-file-excel me-1"></i>Exportar</button>
        <button class="btn btn-outline-primary btn-sm" onclick="imprimirCfdi()"><i class="fas fa-print me-1"></i>Imprimir</button>
        <button class="btn btn-outline-dark btn-sm" onclick="verXml()"><i class="fas fa-code me-1"></i>Ver XML</button>
        <button class="btn btn-outline-info btn-sm" onclick="toggleColumnas()"><i class="fas fa-columns me-1"></i>Columnas</button>
      </div>
    </div>
    <div class="row g-2 mt-1">
      <div class="col-md-12 d-flex gap-1 align-items-center">
        <?php if (!$esConsultor) { ?>
        <button class="btn btn-sm btn-primary fw-bold" onclick="guardarDb()" id="btn-guardar-db">
          <i class="fas fa-database me-1"></i>Guardar en BD
        </button>
        <div class="input-group input-group-sm" style="width:auto;">
          <span class="input-group-text">Desde</span>
          <input type="date" class="form-control" id="cargar-desde" style="width:140px;">
          <span class="input-group-text">Hasta</span>
          <input type="date" class="form-control" id="cargar-hasta" style="width:140px;">
          <button class="btn btn-secondary fw-bold" onclick="cargarDb()">
            <i class="fas fa-sync me-1"></i>Cargar desde BD
          </button>
        </div>
        <button class="btn btn-sm btn-outline-danger" onclick="eliminarTodosDb()" title="Eliminar todos los registros de BD">
          <i class="fas fa-trash me-1"></i>Limpiar BD
        </button>
        <button class="btn btn-sm btn-outline-warning fw-bold" onclick="verificarSat()" id="btn-verificar-sat" title="Verificar estado ante el SAT">
          <i class="fas fa-shield-alt me-1"></i>Verificar SAT
        </button>
        <button class="btn btn-sm btn-outline-secondary" onclick="limpiarEstado()" title="Limpiar estados para re-verificar">
          <i class="fas fa-eraser me-1"></i>Limpiar Estado
        </button>
        <?php } ?>
      </div>
    </div>
  </div>
</div>

<!-- Selector de columnas con drag & drop -->
<div class="card border-0 shadow-sm mb-3" id="panel-columnas" style="display:none; border-radius:12px;">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="fw-bold text-primary mb-0"><i class="fas fa-columns me-1"></i>Columnas — Arrastra para reordenar, marca para exportar</h6>
      <div class="d-flex gap-1">
        <button class="btn btn-sm btn-outline-primary" onclick="seleccionarTodas()"><i class="fas fa-check-double me-1"></i>Todas</button>
        <button class="btn btn-sm btn-outline-secondary" onclick="seleccionarNinguna()"><i class="fas fa-times me-1"></i>Ninguna</button>
        <button class="btn btn-sm btn-outline-warning" onclick="seleccionarBasica()"><i class="fas fa-star me-1"></i>Básicas</button>
      </div>
    </div>
    <div id="columnas-lista" class="row"></div>
  </div>
</div>

<!-- Tabla -->
<div id="tabla-xml-container" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
  <table id="tabla-xml" class="table table-hover mb-0" style="font-size:13px; min-width:2400px;"></table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.25.0/dist/bootstrap-table.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-table@1.25.0/dist/locale/bootstrap-table-es-ES.min.js"></script>
<script src="../libs/bootstrap-table-1.25.0/extensions/reorder-columns/bootstrap-table-reorder-columns.min.js"></script>

<script>
var allData = [];
var columnasConfig = [
  { field: 'uuid',                  title: 'UUID',                   visible: true,  basic: true  },
  { field: 'fecha',                 title: 'Fecha',                  visible: true,  basic: true  },
  { field: 'folio',                 title: 'Folio',                  visible: true,  basic: true  },
  { field: 'serie',                 title: 'Serie',                  visible: true,  basic: true  },
  { field: 'emisor_regimen',        title: 'Régimen Emisor',         visible: true,  basic: true  },
  { field: 'receptor_rfc',          title: 'RFC Receptor',           visible: true,  basic: true  },
  { field: 'receptor_nombre',       title: 'Nombre Receptor',        visible: true,  basic: true  },
  { field: 'receptor_uso_cfdi',     title: 'Uso CFDI',               visible: true,  basic: true  },
  { field: 'subtotal',              title: 'Subtotal',               visible: true,  basic: true  },
  { field: 'descuento',             title: 'Descuento',              visible: true,  basic: true  },
  { field: 'impuestos_traslados',   title: 'IVA Trasladado',         visible: true,  basic: true  },
  { field: 'impuestos_retenidos',   title: 'IVA Retenido',           visible: true,  basic: true  },
  { field: 'iva_neto',              title: 'IVA Neto',               visible: true,  basic: true  },
  { field: 'total',                 title: 'Total',                  visible: true,  basic: true  },
  { field: 'moneda',                title: 'Moneda',                 visible: true,  basic: true  },
  { field: 'forma_pago',            title: 'Forma Pago',             visible: true,  basic: true  },
  { field: 'metodo_pago',           title: 'Método Pago',            visible: true,  basic: true  },
  { field: 'tipo_comprobante',      title: 'Tipo Comprobante',       visible: true,  basic: true  },
  { field: 'es_global',             title: 'CFDI Global',            visible: true,  basic: true  },
  { field: 'periodicidad',          title: 'Periodicidad',           visible: true,  basic: false },
  { field: 'meses',                 title: 'Meses',                  visible: true,  basic: false },
  { field: 'anio',                  title: 'Año',                    visible: true, basic: false },
  { field: 'estado',                title: 'Estado SAT',             visible: true,  basic: false },
  { field: 'emisor_rfc',            title: 'RFC Emisor',             visible: true,  basic: true  },
  { field: 'emisor_nombre',         title: 'Nombre Emisor',          visible: true,  basic: true  },
];

function fmtMoney(v) {
  v = parseFloat(v) || 0;
  return '$' + v.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function fmtTipo(v) {
  var map = { I:'Ingreso', E:'Egreso', T:'Traslado', N:'Nómina', P:'Pago', G:'Global' };
  var cls = { I:'success', E:'danger', T:'primary', N:'warning', P:'info', G:'dark' };
  return '<span class="badge bg-' + (cls[v]||'secondary') + '">' + (map[v]||v) + '</span>';
}

function getColumns() {
  var cols = [
    { field: 'checkbox', checkbox: true },
    { field: 'index', title: '#', formatter: function(v,r,i){ return i+1; }, width: 40, align: 'center' }
  ];
  columnasConfig.forEach(function(c) {
    if (!c.visible) return;
    var col = { field: c.field, title: c.title, sortable: true };
    if (c.field === 'total' || c.field === 'subtotal' || c.field === 'descuento' || c.field === 'impuestos_traslados' || c.field === 'impuestos_retenidos' || c.field === 'iva_neto') {
      col.align = 'right';
      col.formatter = function(v){ return fmtMoney(v); };
      col.sorter = function(a,b){ return parseFloat(a)-parseFloat(b); };
    }
    if (c.field === 'tipo_comprobante') {
      col.formatter = fmtTipo;
      col.align = 'center';
      col.width = 90;
    }
    if (c.field === 'es_global') {
      col.align = 'center';
      col.width = 70;
      col.formatter = function(v) {
        return v == 1
          ? '<span class="badge bg-warning text-dark">Global</span>'
          : '<span class="badge bg-success">Normal</span>';
      };
      col.sorter = function(a,b){ return a-b; };
    }
    if (c.field === 'estado') {
      col.align = 'center';
      col.width = 150;
      col.formatter = function(v, r) {
        if (USER_ROL === 'consultor') {
          if (!v) return '<span class="badge bg-secondary">Sin verificar</span>';
          if (v === 'VIGENTE') return '<span class="badge bg-success">Vigente</span>';
          if (v === 'CANCELADO') return '<span class="badge bg-danger">Cancelado</span>';
          if (v === 'No Encontrado') return '<span class="badge bg-info text-dark">No encontrado</span>';
          return '<span class="badge bg-warning text-dark">' + v + '</span>';
        }
        var cls = 'secondary';
        var txt = 'Sin verificar';
        if (v === 'VIGENTE') { cls = 'success'; txt = 'Vigente'; }
        else if (v === 'CANCELADO') { cls = 'danger'; txt = 'Cancelado'; }
        else if (v === 'No Encontrado') { cls = 'info'; txt = 'No encontrado'; }
        else if (v) { cls = 'warning'; txt = v; }
        return '<button class="btn btn-sm btn-outline-' + cls + ' fw-bold" onclick="verificarUnaFila(' + r.id + ')" title="Re-checar ante el SAT" style="font-size:11px;"><i class="fas fa-sync-alt me-1"></i> ' + txt + '</button>';
      };
    }
    if (c.field === 'fecha') { col.width = 160; }
    if (c.field === 'folio') { col.width = 60; col.align = 'center'; }
    if (c.field === 'serie') { col.width = 60; }
    cols.push(col);
  });
  cols.push({
    field: 'acciones',
    title: '<i class="fas fa-cog"></i>',
    align: 'center',
    width: 60,
    formatter: function(value, row) {
      if (row.id && USER_ROL !== 'consultor') {
        return '<button class="btn btn-sm btn-outline-danger" onclick="eliminarDb(' + row.id + ')" title="Eliminar de BD"><i class="fas fa-trash"></i></button>';
      }
      return '';
    }
  });
  return cols;
}

function initTabla(data) {
  var $tbl = $('#tabla-xml');
  $tbl.bootstrapTable('destroy');
  $tbl.bootstrapTable({
    data: data,
    classes: 'table table-hover',
    theadClasses: 'table-light',
    locale: 'es-ES',
    search: false,
    pagination: true,
    pageSize: 25,
    pageList: [10, 25, 50, 100, 250],
    showColumns: false,
    showColumnsToggleAll: false,
    checkboxHeader: true,
    maintainSelected: true,
    reorderableColumns: true,
    maxMovingRows: 1,
    uniqueId: 'id',
    rowStyle: function(row, index) {
      var classes = [];
      if (row.estado === 'CANCELADO') classes.push('row-cancelado');
      else if (row.estado === 'VIGENTE') classes.push('row-vigente');
      if (row.es_global == 1) classes.push('row-global');
      return classes.length ? { classes: classes.join(' ') } : {};
    },
    onReorderColumn: function(headerFields) {
      var newOrder = [];
      headerFields.forEach(function(field) {
        var found = columnasConfig.find(function(c) { return c.field === field; });
        if (found) newOrder.push(found);
      });
      var hidden = columnasConfig.filter(function(c) {
        return headerFields.indexOf(c.field) === -1;
      });
      columnasConfig = newOrder.concat(hidden);
      renderColumnasCheck();
    },
    columns: getColumns(),
    height: undefined
  });
}

function aplicarFiltros() {
  var busqueda = ($('#filtro-buscar').val() || '').toLowerCase();
  var fechaDesde = $('#filtro-fecha-desde').val() || '';
  var fechaHasta = $('#filtro-fecha-hasta').val() || '';
  var tipo = $('#filtro-tipo').val() || '';
  var soloCanceladas = $('#filtro-canceladas').is(':checked');
  var filtrados = allData.filter(function(r) {
    if (soloCanceladas && r.estado !== 'CANCELADO') return false;
    if (busqueda) {
      var texto = [r.uuid, r.serie, r.folio, r.emisor_rfc, r.emisor_nombre, r.receptor_rfc, r.receptor_nombre, r.archivo, r.no_certificado].join(' ').toLowerCase();
      if (texto.indexOf(busqueda) === -1) return false;
    }
    if (fechaDesde && r.fecha.substring(0,10) < fechaDesde) return false;
    if (fechaHasta && r.fecha.substring(0,10) > fechaHasta) return false;
    if (tipo && r.tipo_comprobante !== tipo) return false;
    return true;
  });
  initTabla(filtrados);
  $('#total-registros').text(filtrados.length + ' de ' + allData.length + ' registros');
}

function limpiarFiltros() {
  $('#filtro-buscar').val('');
  $('#filtro-fecha-desde').val('');
  $('#filtro-fecha-hasta').val('');
  $('#filtro-tipo').val('');
  $('#filtro-canceladas').prop('checked', false);
  initTabla(allData);
  $('#total-registros').text(allData.length + ' registros');
}

function onFolderSelected(input) {
  var files = input.files;
  if (!files || files.length === 0) return;
  var xmlFiles = [];
  for (var i = 0; i < files.length; i++) {
    if (files[i].name.toLowerCase().endsWith('.xml')) {
      xmlFiles.push(files[i]);
    }
  }
  if (xmlFiles.length === 0) {
    MsgNotify('No se encontraron archivos XML en la carpeta', 'warning');
    return;
  }
  if (xmlFiles.length > 200) {
    MsgNotify('Maximo 200 XML por carga. Selecciona una carpeta con menos archivos.', 'warning');
    return;
  }
  var folderPath = files[0].webkitRelativePath.split('/')[0];
  $('#folder-name').text(folderPath + ' (' + xmlFiles.length + ' XML)');
  window._selectedXmlFiles = xmlFiles;
  MsgNotify(xmlFiles.length + ' XML encontrados en: ' + folderPath, 'success');
}

function leerXml() {
  var files = window._selectedXmlFiles;
  if (!files || files.length === 0) {
    MsgNotify('Selecciona una carpeta primero', 'warning');
    return;
  }
  var BATCH = 20;
  var batches = [];
  for (var i = 0; i < files.length; i += BATCH) {
    batches.push(files.slice(i, i + BATCH));
  }
  var allResults = [];
  var idx = 0;
  $('#btn-leer').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Parseando...');

  function sendBatch() {
    if (idx >= batches.length) {
      $('#btn-leer').prop('disabled', false).html('<i class="fas fa-sync-alt me-1"></i>Cargar XML');
      allResults.forEach(function(r) {
        r.iva_neto = parseFloat(r.impuestos_traslados) || 0;
      });
      allData = allResults;
      initTabla(allData);
      $('#total-registros').text(allData.length + ' registros');
      MsgNotify(allData.length + ' XML parseados de ' + files.length + ' archivos', 'success');
      return;
    }
    $('#btn-leer').html('<i class="fas fa-spinner fa-spin me-1"></i>Lote ' + (idx+1) + '/' + batches.length + '...');
    var formData = new FormData();
    formData.append('action', 'leer_xml_files');
    var batch = batches[idx];
    for (var i = 0; i < batch.length; i++) {
      formData.append('xml_files[]', batch[i], batch[i].name);
    }
    $.ajax({
      type: 'post', dataType: 'json',
      url: path.model + 'xml_reader.php?' + Math.random(),
      data: formData, processData: false, contentType: false,
      success: function(dat) {
        if (dat.result && dat.data) {
          allResults = allResults.concat(dat.data);
        }
        idx++;
        sendBatch();
      },
      error: function(xhr) {
        idx++;
        sendBatch();
      }
    });
  }
  sendBatch();
}

function guardarDb() {
  var files = window._selectedXmlFiles;
  if (!files || files.length === 0) {
    MsgNotify('Selecciona una carpeta primero', 'warning');
    return;
  }
  if (!confirm('Se guardarán ' + files.length + ' XML en la base de datos. Los duplicados se omitirán. ¿Continuar?')) return;
  var BATCH = 20;
  var batches = [];
  for (var i = 0; i < files.length; i += BATCH) {
    batches.push(files.slice(i, i + BATCH));
  }
  var totalGuardados = 0;
  var totalOmitidos = 0;
  var idx = 0;
  $('#btn-guardar-db').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Guardando...');

  function sendBatch() {
    if (idx >= batches.length) {
      $('#btn-guardar-db').prop('disabled', false).html('<i class="fas fa-database me-1"></i>Guardar en BD');
      MsgNotify('Guardados: ' + totalGuardados + ', Omitidos: ' + totalOmitidos, 'success');
      return;
    }
    $('#btn-guardar-db').html('<i class="fas fa-spinner fa-spin me-1"></i>Lote ' + (idx+1) + '/' + batches.length + '...');
    var formData = new FormData();
    formData.append('action', 'guardar_db_files');
    var batch = batches[idx];
    for (var i = 0; i < batch.length; i++) {
      formData.append('xml_files[]', batch[i], batch[i].name);
    }
    $.ajax({
      type: 'post', dataType: 'json',
      url: path.model + 'xml_reader.php?' + Math.random(),
      data: formData, processData: false, contentType: false,
      success: function(dat) {
        if (dat.result) {
          if (dat.guardados) totalGuardados += dat.guardados;
          if (dat.omitidos) totalOmitidos += dat.omitidos;
        }
        console.log('Batch ' + (idx+1) + ':', dat.message || dat);
        idx++;
        sendBatch();
      },
      error: function(xhr) {
        console.error('Batch ' + (idx+1) + ' error:', xhr.responseText.substring(0, 200));
        idx++;
        sendBatch();
      }
    });
  }
  sendBatch();
}

function cargarDb() {
  var desde = $('#cargar-desde').val() || '';
  var hasta = $('#cargar-hasta').val() || '';
  var params = { action: 'cargar_db' };
  if (desde) params.desde = desde;
  if (hasta) params.hasta = hasta;
  MsgServer(path.model + 'xml_reader.php', function(dat) {
    if (dat.result) {
      allData = dat.data;
      initTabla(allData);
      $('#total-registros').text(dat.total + ' registros');
      var vigentes = 0, cancelados = 0, noEncontrado = 0, sinVerificar = 0;
      allData.forEach(function(r) {
        if (r.estado === 'VIGENTE') vigentes++;
        else if (r.estado === 'CANCELADO') cancelados++;
        else if (r.estado === 'No Encontrado') noEncontrado++;
        else sinVerificar++;
      });
      MsgNotify(dat.total + ' registros. Vigentes: ' + vigentes + ' | Cancelados: ' + cancelados + ' | No encontrados: ' + noEncontrado + ' | Sin verificar: ' + sinVerificar, 'info');
    } else {
      MsgNotify(dat.message, 'error');
    }
  }, params);
}

function eliminarDb(id) {
  if (!confirm('¿Eliminar este registro de la base de datos?')) return;
  MsgServer(path.model + 'xml_reader.php', function(dat) {
    if (dat.result) {
      MsgNotify(dat.message, 'success');
      cargarDb();
    } else {
      MsgNotify(dat.message, 'error');
    }
  }, { action: 'eliminar_db', id: id });
}

function eliminarTodosDb() {
  if (!confirm('¿Eliminar TODOS los registros de la base de datos? Esta acción no se puede deshacer.')) return;
  MsgServer(path.model + 'xml_reader.php', function(dat) {
    if (dat.result) {
      MsgNotify(dat.message, 'success');
      allData = [];
      initTabla([]);
      $('#total-registros').text('0 registros');
    } else {
      MsgNotify(dat.message, 'error');
    }
  }, { action: 'eliminar_todos_db' });
}

function limpiarEstado() {
  MsgServer(path.model + 'xml_reader.php', function(dat) {
    if (dat.result) {
      MsgNotify(dat.message, 'success');
      cargarDb();
    } else {
      MsgNotify(dat.message, 'error');
    }
  }, { action: 'limpiar_estado' });
}

var verifContadores = { vigentes: 0, cancelados: 0, noEncontrado: 0, errores: 0 };

function verificarSat() {
  if (!allData || allData.length === 0) {
    MsgNotify('No hay registros para verificar. Cargue desde BD primero.', 'warning');
    return;
  }
  verifContadores = { vigentes: 0, cancelados: 0, noEncontrado: 0, errores: 0 };
  var btn = $('#btn-verificar-sat');
  btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Consultando SAT...');
  MsgServer(path.model + 'xml_reader.php', function(dat) {
    if (!dat.result || !dat.registros || dat.registros.length === 0) {
      btn.prop('disabled', false).html('<i class="fas fa-shield-alt me-1"></i>Verificar SAT');
      MsgNotify('No hay registros nuevos por verificar', 'info');
      return;
    }
    verificarLote(dat.registros, 0);
  }, { action: 'verificar_sat' });
}

function verificarUnaFila(id) {
  var btn = $('#tabla-xml').find('button[onclick="verificarUnaFila(' + id + ')"]');
  btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
  MsgServer(path.model + 'xml_reader.php', function(dat) {
    var estado = dat.estado || 'ERROR';
    $('#tabla-xml').bootstrapTable('updateByUniqueId', {
      id: id,
      row: { estado: estado }
    });
    $('#tabla-xml').bootstrapTable('refresh');
    MsgNotify('CFDI verificado: ' + estado, estado === 'VIGENTE' ? 'success' : 'warning');
  }, { action: 'verificar_sat_one', id: id });
}

function verificarLote(registros, idx) {
  if (idx >= registros.length) {
    $('#btn-verificar-sat').prop('disabled', false).html('<i class="fas fa-shield-alt me-1"></i>Verificar SAT');
    var msg = 'Verificación completada. Vigentes: ' + verifContadores.vigentes
      + ' | Cancelados: ' + verifContadores.cancelados
      + ' | No encontrados: ' + verifContadores.noEncontrado
      + ' | Errores: ' + verifContadores.errores;
    MsgNotify(msg, 'success');
    cargarDb();
    return;
  }
  var total = registros.length;
  var actual = idx + 1;
  $('#btn-verificar-sat').html('<i class="fas fa-spinner fa-spin me-1"></i>' + actual + '/' + total);

  MsgServer(path.model + 'xml_reader.php', function(dat) {
    var estado = dat.estado || 'ERROR';
    if (estado === 'VIGENTE') verifContadores.vigentes++;
    else if (estado === 'CANCELADO') verifContadores.cancelados++;
    else if (estado === 'No Encontrado') verifContadores.noEncontrado++;
    else verifContadores.errores++;
    $('#tabla-xml').bootstrapTable('updateByUniqueId', {
      id: registros[idx].id,
      row: { estado: estado }
    });
    $('#tabla-xml').bootstrapTable('refresh');
    verificarLote(registros, idx + 1);
  }, { action: 'verificar_sat_one', id: registros[idx].id });
}

/* ---- Columnas: drag & drop + checkboxes ---- */
var dragSrcIdx = null;

function toggleColumnas() {
  $('#panel-columnas').slideToggle(200);
}

function renderColumnasCheck() {
  var html = '';
  columnasConfig.forEach(function(c, i) {
    html += '<div class="col-md-3 col-sm-4 col-6">'
         +  '<div class="col-item" draggable="true" data-idx="' + i + '">'
         +  '<span class="col-drag"><i class="fas fa-grip-vertical"></i></span>'
         +  '<input class="form-check-input col-check" type="checkbox" id="col-' + i + '" '
         +     (c.visible ? 'checked' : '') + ' onchange="cambiarColumna(' + i + ', this.checked)">'
         +  '<label class="col-title" for="col-' + i + '">' + c.title + '</label>'
         +  '<span class="col-badge"><i class="fas fa-arrows-alt"></i></span>'
         +  '</div></div>';
  });
  $('#columnas-lista').html(html);
  initDragDrop();
}

function initDragDrop() {
  var items = document.querySelectorAll('#columnas-lista .col-item');
  items.forEach(function(item) {
    item.addEventListener('dragstart', function(e) {
      dragSrcIdx = parseInt(this.dataset.idx);
      this.classList.add('dragging');
      e.dataTransfer.effectAllowed = 'move';
      e.dataTransfer.setData('text/plain', dragSrcIdx);
    });
    item.addEventListener('dragend', function() {
      this.classList.remove('dragging');
      document.querySelectorAll('#columnas-lista .col-item').forEach(function(el) {
        el.classList.remove('drag-over');
      });
      dragSrcIdx = null;
    });
    item.addEventListener('dragover', function(e) {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      this.classList.add('drag-over');
    });
    item.addEventListener('dragleave', function() {
      this.classList.remove('drag-over');
    });
    item.addEventListener('drop', function(e) {
      e.preventDefault();
      this.classList.remove('drag-over');
      var fromIdx = parseInt(e.dataTransfer.getData('text/plain'));
      var toIdx = parseInt(this.dataset.idx);
      if (fromIdx === toIdx) return;
      var moved = columnasConfig.splice(fromIdx, 1)[0];
      columnasConfig.splice(toIdx, 0, moved);
      renderColumnasCheck();
      if (allData.length > 0) initTabla($('#tabla-xml').bootstrapTable('getData'));
    });
  });
}

function cambiarColumna(idx, visible) {
  columnasConfig[idx].visible = visible;
  if (allData.length > 0) initTabla($('#tabla-xml').bootstrapTable('getData'));
}

function seleccionarTodas() {
  columnasConfig.forEach(function(c){ c.visible = true; });
  renderColumnasCheck();
  if (allData.length > 0) initTabla($('#tabla-xml').bootstrapTable('getData'));
}

function seleccionarNinguna() {
  columnasConfig.forEach(function(c){ c.visible = false; });
  renderColumnasCheck();
  if (allData.length > 0) initTabla($('#tabla-xml').bootstrapTable('getData'));
}

function seleccionarBasica() {
  columnasConfig.forEach(function(c){ c.visible = c.basic; });
  renderColumnasCheck();
  if (allData.length > 0) initTabla($('#tabla-xml').bootstrapTable('getData'));
}

/* ---- Exportar Excel ---- */
function exportarExcel() {
  var a = document.createElement('a');
  a.href = path.model + 'export_excel.php?action=exportar_excel';
  a.target = '_blank';
  a.click();
}

/* ---- Imprimir CFDI ---- */
function imprimirCfdi() {
  var sel = $('#tabla-xml').bootstrapTable('getSelections');
  if (!sel || sel.length === 0) {
    MsgNotify('Seleccione un registro para imprimir', 'warning');
    return;
  }
  var r = sel[0];

  var estado = r.estado || '';
  var estadoColor = '#666';
  if (estado === 'VIGENTE') estadoColor = '#198754';
  else if (estado === 'CANCELADO') estadoColor = '#dc3545';

  var html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
  html += '<title>CFDI 4.0 - Folio Fiscal: ' + (r.uuid || '') + '</title>';
  html += '<style>';
  html += '* { box-sizing: border-box; margin: 0; padding: 0; }';
  html += 'body { font-family: Arial, Helvetica, sans-serif; font-size: 10px; color: #333; background: #fff; }';
  html += '.page { width: 210mm; margin: 10mm auto; padding: 8mm; }';
  html += '.sat-header { text-align: center; margin-bottom: 6px; padding-bottom: 6px; border-bottom: 2px solid #0066cc; }';
  html += '.sat-header img { height: 28px; }';
  html += '.sat-header h1 { font-size: 9px; color: #0066cc; margin-top: 2px; letter-spacing: 0.5px; }';
  html += '.sat-header p { font-size: 8px; color: #666; }';
  html += '.cols { display: flex; gap: 10px; margin-bottom: 6px; }';
  html += '.col-left { flex: 1; }';
  html += '.col-right { flex: 1; }';
  html += '.section { border: 1px solid #ccc; border-radius: 4px; margin-bottom: 6px; padding: 6px; }';
  html += '.section-title { font-size: 9px; font-weight: bold; color: #0066cc; text-transform: uppercase; margin-bottom: 4px; border-bottom: 1px solid #ddd; padding-bottom: 2px; }';
  html += '.field { display: flex; margin-bottom: 2px; }';
  html += '.field-label { font-weight: bold; min-width: 110px; color: #555; }';
  html += '.field-value { color: #333; }';
  html += '.uuid-box { text-align: center; border: 2px solid #0066cc; border-radius: 6px; padding: 8px; margin: 8px 0; background: #f0f7ff; }';
  html += '.uuid-box .label { font-size: 8px; color: #666; text-transform: uppercase; letter-spacing: 1px; }';
  html += '.uuid-box .value { font-size: 11px; font-weight: bold; color: #0066cc; letter-spacing: 1px; margin-top: 2px; }';
  html += '.totales-table { width: 100%; border-collapse: collapse; margin-top: 6px; }';
  html += '.totales-table td { padding: 3px 6px; border-bottom: 1px solid #eee; }';
  html += '.totales-table td:last-child { text-align: right; font-weight: bold; }';
  html += '.totales-table tr.total-final td { border-top: 2px solid #0066cc; font-size: 12px; color: #0066cc; }';
  html += '.estado-box { text-align: center; margin: 8px 0; padding: 6px; border: 2px solid ' + estadoColor + '; border-radius: 4px; }';
  html += '.estado-box span { font-size: 11px; font-weight: bold; color: ' + estadoColor + '; }';
  html += '.footer { text-align: center; font-size: 7px; color: #999; margin-top: 10px; border-top: 1px solid #ddd; padding-top: 5px; }';
  html += '@media print { .page { margin: 5mm; width: 100%; } body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }';
  html += '</style></head><body>';

  html += '<div class="page">';

  html += '<div class="sat-header">';
  html += '<h1>COMPROBANTE FISCAL DIGITAL POR INTERNET</h1>';
  html += '<p>CFDI Versión 4.0</p>';
  html += '</div>';

  html += '<div class="uuid-box">';
  html += '<div class="label">Folio Fiscal</div>';
  html += '<div class="value">' + (r.uuid || '') + '</div>';
  html += '</div>';

  html += '<div class="cols">';

  html += '<div class="col-left">';
  html += '<div class="section">';
  html += '<div class="section-title">Emisor</div>';
  html += '<div class="field"><span class="field-label">RFC:</span><span class="field-value">' + (r.emisor_rfc || '') + '</span></div>';
  html += '<div class="field"><span class="field-label">Nombre:</span><span class="field-value">' + (r.emisor_nombre || '') + '</span></div>';
  html += '<div class="field"><span class="field-label">Régimen fiscal:</span><span class="field-value">' + (r.emisor_regimen || '') + '</span></div>';
  html += '</div>';
  html += '</div>';

  html += '<div class="col-right">';
  html += '<div class="section">';
  html += '<div class="section-title">Receptor</div>';
  html += '<div class="field"><span class="field-label">RFC:</span><span class="field-value">' + (r.receptor_rfc || '') + '</span></div>';
  html += '<div class="field"><span class="field-label">Nombre:</span><span class="field-value">' + (r.receptor_nombre || '') + '</span></div>';
  html += '<div class="field"><span class="field-label">Régimen fiscal receptor:</span><span class="field-value">' + (r.receptor_regimen || '') + '</span></div>';
  html += '<div class="field"><span class="field-label">Uso CFDI:</span><span class="field-value">' + (r.receptor_uso_cfdi || '') + '</span></div>';
  html += '</div>';
  html += '</div>';

  html += '</div>';

  html += '<div class="section">';
  html += '<div class="section-title">Comprobante</div>';
  html += '<div class="cols">';
  html += '<div class="col-left">';
  html += '<div class="field"><span class="field-label">Lugar de expedición:</span><span class="field-value">' + (r.lugar_expedicion || '') + '</span></div>';
  html += '<div class="field"><span class="field-label">Fecha y hora:</span><span class="field-value">' + (r.fecha || '') + '</span></div>';
  html += '<div class="field"><span class="field-label">Folio:</span><span class="field-value">' + (r.serie || '') + ' ' + (r.folio || '') + '</span></div>';
  html += '<div class="field"><span class="field-label">No. certificado:</span><span class="field-value">' + (r.no_certificado || '') + '</span></div>';
  html += '</div>';
  html += '<div class="col-right">';
  html += '<div class="field"><span class="field-label">Forma de pago:</span><span class="field-value">' + (r.forma_pago || '') + '</span></div>';
  html += '<div class="field"><span class="field-label">Método de pago:</span><span class="field-value">' + (r.metodo_pago || '') + '</span></div>';
  html += '<div class="field"><span class="field-label">Tipo de comprobante:</span><span class="field-value">' + (r.tipo_comprobante || '') + '</span></div>';
  html += '<div class="field"><span class="field-label">Moneda:</span><span class="field-value">' + (r.moneda || '') + '</span></div>';
  html += '<div class="field"><span class="field-label">Exportación:</span><span class="field-value">01</span></div>';
  if (r.es_global == 1) {
    html += '<div class="field"><span class="field-label">Periodicidad:</span><span class="field-value">' + (r.periodicidad || '') + '</span></div>';
    html += '<div class="field"><span class="field-label">Meses:</span><span class="field-value">' + (r.meses || '') + '</span></div>';
    html += '<div class="field"><span class="field-label">Año:</span><span class="field-value">' + (r.anio || '') + '</span></div>';
  }
  html += '</div></div></div>';

  html += '<div class="section">';
  html += '<div class="section-title">Importes</div>';
  html += '<table class="totales-table">';
  html += '<tr><td>Subtotal</td><td>$' + parseFloat(r.subtotal||0).toFixed(2) + '</td></tr>';
  html += '<tr><td>Descuento</td><td>$' + parseFloat(r.descuento||0).toFixed(2) + '</td></tr>';
  html += '<tr><td>IVA 16% Trasladado</td><td>$' + parseFloat(r.impuestos_traslados||0).toFixed(2) + '</td></tr>';
  html += '<tr><td>IVA Retenido</td><td>$' + parseFloat(r.impuestos_retenidos||0).toFixed(2) + '</td></tr>';
  html += '<tr><td>IVA Neto</td><td>$' + parseFloat(r.iva_neto||0).toFixed(2) + '</td></tr>';
  html += '<tr class="total-final"><td>TOTAL</td><td>$' + parseFloat(r.total||0).toFixed(2) + '</td></tr>';
  html += '</table></div>';

  html += '<div class="estado-box">';
  html += '<span>Estado ante el SAT: ' + (estado || 'SIN VERIFICAR') + '</span>';
  html += '</div>';

  html += '<div class="footer">';
  html += 'Este documento es una representación impresa de un Comprobante Fiscal Digital por Internet (CFDI).<br>';
  html += 'Para su verificación consulte en la página del SAT: https://verificacfdi.facturaelectronica.sat.gob.mx/<br>';
  html += 'Sistema de Emisión CFDI 4.0';
  html += '</div>';

  html += '</div>';

  html += '<script>window.onload = function(){ window.print(); }<\/script>';
  html += '</body></html>';

  var w = window.open('', '_blank', 'width=900,height=700');
  w.document.write(html);
  w.document.close();
}

/* ---- Ver XML ---- */
function verXml() {
  var sel = $('#tabla-xml').bootstrapTable('getSelections');
  if (!sel || sel.length === 0) {
    MsgNotify('Seleccione un registro para ver el XML', 'warning');
    return;
  }
  var r = sel[0];
  if (!r.id) {
    MsgNotify('El registro no tiene ID (no esta guardado en BD)', 'warning');
    return;
  }

  MsgServer(path.model + 'xml_reader.php', function(dat) {
    if (!dat.result || !dat.xml) {
      MsgNotify(dat.message || 'No se pudo obtener el XML', 'error');
      return;
    }
    var w = window.open('', '_blank', 'width=900,height=700');
    w.document.write('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>XML - ' + (r.uuid || '') + '</title>');
    w.document.write('<style>body{font-family:"Courier New",monospace;font-size:12px;margin:20px;white-space:pre-wrap;background:#f8f9fa;} h3{font-family:Arial;color:#0d47a1;border-bottom:2px solid #0d47a1;padding-bottom:5px;} .copy-btn{position:fixed;top:10px;right:10px;padding:8px 16px;background:#0d47a1;color:white;border:none;border-radius:6px;cursor:pointer;font-size:13px;z-index:99;} .copy-btn:hover{background:#1565c0;} pre{margin:0;}</style></head><body>');
    w.document.write('<h3>CFDI XML - ' + (r.uuid || '') + '</h3>');
    w.document.write('<button class="copy-btn" onclick="navigator.clipboard.writeText(document.getElementById(\'xml-code\').textContent).then(function(){alert(\'XML copiado!\')})"><i class=\"fas fa-copy\"></i> Copiar XML</button>');
    w.document.write('<pre id="xml-code">' + dat.xml.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</pre>');
    w.document.write('</body></html>');
    w.document.close();
  }, { action: 'get_xml', id: r.id });
}

$(function() {
  renderColumnasCheck();
  initTabla([]);
});
</script>

<?php include('view_footer.php'); ?>