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
?>

<h4 class="fw-bold text-dark mb-4"><i class="fas fa-address-book me-2"></i>Catálogo de Clientes</h4>

<div class="d-flex justify-content-end mb-3">
  <button class="btn btn-success btn-sm fw-bold" onclick="openModal()"><i class="fas fa-plus me-1"></i>Agregar Cliente</button>
</div>

<div class="card border-0 shadow-sm" style="border-radius: 12px;">
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>RFC</th>
          <th>Razón Social</th>
          <th>Régimen</th>
          <th>Uso CFDI</th>
          <th>Email</th>
          <th>Estatus</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody id="clientes-body"></tbody>
    </table>
  </div>
</div>

<!-- Modal Cliente -->
<div class="modal fade" id="clienteModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius: 12px;">
      <div class="modal-header" style="background: linear-gradient(135deg, #0d47a1, #1565c0); color: white;">
        <h5 class="modal-title fw-bold" id="modal-title">Nuevo Cliente</h5>
        <button type="button" class="btn-close btn-close-white" onclick="$('#clienteModal').modal('hide')"></button>
      </div>
      <div class="modal-body">
        <form id="form-cliente">
          <input type="hidden" id="f-id">
          <h6 class="text-primary fw-bold mb-3"><i class="fas fa-id-card me-1"></i>Datos Fiscales</h6>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">RFC *</label>
              <input type="text" class="form-control" id="f-rfc" maxlength="13" required style="text-transform:uppercase;">
            </div>
            <div class="col-md-8 mb-3">
              <label class="form-label">Razón Social *</label>
              <input type="text" class="form-control" id="f-razon_social" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Régimen Fiscal *</label>
              <select class="form-select" id="f-regimen_fiscal_receptor" required></select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Uso de CFDI *</label>
              <select class="form-select" id="f-uso_cfdi" required></select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" id="f-email">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Teléfono</label>
              <input type="text" class="form-control" id="f-telefono">
            </div>
          </div>

          <h6 class="text-primary fw-bold mb-3 mt-3"><i class="fas fa-map-marker-alt me-1"></i>Domicilio</h6>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Calle</label>
              <input type="text" class="form-control" id="f-calle">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Núm. Exterior</label>
              <input type="text" class="form-control" id="f-numero_exterior">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Núm. Interior</label>
              <input type="text" class="form-control" id="f-numero_interior">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Colonia</label>
              <input type="text" class="form-control" id="f-colonia">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Código Postal *</label>
              <input type="text" class="form-control" id="f-codigo_postal" maxlength="5" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Localidad</label>
              <input type="text" class="form-control" id="f-localidad">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Municipio / Alcaldía</label>
              <input type="text" class="form-control" id="f-municipio">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Estado</label>
              <select class="form-select" id="f-estado"></select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">País</label>
              <select class="form-select" id="f-pais">
                <option value="MEX">México (MEX)</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Estatus</label>
            <select class="form-select" id="f-estatus">
              <option value="activo">Activo</option>
              <option value="inactivo">Inactivo</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" onclick="$('#clienteModal').modal('hide')"><i class="fas fa-times me-1"></i>Cancelar</button>
        <button class="btn btn-primary" onclick="saveCliente()"><i class="fas fa-save me-1"></i>Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
var clientesData = [];

function loadClientes() {
    MsgServer( path.model + 'clientes.php', function(dat) {
        if (dat.result) {
            clientesData = dat.data;
            renderTable(dat.data);
        }
    }, { action: 'getclientes' });
}

function renderTable(data) {
    var html = '';
    data.forEach(function(c) {
        var badge = c.estatus === 'activo' ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>';
        html += '<tr>';
        html += '<td>' + c.id + '</td>';
        html += '<td><code>' + c.rfc + '</code></td>';
        html += '<td>' + c.razon_social + '</td>';
        html += '<td>' + c.regimen_fiscal_receptor + '</td>';
        html += '<td>' + c.uso_cfdi + '</td>';
        html += '<td>' + (c.email || '-') + '</td>';
        html += '<td>' + badge + '</td>';
        html += '<td>';
        html += '<button class="btn btn-sm btn-info me-1" onclick="editCliente(' + c.id + ')"><i class="fas fa-edit"></i></button>';
        html += '<button class="btn btn-sm btn-danger" onclick="deleteCliente(' + c.id + ')"><i class="fas fa-trash"></i></button>';
        html += '</td></tr>';
    });
    $('#clientes-body').html(html || '<tr><td colspan="8" class="text-center text-muted">Sin clientes registrados</td></tr>');
}

