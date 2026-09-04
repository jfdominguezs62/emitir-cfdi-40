<?php

function tieneRol( $rolRequerido ) {
	$oSession = new TSession( APP_SESSION );
	$oSession->lExeError = false;
	$rol = $oSession->GetVar('rol');
	if ( empty($rol) ) $rol = 'operador';

	if ( $rolRequerido == 'admin' ) {
		return $rol === 'admin';
	}
	return true;
}

function rolUsuario() {
	$oSession = new TSession( APP_SESSION );
	$oSession->lExeError = false;
	$rol = $oSession->GetVar('rol');
	return $rol ?: 'operador';
}

function esConsultor() {
	return rolUsuario() === 'consultor';
}

function esAdmin() {
	return rolUsuario() === 'admin';
}

function checkAcceso( $rolesPermitidos = [] ) {
	if ( empty($rolesPermitidos) ) return;
	$oSession = new TSession( APP_SESSION );
	if ( is_string($rolesPermitidos) ) {
		$args = func_get_args();
		$rolesPermitidos = isset($args[1]) ? $args[1] : [];
		if ( empty($rolesPermitidos) ) return;
	}
	$rol = $oSession->GetVar('rol') ?: 'operador';
	if ( !in_array($rol, $rolesPermitidos) ) {
		header("Location: menu.php");
		exit;
	}
}

function getEmisorConfig() {
	$oDb = create_conex();
	$sql = "SELECT * FROM emisor_config ORDER BY id DESC LIMIT 1";
	$oDb->query($sql);
	$row = $oDb->getrow();
	$oDb->Close();
	return $row;
}

function getSiguienteFolio( $serie ) {
	$oDb = create_conex();
	$sql = "SELECT folio_actual FROM series WHERE serie = ? AND activa = 1 LIMIT 1";
	$oDb->bind_params($sql, [$serie]);
	$row = $oDb->getrow();
	if ( !$row ) {
		$oDb->Close();
		return false;
	}
	$folio = $row['folio_actual'];
	$oDb->update('series', ['folio_actual' => $folio + 1], 'serie', $serie);
	$oDb->Close();
	return $folio;
}
?>