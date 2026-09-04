<?php
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( false );

include ( Constants::getpath_root() . 'config_model.php' );
include ( Constants::getpath_root() . 'config_db.php' );
include ( Constants::getpath_root() . 'helpers.php' );

require_once Constants::getpath_root() . 'vendor/autoload.php';
require_once Constants::getpath_root() . 'vendor/phpcfdi/sat-ws-descarga-masiva/src/WebClient/CurlWebClient.php';

use PhpCfdi\SatWsDescargaMasiva\Service;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\Fiel;
use PhpCfdi\SatWsDescargaMasiva\RequestBuilder\FielRequestBuilder\FielRequestBuilder;
use PhpCfdi\SatWsDescargaMasiva\WebClient\CurlWebClient;
use PhpCfdi\SatWsDescargaMasiva\Services\Query\QueryParameters;
use PhpCfdi\SatWsDescargaMasiva\Shared\DateTimePeriod;
use PhpCfdi\SatWsDescargaMasiva\Shared\DownloadType;
use PhpCfdi\SatWsDescargaMasiva\Shared\RequestType;
use PhpCfdi\SatWsDescargaMasiva\Shared\DocumentStatus;

ob_clean();
header('Content-Type: application/json; charset=utf-8');

$cAction = filter_post( 'action' );

switch( $cAction ) {
	case 'solicitar':       $result = Solicitar();       break;
	case 'verificar':       $result = Verificar();       break;
	case 'descargar':       $result = Descargar();       break;
	case 'listar_paquetes': $result = ListarPaquetes();  break;
	default:                $result = [ 'result' => false, 'message' => 'Acción no válida' ];
}

die( json_encode( $result ) );

function getTempDir() {
	$dir = sys_get_temp_dir() . '/sat_descarga/';
	if (!is_dir($dir)) mkdir($dir, 0777, true);
	return $dir;
}

function createService($cerFile, $keyFile, $password) {
	$cerContent = file_get_contents($cerFile['tmp_name']);
	$keyContent = file_get_contents($keyFile['tmp_name']);

	if ($cerContent === false || $keyContent === false) {
		return ['error' => 'Error al leer los archivos de e.firma'];
	}

	$fiel = Fiel::create($cerContent, $keyContent, $password);

	if (!$fiel->isValid()) {
		return ['error' => 'La e.firma no es válida o no es tipo FIEL (puede ser CSD o estar vencida)'];
	}

	$webClient = new CurlWebClient();
	$requestBuilder = new FielRequestBuilder($fiel);
	$service = new Service($requestBuilder, $webClient);

	return ['service' => $service, 'rfc' => $fiel->getRfc()];
}

