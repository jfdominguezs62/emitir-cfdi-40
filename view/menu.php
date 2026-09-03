<?php 
include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( true );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_tweb() . 'core.php' );
include ( Constants::getpath_root() . 'helpers.php' );

$oSession = new TSession( APP_SESSION );
$oSession->lExeError = false;
if ( !$oSession->Valid() ) {
  header("Location: ../index.php");
  exit;
}

$oWeb = new TWeb( APP_TITLE );
$oWeb->lAwesome = true; 
$oWeb->SetIcon( IMAGE_PATH . 'favicon.ico' );
$oWeb->SetFontFamily( FONT_FAMILY );
$oWeb->Activate();

$nombreUsuario = $oSession->GetVar('usuario');
$rolUsuario = $oSession->GetVar('rol') ?: 'operador';
?>

<div id="app-content" style="margin-left: 0; transition: margin-left 0.15s;">
  <nav class="navbar navbar-expand-lg navbar-dark shadow-sm py-3 px-4" style="background: linear-gradient(135deg, #0d47a1, #1565c0);">
    <a class="navbar-brand font-weight-bold" href="menu.php">
      <i class="fas fa-file-invoice me-2"></i> Sistema de Emisión CFDI
    </a>
    <div class="ms-auto d-flex align-items-center">
      <span class="text-white me-3">
        <i class="fas fa-user-circle me-1"></i> <span class="fw-bold"><?php echo htmlspecialchars($nombreUsuario); ?></span>
        <span class="badge bg-light text-dark ms-1"><?php echo strtoupper($rolUsuario); ?></span>
      </span>
      <a class="btn btn-sm btn-outline-light ms-2" href="#" onclick="logout()"><i class="fas fa-sign-out-alt me-1"></i>Salir</a>
    </div>
  </nav>

  <div class="container-fluid py-4 px-4">
    <div class="row justify-content-center mb-4">
      <div class="col-lg-10">
        <div class="card border-0 shadow-lg" style="border-radius: 16px; background: rgba(255, 255, 255, 0.95);">
          <div class="card-body p-4 text-center">
            <h3 class="fw-bold text-dark mb-1">¡Bienvenido, <?php echo htmlspecialchars($nombreUsuario); ?>!</h3>
            <p class="text-muted">Selecciona un módulo para comenzar</p>
          </div>
        </div>
      </div>
    </div>

    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="row">
          <?php if ($rolUsuario !== 'consultor') { ?>
          <div class="col-md-4 mb-3">
            <a href="clientes.php" class="text-decoration-none">
              <div class="card border-0 shadow-sm h-100 text-center" style="border-radius: 16px; transition: transform .2s;"
                   onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
                <div class="card-body p-4">
                  <div class="rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 65px; height: 65px; background: linear-gradient(135deg, #0d47a1, #1565c0);">
                    <i class="fas fa-address-book text-white" style="font-size: 28px;"></i>
                  </div>
                  <h5 class="fw-bold text-dark">Clientes</h5>
                  <small class="text-muted">Catálogo de receptores CFDI</small>
                </div>
              </div>
            </a>
          </div>

          <div class="col-md-4 mb-3">
            <a href="facturas.php" class="text-decoration-none">
              <div class="card border-0 shadow-sm h-100 text-center" style="border-radius: 16px; transition: transform .2s;"
                   onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
                <div class="card-body p-4">
                  <div class="rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 65px; height: 65px; background: linear-gradient(135deg, #1b5e20, #2e7d32);">
                    <i class="fas fa-file-invoice text-white" style="font-size: 28px;"></i>
                  </div>
                  <h5 class="fw-bold text-dark">Facturas</h5>
                  <small class="text-muted">Emitir y consultar CFDI</small>
                </div>
              </div>
            </a>
          </div>

          <div class="col-md-4 mb-3">
            <a href="#" class="text-decoration-none" onclick="openConfigModal()">
              <div class="card border-0 shadow-sm h-100 text-center" style="border-radius: 16px; transition: transform .2s;"
                   onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
                <div class="card-body p-4">
                  <div class="rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 65px; height: 65px; background: linear-gradient(135deg, #e65100, #ef6c00);">
                    <i class="fas fa-cog text-white" style="font-size: 28px;"></i>
                  </div>
                  <h5 class="fw-bold text-dark">Configuración</h5>
                  <small class="text-muted">Datos del emisor y PAC</small>
                </div>
              </div>
            </a>
          </div>

          <?php } ?>

          <div class="col-md-4 mb-3">
            <a href="xml_import.php" class="text-decoration-none">
              <div class="card border-0 shadow-sm h-100 text-center" style="border-radius: 16px; transition: transform .2s;"
                   onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
                <div class="card-body p-4">
                  <div class="rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 65px; height: 65px; background: linear-gradient(135deg, #6a1b9a, #8e24aa);">
                    <i class="fas fa-file-import text-white" style="font-size: 28px;"></i>
                  </div>
                  <h5 class="fw-bold text-dark">Importar XML</h5>
                  <small class="text-muted">Leer XML de carpeta y exportar</small>
                </div>
              </div>
            </a>
          </div>

          <div class="col-md-4 mb-3">
            <a href="reportes.php" class="text-decoration-none">
              <div class="card border-0 shadow-sm h-100 text-center" style="border-radius: 16px; transition: transform .2s;"
                   onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
                <div class="card-body p-4">
                  <div class="rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 65px; height: 65px; background: linear-gradient(135deg, #00695c, #00897b);">
                    <i class="fas fa-chart-bar text-white" style="font-size: 28px;"></i>
                  </div>
                  <h5 class="fw-bold text-dark">Reportes</h5>
                  <small class="text-muted">Informes por día, mes o año</small>
                </div>
              </div>
            </a>
          </div>

          <div class="col-md-4 mb-3">
            <a href="cfdi_cierres.php" class="text-decoration-none">
              <div class="card border-0 shadow-sm h-100 text-center" style="border-radius: 16px; transition: transform .2s;"
                   onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
                <div class="card-body p-4">
                  <div class="rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 65px; height: 65px; background: linear-gradient(135deg, #e65100, #f57c00);">
                    <i class="fas fa-calendar-check text-white" style="font-size: 28px;"></i>
                  </div>
                  <h5 class="fw-bold text-dark">Cierres</h5>
                  <small class="text-muted">Fechas de cierre de CFDI</small>
                </div>
              </div>
            </a>
          </div>

          <?php if ($rolUsuario === 'admin') { ?>
          <div class="col-md-4 mb-3">
            <a href="usuarios.php" class="text-decoration-none">
              <div class="card border-0 shadow-sm h-100 text-center" style="border-radius: 16px; transition: transform .2s;"
                   onmouseover="this.style.transform='translateY(-4px)'" onmouseout="this.style.transform=''">
                <div class="card-body p-4">
                  <div class="rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 65px; height: 65px; background: linear-gradient(135deg, #b71c1c, #d32f2f);">
                    <i class="fas fa-users-cog text-white" style="font-size: 28px;"></i>
                  </div>
                  <h5 class="fw-bold text-dark">Usuarios</h5>
                  <small class="text-muted">Gestionar usuarios del sistema</small>
                </div>
              </div>
            </a>
          </div>
          <?php } ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Configuración Emisor -->
