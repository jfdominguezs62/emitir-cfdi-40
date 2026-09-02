<?php
include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( false );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_root() . 'config_db.php' );

$cAction = filter_post( 'action' );

switch( $cAction ) {
	case 'reporte_periodo':    $result = ReportePeriodo();    break;
	case 'reporte_detalle':    $result = ReporteDetalle();    break;
	default: $result = [ 'result' => false, 'message' => 'Acción no válida' ];
}

ob_clean();
header('Content-Type: application/json; charset=utf-8');
echo json_encode( $result );
exit;

function ReportePeriodo() {
	$oMysql = create_conex();
	$tipo    = filter_post('tipo');      // dia, mes, anio
	$desde   = filter_post('desde');
	$hasta   = filter_post('hasta');
	$estado  = filter_post('estado');    // '', 'VIGENTE', 'CANCELADO'
	$usarCierre = (int)filter_post('usar_cierre');

	$campoFecha = 'x.fecha';
	$join = '';
	if ( $usarCierre ) {
		$campoFecha = 'c.fecharealcierre';
		$join = 'LEFT JOIN cfdi_cierres c ON c.uuid = x.uuid COLLATE utf8mb4_unicode_ci';
	}

	if ( $tipo === 'dia' ) {
		$agrupar = "DATE({$campoFecha})";
		$ordenar = "DATE({$campoFecha}) ASC";
	} elseif ( $tipo === 'mes' ) {
		$agrupar = "DATE({$campoFecha})";
		$ordenar = "DATE({$campoFecha}) ASC";
	} else {
		$agrupar = "DATE_FORMAT({$campoFecha}, '%Y-%m')";
		$ordenar = "DATE_FORMAT({$campoFecha}, '%Y-%m') ASC";
	}

	$where = "WHERE 1=1";
	if ( $usarCierre ) {
		if ( !empty($desde) ) $where .= " AND {$campoFecha} >= '{$desde}'";
		if ( !empty($hasta) ) $where .= " AND {$campoFecha} <= '{$hasta}'";
	} else {
		if ( !empty($desde) ) $where .= " AND {$campoFecha} >= '{$desde} 00:00:00'";
		if ( !empty($hasta) ) $where .= " AND {$campoFecha} <= '{$hasta} 23:59:59'";
	}
	if ( !empty($estado) ) $where .= " AND x.estado = '" . $oMysql->escape($estado) . "'";

	$sql = "SELECT 
		{$agrupar} as periodo,
		COUNT(*) as total_cfdi,
		SUM(CASE WHEN x.estado = 'VIGENTE' THEN 1 ELSE 0 END) as vigentes,
		SUM(CASE WHEN x.estado = 'CANCELADO' THEN 1 ELSE 0 END) as cancelados,
		SUM(CASE WHEN x.estado IS NULL OR x.estado = '' OR x.estado = 'No Encontrado' THEN 1 ELSE 0 END) as sin_verificar,
		SUM(CASE WHEN x.estado != 'CANCELADO' THEN x.subtotal ELSE 0 END) as total_subtotal,
		SUM(CASE WHEN x.estado != 'CANCELADO' THEN x.descuento ELSE 0 END) as total_descuento,
		SUM(CASE WHEN x.estado != 'CANCELADO' THEN x.impuestos_traslados ELSE 0 END) as total_iva,
		SUM(CASE WHEN x.estado != 'CANCELADO' THEN x.total ELSE 0 END) as total_total,
		SUM(CASE WHEN x.es_global = 1 THEN 1 ELSE 0 END) as globales
		FROM xml_importados x
		{$join}
		{$where}
		GROUP BY periodo 
		ORDER BY {$ordenar}";

	$oMysql->query($sql);
	$registros = [];
	while ( $f = $oMysql->getrow() ) {
		$registros[] = [
			'periodo'        => $f['periodo'],
			'total_cfdi'     => (int)$f['total_cfdi'],
			'vigentes'       => (int)$f['vigentes'],
			'cancelados'     => (int)$f['cancelados'],
			'sin_verificar'  => (int)$f['sin_verificar'],
			'total_subtotal' => round((float)$f['total_subtotal'], 2),
			'total_descuento'=> round((float)$f['total_descuento'], 2),
			'total_iva'      => round((float)$f['total_iva'], 2),
			'total_total'    => round((float)$f['total_total'], 2),
			'globales'       => (int)$f['globales'],
		];
	}

	$totalCfdi = 0;
	$totalVigentes = 0;
	$totalCancelados = 0;
	$totalSinVerificar = 0;
	$totalImporte = 0;
	$totalIva = 0;
	foreach ( $registros as $r ) {
		$totalCfdi += $r['total_cfdi'];
		$totalVigentes += $r['vigentes'];
		$totalCancelados += $r['cancelados'];
		$totalSinVerificar += $r['sin_verificar'];
		$totalImporte += $r['total_total'];
		$totalIva += $r['total_iva'];
	}

	return [
		'result' => true,
		'data' => $registros,
		'totales' => [
			'cfdi'          => $totalCfdi,
			'vigentes'      => $totalVigentes,
			'cancelados'    => $totalCancelados,
			'sin_verificar' => $totalSinVerificar,
			'importe'       => round($totalImporte, 2),
			'iva'           => round($totalIva, 2),
		]
	];
}

