<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( false );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_root() . 'config_db.php' );

$cAction = filter_post( 'action' );

switch( $cAction ) {	
	case 'leer_xml':       $result = LeerXml();       break;
	case 'leer_xml_files':  $result = LeerXmlFiles();  break;
	case 'listar_carpetas': $result = ListarCarpetas(); break;
	case 'guardar_db':     $result = GuardarDb();      break;
	case 'guardar_db_files': $result = GuardarDbFiles(); break;
	case 'cargar_db':      $result = CargarDb();       break;
	case 'eliminar_db':    $result = EliminarDb();     break;
	case 'eliminar_todos_db': $result = EliminarTodosDb(); break;
	case 'verificar_sat':     $result = VerificarSat();     break;
	case 'verificar_sat_one': $result = VerificarSatOne();  break;
	case 'limpiar_estado':    $result = LimpiarEstado();    break;
	case 'get_xml':           $result = GetXml();           break;
	default:               $result = [ 'result' => false, 'message' => 'Acción no válida' ];
}

ob_clean();
header('Content-Type: application/json; charset=utf-8');
die( json_encode( $result ) );

function ListarCarpetas() {
	$ruta = trim(filter_post( 'ruta' ));

	if ( empty($ruta) ) {
		$ruta = 'C:\\';
	}

	$ruta = str_replace('/', '\\', $ruta);
	$ruta = rtrim($ruta, '\\');

	if ( preg_match('/^[A-Za-z]:$/', $ruta) ) {
		$ruta .= '\\';
	}

	if ( !is_dir($ruta) ) {
		return [ 'result' => false, 'message' => 'Ruta no válida: ' . $ruta ];
	}

	$carpetas = [];
	$items = @scandir($ruta);

	if ( $items === false ) {
		return [ 'result' => false, 'message' => 'No se pudo acceder a: ' . $ruta ];
	}

	$xmlCount = 0;

	foreach ( $items as $item ) {
		if ( $item === '.' || $item === '..' ) continue;
		$rutaCompleta = $ruta . '\\' . $item;
		if ( is_dir($rutaCompleta) ) {
			$xmlHijos = count(glob($rutaCompleta . '\\*.xml'));
			$fechaMod = date('d/m/Y H:i', @filemtime($rutaCompleta));
			$carpetas[] = [
				'nombre'     => $item,
				'ruta'       => $rutaCompleta,
				'xml_count'  => $xmlHijos,
				'fecha_mod'  => $fechaMod,
			];
		} else if ( strtolower(substr($item, -4)) === '.xml' ) {
			$xmlCount++;
		}
	}

	usort($carpetas, function($a, $b) {
		return strcasecmp($a['nombre'], $b['nombre']);
	});

	$padre = dirname($ruta);
	if ( $padre === $ruta || $padre === '.' ) $padre = '';

	$segmentos = explode('\\', $ruta);
	$bread = [];
	$rutaAcum = '';
	foreach ( $segmentos as $s ) {
		if ( $s === '' ) continue;
		if ( preg_match('/^[A-Za-z]:$/', $s) ) {
			$rutaAcum = $s . '\\';
			$bread[] = ['nombre' => $s . '\\', 'ruta' => $rutaAcum];
			continue;
		}
		$rutaAcum .= ($rutaAcum && substr($rutaAcum, -1) !== '\\' ? '\\' : '') . $s;
		$bread[] = ['nombre' => $s, 'ruta' => $rutaAcum];
	}

	return [
		'result'    => true,
		'actual'    => $ruta,
		'padre'     => $padre,
		'carpetas'  => $carpetas,
		'xml_count' => $xmlCount,
		'bread'     => $bread,
	];
}

function LeerXml() {
	$carpeta = trim(filter_post( 'carpeta' ));

	if ( empty($carpeta) ) {
		return [ 'result' => false, 'message' => 'Debe especificar la ruta de la carpeta' ];
	}

	$carpeta = rtrim($carpeta, '/\\');

	if ( !is_dir($carpeta) ) {
		return [ 'result' => false, 'message' => 'La carpeta no existe: ' . $carpeta ];
	}

	$archivos = glob($carpeta . '/*.xml');

	if ( empty($archivos) ) {
		return [ 'result' => false, 'message' => 'No se encontraron archivos XML en la carpeta' ];
	}

	$registros = [];

	foreach ( $archivos as $archivo ) {
		$contenido = file_get_contents($archivo);
		if ( $contenido === false ) continue;

		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = false;

		if ( @$dom->loadXML($contenido) === false ) continue;

		$registro = parsearCfdi($dom, basename($archivo));
		if ( $registro ) {
			$registros[] = $registro;
		}
	}

	usort($registros, function($a, $b) {
		return strcmp($b['fecha'], $a['fecha']);
	});

	return [ 'result' => true, 'data' => $registros, 'total' => count($registros) ];
}