<div class="modal fade" id="configModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius: 12px;">
      <div class="modal-header" style="background: linear-gradient(135deg, #e65100, #ef6c00); color: white;">
        <h5 class="modal-title fw-bold">Configuración del Emisor</h5>
        <button type="button" class="btn-close btn-close-white" onclick="$('#configModal').modal('hide')"></button>
      </div>
      <div class="modal-body">
        <form id="form-config">
          <input type="hidden" id="cfg-id">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">RFC del Emisor *</label>
              <input type="text" class="form-control" id="cfg-rfc" maxlength="13" required style="text-transform:uppercase;">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Razón Social *</label>
              <input type="text" class="form-control" id="cfg-razon_social" required>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Nombre Comercial</label>
              <input type="text" class="form-control" id="cfg-nombre_comercial">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Régimen Fiscal *</label>
              <select class="form-select" id="cfg-regimen_fiscal" required></select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Código Postal *</label>
              <input type="text" class="form-control" id="cfg-codigo_postal" maxlength="5" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">No. Certificado</label>
              <input type="text" class="form-control" id="cfg-no_certificado">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Modo</label>
              <select class="form-select" id="cfg-modo">
                <option value="pruebas">Pruebas (Producción Pruebas SAT)</option>
                <option value="produccion">Producción</option>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" onclick="$('#configModal').modal('hide')"><i class="fas fa-times me-1"></i>Cancelar</button>
        <button class="btn btn-primary" onclick="saveConfig()"><i class="fas fa-save me-1"></i>Guardar</button>
      </div>
    </div>
  </div>
</div>

<script>
function logout() {
  $.ajax({
    url: path.model + 'login.php',
    type: 'POST',
    dataType: 'json',
    data: { action: 'logout' },
    success: function(dat) {
      location.href = '../index.php';
    },
    error: function() {
      location.href = '../index.php';
    }
  });
}

function openConfigModal() {
  loadRegimenes(function(regimenes) {
    var sel = $('#cfg-regimen_fiscal');
    sel.empty();
    regimenes.forEach(function(r) {
      sel.append('<option value="' + r.clave + '">' + r.clave + ' - ' + r.descripcion + '</option>');
    });

    MsgServer(path.model + 'catalogos.php', function(dat) {
      if (dat.result && dat.data) {
        var e = dat.data;
        $('#cfg-id').val(e.id);
        $('#cfg-rfc').val(e.rfc);
        $('#cfg-razon_social').val(e.razon_social);
        $('#cfg-nombre_comercial').val(e.nombre_comercial);
        $('#cfg-regimen_fiscal').val(e.regimen_fiscal);
        $('#cfg-codigo_postal').val(e.codigo_postal);
        $('#cfg-no_certificado').val(e.no_certificado);
        $('#cfg-modo').val(e.modo);
      }
      $('#configModal').modal('show');
    }, { action: 'getemisor' });
  });
}

function loadRegimenes(callback) {
  MsgServer(path.model + 'catalogos.php', function(dat) {
    if (dat.result) callback(dat.data);
  }, { action: 'getregimenes' });
}

function saveConfig() {
  var aPar = {
    action: 'saveemisor',
    id: $('#cfg-id').val(),
    rfc: $('#cfg-rfc').val(),
    razon_social: $('#cfg-razon_social').val(),
    nombre_comercial: $('#cfg-nombre_comercial').val(),
    regimen_fiscal: $('#cfg-regimen_fiscal').val(),
    codigo_postal: $('#cfg-codigo_postal').val(),
    no_certificado: $('#cfg-no_certificado').val(),
    modo: $('#cfg-modo').val()
  };
  MsgServer(path.model + 'catalogos.php', function(dat) {
    if (dat.result) {
      MsgNotify('Configuración guardada', 'success');
      $('#configModal').modal('hide');
    } else {
      MsgNotify(dat.message || 'Error', 'danger');
    }
  }, aPar);
}
</script>