function Solicitar() {
	$cerFile = $_FILES['cer_file'] ?? null;
	$keyFile = $_FILES['key_file'] ?? null;
	$password = trim(filter_post('password'));
	$fechaDesde = filter_post('fecha_desde');
	$fechaHasta = filter_post('fecha_hasta');
	$tipoDescarga = filter_post('tipo');
	$rfcEmisor = trim(filter_post('rfc_emisor'));
	$rfcReceptor = trim(filter_post('rfc_receptor'));

	if (!$cerFile || !$keyFile || empty($password)) {
		return ['result' => false, 'message' => 'Debe proporcionar archivos .cer, .key y contraseña'];
	}
	if (empty($fechaDesde) || empty($fechaHasta)) {
		return ['result' => false, 'message' => 'Debe especificar rango de fechas'];
	}

	$svc = createService($cerFile, $keyFile, $password);
	if (isset($svc['error'])) {
		return ['result' => false, 'message' => $svc['error']];
	}
	$service = $svc['service'];
	$rfc = $svc['rfc'];

	$fechaDesdeXml = $fechaDesde . ' 00:00:00';
	$fechaHastaXml = $fechaHasta . ' 23:59:59';

	$query = QueryParameters::create(
		DateTimePeriod::createFromValues($fechaDesdeXml, $fechaHastaXml),
		$tipoDescarga === 'emitidos' ? DownloadType::issued() : DownloadType::received(),
		RequestType::xml()
	)->withDocumentStatus(DocumentStatus::active());

	if (!empty($rfcEmisor)) {
		$query = $query->withRfcMatch(
			\PhpCfdi\SatWsDescargaMasiva\Shared\RfcMatch::create($rfcEmisor)
		);
	}
	if (!empty($rfcReceptor)) {
		$query = $query->withRfcOnBehalf(
			\PhpCfdi\SatWsDescargaMasiva\Shared\RfcOnBehalf::create($rfcReceptor)
		);
	}

	$queryResult = $service->query($query);

	if (!$queryResult->getStatus()->isAccepted()) {
		$code = $queryResult->getStatus()->getCode();
		$msg = $queryResult->getStatus()->getMessage();
		return ['result' => false, 'message' => "Error SAT ({$code}): {$msg}"];
	}

	$requestId = $queryResult->getRequestId();

	$oDb = create_conex();
	$oDb->insert('sat_descargas', [
		'rfc' => $rfc,
		'request_id' => $requestId,
		'fecha_desde' => $fechaDesde,
		'fecha_hasta' => $fechaHasta,
		'tipo' => $tipoDescarga,
		'estado' => 'solicitado',
		'token' => ''
	]);
	$id = $oDb->lastInsertID();
	$oDb->Close();

	return ['result' => true, 'request_id' => $requestId, 'id' => $id, 'rfc' => $rfc, 'message' => 'Solicitud enviada al SAT. Request ID: ' . $requestId];
}

function Verificar() {
	$requestId = trim(filter_post('request_id'));
	$token = trim(filter_post('token'));
	$cerFile = $_FILES['cer_file'] ?? null;
	$keyFile = $_FILES['key_file'] ?? null;
	$password = trim(filter_post('password'));

	if (empty($requestId)) {
		return ['result' => false, 'message' => 'Request ID requerido'];
	}
	if (!$cerFile || !$keyFile || empty($password)) {
		return ['result' => false, 'message' => 'Debe proporcionar archivos .cer, .key y contraseña para verificar'];
	}

	$svc = createService($cerFile, $keyFile, $password);
	if (isset($svc['error'])) {
		return ['result' => false, 'message' => $svc['error']];
	}
	$service = $svc['service'];

	$verifyResult = $service->verify($requestId);

	if (!$verifyResult->getStatus()->isAccepted()) {
		$code = $verifyResult->getStatus()->getCode();
		$msg = $verifyResult->getStatus()->getMessage();
		return ['result' => false, 'message' => "Error SAT ({$code}): {$msg}"];
	}

	$codeRequest = $verifyResult->getCodeRequest()->getCode();
	$statusRequest = $verifyResult->getStatusRequest()->getCode();
	$paquetes = $verifyResult->getPackagesIds();
	$numCfdis = $verifyResult->getNumberCfdis();
	$estado = $verifyResult->getStatusRequest()->isFinished() ? '3' : ($verifyResult->getStatusRequest()->isInProgress() ? '2' : ($verifyResult->getStatusRequest()->isRejected() ? '5' : ($verifyResult->getStatusRequest()->isFailure() ? '4' : '1')));

	if ($verifyResult->getStatusRequest()->isFinished()) {
		$oDb = create_conex();
		$oDb->query("UPDATE sat_descargas SET estado = 'completado' WHERE request_id = '" . $oDb->escape($requestId) . "'");
		$oDb->Close();
	}

	return [
		'result' => true,
		'estado' => $estado,
		'estado_texto' => getEstadoTexto($estado),
		'code_request' => $codeRequest,
		'status_request' => $statusRequest,
		'paquetes' => $paquetes,
		'num_cfdis' => $numCfdis,
		'message' => $verifyResult->getStatusRequest()->isFinished()
			? "Completado. {$numCfdis} CFDIs en " . count($paquetes) . " paquete(s)."
			: getEstadoTexto($estado) . " (código: {$codeRequest})"
	];
}