function LeerXmlFiles() {
	if ( empty($_FILES['xml_files']) ) {
		return [ 'result' => false, 'message' => 'No se recibieron archivos' ];
	}

	$registros = [];
	$archivos = $_FILES['xml_files'];

	for ( $i = 0; $i < count($archivos['name']); $i++ ) {
		$nombre = $archivos['name'][$i];
		$tmp = $archivos['tmp_name'][$i];
		$error = $archivos['error'][$i];

		if ( $error !== UPLOAD_ERR_OK ) continue;
		$contenido = @file_get_contents($tmp);
		if ( $contenido === false ) continue;

		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = false;
		if ( @$dom->loadXML($contenido) === false ) continue;

		$registro = parsearCfdi($dom, $nombre);
		if ( $registro ) {
			$registros[] = $registro;
		}
	}

	usort($registros, function($a, $b) {
		return strcmp($b['fecha'], $a['fecha']);
	});

	return [ 'result' => true, 'data' => $registros, 'total' => count($registros) ];
}

function GuardarDbFiles() {
	if ( empty($_FILES['xml_files']) ) {
		return [ 'result' => false, 'message' => 'No se recibieron archivos' ];
	}

	$oMysql = create_conex();

	$archivos = $_FILES['xml_files'];
	$guardados = 0;
	$omitidos = 0;
	$errores = 0;

	$totalFiles = count($archivos['name']);

	if ( !is_array($archivos['name']) ) {
		return [ 'result' => false, 'message' => 'Estructura de archivos inesperada' ];
	}

	$uuidsExistentes = [];
	$oMysql->query("SELECT uuid FROM xml_importados WHERE uuid != ''");
	while ( $row = $oMysql->getrow() ) {
		$uuidsExistentes[$row['uuid']] = true;
	}

	for ( $i = 0; $i < $totalFiles; $i++ ) {
		$nombre = $archivos['name'][$i];
		$tmp = $archivos['tmp_name'][$i];
		$error = $archivos['error'][$i];

		if ( $error !== UPLOAD_ERR_OK ) { $errores++; continue; }
		$contenido = @file_get_contents($tmp);
		if ( $contenido === false ) { $errores++; continue; }

		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = false;
		if ( @$dom->loadXML($contenido) === false ) { $errores++; continue; }

		$registro = parsearCfdi($dom, $nombre);
		if ( !$registro ) { $errores++; continue; }

		$uuid = $registro['uuid'];
		if ( !empty($uuid) && isset($uuidsExistentes[$uuid]) ) { $omitidos++; continue; }

		$fecha = !empty($registro['fecha']) ? date('Y-m-d H:i:s', strtotime($registro['fecha'])) : null;
		$timbreFecha = !empty($registro['timbre_fecha']) ? date('Y-m-d H:i:s', strtotime($registro['timbre_fecha'])) : null;

		$sql = "INSERT INTO xml_importados SET
			archivo           = '" . $oMysql->escape($registro['archivo']) . "',
			xml_content       = '" . $oMysql->escape($contenido) . "',
			uuid              = '" . $oMysql->escape($registro['uuid']) . "',
			version           = '" . $oMysql->escape($registro['version']) . "',
			serie             = '" . $oMysql->escape($registro['serie']) . "',
			folio             = '" . $oMysql->escape($registro['folio']) . "',
			fecha             = " . ($fecha ? "'" . $oMysql->escape($fecha) . "'" : "NULL") . ",
			forma_pago        = '" . $oMysql->escape($registro['forma_pago']) . "',
			metodo_pago       = '" . $oMysql->escape($registro['metodo_pago']) . "',
			lugar_expedicion  = '" . $oMysql->escape($registro['lugar_expedicion']) . "',
			no_certificado    = '" . $oMysql->escape($registro['no_certificado']) . "',
			subtotal          = " . $registro['subtotal'] . ",
			descuento         = " . $registro['descuento'] . ",
			total             = " . $registro['total'] . ",
			moneda            = '" . $oMysql->escape($registro['moneda']) . "',
			tipo_comprobante  = '" . $oMysql->escape($registro['tipo_comprobante']) . "',
			es_global         = " . $registro['es_global'] . ",
			exportacion       = '" . $oMysql->escape($registro['exportacion']) . "',
			periodicidad      = '" . $oMysql->escape($registro['periodicidad']) . "',
			meses             = '" . $oMysql->escape($registro['meses']) . "',
			anio              = '" . $oMysql->escape($registro['anio']) . "',
			emisor_rfc        = '" . $oMysql->escape($registro['emisor_rfc']) . "',
			emisor_nombre     = '" . $oMysql->escape($registro['emisor_nombre']) . "',
			emisor_regimen    = '" . $oMysql->escape($registro['emisor_regimen']) . "',
			receptor_rfc      = '" . $oMysql->escape($registro['receptor_rfc']) . "',
			receptor_nombre   = '" . $oMysql->escape($registro['receptor_nombre']) . "',
			receptor_regimen  = '" . $oMysql->escape($registro['receptor_regimen']) . "',
			receptor_uso_cfdi = '" . $oMysql->escape($registro['receptor_uso_cfdi']) . "',
			receptor_cp       = '" . $oMysql->escape($registro['receptor_cp']) . "',
			impuestos_traslados = " . $registro['impuestos_traslados'] . ",
			impuestos_retenidos = " . $registro['impuestos_retenidos'] . ",
			iva_neto            = " . $registro['iva_neto'] . ",
			timbre_uuid       = '" . $oMysql->escape($registro['timbre_uuid']) . "',
			timbre_fecha      = " . ($timbreFecha ? "'" . $oMysql->escape($timbreFecha) . "'" : "NULL") . ",
			carpeta_origen    = '" . $oMysql->escape($nombre) . "'";

		$oMysql->query($sql);
		$nuevoId = $oMysql->lastInsertID();

		if ( $nuevoId > 0 ) {
			$guardados++;
			if ( !empty($uuid) ) $uuidsExistentes[$uuid] = true;

			$fechaTimbrado = !empty($registro['timbre_fecha']) ? $registro['timbre_fecha'] : null;
			if ( !empty($uuid) ) {
				$check = $oMysql->getConnection()->query("SELECT id FROM cfdi_cierres WHERE uuid = '" . $oMysql->getConnection()->real_escape_string($uuid) . "' LIMIT 1");
				$row = $check ? $check->fetch_assoc() : null;
				if ( $check ) $check->free();
				if ( !$row ) {
					$oMysql->query("INSERT INTO cfdi_cierres (uuid, fecha_timbrado, fecharealcierre) 
						VALUES ('" . $oMysql->escape($uuid) . "', " . ($fechaTimbrado ? "'" . $oMysql->escape($fechaTimbrado) . "'" : "NULL") . ", " . ($fechaTimbrado ? "'" . $oMysql->escape(substr($fechaTimbrado, 0, 10)) . "'" : "NULL") . ")");
				}
			}

			if ( !empty($registro['conceptos']) ) {
				foreach ( $registro['conceptos'] as $c ) {
					$sqlCon = "INSERT INTO xml_importados_conceptos SET
						xml_importado_id = " . $nuevoId . ",
						clave_prod_serv  = '" . $oMysql->escape($c['clave_prod_serv']) . "',
						cantidad         = " . (float)$c['cantidad'] . ",
						clave_unidad     = '" . $oMysql->escape($c['clave_unidad']) . "',
						descripcion      = '" . $oMysql->escape($c['descripcion']) . "',
						valor_unitario   = " . (float)$c['valor_unitario'] . ",
						importe          = " . (float)$c['importe'] . ",
						descuento        = " . (float)$c['descuento'] . ",
						objeto_imp       = '" . $oMysql->escape($c['objeto_imp']) . "'";
					$oMysql->query($sqlCon);
				}
			}
		} else {
			$errores++;
		}
	}

	$oMysql->getConnection()->commit();

	return [
		'result'    => true,
		'message'   => "Guardados: {$guardados}, Omitidos: {$omitidos}, Errores: {$errores}",
		'guardados' => $guardados,
		'omitidos'  => $omitidos,
		'errores'   => $errores,
	];
}

function parsearCfdi($dom, $nombreArchivo) {
	$xpath = new DOMXPath($dom);

	$registro = [
		'archivo'           => $nombreArchivo,
		'uuid'              => '',
		'version'           => '',
		'serie'             => '',
		'folio'             => '',
		'fecha'             => '',
		'forma_pago'        => '',
		'metodo_pago'       => '',
		'lugar_expedicion'  => '',
		'no_certificado'    => '',
		'subtotal'          => 0,
		'descuento'         => 0,
		'total'             => 0,
		'moneda'            => '',
		'tipo_comprobante'  => '',
		'exportacion'       => '',
		'periodicidad'      => '',
		'meses'             => '',
		'anio'              => '',
		'emisor_rfc'        => '',
		'emisor_nombre'     => '',
		'emisor_regimen'    => '',
		'receptor_rfc'      => '',
		'receptor_nombre'   => '',
		'receptor_regimen'  => '',
		'receptor_uso_cfdi' => '',
		'receptor_cp'       => '',
		'impuestos_traslados' => 0,
		'impuestos_retenidos' => 0,
		'iva_neto'            => 0,
		'timbre_uuid'       => '',
		'timbre_fecha'      => '',
		'timbre_sello_cfd'  => '',
		'timbre_no_certificado' => '',
		'conceptos'         => [],
	];

	$nodoComprobante = $xpath->query('//*[local-name()="Comprobante"]')->item(0);
	if ( !$nodoComprobante ) return null;

	$registro['version']           = $nodoComprobante->getAttribute('Version') ?: $nodoComprobante->getAttribute('version');
	$registro['serie']             = $nodoComprobante->getAttribute('Serie');
	$registro['folio']             = $nodoComprobante->getAttribute('Folio');
	$registro['fecha']             = $nodoComprobante->getAttribute('Fecha');
	$registro['forma_pago']        = $nodoComprobante->getAttribute('FormaPago');
	$registro['metodo_pago']       = $nodoComprobante->getAttribute('MetodoPago');
	$registro['lugar_expedicion']  = $nodoComprobante->getAttribute('LugarExpedicion');
	$registro['no_certificado']    = $nodoComprobante->getAttribute('NoCertificado');
	$registro['subtotal']          = (float)($nodoComprobante->getAttribute('SubTotal') ?: 0);
	$registro['descuento']         = (float)($nodoComprobante->getAttribute('Descuento') ?: 0);
	$registro['total']             = (float)($nodoComprobante->getAttribute('Total') ?: 0);
	$registro['moneda']            = $nodoComprobante->getAttribute('Moneda');
	$registro['tipo_comprobante']  = $nodoComprobante->getAttribute('TipoDeComprobante');
	$registro['exportacion']       = $nodoComprobante->getAttribute('Exportacion');

	$registro['es_global'] = 0;
	$registro['periodicidad'] = '';
	$registro['meses'] = '';
	$registro['anio'] = '';
	$nodoInfoGlobal = $xpath->query('//*[local-name()="InformacionGlobal"]')->item(0);
	if ( $nodoInfoGlobal ) {
		$registro['es_global'] = 1;
		$registro['periodicidad'] = $nodoInfoGlobal->getAttribute('Periodicidad');
		$registro['meses'] = $nodoInfoGlobal->getAttribute('Meses');
		$registro['anio'] = $nodoInfoGlobal->getAttribute('Año');
	}

	$nodoEmisor = $xpath->query('//*[local-name()="Emisor"]')->item(0);
	if ( $nodoEmisor ) {
		$registro['emisor_rfc']     = $nodoEmisor->getAttribute('Rfc');
		$registro['emisor_nombre']  = $nodoEmisor->getAttribute('Nombre');
		$registro['emisor_regimen'] = $nodoEmisor->getAttribute('RegimenFiscal');
	}

	$nodoReceptor = $xpath->query('//*[local-name()="Receptor"]')->item(0);
	if ( $nodoReceptor ) {
		$registro['receptor_rfc']      = $nodoReceptor->getAttribute('Rfc');
		$registro['receptor_nombre']   = $nodoReceptor->getAttribute('Nombre');
		$registro['receptor_regimen']  = $nodoReceptor->getAttribute('RegimenFiscalReceptor');
		$registro['receptor_uso_cfdi'] = $nodoReceptor->getAttribute('UsoCFDI');
		$registro['receptor_cp']       = $nodoReceptor->getAttribute('DomicilioFiscalReceptor');
	}

	$nodosConceptos = $xpath->query('//*[local-name()="Conceptos"]/*[local-name()="Concepto"]');
	foreach ( $nodosConceptos as $nc ) {
		$registro['conceptos'][] = [
			'clave_prod_serv' => $nc->getAttribute('ClaveProdServ'),
			'cantidad'        => $nc->getAttribute('Cantidad'),
			'clave_unidad'    => $nc->getAttribute('ClaveUnidad'),
			'descripcion'     => $nc->getAttribute('Descripcion'),
			'valor_unitario'  => $nc->getAttribute('ValorUnitario'),
			'importe'         => $nc->getAttribute('Importe'),
			'descuento'       => $nc->getAttribute('Descuento'),
			'objeto_imp'      => $nc->getAttribute('ObjetoImp'),
		];
	}

	$nodoImpuestos = $xpath->query('//*[local-name()="Comprobante"]/*[local-name()="Impuestos"]')->item(0);
	if ( $nodoImpuestos ) {
		$registro['impuestos_traslados'] = (float)($nodoImpuestos->getAttribute('TotalImpuestosTrasladados') ?: 0);
		$registro['impuestos_retenidos'] = (float)($nodoImpuestos->getAttribute('TotalImpuestosRetenidos') ?: 0);

		if ( $registro['impuestos_traslados'] == 0 ) {
			$subXpath = new DOMXPath($nodoImpuestos->ownerDocument);
			$nodosTraslados = $subXpath->query('./*[local-name()="Traslados"]/*[local-name()="Traslado"]', $nodoImpuestos);
			foreach ( $nodosTraslados as $t ) {
				$registro['impuestos_traslados'] += (float)($t->getAttribute('Importe') ?: 0);
			}
		}

		if ( $registro['impuestos_retenidos'] == 0 ) {
			$subXpath = new DOMXPath($nodoImpuestos->ownerDocument);
			$nodosRetenciones = $subXpath->query('./*[local-name()="Retenciones"]/*[local-name()="Retencion"]', $nodoImpuestos);
			foreach ( $nodosRetenciones as $r ) {
				$registro['impuestos_retenidos'] += (float)($r->getAttribute('Importe') ?: 0);
			}
		}
	}

	$registro['iva_neto'] = $registro['impuestos_traslados'];

	$nodoTimbre = $xpath->query('//*[local-name()="TimbreFiscalDigital"]')->item(0);
	if ( $nodoTimbre ) {
		$registro['timbre_uuid']             = $nodoTimbre->getAttribute('UUID');
		$registro['timbre_fecha']            = $nodoTimbre->getAttribute('FechaTimbrado');
		$registro['timbre_sello_cfd']        = $nodoTimbre->getAttribute('SelloCFD');
		$registro['timbre_no_certificado']   = $nodoTimbre->getAttribute('NoCertificadoSAT');
	}

	if ( empty($registro['uuid']) && !empty($registro['timbre_uuid']) ) {
		$registro['uuid'] = $registro['timbre_uuid'];
	}

	return $registro;
}

function GuardarDb() {
	$carpeta = trim(filter_post( 'carpeta' ));
	$oMysql = create_conex();

	$carpeta = rtrim($carpeta, '/\\');
	$archivos = glob($carpeta . '/*.xml');

	if ( empty($archivos) ) {
		return [ 'result' => false, 'message' => 'No se encontraron archivos XML' ];
	}

	$guardados = 0;
	$omitidos = 0;
	$errores = 0;

	$uuidsExistentes = [];
	$oMysql->query("SELECT uuid FROM xml_importados WHERE uuid != ''");
	while ( $row = $oMysql->getrow() ) {
		$uuidsExistentes[$row['uuid']] = true;
	}

	foreach ( $archivos as $archivo ) {
		$contenido = file_get_contents($archivo);
		if ( $contenido === false ) { $errores++; continue; }

		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = false;
		if ( @$dom->loadXML($contenido) === false ) { $errores++; continue; }

		$registro = parsearCfdi($dom, basename($archivo));
		if ( !$registro ) { $errores++; continue; }

		$uuid = $registro['uuid'];
		if ( !empty($uuid) && isset($uuidsExistentes[$uuid]) ) { $omitidos++; continue; }

		$fecha = !empty($registro['fecha']) ? date('Y-m-d H:i:s', strtotime($registro['fecha'])) : null;
		$timbreFecha = !empty($registro['timbre_fecha']) ? date('Y-m-d H:i:s', strtotime($registro['timbre_fecha'])) : null;

		$sql = "INSERT INTO xml_importados SET
			archivo           = '" . $oMysql->escape($registro['archivo']) . "',
			xml_content       = '" . $oMysql->escape($contenido) . "',
			uuid              = '" . $oMysql->escape($registro['uuid']) . "',
			version           = '" . $oMysql->escape($registro['version']) . "',
			serie             = '" . $oMysql->escape($registro['serie']) . "',
			folio             = '" . $oMysql->escape($registro['folio']) . "',
			fecha             = " . ($fecha ? "'" . $oMysql->escape($fecha) . "'" : "NULL") . ",
			forma_pago        = '" . $oMysql->escape($registro['forma_pago']) . "',
			metodo_pago       = '" . $oMysql->escape($registro['metodo_pago']) . "',
			lugar_expedicion  = '" . $oMysql->escape($registro['lugar_expedicion']) . "',
			no_certificado    = '" . $oMysql->escape($registro['no_certificado']) . "',
			subtotal          = " . $registro['subtotal'] . ",
			descuento         = " . $registro['descuento'] . ",
			total             = " . $registro['total'] . ",
			moneda            = '" . $oMysql->escape($registro['moneda']) . "',
			tipo_comprobante  = '" . $oMysql->escape($registro['tipo_comprobante']) . "',
			es_global         = " . $registro['es_global'] . ",
			exportacion       = '" . $oMysql->escape($registro['exportacion']) . "',
			periodicidad      = '" . $oMysql->escape($registro['periodicidad']) . "',
			meses             = '" . $oMysql->escape($registro['meses']) . "',
			anio              = '" . $oMysql->escape($registro['anio']) . "',
			emisor_rfc        = '" . $oMysql->escape($registro['emisor_rfc']) . "',
			emisor_nombre     = '" . $oMysql->escape($registro['emisor_nombre']) . "',
			emisor_regimen    = '" . $oMysql->escape($registro['emisor_regimen']) . "',
			receptor_rfc      = '" . $oMysql->escape($registro['receptor_rfc']) . "',
			receptor_nombre   = '" . $oMysql->escape($registro['receptor_nombre']) . "',
			receptor_regimen  = '" . $oMysql->escape($registro['receptor_regimen']) . "',
			receptor_uso_cfdi = '" . $oMysql->escape($registro['receptor_uso_cfdi']) . "',
			receptor_cp       = '" . $oMysql->escape($registro['receptor_cp']) . "',
			impuestos_traslados = " . $registro['impuestos_traslados'] . ",
			impuestos_retenidos = " . $registro['impuestos_retenidos'] . ",
			iva_neto            = " . $registro['iva_neto'] . ",
			timbre_uuid       = '" . $oMysql->escape($registro['timbre_uuid']) . "',
			timbre_fecha      = " . ($timbreFecha ? "'" . $oMysql->escape($timbreFecha) . "'" : "NULL") . ",
			carpeta_origen    = '" . $oMysql->escape($carpeta) . "'";

		$oMysql->query($sql);
		$nuevoId = $oMysql->lastInsertID();

		if ( $nuevoId > 0 ) {
			$guardados++;

			$fechaTimbrado = !empty($registro['timbre_fecha']) ? $registro['timbre_fecha'] : null;
			$uuidCierre = !empty($registro['timbre_uuid']) ? $registro['timbre_uuid'] : $registro['uuid'];
			if ( !empty($uuidCierre) ) {
				$check = $oMysql->getConnection()->query("SELECT id FROM cfdi_cierres WHERE uuid = '" . $oMysql->getConnection()->real_escape_string($uuidCierre) . "' LIMIT 1");
				$row = $check ? $check->fetch_assoc() : null;
				if ( $check ) $check->free();
				if ( !$row ) {
					$oMysql->query("INSERT INTO cfdi_cierres (uuid, fecha_timbrado, fecharealcierre) 
						VALUES ('" . $oMysql->escape($uuidCierre) . "', " . ($fechaTimbrado ? "'" . $oMysql->escape($fechaTimbrado) . "'" : "NULL") . ", " . ($fechaTimbrado ? "'" . $oMysql->escape(substr($fechaTimbrado, 0, 10)) . "'" : "NULL") . ")");
				}
			}

			if ( !empty($registro['conceptos']) ) {
				foreach ( $registro['conceptos'] as $c ) {
					$sqlCon = "INSERT INTO xml_importados_conceptos SET
						xml_importado_id = " . $nuevoId . ",
						clave_prod_serv  = '" . $oMysql->escape($c['clave_prod_serv']) . "',
						cantidad         = " . (float)$c['cantidad'] . ",
						clave_unidad     = '" . $oMysql->escape($c['clave_unidad']) . "',
						descripcion      = '" . $oMysql->escape($c['descripcion']) . "',
						valor_unitario   = " . (float)$c['valor_unitario'] . ",
						importe          = " . (float)$c['importe'] . ",
						descuento        = " . (float)$c['descuento'] . ",
						objeto_imp       = '" . $oMysql->escape($c['objeto_imp']) . "'";
					$oMysql->query($sqlCon);
				}
			}
		} else {
			$errores++;
		}
	}

	$oMysql->getConnection()->commit();
	return [
		'result'   => true,
		'message'  => "Guardados: {$guardados}, Omitidos (duplicados): {$omitidos}, Errores: {$errores}",
		'guardados' => $guardados,
		'omitidos'  => $omitidos,
		'errores'   => $errores,
	];
}

function CargarDb() {
	$oMysql = create_conex();

	$where = '';
	$desde = filter_post('desde');
	$hasta = filter_post('hasta');

	if ( !empty($desde) ) {
		$where .= " AND fecha >= '" . $oMysql->escape($desde) . " 00:00:00'";
	}
	if ( !empty($hasta) ) {
		$where .= " AND fecha <= '" . $oMysql->escape($hasta) . " 23:59:59'";
	}

	$oMysql->query("SELECT * FROM xml_importados WHERE 1=1 " . $where . " ORDER BY serie ASC, CAST(folio AS UNSIGNED) ASC, id ASC");
	$registros = [];

	while ( $f = $oMysql->getrow() ) {
		$registros[] = [
			'id'                  => $f['id'],
			'archivo'             => $f['archivo'],
			'uuid'                => $f['uuid'],
			'version'             => $f['version'],
			'serie'               => $f['serie'],
			'folio'               => $f['folio'],
			'fecha'               => $f['fecha'],
			'forma_pago'          => $f['forma_pago'],
			'metodo_pago'         => $f['metodo_pago'],
			'lugar_expedicion'    => $f['lugar_expedicion'],
			'no_certificado'      => $f['no_certificado'],
			'subtotal'            => (float)$f['subtotal'],
			'descuento'           => (float)$f['descuento'],
			'total'               => (float)$f['total'],
			'moneda'              => $f['moneda'],
			'tipo_comprobante'    => $f['tipo_comprobante'],
			'es_global'           => (int)$f['es_global'],
			'exportacion'         => $f['exportacion'],
			'periodicidad'        => $f['periodicidad'],
			'meses'               => $f['meses'],
			'anio'                => $f['anio'],
			'emisor_rfc'          => $f['emisor_rfc'],
			'emisor_nombre'       => $f['emisor_nombre'],
			'emisor_regimen'      => $f['emisor_regimen'],
			'receptor_rfc'        => $f['receptor_rfc'],
			'receptor_nombre'     => $f['receptor_nombre'],
			'receptor_regimen'    => $f['receptor_regimen'],
			'receptor_uso_cfdi'   => $f['receptor_uso_cfdi'],
			'receptor_cp'         => $f['receptor_cp'],
			'impuestos_traslados' => (float)$f['impuestos_traslados'],
			'impuestos_retenidos' => (float)$f['impuestos_retenidos'],
			'iva_neto'           => (float)$f['iva_neto'],
			'timbre_uuid'         => $f['timbre_uuid'],
			'timbre_fecha'        => $f['timbre_fecha'],
			'carpeta_origen'      => $f['carpeta_origen'],
			'fecha_importacion'   => $f['fecha_importacion'],
			'estado'              => $f['estado'],
		];
	}

	return [ 'result' => true, 'data' => $registros, 'total' => count($registros) ];
}

function EliminarDb() {
	$id = (int)filter_post( 'id' );
	if ( $id <= 0 ) {
		return [ 'result' => false, 'message' => 'ID no válido' ];
	}
	$oMysql = create_conex();
	$oMysql->getConnection()->autocommit(true);
	$oMysql->query("DELETE FROM xml_importados WHERE id = " . $id);
	return [ 'result' => true, 'message' => 'Registro eliminado' ];
}

function EliminarTodosDb() {
	$oMysql = create_conex();
	$oMysql->getConnection()->autocommit(true);
	$oMysql->query("DELETE FROM xml_importados_conceptos");
	$oMysql->query("DELETE FROM xml_importados");
	return [ 'result' => true, 'message' => 'Todos los registros eliminados' ];
}

function VerificarSat() {
	$oMysql = create_conex();
	$oMysql->query("SELECT id, uuid, emisor_rfc, receptor_rfc, total, fecha, forma_pago, estado FROM xml_importados WHERE uuid != '' AND uuid LIKE '%-%' AND (estado IS NULL OR estado = '' OR estado = 'DESCONOCIDO')");
	$registros = [];
	while ( $f = $oMysql->getrow() ) {
		$registros[] = [ 'id' => $f['id'], 'uuid' => $f['uuid'] ];
	}
	return [ 'result' => true, 'registros' => $registros, 'total' => count($registros) ];
}

function VerificarSatOne() {
	$id = (int)filter_post( 'id' );
	if ( $id <= 0 ) return [ 'result' => false, 'message' => 'ID no válido' ];

	$oMysql = create_conex();
	$oMysql->query("SELECT id, uuid, emisor_rfc, receptor_rfc, total, fecha, forma_pago FROM xml_importados WHERE id = " . $id);
	$r = $oMysql->getrow();
	if ( !$r ) return [ 'result' => false, 'message' => 'Registro no encontrado' ];

	$uuid     = $r['uuid'];
	$emisor   = $r['emisor_rfc'];
	$receptor = $r['receptor_rfc'];
	$total    = number_format((float)$r['total'], 2, '.', '');

	if ( empty($uuid) || empty($emisor) || empty($receptor) ) {
		return [ 'result' => false, 'message' => 'Datos incompletos' ];
	}

	$satUrl = 'https://consultaqr.facturaelectronica.sat.gob.mx/ConsultaCFDIService.svc';

	$expresionImpresa = '?re=' . urlencode($emisor) . '&rr=' . urlencode($receptor) . '&tt=' . $total . '&id=' . urlencode($uuid);

	$soapBody = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:tem="http://tempuri.org/">
  <soap:Body>
    <tem:Consulta>
      <tem:expresionImpresa>' . htmlspecialchars($expresionImpresa) . '</tem:expresionImpresa>
    </tem:Consulta>
  </soap:Body>
</soap:Envelope>';

	$ch = curl_init($satUrl);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $soapBody);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: text/xml; charset=utf-8',
		'SOAPAction: "http://tempuri.org/IConsultaCFDIService/Consulta"',
	]);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$curlErr  = curl_error($ch);
	curl_close($ch);

	if ( !$response || $httpCode != 200 ) {
		return [ 'result' => false, 'message' => 'Error SAT: ' . ($curlErr ?: 'HTTP ' . $httpCode), 'estado' => 'ERROR' ];
	}

	$estado = 'DESCONOCIDO';
	if ( preg_match('/<a:Estado[^>]*>(.*?)<\/a:Estado>/', $response, $m) ) {
		$estadoSat = trim($m[1]);
		if ( stripos($estadoSat, 'Vigente') !== false ) {
			$estado = 'VIGENTE';
		} elseif ( stripos($estadoSat, 'Cancelado') !== false ) {
			$estado = 'CANCELADO';
		} else {
			$estado = $estadoSat;
		}
	}

	$oMysql->getConnection()->autocommit(true);
	$oMysql->query("UPDATE xml_importados SET estado = '" . $oMysql->escape($estado) . "' WHERE id = " . $id);

	return [ 'result' => true, 'estado' => $estado ];
}

