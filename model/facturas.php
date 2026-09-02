<?php
include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( false );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_root() . 'config_db.php' );
include ( Constants::getpath_root() . 'helpers.php' );
include ( Constants::getpath_root() . 'libs/cfdi/CfdiBuilder.php' );

$cAction = filter_post( 'action' );

switch( $cAction ) {	
	case 'getfacturas':     $result = GetFacturas();     break;
	case 'getfactura':      $result = GetFactura();      break;
	case 'nueva':           $result = NuevaFactura();    break;
	case 'saveconcepto':    $result = SaveConcepto();    break;
	case 'deleteconcepto':  $result = DeleteConcepto();  break;
	case 'emitir':          $result = EmitirFactura();   break;
	case 'cancelar':        $result = CancelarFactura(); break;
	default:                $result = [ 'result' => false, 'message' => 'Acción no válida' ];
}

die( json_encode( $result ) );

function GetFacturas() {
	$oDb = create_conex();
	$sql = "SELECT f.*, c.rfc as rfc_cliente, c.razon_social 
	        FROM facturas f 
	        JOIN clientes c ON f.cliente_id = c.id 
	        ORDER BY f.fecha DESC";
	$oDb->query($sql);
	$rows = [];
	while ( $row = $oDb->getrow() ) {
		$rows[] = $row;
	}
	$oDb->Close();
	return [ 'result' => true, 'data' => $rows ];
}

function GetFactura() {
	$id = filter_post( 'id' );
	$oDb = create_conex();

	$sql = "SELECT f.*, c.rfc as rfc_cliente, c.razon_social, c.regimen_fiscal_receptor,
	                c.uso_cfdi, c.email as email_cliente,
	                c.calle as cli_calle, c.numero_exterior as cli_num_ext, c.numero_interior as cli_num_int,
	                c.colonia as cli_colonia, c.codigo_postal as cli_cp, c.municipio as cli_municipio,
	                c.estado as cli_estado, c.pais as cli_pais
	        FROM facturas f 
	        JOIN clientes c ON f.cliente_id = c.id 
	        WHERE f.id = ? LIMIT 1";
	$oDb->bind_params($sql, [$id]);
	$factura = $oDb->getrow();

	if ( !$factura ) {
		$oDb->Close();
		return [ 'result' => false, 'message' => 'Factura no encontrada' ];
	}

	$sql2 = "SELECT * FROM factura_conceptos WHERE factura_id = ? ORDER BY id";
	$oDb->bind_params($sql2, [$id]);
	$conceptos = [];
	while ( $row = $oDb->getrow() ) {
		$conceptos[] = $row;
	}
	$oDb->Close();

	$factura['conceptos'] = $conceptos;
	return [ 'result' => true, 'data' => $factura ];
}

function NuevaFactura() {
	$cliente_id    = filter_post( 'cliente_id' );
	$serie         = filter_post( 'serie' );
	$forma_pago    = filter_post( 'forma_pago' );
	$metodo_pago   = filter_post( 'metodo_pago' );

	if ( empty($cliente_id) || empty($serie) || empty($forma_pago) || empty($metodo_pago) ) {
		return [ 'result' => false, 'message' => 'Todos los campos son obligatorios' ];
	}

	$folio = getSiguienteFolio( $serie );
	if ( $folio === false ) {
		return [ 'result' => false, 'message' => 'Serie no encontrada o inactiva' ];
	}

	$oDb = create_conex();
	$sql = "INSERT INTO facturas (serie, folio, cliente_id, forma_pago, metodo_pago) 
	        VALUES (?,?,?,?,?)";
	$success = $oDb->bind_params($sql, [$serie, $folio, $cliente_id, $forma_pago, $metodo_pago], true);
	$factura_id = $oDb->getConnection()->insert_id;
	$oDb->Close();

	if ( $success ) {
		return [ 'result' => true, 'factura_id' => $factura_id, 'folio' => $folio ];
	}
	return [ 'result' => false, 'message' => 'Error al crear la factura' ];
}

function SaveConcepto() {
	$factura_id     = filter_post( 'factura_id' );
	$clave_prod_serv = filter_post( 'clave_prod_serv' );
	$clave_unidad   = filter_post( 'clave_unidad' );
	$descripcion    = trim(filter_post( 'descripcion' ));
	$cantidad       = filter_post( 'cantidad' );
	$valor_unitario = filter_post( 'valor_unitario' );
	$descuento      = filter_post( 'descuento' );

	if ( $factura_id == '' || $factura_id == null ) {
		return [ 'result' => false, 'message' => 'No se ha creado la factura. Primero seleccione un cliente y cree la factura.' ];
	}
	if ( $descripcion == '' ) {
		return [ 'result' => false, 'message' => 'La descripción es obligatoria' ];
	}
	if ( $cantidad == '' || $cantidad == null ) {
		return [ 'result' => false, 'message' => 'La cantidad es obligatoria' ];
	}
	if ( $valor_unitario == '' || $valor_unitario == null ) {
		return [ 'result' => false, 'message' => 'El valor unitario es obligatorio' ];
	}

	$cantidad       = (float)$cantidad;
	$valor_unitario = (float)$valor_unitario;
	$descuento      = (float)($descuento ?: 0);
	$importe        = $cantidad * $valor_unitario;
	$base_iva       = $importe - $descuento;
	$tasa_iva       = 0.1600;
	$importe_iva    = round($base_iva * $tasa_iva, 2);

	$oDb = create_conex();

	$oDb->bind_params("UPDATE facturas SET estado = 'borrador' WHERE id = ? AND estado != 'borrador'", [$factura_id], true);

	$sql = "INSERT INTO factura_conceptos (factura_id, clave_prod_serv, clave_unidad, descripcion, 
	                cantidad, valor_unitario, importe, descuento, base_iva, tasa_iva, importe_iva) 
	        VALUES (?,?,?,?,?,?,?,?,?,?,?)";
	$success = $oDb->bind_params($sql, [
		$factura_id, $clave_prod_serv, $clave_unidad, $descripcion,
		$cantidad, $valor_unitario, $importe, $descuento,
		$base_iva, $tasa_iva, $importe_iva
	], true);

	if ( $success ) {
		actualizarTotales( $oDb, $factura_id );
		$oDb->Close();
		return [ 'result' => true ];
	}
	$oDb->Close();
	return [ 'result' => false, 'message' => 'Error al guardar el concepto' ];
}