function loadCatalogos(callback) {
    var loaded = 0;
    var total = 3;
    var regimenes = [];
    var usos = [];
    var estados = [];

    MsgServer(path.model + 'catalogos.php', function(dat) {
        if (dat.result) regimenes = dat.data;
        if (++loaded === total) buildSelects();
    }, { action: 'getregimenes' });

    MsgServer(path.model + 'catalogos.php', function(dat) {
        if (dat.result) usos = dat.data;
        if (++loaded === total) buildSelects();
    }, { action: 'getusos_cfdi' });

    MsgServer(path.model + 'catalogos.php', function(dat) {
        if (dat.result) estados = dat.data;
        if (++loaded === total) buildSelects();
    }, { action: 'getestados' });

    function buildSelects() {
        var selReg = $('#f-regimen_fiscal_receptor');
        selReg.empty().append('<option value="">-- Seleccionar --</option>');
        regimenes.forEach(function(r) {
            selReg.append('<option value="' + r.clave + '">' + r.clave + ' - ' + r.descripcion + '</option>');
        });

        var selUso = $('#f-uso_cfdi');
        selUso.empty().append('<option value="">-- Seleccionar --</option>');
        usos.forEach(function(u) {
            selUso.append('<option value="' + u.clave + '">' + u.clave + ' - ' + u.descripcion + '</option>');
        });

        var selEst = $('#f-estado');
        selEst.empty().append('<option value="">-- Seleccionar --</option>');
        estados.forEach(function(e) {
            selEst.append('<option value="' + e.descripcion + '">' + e.descripcion + '</option>');
        });

        if (callback) callback();
    }
}

function openModal() {
    $('#form-cliente')[0].reset();
    $('#f-id').val('');
    $('#f-estatus').val('activo');
    $('#f-pais').val('MEX');
    $('#modal-title').text('Nuevo Cliente');
    loadCatalogos(function() {
        $('#clienteModal').modal('show');
    });
}

function editCliente(id) {
    var c = clientesData.find(function(x){ return x.id == id; });
    if (!c) return;
    loadCatalogos(function() {
        $('#f-id').val(c.id);
        $('#f-rfc').val(c.rfc);
        $('#f-razon_social').val(c.razon_social);
        $('#f-regimen_fiscal_receptor').val(c.regimen_fiscal_receptor);
        $('#f-uso_cfdi').val(c.uso_cfdi);
        $('#f-email').val(c.email);
        $('#f-telefono').val(c.telefono);
        $('#f-calle').val(c.calle);
        $('#f-numero_exterior').val(c.numero_exterior);
        $('#f-numero_interior').val(c.numero_interior);
        $('#f-colonia').val(c.colonia);
        $('#f-codigo_postal').val(c.codigo_postal);
        $('#f-localidad').val(c.localidad);
        $('#f-municipio').val(c.municipio);
        $('#f-estado').val(c.estado);
        $('#f-pais').val(c.pais);
        $('#f-estatus').val(c.estatus);
        $('#modal-title').text('Editar Cliente');
        $('#clienteModal').modal('show');
    });
}

function saveCliente() {
    var aPar = {
        action: 'save',
        id: $('#f-id').val(),
        rfc: $('#f-rfc').val(),
        razon_social: $('#f-razon_social').val(),
        regimen_fiscal_receptor: $('#f-regimen_fiscal_receptor').val(),
        uso_cfdi: $('#f-uso_cfdi').val(),
        email: $('#f-email').val(),
        telefono: $('#f-telefono').val(),
        calle: $('#f-calle').val(),
        numero_exterior: $('#f-numero_exterior').val(),
        numero_interior: $('#f-numero_interior').val(),
        colonia: $('#f-colonia').val(),
        codigo_postal: $('#f-codigo_postal').val(),
        localidad: $('#f-localidad').val(),
        municipio: $('#f-municipio').val(),
        estado: $('#f-estado').val(),
        pais: $('#f-pais').val(),
        estatus: $('#f-estatus').val()
    };
    MsgServer( path.model + 'clientes.php', function(dat) {
        if (dat.result) {
            MsgNotify('Cliente guardado', 'success');
            $('#clienteModal').modal('hide');
            loadClientes();
        } else {
            MsgNotify(dat.message || 'Error al guardar', 'danger');
        }
    }, aPar);
}

function deleteCliente(id) {
    Swal.fire({
        title: '¿Eliminar este cliente?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (result.isConfirmed) {
            MsgServer( path.model + 'clientes.php', function(dat) {
                if (dat.result) { MsgNotify('Cliente eliminado', 'success'); loadClientes(); }
                else { MsgNotify(dat.message || 'Error', 'danger'); }
            }, { action: 'delete', id: id });
        }
    });
}

$(function() { loadClientes(); });
</script>

<?php include('view_footer.php'); ?>