function LimpiarEstado() {
	$oMysql = create_conex();
	$oMysql->getConnection()->autocommit(true);
	$oMysql->query("UPDATE xml_importados SET estado = NULL");
	return [ 'result' => true, 'message' => 'Estado limpiado. Todos los registros listos para re-verificar.' ];
}

function GetXml() {
	$id = (int)filter_post('id');
	if ( $id <= 0 ) {
		return [ 'result' => false, 'message' => 'ID no válido' ];
	}

	$oMysql = create_conex();
	$mysqli = $oMysql->getConnection();
	$res = $mysqli->query("SELECT xml_content FROM xml_importados WHERE id = " . $id);
	if ( !$res ) {
		return [ 'result' => false, 'message' => 'Error al consultar' ];
	}
	$row = $res->fetch_assoc();
	$res->free();
	$oMysql->Close();

	if ( !$row || empty($row['xml_content']) ) {
		return [ 'result' => false, 'message' => 'No se encontro el XML para este registro' ];
	}

	$xmlFormateado = formatXml($row['xml_content']);
	return [ 'result' => true, 'xml' => $xmlFormateado ];
}

function formatXml($xml) {
	$xml = trim($xml);
	if ( empty($xml) ) return $xml;

	$dom = new DOMDocument();
	$dom->preserveWhiteSpace = false;
	$dom->formatOutput = true;
	if ( @$dom->loadXML($xml) === false ) {
		return $xml;
	}
	return $dom->saveXML();
}
?>