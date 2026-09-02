<?php
include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( false );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_root() . 'config_db.php' );

$cAction = filter_post( 'action' );

switch( $cAction ) {
	case 'listar':    $result = Listar();    break;
	case 'guardar':   $result = Guardar();   break;
	case 'eliminar':  $result = Eliminar();  break;
	case 'poblar':    $result = Poblar();    break;
	default: $result = [ 'result' => false, 'message' => 'Acción no válida' ];
}

ob_clean();
header('Content-Type: application/json; charset=utf-8');
echo json_encode( $result );
exit;

function Listar() {
	$oMysql = create_conex();
	$oMysql->query("SELECT c.*, x.emisor_rfc, x.emisor_nombre, x.receptor_rfc, x.receptor_nombre, x.total, x.estado
		FROM cfdi_cierres c
		LEFT JOIN xml_importados x ON x.uuid COLLATE utf8mb4_unicode_ci = c.uuid COLLATE utf8mb4_unicode_ci
		ORDER BY c.fecha_timbrado ASC");
	$registros = [];
	while ( $f = $oMysql->getrow() ) {
		$registros[] = $f;
	}
	return [ 'result' => true, 'data' => $registros, 'total' => count($registros) ];
}

function Guardar() {
	$id = (int)filter_post('id');
	$fecharealcierre = trim(filter_post('fecharealcierre'));

	if ( $id <= 0 ) return [ 'result' => false, 'message' => 'ID no válido' ];

	$oMysql = create_conex();
	$oMysql->getConnection()->autocommit(true);

	$fechaSql = !empty($fecharealcierre) ? "'" . $oMysql->escape($fecharealcierre) . "'" : "NULL";
	$oMysql->query("UPDATE cfdi_cierres SET fecharealcierre = {$fechaSql} WHERE id = " . $id);

	return [ 'result' => true, 'message' => 'Fecha de cierre actualizada' ];
}

function Eliminar() {
	$id = (int)filter_post('id');
	if ( $id <= 0 ) return [ 'result' => false, 'message' => 'ID no válido' ];

	$oMysql = create_conex();
	$oMysql->getConnection()->autocommit(true);
	$oMysql->query("DELETE FROM cfdi_cierres WHERE id = " . $id);

	return [ 'result' => true, 'message' => 'Registro eliminado' ];
}

function Poblar() {
	$oMysql = create_conex();
	$oMysql->getConnection()->autocommit(true);
	$mysqli = $oMysql->getConnection();

	$uuids = [];
	$res = $mysqli->query("SELECT uuid, timbre_fecha FROM xml_importados WHERE uuid != '' AND uuid LIKE '%-%'");
	while ( $f = $res->fetch_assoc() ) {
		$uuids[] = [ 'uuid' => $f['uuid'], 'timbre_fecha' => $f['timbre_fecha'] ];
	}
	$res->free();

	$nuevos = 0;
	foreach ( $uuids as $f ) {
		$uuid = $f['uuid'];
		$fechaTimbrado = !empty($f['timbre_fecha']) ? $f['timbre_fecha'] : null;

		$check = $mysqli->query("SELECT id FROM cfdi_cierres WHERE uuid = '" . $mysqli->real_escape_string($uuid) . "' LIMIT 1");
		$row = $check->fetch_assoc();
		$check->free();

		if ( !$row ) {
			$fechaSql = $fechaTimbrado ? "'" . $mysqli->real_escape_string($fechaTimbrado) . "'" : "NULL";
			$fechaCierreSql = $fechaTimbrado ? "'" . $mysqli->real_escape_string(substr($fechaTimbrado, 0, 10)) . "'" : "NULL";
			$mysqli->query("INSERT INTO cfdi_cierres (uuid, fecha_timbrado, fecharealcierre) 
				VALUES ('" . $mysqli->real_escape_string($uuid) . "', {$fechaSql}, {$fechaCierreSql})");
			$nuevos++;
		}
	}

	return [ 'result' => true, 'message' => "Se agregaron {$nuevos} registros nuevos a cierres" ];
}
?>
