<?php
include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( false );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_root() . 'config_db.php' );

$cAction = filter_post( 'action' );

switch( $cAction ) {	
	case 'getclientes':  $result = GetClientes();  break;
	case 'getcliente':   $result = GetCliente();   break;
	case 'save':         $result = SaveCliente();   break;
	case 'delete':       $result = DeleteCliente(); break;
	default:             $result = [ 'result' => false, 'message' => 'Acción no válida' ];
}

die( json_encode( $result ) );

function GetClientes() {
	$oDb = create_conex();
	$sql = "SELECT id, rfc, razon_social, regimen_fiscal_receptor, uso_cfdi, email, telefono, 
	                calle, numero_exterior, numero_interior, colonia, codigo_postal, 
	                localidad, municipio, estado, pais, estatus, created_at 
	        FROM clientes ORDER BY razon_social";
	$oDb->query($sql);
	$rows = [];
	while ( $row = $oDb->getrow() ) {
		$rows[] = $row;
	}
	$oDb->Close();
	return [ 'result' => true, 'data' => $rows ];
}

function GetCliente() {
	$id = filter_post( 'id' );
	$oDb = create_conex();
	$sql = "SELECT * FROM clientes WHERE id = ? LIMIT 1";
	if ( $oDb->bind_params($sql, [$id]) ) {
		$row = $oDb->getrow();
		$oDb->Close();
		if ( $row ) return [ 'result' => true, 'data' => $row ];
	}
	$oDb->Close();
	return [ 'result' => false ];
}

function SaveCliente() {
	$id                       = filter_post( 'id' );
	$rfc                      = strtoupper(trim(filter_post( 'rfc' )));
	$razon_social             = trim(filter_post( 'razon_social' ));
	$regimen_fiscal_receptor  = filter_post( 'regimen_fiscal_receptor' );
	$uso_cfdi                 = filter_post( 'uso_cfdi' );
	$email                    = trim(filter_post( 'email' ));
	$telefono                 = trim(filter_post( 'telefono' ));
	$calle                    = trim(filter_post( 'calle' ));
	$numero_exterior          = trim(filter_post( 'numero_exterior' ));
	$numero_interior          = trim(filter_post( 'numero_interior' ));
	$colonia                  = trim(filter_post( 'colonia' ));
	$codigo_postal            = trim(filter_post( 'codigo_postal' ));
	$localidad                = trim(filter_post( 'localidad' ));
	$municipio                = trim(filter_post( 'municipio' ));
	$estado                   = trim(filter_post( 'estado' ));
	$pais                     = strtoupper(trim(filter_post( 'pais' )));
	$estatus                  = filter_post( 'estatus' );

	if ( empty($rfc) || empty($razon_social) ) {
		return [ 'result' => false, 'message' => 'RFC y Razón Social son obligatorios' ];
	}

	if ( strlen($rfc) !== 12 && strlen($rfc) !== 13 ) {
		return [ 'result' => false, 'message' => 'El RFC debe tener 12 o 13 caracteres' ];
	}

	if ( empty($regimen_fiscal_receptor) ) {
		return [ 'result' => false, 'message' => 'El régimen fiscal del receptor es obligatorio' ];
	}

	if ( empty($uso_cfdi) ) {
		return [ 'result' => false, 'message' => 'El uso de CFDI es obligatorio' ];
	}

	if ( empty($codigo_postal) || strlen($codigo_postal) !== 5 ) {
		return [ 'result' => false, 'message' => 'El código postal debe tener 5 dígitos' ];
	}

	$oDb = create_conex();

	$sqlCheck = "SELECT id FROM clientes WHERE rfc = ?";
	$paramsCheck = [$rfc];
	if ( !empty($id) ) {
		$sqlCheck .= " AND id != ?";
		$paramsCheck[] = $id;
	}
	$sqlCheck .= " LIMIT 1";
	
	if ( $oDb->bind_params($sqlCheck, $paramsCheck) ) {
		if ( $row = $oDb->getrow() ) {
			$oDb->Close();
			return [ 'result' => false, 'message' => 'Ya existe un cliente con ese RFC' ];
		}
	}

	if ( !empty($id) ) {
		$sql = "UPDATE clientes SET rfc=?, razon_social=?, regimen_fiscal_receptor=?, uso_cfdi=?, 
		                email=?, telefono=?, calle=?, numero_exterior=?, numero_interior=?, 
		                colonia=?, codigo_postal=?, localidad=?, municipio=?, estado=?, 
		                pais=?, estatus=? WHERE id=?";
		$params = [$rfc, $razon_social, $regimen_fiscal_receptor, $uso_cfdi, 
		           $email, $telefono, $calle, $numero_exterior, $numero_interior, 
		           $colonia, $codigo_postal, $localidad, $municipio, $estado, 
		           $pais, $estatus, $id];
	} else {
		$sql = "INSERT INTO clientes (rfc, razon_social, regimen_fiscal_receptor, uso_cfdi, 
		                email, telefono, calle, numero_exterior, numero_interior, 
		                colonia, codigo_postal, localidad, municipio, estado, pais, estatus) 
		        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
		$params = [$rfc, $razon_social, $regimen_fiscal_receptor, $uso_cfdi, 
		           $email, $telefono, $calle, $numero_exterior, $numero_interior, 
		           $colonia, $codigo_postal, $localidad, $municipio, $estado, 
		           $pais, $estatus];
	}

	$success = $oDb->bind_params($sql, $params, true);
	$oDb->Close();

	return $success ? [ 'result' => true ] : [ 'result' => false, 'message' => 'Error al guardar el cliente' ];
}

function DeleteCliente() {
	$id = filter_post( 'id' );
	$oDb = create_conex();

	$count = $oDb->query("SELECT COUNT(*) as total FROM facturas WHERE cliente_id = ? AND estado = 'timbrada'");
	$oDb->bind_params("SELECT COUNT(*) as total FROM facturas WHERE cliente_id = ? AND estado = 'timbrada'", [$id]);
	$c = $oDb->getrow();
	if ( $c && $c['total'] > 0 ) {
		$oDb->Close();
		return [ 'result' => false, 'message' => 'No se puede eliminar: el cliente tiene facturas timbradas' ];
	}

	$sql = "DELETE FROM clientes WHERE id = ?";
	$success = $oDb->bind_params($sql, [$id], true);
	$oDb->Close();
	return $success ? [ 'result' => true ] : [ 'result' => false, 'message' => 'Error al eliminar' ];
}
?>