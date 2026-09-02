<?php
include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( false );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_root() . 'config_db.php' );

$cAction = filter_post( 'action' );

switch( $cAction ) {	
	case 'login':    $result = Login();    break;
	case 'logout':   $result = Logout();   break;
	default:         $result = [ 'result' => false, 'message' => 'Acción no válida' ];
}

die( json_encode( $result ) );

function Login() {
	$usuario = filter_post( 'usuario' );
	$clave   = filter_post( 'clave' );

	if ( empty($usuario) || empty($clave) ) {
		return [ 'result' => false, 'message' => 'Usuario y contraseña son obligatorios' ];
	}

	$oDb = create_conex();
	$sql = "SELECT id, username, user, pasw1, rol FROM users WHERE user = ? LIMIT 1";
	$oDb->bind_params($sql, [$usuario]);
	$row = $oDb->getrow();
	$oDb->Close();

	if ( !$row ) {
		return [ 'result' => false, 'message' => 'Usuario no encontrado' ];
	}

	if ( !password_verify($clave, $row['pasw1']) ) {
		return [ 'result' => false, 'message' => 'Contraseña incorrecta' ];
	}

	$oSession = new TSession( APP_SESSION );
	$oSession->AddVar( 'usuario', $row['username'] );
	$oSession->AddVar( 'id', $row['id'] );
	$oSession->AddVar( 'rol', $row['rol'] );
	$oSession->Login();

	return [ 'result' => true ];
}

function Logout() {
	$oSession = new TSession( APP_SESSION );
	$oSession->Logout();
	return [ 'result' => true ];
}
?>