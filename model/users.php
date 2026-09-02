<?php
include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( false );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_root() . 'config_db.php' );
include ( Constants::getpath_root() . 'helpers.php' );

header('Content-Type: application/json; charset=utf-8');

$cAction = filter_post( 'action' );

switch( $cAction ) {
	case 'listar':    $result = Listar();    break;
	case 'guardar':   $result = Guardar();   break;
	case 'eliminar':  $result = Eliminar();  break;
	default:          $result = [ 'result' => false, 'message' => 'Acción no válida' ];
}

die( json_encode( $result ) );

function Listar() {
	$oDb = create_conex();
	$oDb->query("SELECT id, username, user, rol, created_at FROM users ORDER BY id ASC");
	$registros = [];
	while ( $f = $oDb->getrow() ) {
		$registros[] = $f;
	}
	$oDb->Close();
	return [ 'result' => true, 'data' => $registros, 'total' => count($registros) ];
}

function Guardar() {
	$id       = (int)filter_post('id');
	$username = trim(filter_post('username'));
	$user     = trim(filter_post('user'));
	$clave    = trim(filter_post('clave'));
	$rol      = trim(filter_post('rol'));

	if ( empty($username) || empty($user) || empty($rol) ) {
		return [ 'result' => false, 'message' => 'Nombre, usuario y rol son obligatorios' ];
	}

	$oDb = create_conex();

	// Verificar usuario duplicado
	$sqlCheck = "SELECT id FROM users WHERE user = '" . $oDb->escape($user) . "'";
	if ( $id > 0 ) $sqlCheck .= " AND id != {$id}";
	$oDb->query($sqlCheck);
	if ( $oDb->getrow() ) {
		$oDb->Close();
		return [ 'result' => false, 'message' => 'El usuario ya existe' ];
	}

	if ( $id > 0 ) {
		$data = [
			'username' => $username,
			'user'     => $user,
			'rol'      => $rol
		];
		if ( !empty($clave) ) {
			$data['pasw1'] = password_hash($clave, PASSWORD_BCRYPT);
		}
		$oDb->update('users', $data, 'id', $id);
	} else {
		if ( empty($clave) ) {
			$oDb->Close();
			return [ 'result' => false, 'message' => 'La contraseña es obligatoria para nuevo usuario' ];
		}
		$oDb->insert('users', [
			'username' => $username,
			'user'     => $user,
			'pasw1'    => password_hash($clave, PASSWORD_BCRYPT),
			'rol'      => $rol
		]);
		$id = $oDb->lastInsertID();
	}

	$oDb->Close();
	return [ 'result' => true, 'id' => $id, 'message' => $id > 0 ? 'Usuario actualizado' : 'Usuario creado' ];
}

function Eliminar() {
	$id = (int)filter_post('id');
	if ( $id <= 0 ) {
		return [ 'result' => false, 'message' => 'ID no válido' ];
	}

	$oDb = create_conex();
	$oDb->delete('users', 'id', $id);
	$oDb->Close();
	return [ 'result' => true, 'message' => 'Usuario eliminado' ];
}
?>
