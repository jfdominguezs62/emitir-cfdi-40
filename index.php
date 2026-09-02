<?php
include( 'constants.php' );
Constants::unset();

include( Constants::getpath_root() . 'config.php' );
include( Constants::getpath_tweb() . 'core.php' );

$oWeb = new TWeb( APP_TITLE );
$oWeb->lAwesome = true; 
$oWeb->SetFontFamily( FONT_FAMILY );
$oWeb->Activate();

$oSession = new TSession( APP_SESSION );
$oSession->lExeError = false;
if ( $oSession->Valid() ) {
  ob_start();
  header("Location: view/menu.php");
  ob_end_flush();
  exit;
}
?>

<div class="container">
  <div class="row justify-content-center mt-5">
    <div class="col-md-5">
      <div class="card shadow-lg border-0" style="border-radius: 16px;">
        <div class="card-body p-5">
          <div class="text-center mb-4">
            <div class="rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 80px; height: 80px; background: linear-gradient(135deg, #0d47a1, #1565c0);">
              <i class="fas fa-file-invoice text-white" style="font-size: 36px;"></i>
            </div>
            <h4 class="fw-bold text-dark">Sistema de Emisión CFDI</h4>
            <p class="text-muted">Ingrese sus credenciales</p>
          </div>
          <form id="form-login" onsubmit="return login();">
            <div class="mb-3">
              <label class="form-label fw-bold">Usuario</label>
              <input type="text" class="form-control form-control-lg" id="f-usuario" required autofocus>
            </div>
            <div class="mb-4">
              <label class="form-label fw-bold">Contraseña</label>
              <input type="password" class="form-control form-control-lg" id="f-clave" required>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold" style="background: linear-gradient(135deg, #0d47a1, #1565c0); border: none;">
              <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
            </button>
          </form>
        </div>
      </div>
      <p class="text-center text-muted mt-3"><small>&copy; Emisión CFDI v1.0</small></p>
    </div>
  </div>
</div>

<script>
function login() {
  var usuario = document.getElementById('f-usuario').value;
  var clave   = document.getElementById('f-clave').value;

  if (!usuario || !clave) {
    MsgNotify("Ingrese usuario y contraseña", "error");
    return false;
  }

  $.ajax({
    url: path.model + 'login.php',
    type: 'POST',
    dataType: 'json',
    data: { action: 'login', usuario: usuario, clave: clave },
    success: function(dat) {
      if (dat.result) {
        MsgNotify("¡Acceso concedido!", "success");
        setTimeout(function() { location.href = path.view + 'menu.php'; }, 500);
      } else {
        MsgNotify(dat.message || "Acceso denegado", "error");
      }
    },
    error: function(xhr, status, error) {
      MsgNotify("Error de conexión: " + error, "error");
    }
  });

  return false;
}

$(function() {
  document.getElementById('f-usuario').focus();
});
</script>

<?php $oWeb->End(); ?>
