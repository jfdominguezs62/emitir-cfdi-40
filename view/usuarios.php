<?php 
include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( true );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_tweb() . 'core.php' );
include ( Constants::getpath_root() . 'helpers.php' );

$nombreUsuario = '';
$rolUsuario = 'operador';
$currentPage = 'usuarios';

if ( !esAdmin() ) {
  header("Location: menu.php");
  exit;
}

include('view_header.php');

$roles = ['admin', 'operador', 'consultor'];
?>

<h4 class="fw-bold text-dark mb-4"><i class="fas fa-users-cog me-2"></i>Gestión de Usuarios</h4>

<div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
  <div class="card-body">
    <div class="d-flex gap-2">
      <button class="btn btn-primary fw-bold" onclick="nuevoUsuario()"><i class="fas fa-plus me-1"></i>Nuevo Usuario</button>
      <button class="btn btn-outline-secondary fw-bold" onclick="cargarUsuarios()"><i class="fas fa-sync me-1"></i>Recargar</button>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius:12px;">
  <div class="card-body p-0">
    <div style="overflow-x:auto;">
      <table id="tabla-usuarios" class="table table-hover table-sm mb-0" style="font-size:13px;"></table>
    </div>
  </div>
</div>

<!-- Modal Usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius:12px;">
      <div class="modal-header" style="background: linear-gradient(135deg, #0d47a1, #1565c0); color: white;">
        <h5 class="modal-title fw-bold" id="modalUsuarioTitle">Nuevo Usuario</h5>
        <button type="button" class="btn-close btn-close-white" onclick="$('#modalUsuario').modal('hide')"></button>
      </div>
      <div class="modal-body">
        <form id="form-usuario">
          <input type="hidden" id="usr-id">
          <div class="mb-3">
            <label class="form-label fw-bold">Nombre completo *</label>
            <input type="text" class="form-control" id="usr-username" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Usuario (login) *</label>
            <input type="text" class="form-control" id="usr-user" required style="text-transform:lowercase;">
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold" id="lbl-clave">Contraseña *</label>
            <input type="password" class="form-control" id="usr-clave">
            <small class="text-muted" id="clave-hint" style="display:none;">Dejar vacío para mantener la actual</small>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Rol *</label>
            <select class="form-select" id="usr-rol" required>
              <?php foreach ($roles as $r) { ?>
              <option value="<?php echo $r; ?>"><?php echo ucfirst($r); ?></option>
              <?php } ?>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" onclick="$('#modalUsuario').modal('hide')">Cancelar</button>
        <button class="btn btn-primary fw-bold" onclick="guardarUsuario()"><i class="fas fa-save me-1"></i>Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
var usrData = [];

function cargarUsuarios() {
  MsgServer(path.model + 'users.php', function(dat) {
    if (!dat.result) { MsgNotify(dat.message, 'error'); return; }
    usrData = dat.data;
    initTabla(dat.data);
  }, { action: 'listar' });
}

function initTabla(data) {
  var $tbl = $('#tabla-usuarios');
  $tbl.bootstrapTable('destroy');
  $tbl.bootstrapTable({
    data: data,
    classes: 'table table-hover',
    theadClasses: 'table-light',
    locale: 'es-ES',
    search: true,
    pagination: true,
    pageSize: 25,
    uniqueId: 'id',
    columns: [
      { field: 'id', title: '#', width: 50, align: 'center' },
      { field: 'username', title: 'Nombre', width: 200 },
      { field: 'user', title: 'Usuario', width: 150 },
      { field: 'rol', title: 'Rol', width: 120, formatter: function(v) {
        var cls = 'secondary';
        if (v === 'admin') cls = 'danger';
        else if (v === 'operador') cls = 'primary';
        else if (v === 'consultor') cls = 'info';
        return '<span class="badge bg-' + cls + '">' + v.toUpperCase() + '</span>';
      }},
      { field: 'created_at', title: 'Creado', width: 160 },
      { field: 'acciones', title: '<i class="fas fa-cog"></i>', width: 120, align: 'center', formatter: function(v, r) {
        return '<button class="btn btn-sm btn-outline-primary me-1" onclick="editarUsuario(' + r.id + ')" title="Editar"><i class="fas fa-edit"></i></button>'
             + '<button class="btn btn-sm btn-outline-danger" onclick="eliminarUsuario(' + r.id + ', \'' + r.username + '\')" title="Eliminar"><i class="fas fa-trash"></i></button>';
      }}
    ]
  });
}

function nuevoUsuario() {
  $('#modalUsuarioTitle').text('Nuevo Usuario');
  $('#usr-id').val('');
  $('#usr-username').val('');
  $('#usr-user').val('').prop('readonly', false);
  $('#usr-clave').val('').attr('required', true);
  $('#lbl-clave').text('Contraseña *');
  $('#clave-hint').hide();
  $('#usr-rol').val('operador');
  $('#modalUsuario').modal('show');
}

function editarUsuario(id) {
  var row = usrData.find(function(r){ return r.id == id; });
  if (!row) { MsgNotify('Usuario no encontrado', 'error'); return; }

  $('#modalUsuarioTitle').text('Editar Usuario');
  $('#usr-id').val(row.id);
  $('#usr-username').val(row.username);
  $('#usr-user').val(row.user).prop('readonly', true);
  $('#usr-clave').val('').removeAttr('required');
  $('#lbl-clave').text('Contraseña');
  $('#clave-hint').show();
  $('#usr-rol').val(row.rol);
  $('#modalUsuario').modal('show');
}

function guardarUsuario() {
  var id       = $('#usr-id').val();
  var username = $('#usr-username').val().trim();
  var user     = $('#usr-user').val().trim();
  var clave    = $('#usr-clave').val().trim();
  var rol      = $('#usr-rol').val();

  if (!username || !user || !rol) {
    MsgNotify('Nombre, usuario y rol son obligatorios', 'error');
    return;
  }
  if (!id && !clave) {
    MsgNotify('La contraseña es obligatoria para nuevo usuario', 'error');
    return;
  }

  MsgServer(path.model + 'users.php', function(dat) {
    if (!dat.result) { MsgNotify(dat.message, 'error'); return; }
    MsgNotify(dat.message, 'success');
    $('#modalUsuario').modal('hide');
    cargarUsuarios();
  }, { action: 'guardar', id: id, username: username, user: user, clave: clave, rol: rol });
}

function eliminarUsuario(id, nombre) {
  if (!confirm('¿Eliminar el usuario "' + nombre + '"?')) return;
  MsgServer(path.model + 'users.php', function(dat) {
    if (!dat.result) { MsgNotify(dat.message, 'error'); return; }
    MsgNotify('Usuario eliminado', 'success');
    cargarUsuarios();
  }, { action: 'eliminar', id: id });
}

$(function(){ cargarUsuarios(); });
</script>

</div>

<?php include('view_footer.php'); ?>