function ReporteDetalle() {
	$oMysql = create_conex();
	$periodo = filter_post('periodo');
	$tipo    = filter_post('tipo');
	$estado  = filter_post('estado');
	$usarCierre = (int)filter_post('usar_cierre');

	$campoFecha = 'x.fecha';
	$join = '';
	if ( $usarCierre ) {
		$campoFecha = 'c.fecharealcierre';
		$join = 'LEFT JOIN cfdi_cierres c ON c.uuid = x.uuid COLLATE utf8mb4_unicode_ci';
	}

	if ( strlen($periodo) > 7 ) {
		$wherePeriodo = "AND DATE({$campoFecha}) = '{$periodo}'";
	} else {
		$wherePeriodo = "AND DATE_FORMAT({$campoFecha}, '%Y-%m') = '{$periodo}'";
	}

	$whereEstado = '';
	if ( !empty($estado) ) $whereEstado = "AND x.estado = '" . $oMysql->escape($estado) . "'";

	$sql = "SELECT x.id, x.uuid, x.serie, x.folio, x.fecha, x.emisor_rfc, x.emisor_nombre, x.receptor_rfc, x.receptor_nombre,
		x.subtotal, x.descuento, x.impuestos_traslados, x.impuestos_retenidos, x.iva_neto, x.total,
		x.forma_pago, x.metodo_pago, x.tipo_comprobante, x.es_global, x.estado
		FROM xml_importados x
		{$join}
		WHERE 1=1 {$wherePeriodo} {$whereEstado}
		ORDER BY x.serie ASC, CAST(x.folio AS UNSIGNED) ASC, x.id ASC";

	$oMysql->query($sql);
	$registros = [];
	while ( $f = $oMysql->getrow() ) {
		$registros[] = [
			'id'                  => $f['id'],
			'uuid'                => $f['uuid'],
			'serie'               => $f['serie'],
			'folio'               => $f['folio'],
			'fecha'               => $f['fecha'],
			'emisor_rfc'          => $f['emisor_rfc'],
			'emisor_nombre'       => $f['emisor_nombre'],
			'receptor_rfc'        => $f['receptor_rfc'],
			'receptor_nombre'     => $f['receptor_nombre'],
			'subtotal'            => round((float)$f['subtotal'], 2),
			'descuento'           => round((float)$f['descuento'], 2),
			'impuestos_traslados' => round((float)$f['impuestos_traslados'], 2),
			'impuestos_retenidos' => round((float)$f['impuestos_retenidos'], 2),
			'iva_neto'            => round((float)$f['iva_neto'], 2),
			'total'               => round((float)$f['total'], 2),
			'forma_pago'          => $f['forma_pago'],
			'metodo_pago'         => $f['metodo_pago'],
			'tipo_comprobante'    => $f['tipo_comprobante'],
			'es_global'           => (int)$f['es_global'],
			'estado'              => $f['estado'],
		];
	}

	return [ 'result' => true, 'data' => $registros, 'total' => count($registros) ];
}
?>