function Descargar() {
	$paqueteId = trim(filter_post('paquete_id'));
	$cerFile = $_FILES['cer_file'] ?? null;
	$keyFile = $_FILES['key_file'] ?? null;
	$password = trim(filter_post('password'));

	if (empty($paqueteId)) {
		return ['result' => false, 'message' => 'Paquete ID requerido'];
	}
	if (!$cerFile || !$keyFile || empty($password)) {
		return ['result' => false, 'message' => 'Debe proporcionar archivos .cer, .key y contraseña para descargar'];
	}

	$svc = createService($cerFile, $keyFile, $password);
	if (isset($svc['error'])) {
		return ['result' => false, 'message' => $svc['error']];
	}
	$service = $svc['service'];

	$downloadResult = $service->download($paqueteId);

	if (!$downloadResult->getStatus()->isAccepted()) {
		$code = $downloadResult->getStatus()->getCode();
		$msg = $downloadResult->getStatus()->getMessage();
		return ['result' => false, 'message' => "Error SAT ({$code}): {$msg}"];
	}

	$zipContent = $downloadResult->getPackageContent();
	$zipSize = $downloadResult->getPackageSize();

	$zipFile = getTempDir() . $paqueteId . '.zip';
	file_put_contents($zipFile, $zipContent);

	$zip = new ZipArchive();
	if ($zip->open($zipFile) !== TRUE) {
		unlink($zipFile);
		return ['result' => false, 'message' => 'Error al abrir el archivo ZIP'];
	}

	$oDb = create_conex();
	$guardados = 0;
	$omitidos = 0;

	$uuidsExistentes = [];
	$oDb->query("SELECT uuid FROM xml_importados WHERE uuid != ''");
	while ($row = $oDb->getrow()) {
		$uuidsExistentes[$row['uuid']] = true;
	}

	for ($i = 0; $i < $zip->numFiles; $i++) {
		$nombre = $zip->getNameIndex($i);
		if (substr($nombre, -4) !== '.xml') continue;

		$contenido = $zip->getFromIndex($i);
		if ($contenido === false) continue;

		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = false;
		if (@$dom->loadXML($contenido) === false) continue;

		include_once '../model/xml_reader.php';
		$registro = parsearCfdi($dom, $nombre);
		if (!$registro) continue;

		$uuid = $registro['uuid'];
		if (!empty($uuid) && isset($uuidsExistentes[$uuid])) { $omitidos++; continue; }

		$fecha = !empty($registro['fecha']) ? date('Y-m-d H:i:s', strtotime($registro['fecha'])) : null;
		$timbreFecha = !empty($registro['timbre_fecha']) ? date('Y-m-d H:i:s', strtotime($registro['timbre_fecha'])) : null;

		$sql = "INSERT INTO xml_importados SET
			archivo           = '" . $oDb->escape($registro['archivo']) . "',
			xml_content       = '" . $oDb->escape($contenido) . "',
			uuid              = '" . $oDb->escape($registro['uuid']) . "',
			version           = '" . $oDb->escape($registro['version']) . "',
			serie             = '" . $oDb->escape($registro['serie']) . "',
			folio             = '" . $oDb->escape($registro['folio']) . "',
			fecha             = " . ($fecha ? "'" . $oDb->escape($fecha) . "'" : "NULL") . ",
			forma_pago        = '" . $oDb->escape($registro['forma_pago']) . "',
			metodo_pago       = '" . $oDb->escape($registro['metodo_pago']) . "',
			lugar_expedicion  = '" . $oDb->escape($registro['lugar_expedicion']) . "',
			no_certificado    = '" . $oDb->escape($registro['no_certificado']) . "',
			subtotal          = " . $registro['subtotal'] . ",
			descuento         = " . $registro['descuento'] . ",
			total             = " . $registro['total'] . ",
			moneda            = '" . $oDb->escape($registro['moneda']) . "',
			tipo_comprobante  = '" . $oDb->escape($registro['tipo_comprobante']) . "',
			es_global         = " . $registro['es_global'] . ",
			exportacion       = '" . $oDb->escape($registro['exportacion']) . "',
			periodicidad      = '" . $oDb->escape($registro['periodicidad']) . "',
			meses             = '" . $oDb->escape($registro['meses']) . "',
			anio              = '" . $oDb->escape($registro['anio']) . "',
			emisor_rfc        = '" . $oDb->escape($registro['emisor_rfc']) . "',
			emisor_nombre     = '" . $oDb->escape($registro['emisor_nombre']) . "',
			emisor_regimen    = '" . $oDb->escape($registro['emisor_regimen']) . "',
			receptor_rfc      = '" . $oDb->escape($registro['receptor_rfc']) . "',
			receptor_nombre   = '" . $oDb->escape($registro['receptor_nombre']) . "',
			receptor_regimen  = '" . $oDb->escape($registro['receptor_regimen']) . "',
			receptor_uso_cfdi = '" . $oDb->escape($registro['receptor_uso_cfdi']) . "',
			receptor_cp       = '" . $oDb->escape($registro['receptor_cp']) . "',
			impuestos_traslados = " . $registro['impuestos_traslados'] . ",
			impuestos_retenidos = " . $registro['impuestos_retenidos'] . ",
			iva_neto            = " . $registro['iva_neto'] . ",
			timbre_uuid       = '" . $oDb->escape($registro['timbre_uuid']) . "',
			timbre_fecha      = " . ($timbreFecha ? "'" . $oDb->escape($timbreFecha) . "'" : "NULL") . ",
			carpeta_origen    = 'descarga_sat'";

		$oDb->query($sql);
		$nuevoId = $oDb->lastInsertID();

		if ($nuevoId > 0) {
			$guardados++;
			$uuidsExistentes[$uuid] = true;

			$uuidCierre = !empty($registro['timbre_uuid']) ? $registro['timbre_uuid'] : $registro['uuid'];
			if (!empty($uuidCierre)) {
				$check = $oDb->getConnection()->query("SELECT id FROM cfdi_cierres WHERE uuid = '" . $oDb->getConnection()->real_escape_string($uuidCierre) . "' LIMIT 1");
				$row = $check ? $check->fetch_assoc() : null;
				if ($check) $check->free();
				if (!$row) {
					$oDb->query("INSERT INTO cfdi_cierres (uuid, fecha_timbrado, fecharealcierre)
						VALUES ('" . $oDb->escape($uuidCierre) . "', " . ($timbreFecha ? "'" . $oDb->escape($timbreFecha) . "'" : "NULL") . ", " . ($timbreFecha ? "'" . $oDb->escape(substr($timbreFecha, 0, 10)) . "'" : "NULL") . ")");
				}
			}
		}
	}

	$oDb->Close();
	$zip->close();
	unlink($zipFile);

	return ['result' => true, 'guardados' => $guardados, 'omitidos' => $omitidos, 'paquete_id' => $paqueteId, 'zip_size' => $zipSize, 'message' => "Paquete {$paqueteId} procesado. Guardados: {$guardados}, omitidos: {$omitidos}"];
}

function ListarPaquetes() {
	$oDb = create_conex();
	$oDb->query("SELECT * FROM sat_descargas ORDER BY id DESC LIMIT 50");
	$registros = [];
	while ($f = $oDb->getrow()) {
		$registros[] = $f;
	}
	$oDb->Close();
	return ['result' => true, 'data' => $registros];
}

function getEstadoTexto($estado) {
	$estados = [
		'0' => 'En espera',
		'1' => 'Aceptada',
		'2' => 'En proceso',
		'3' => 'Completado',
		'4' => 'Error',
		'5' => 'Rechazada',
	];
	return $estados[$estado] ?? 'Desconocido';
}
?>
