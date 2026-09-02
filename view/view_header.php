<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

$oSession = new TSession( APP_SESSION );
$oSession->lExeError = false;
if ( !$oSession->Valid() ) {
  header("Location: ../index.php");
  exit;
}

$nombreUsuario = $oSession->GetVar('usuario');
$rolUsuario = $oSession->GetVar('rol') ?: 'operador';

$oWeb = new TWeb( APP_TITLE );
$oWeb->lAwesome = true; 
$oWeb->SetIcon( IMAGE_PATH . 'favicon.ico' );
$oWeb->SetFontFamily( FONT_FAMILY );
$oWeb->Activate();
?>

<nav class="navbar navbar-expand-lg navbar-dark shadow-sm py-2 px-3" style="background: linear-gradient(135deg, #0d47a1, #1565c0);">
  <a class="navbar-brand fw-bold" href="menu.php">
    <i class="fas fa-file-invoice me-2"></i>CFDI 4.0
  </a>
  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarNav">
    <ul class="navbar-nav me-auto">
      <li class="nav-item"><a class="nav-link <?php echo $currentPage==='menu'?'active':''; ?>" href="menu.php"><i class="fas fa-th-large me-1"></i>Menú</a></li>
      <?php if ($rolUsuario !== 'consultor') { ?>
      <li class="nav-item"><a class="nav-link <?php echo $currentPage==='clientes'?'active':''; ?>" href="clientes.php"><i class="fas fa-address-book me-1"></i>Clientes</a></li>
      <li class="nav-item"><a class="nav-link <?php echo $currentPage==='facturas'?'active':''; ?>" href="facturas.php"><i class="fas fa-file-invoice me-1"></i>Facturas</a></li>
      <li class="nav-item"><a class="nav-link <?php echo $currentPage==='xml_import'?'active':''; ?>" href="xml_import.php"><i class="fas fa-file-import me-1"></i>Importar XML</a></li>
      <?php } ?>
      <li class="nav-item"><a class="nav-link <?php echo $currentPage==='reportes'?'active':''; ?>" href="reportes.php"><i class="fas fa-chart-bar me-1"></i>Reportes</a></li>
      <li class="nav-item"><a class="nav-link <?php echo $currentPage==='cfdi_cierres'?'active':''; ?>" href="cfdi_cierres.php"><i class="fas fa-calendar-check me-1"></i>Cierres</a></li>
      <?php if ($rolUsuario === 'admin') { ?>
      <li class="nav-item"><a class="nav-link <?php echo $currentPage==='usuarios'?'active':''; ?>" href="usuarios.php"><i class="fas fa-users-cog me-1"></i>Usuarios</a></li>
      <?php } ?>
    </ul>
    <ul class="navbar-nav">
      <li class="nav-item">
        <span class="nav-link text-white-50">
          <i class="fas fa-user-circle me-1"></i><span class="fw-bold"><?php echo htmlspecialchars($nombreUsuario); ?></span>
          <span class="badge bg-light text-dark ms-1"><?php echo strtoupper($rolUsuario); ?></span>
        </span>
      </li>
      <li class="nav-item"><a class="nav-link" href="#" onclick="logout()"><i class="fas fa-sign-out-alt me-1"></i>Salir</a></li>
    </ul>
  </div>
</nav>

<div class="container-fluid py-3 px-3">