function DeleteConcepto() {
	$concepto_id  = filter_post( 'concepto_id' );
	$factura_id   = filter_post( 'factura_id' );

	$oDb = create_conex();
	$sql = "DELETE FROM factura_conceptos WHERE id = ? AND factura_id = ?";
	$success = $oDb->bind_params($sql, [$concepto_id, $factura_id], true);

	if ( $success ) {
		actualizarTotales( $oDb, $factura_id );
		$oDb->Close();
		return [ 'result' => true ];
	}
	$oDb->Close();
	return [ 'result' => false, 'message' => 'Error al eliminar el concepto' ];
}

function actualizarTotales( $oDb, $factura_id ) {
	$sql = "SELECT SUM(importe) as subtotal, SUM(descuento) as descuento, SUM(importe_iva) as total_iva 
	        FROM factura_conceptos WHERE factura_id = ?";
	$oDb->bind_params($sql, [$factura_id]);
	$totales = $oDb->getrow();

	$subtotal  = $totales['subtotal'] ?? 0;
	$descuento = $totales['descuento'] ?? 0;
	$total_iva = $totales['total_iva'] ?? 0;
	$total     = $subtotal - $descuento + $total_iva;

	$oDb->update('facturas', [
		'subtotal'  => $subtotal,
		'descuento' => $descuento,
		'total_iva' => $total_iva,
		'total'     => $total
	], 'id', $factura_id);
}

function EmitirFactura() {
	$factura_id = filter_post( 'factura_id' );

	$oDb = create_conex();
	$sql = "SELECT f.*, c.rfc as rfc_cliente, c.razon_social as nombre_cliente, 
	                c.regimen_fiscal_receptor, c.uso_cfdi,
	                c.calle as cli_calle, c.numero_exterior as cli_num_ext, c.numero_interior as cli_num_int,
	                c.colonia as cli_colonia, c.codigo_postal as cli_cp, c.municipio as cli_municipio,
	                c.estado as cli_estado, c.pais as cli_pais
	        FROM facturas f 
	        JOIN clientes c ON f.cliente_id = c.id 
	        WHERE f.id = ? AND f.estado = 'borrador' LIMIT 1";
	$oDb->bind_params($sql, [$factura_id]);
	$factura = $oDb->getrow();

	if ( !$factura ) {
		$oDb->Close();
		return [ 'result' => false, 'message' => 'Factura no encontrada o no está en estado borrador' ];
	}

	$sql2 = "SELECT * FROM factura_conceptos WHERE factura_id = ?";
	$oDb->bind_params($sql2, [$factura_id]);
	$conceptos = [];
	while ( $row = $oDb->getrow() ) {
		$conceptos[] = $row;
	}

	$emisor = getEmisorConfig();
	if ( !$emisor ) {
		$oDb->Close();
		return [ 'result' => false, 'message' => 'No hay configuración del emisor' ];
	}

	$oDb->Close();

	$builder = new CfdiBuilder();
	$xml = $builder->generar( $factura, $conceptos, $emisor );

	$sello = $builder->firmarCsd( $xml, $emisor );

	$oDb = create_conex();
	$oDb->update('facturas', [
		'no_certificado' => $emisor['no_certificado'],
		'sello_digital'  => $sello
	], 'id', $factura_id);
	$oDb->Close();

	return [ 
		'result'  => true, 
		'message' => 'CFDI generado correctamente. Pendiente de timbrado con PAC.',
		'xml'     => $xml,
		'uuid'    => 'TIMBRAR_CON_PAC'
	];
}

function CancelarFactura() {
	$factura_id = filter_post( 'factura_id' );
	$oDb = create_conex();

	$sql = "SELECT estado, uuid FROM facturas WHERE id = ? LIMIT 1";
	$oDb->bind_params($sql, [$factura_id]);
	$factura = $oDb->getrow();

	if ( !$factura ) {
		$oDb->Close();
		return [ 'result' => false, 'message' => 'Factura no encontrada' ];
	}

	if ( $factura['estado'] === 'timbrada' ) {
		$oDb->update('facturas', ['estado' => 'cancelada'], 'id', $factura_id);
		$oDb->Close();
		return [ 'result' => true, 'message' => 'Factura cancelada. Pendiente cancelación en PAC si tiene UUID.' ];
	}

	if ( $factura['estado'] === 'borrador' ) {
		$oDb->update('facturas', ['estado' => 'cancelada'], 'id', $factura_id);
		$oDb->Close();
		return [ 'result' => true, 'message' => 'Factura borrador cancelada.' ];
	}

	$oDb->Close();
	return [ 'result' => false, 'message' => 'No se puede cancelar la factura en su estado actual' ];
}
?>