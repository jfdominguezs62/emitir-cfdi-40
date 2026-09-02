<?php
include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( false );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_root() . 'config_db.php' );

$cAction = filter_post( 'action' );

switch( $cAction ) {	
	case 'getregimenes':      $result = GetRegimenes();      break;
	case 'getusos_cfdi':      $result = GetUsosCfdi();       break;
	case 'getformas_pago':    $result = GetFormasPago();     break;
	case 'getmetodos_pago':   $result = GetMetodosPago();    break;
	case 'getestados':        $result = GetEstados();        break;
	case 'getunidades':       $result = GetUnidades();       break;
	case 'getemisor':         $result = GetEmisor();         break;
	case 'saveemisor':        $result = SaveEmisor();        break;
	default:                  $result = [ 'result' => false, 'message' => 'Acción no válida' ];
}

die( json_encode( $result ) );

function GetRegimenes() {
	$regimenes = [
		['clave' => '601', 'descripcion' => 'General de Ley Personas Morales'],
		['clave' => '603', 'descripcion' => 'Personas Morales con Fines no Lucrativos'],
		['clave' => '605', 'descripcion' => 'Sueldos y Salarios e Ingresos Asimilados a Salarios'],
		['clave' => '606', 'descripcion' => 'Arrendamiento'],
		['clave' => '607', 'descripcion' => 'Regimen de Enajenación o Adquisición de Bienes'],
		['clave' => '608', 'descripcion' => 'Demás ingresos'],
		['clave' => '609', 'descripcion' => 'Consolidación'],
		['clave' => '610', 'descripcion' => 'Residentes en el Extranjero sin Establecimiento Permanente en México'],
		['clave' => '611', 'descripcion' => 'Régimen de los Activos Empresariales'],
		['clave' => '612', 'descripcion' => 'Personas Físicas con Actividades Empresariales y Profesionales'],
		['clave' => '614', 'descripcion' => 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras'],
		['clave' => '615', 'descripcion' => 'Régimen de los Ingresos por Dividendos (socios y accionistas)'],
		['clave' => '616', 'descripcion' => 'Régimen de los Ingresos por Asignaciones'],
		['clave' => '620', 'descripcion' => 'Sociedades Cooperativas de Producción que optan por sus ingresos'],
		['clave' => '621', 'descripcion' => 'Régimen Incorporación Fiscal'],
		['clave' => '622', 'descripcion' => 'Actividades Empresariales con ingresos por ventilación marginal'],
		['clave' => '623', 'descripcion' => 'Obligados a los tributos de la Ley del ISR por cuenta de terceros'],
		['clave' => '624', 'descripcion' => 'Régimen de los ingresos por premios'],
		['clave' => '625', 'descripcion' => 'Régimen de las personas que se dedican a las actividades de plataformas tecnológicas y de la economía colaborativa'],
		['clave' => '626', 'descripcion' => 'Régimen Simplificado de Confianza'],
	];
	return [ 'result' => true, 'data' => $regimenes ];
}

function GetUsosCfdi() {
	$usos = [
		['clave' => 'G01', 'descripcion' => 'Adquisición de mercancías'],
		['clave' => 'G02', 'descripcion' => 'Adquisición de materiales de construcción'],
		['clave' => 'G03', 'descripcion' => 'Adquisición de maquinaria y equipo'],
		['clave' => 'G04', 'descripcion' => 'Gastos en general'],
		['clave' => 'G05', 'descripcion' => 'Adquisición de mobiliario y equipo de oficina'],
		['clave' => 'G06', 'descripcion' => 'Adquisición de equipo de transporte'],
		['clave' => 'G07', 'descripcion' => 'Adquisición de equipo de cómputo y accesorios'],
		['clave' => 'G08', 'descripcion' => 'Adquisición de尧coniformes y muebles de oficina'],
		['clave' => 'G09', 'descripcion' => 'Adquisición de enseres domésticos'],
		['clave' => 'G10', 'descripcion' => 'Adquisición de materiales de oficina y de operación'],
		['clave' => 'G11', 'descripcion' => 'Adquisición de uniformes y vestuario'],
		['clave' => 'G12', 'descripcion' => 'Adquisición de medicamentos'],
		['clave' => 'G13', 'descripcion' => 'Gastos de manutención'],
		['clave' => 'G14', 'descripcion' => 'Gastos de viaje oficial'],
		['clave' => 'G15', 'descripcion' => 'Gastos de funeraria'],
		['clave' => 'G16', 'descripcion' => 'Gastos donativos'],
		['clave' => 'G17', 'descripcion' => 'Gastos de primas de seguros'],
		['clave' => 'G18', 'descripcion' => 'Gastos de hospitalización'],
		['clave' => 'G19', 'descripcion' => 'Gastos de medicamentos para hospitales'],
		['clave' => 'G20', 'descripcion' => 'Gastos de atención médica, dental y hospitalaria'],
		['clave' => 'G21', 'descripcion' => 'Gastos médicos mayores'],
		['clave' => 'G22', 'descripcion' => 'Gastos de entretenimiento'],
		['clave' => 'G23', 'descripcion' => 'Gastos de capacitación'],
		['clave' => 'G24', 'descripcion' => 'Gastos de créditos hipotecarios para vivienda'],
		['clave' => 'G25', 'descripcion' => 'Gastos de amortización de créditos hipotecarios para vivienda'],
		['clave' => 'G26', 'descripcion' => 'Gastos de pago a favor de organismos gubernamentales'],
		['clave' => 'G27', 'descripcion' => 'Gastos de alimentación y hospedaje'],
		['clave' => 'G28', 'descripcion' => 'Gastos de educación y capacitación'],
		['clave' => 'G29', 'descripcion' => 'Gastos de deporte y cultura'],
		['clave' => 'G30', 'descripcion' => 'Gastos de arte'],
		['clave' => 'G31', 'descripcion' => 'Gastos de transporte de personal'],
		['clave' => 'G32', 'descripcion' => 'Gastos de transporte de pasajeros'],
		['clave' => 'G33', 'descripcion' => 'Gastos de fletes y acarreos'],
		['clave' => 'G34', 'descripcion' => 'Gastos de comisiones y honorarios'],
		['clave' => 'G35', 'descripcion' => 'Gastos de servicios personales'],
		['clave' => 'G36', 'descripcion' => 'Gastos de servicios de mantto y reparaciones'],
		['clave' => 'G37', 'descripcion' => 'Gastos de seguros y fianzas'],
		['clave' => 'G38', 'descripcion' => 'Gastos de servicios administrativos'],
		['clave' => 'G39', 'descripcion' => 'Gastos de servicios de limpieza'],
		['clave' => 'G40', 'descripcion' => 'Gastos de servicios de seguridad'],
		['clave' => 'G41', 'descripcion' => 'Gastos de servicios de vigilancia'],
		['clave' => 'G42', 'descripcion' => 'Gastos de servicios de internet'],
		['clave' => 'G43', 'descripcion' => 'Gastos de servicios de telefonía'],
		['clave' => 'G44', 'descripcion' => 'Gastos de servicios de mensajería y paquetería'],
		['clave' => 'G45', 'descripcion' => 'Gastos de servicios de transporte'],
		['clave' => 'G46', 'descripcion' => 'Gastos de servicios de impresión'],
		['clave' => 'G47', 'descripcion' => 'Gastos de servicios de ingeniería y arquitectura'],
		['clave' => 'G48', 'descripcion' => 'Gastos de servicios de publicidad y promoción'],
		['clave' => 'G49', 'descripcion' => 'Gastos de servicios de fotografía y video'],
		['clave' => 'G50', 'descripcion' => 'Gastos de servicios de contabilidad y auditoría'],
		['clave' => 'G51', 'descripcion' => 'Gastos de servicios legales'],
		['clave' => 'G52', 'descripcion' => 'Gastos de servicios de arrendamiento'],
		['clave' => 'G53', 'descripcion' => 'Gastos de servicios de consultoría'],
		['clave' => 'G54', 'descripcion' => 'Gastos de capacitación y desarrollo de personal'],
		['clave' => 'G55', 'descripcion' => 'Gastos de servicios de investigación y desarrollo'],
		['clave' => 'G56', 'descripcion' => 'Gastos de servicios de traducción e interpretación'],
		['clave' => 'G57', 'descripcion' => 'Gastos de servicios de diseño y decoración'],
		['clave' => 'G58', 'descripcion' => 'Gastos de servicios de ingeniería'],
		['clave' => 'G59', 'descripcion' => 'Gastos de servicios de arquitectura'],
		['clave' => 'G60', 'descripcion' => 'Gastos de servicios de consultoría empresarial'],
		['clave' => 'D01', 'descripcion' => 'Honorarios médicos, dentales y hospitalarios'],
		['clave' => 'D02', 'descripcion' => 'Gastos médicos por incapacidad o discapacidad'],
		['clave' => 'D03', 'descripcion' => 'Gastos funerarios'],
		['clave' => 'D04', 'descripcion' => 'Gastos de donativos'],
		['clave' => 'D05', 'descripcion' => 'Gastos de intereses reales efectivamente pagados por créditos hipotecarios para vivienda'],
		['clave' => 'D06', 'descripcion' => 'Aportaciones voluntarias al AFORE'],
		['clave' => 'D07', 'descripcion' => 'Aportaciones a fondos de retiro'],
		['clave' => 'D08', 'descripcion' => 'Primas de seguros de gastos médicos'],
		['clave' => 'D09', 'descripcion' => 'Primas de seguros de gastos médicos mayores'],
		['clave' => 'D10', 'descripcion' => 'Primas de seguros de vida'],
		['clave' => 'D11', 'descripcion' => 'Gastos de educación preescolar, primaria, secundaria, media superior y superior'],
		['clave' => 'I01', 'descripcion' => 'Inversión en Maquinaria y Equipo'],
		['clave' => 'I02', 'descripcion' => 'Inversión en Equipo de Oficina'],
		['clave' => 'I03', 'descripcion' => 'Inversión en Equipo de Transporte'],
		['clave' => 'I04', 'descripcion' => 'Inversión en Equipo de Computo'],
		['clave' => 'I05', 'descripcion' => 'Inversión en Otros Activos Fijos'],
		['clave' => 'I06', 'descripcion' => 'Inversión en Construcciones'],
		['clave' => 'I07', 'descripcion' => 'Inversión en Depósitos Fijos'],
		['clave' => 'I08', 'descripcion' => 'Inversión en Terrenos'],
		['clave' => 'I09', 'descripcion' => 'Inversión en Edificios'],
		['clave' => 'I10', 'descripcion' => 'Inversiones en Instrumentos Financieros'],
		['clave' => 'I11', 'descripcion' => 'Inversión en Cryptoactivos'],
		['clave' => 'I12', 'descripcion' => 'Inversión en bienes incorporados al Régimen de Activos Empresariales'],
		['clave' => 'I13', 'descripcion' => 'Inversión en activos intangibles'],
		['clave' => 'I14', 'descripcion' => 'Inversión en activos diferidos'],
		['clave' => 'I15', 'descripcion' => 'Inversión en inversiones temporales'],
		['clave' => 'I16', 'descripcion' => 'Inversión en inversiones a largo plazo'],
		['clave' => 'I17', 'descripcion' => 'Inversión en otros activos'],
		['clave' => 'I18', 'descripcion' => 'Inversión en cryptoactivos'],
		['clave' => 'I19', 'descripcion' => 'Inversión en bienes incorporados al régimen de Activos Empresariales'],
		['clave' => 'I20', 'descripcion' => 'Inversión en activos intangibles'],
		['clave' => 'I21', 'descripcion' => 'Inversión en activos diferidos'],
		['clave' => 'I22', 'descripcion' => 'Inversión en inversiones temporales'],
		['clave' => 'I23', 'descripcion' => 'Inversión en inversiones a largo plazo'],
		['clave' => 'I24', 'descripcion' => 'Inversión en otros activos'],
		['clave' => 'I25', 'descripcion' => 'Inversión en cryptoactivos'],
		['clave' => 'I26', 'descripcion' => 'Inversión en bienes incorporados al régimen de Activos Empresariales'],
		['clave' => 'I27', 'descripcion' => 'Inversión en activos intangibles'],
		['clave' => 'I28', 'descripcion' => 'Inversión en activos diferidos'],
		['clave' => 'I29', 'descripcion' => 'Inversión en inversiones temporales'],
		['clave' => 'I30', 'descripcion' => 'Inversión en inversiones a largo plazo'],
		['clave' => 'I31', 'descripcion' => 'Inversión en otros activos'],
		['clave' => 'I32', 'descripcion' => 'Inversión en cryptoactivos'],
		['clave' => 'CN01', 'descripcion' => 'Entrega de mercancías a un comisionista para su enajenación'],
		['clave' => 'CN02', 'descripcion' => 'Pago a un comisionista por la venta de mercancías'],
		['clave' => 'CN03', 'descripcion' => 'Entrega de mercancías a un comitente para su enajenación'],
		['clave' => 'CN04', 'descripcion' => 'Pago a un comitente por la venta de mercancías'],
		['clave' => 'CN05', 'descripcion' => 'Entrega de mercancías a un agente para su enajenación'],
		['clave' => 'CN06', 'descripcion' => 'Pago a un agente por la venta de mercancías'],
		['clave' => 'CN07', 'descripcion' => 'Entrega de mercancías a un distribuidor para su enajenación'],
		['clave' => 'CN08', 'descripcion' => 'Pago a un distribuidor por la venta de mercancías'],
		['clave' => 'CN09', 'descripcion' => 'Entrega de mercancías a un revendedor para su enajenación'],
		['clave' => 'CN10', 'descripcion' => 'Pago a un revendedor por la venta de mercancías'],
		['clave' => 'CN11', 'descripcion' => 'Entrega de mercancías a un proveedor de servicios de paquetería'],
		['clave' => 'CN12', 'descripcion' => 'Pago a un proveedor de servicios de paquetería'],
		['clave' => 'S01', 'descripcion' => 'Sin efectos fiscales'],
		['clave' => 'CP01', 'descripcion' => 'Pagos por servicios de comisiones y corredurías'],
		['clave' => 'CP02', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por enajenación de inmuebles'],
		['clave' => 'CP03', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios'],
		['clave' => 'CP04', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por arrendamiento de inmuebles'],
		['clave' => 'CP05', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios profesionales'],
		['clave' => 'CP06', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios de transporte'],
		['clave' => 'CP07', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios de telecomunicaciones'],
		['clave' => 'CP08', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios de-hotelería'],
		['clave' => 'CP09', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios de alimentos y bebidas'],
		['clave' => 'CP10', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios de entretenimiento'],
		['clave' => 'CP11', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios de arte'],
		['clave' => 'CP12', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios de educación'],
		['clave' => 'CP13', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios de salud'],
		['clave' => 'CP14', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios de deporte'],
		['clave' => 'CP15', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios de cultura'],
		['clave' => 'CP16', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios deTurismo'],
		['clave' => 'CP17', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios deTransporte aéreo'],
		['clave' => 'CP18', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios deTransporte terrestre'],
		['clave' => 'CP19', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios deTransporte marítimo'],
		['clave' => 'CP20', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios deTransporte fluvial'],
		['clave' => 'CP21', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios deTransporte multimodal'],
		['clave' => 'CP22', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios deLogística'],
		['clave' => 'CP23', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios deAlmacenamiento'],
		['clave' => 'CP24', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios deDistribución'],
		['clave' => 'CP25', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios deComercialización'],
		['clave' => 'CP26', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios deMarketing'],
		['clave' => 'CP27', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios dePublicidad'],
		['clave' => 'CP28', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios deRelaciones públicas'],
		['clave' => 'CP29', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios deInvestigación de mercados'],
		['clave' => 'CP30', 'descripcion' => 'Pagos por servicios de comisiones y corredurías por prestación de servicios deConsultoría'],
	];
	return [ 'result' => true, 'data' => $usos ];
}

function GetFormasPago() {
	$formas = [
		['clave' => '01', 'descripcion' => 'Efectivo'],
		['clave' => '02', 'descripcion' => 'Cheque nominativo'],
		['clave' => '03', 'descripcion' => 'Transferencia electrónica de fondos'],
		['clave' => '04', 'descripcion' => 'Tarjeta de crédito'],
		['clave' => '05', 'descripcion' => 'Monedero electrónico'],
		['clave' => '06', 'descripcion' => 'Dinero electrónico'],
		['clave' => '08', 'descripcion' => 'Vales de despensa'],
		['clave' => '10', 'descripcion' => 'Enajenación de bienes o servicios realizados a través de internet'],
		['clave' => '11', 'descripcion' => 'Medios que no representan el pago'],
		['clave' => '12', 'descripcion' => 'Pago por la adquisición de mercancías'],
		['clave' => '13', 'descripcion' => 'Pago por la prestación de servicios'],
		['clave' => '14', 'descripcion' => 'Otros'],
		['clave' => '15', 'descripcion' => 'Aplicación de anticipos'],
		['clave' => '17', 'descripcion' => 'Compensación de deudas'],
		['clave' => '23', 'descripcion' => 'Novación'],
		['clave' => '24', 'descripcion' => 'Definitivo (Pago en una sola exhibición)'],
		['clave' => '25', 'descripcion' => 'Pagos por cortesía'],
		['clave' => '26', 'descripcion' => 'Condicionado'],
		['clave' => '27', 'descripcion' => 'Financiamiento (Pago diferido)'],
		['clave' => '28', 'descripcion' => 'Entrega a cuenta de pagos anticipados o parciales'],
		['clave' => '29', 'descripcion' => 'Pago por cuenta de un tercero'],
		['clave' => '30', 'descripcion' => 'Pago por escrito'],
		['clave' => '31', 'descripcion' => 'Pago por transferencia bancaria'],
		['clave' => '99', 'descripcion' => 'Por definir'],
	];
	return [ 'result' => true, 'data' => $formas ];
}

function GetMetodosPago() {
	$metodos = [
		['clave' => 'PUE', 'descripcion' => 'Pago en una sola exhibición'],
		['clave' => 'PPD', 'descripcion' => 'Pago en parcialidades o diferido'],
	];
	return [ 'result' => true, 'data' => $metodos ];
}

function GetEstados() {
	$estados = [
		['clave' => 'AGU', 'descripcion' => 'Aguascalientes'],
		['clave' => 'BCN', 'descripcion' => 'Baja California'],
		['clave' => 'BCS', 'descripcion' => 'Baja California Sur'],
		['clave' => 'CAM', 'descripcion' => 'Campeche'],
		['clave' => 'CHP', 'descripcion' => 'Chiapas'],
		['clave' => 'CHH', 'descripcion' => 'Chihuahua'],
		['clave' => 'COA', 'descripcion' => 'Coahuila'],
		['clave' => 'COL', 'descripcion' => 'Colima'],
		['clave' => 'CMX', 'descripcion' => 'Ciudad de México'],
		['clave' => 'DUR', 'descripcion' => 'Durango'],
		['clave' => 'GUA', 'descripcion' => 'Guanajuato'],
		['clave' => 'GRO', 'descripcion' => 'Guerrero'],
		['clave' => 'HID', 'descripcion' => 'Hidalgo'],
		['clave' => 'JAL', 'descripcion' => 'Jalisco'],
		['clave' => 'MEX', 'descripcion' => 'Estado de México'],
		['clave' => 'MIC', 'descripcion' => 'Michoacán'],
		['clave' => 'MOR', 'descripcion' => 'Morelos'],
		['clave' => 'NAY', 'descripcion' => 'Nayarit'],
		['clave' => 'NLE', 'descripcion' => 'Nuevo León'],
		['clave' => 'OAX', 'descripcion' => 'Oaxaca'],
		['clave' => 'PUE', 'descripcion' => 'Puebla'],
		['clave' => 'QUE', 'descripcion' => 'Querétaro'],
		['clave' => 'ROO', 'descripcion' => 'Quintana Roo'],
		['clave' => 'SLP', 'descripcion' => 'San Luis Potosí'],
		['clave' => 'SIN', 'descripcion' => 'Sinaloa'],
		['clave' => 'SON', 'descripcion' => 'Sonora'],
		['clave' => 'TAB', 'descripcion' => 'Tabasco'],
		['clave' => 'TAM', 'descripcion' => 'Tamaulipas'],
		['clave' => 'TLA', 'descripcion' => 'Tlaxcala'],
		['clave' => 'VER', 'descripcion' => 'Veracruz'],
		['clave' => 'YUC', 'descripcion' => 'Yucatán'],
		['clave' => 'ZAC', 'descripcion' => 'Zacatecas'],
	];
	return [ 'result' => true, 'data' => $estados ];
}

function GetUnidades() {
	$unidades = [
		['clave' => 'H87', 'descripcion' => 'Pieza'],
		['clave' => 'E48', 'descripcion' => 'Unidad de servicio'],
		['clave' => 'KGM', 'descripcion' => 'Kilogramo'],
		['clave' => 'MTR', 'descripcion' => 'Metro'],
		['clave' => 'LTR', 'descripcion' => 'Litro'],
		['clave' => 'HUR', 'descripcion' => 'Hora'],
		['clave' => 'DAY', 'descripcion' => 'Día'],
		['clave' => 'MON', 'descripcion' => 'Mes'],
		['clave' => 'ANN', 'descripcion' => 'Año'],
		['clave' => 'TMK', 'descripcion' => 'Tonelada métrica'],
		['clave' => 'GMK', 'descripcion' => 'Gramo'],
		['clave' => 'M2', 'descripcion' => 'Metro cuadrado'],
		['clave' => 'M3', 'descripcion' => 'Metro cúbico'],
		['clave' => 'LO', 'descripcion' => 'Lote'],
		['clave' => 'SET', 'descripcion' => 'Juego'],
		['clave' => 'PL', 'descripcion' => 'Página'],
		['clave' => 'BX', 'descripcion' => 'Caja'],
		['clave' => 'PA', 'descripcion' => 'Paquete'],
		['clave' => 'BT', 'descripcion' => 'Botella'],
		['clave' => 'BO', 'descripcion' => 'Barril'],
	];
	return [ 'result' => true, 'data' => $unidades ];
}

function GetEmisor() {
	$oDb = create_conex();
	$sql = "SELECT * FROM emisor_config ORDER BY id DESC LIMIT 1";
	$oDb->query($sql);
	$row = $oDb->getrow();
	$oDb->Close();
	return [ 'result' => true, 'data' => $row ];
}

function SaveEmisor() {
	$id                = filter_post( 'id' );
	$rfc               = strtoupper(trim(filter_post( 'rfc' )));
	$razon_social      = trim(filter_post( 'razon_social' ));
	$nombre_comercial  = trim(filter_post( 'nombre_comercial' ));
	$regimen_fiscal    = filter_post( 'regimen_fiscal' );
	$codigo_postal     = trim(filter_post( 'codigo_postal' ));
	$no_certificado    = trim(filter_post( 'no_certificado' ));
	$modo              = filter_post( 'modo' );

	if ( empty($rfc) || empty($razon_social) ) {
		return [ 'result' => false, 'message' => 'RFC y Razón Social son obligatorios' ];
	}

	$oDb = create_conex();

	if ( !empty($id) ) {
		$sql = "UPDATE emisor_config SET rfc=?, razon_social=?, nombre_comercial=?, 
		                regimen_fiscal=?, codigo_postal=?, no_certificado=?, modo=? WHERE id=?";
		$params = [$rfc, $razon_social, $nombre_comercial, $regimen_fiscal, 
		           $codigo_postal, $no_certificado, $modo, $id];
	} else {
		$sql = "INSERT INTO emisor_config (rfc, razon_social, nombre_comercial, regimen_fiscal, 
		                codigo_postal, no_certificado, modo) VALUES (?,?,?,?,?,?,?)";
		$params = [$rfc, $razon_social, $nombre_comercial, $regimen_fiscal, 
		           $codigo_postal, $no_certificado, $modo];
	}

	$success = $oDb->bind_params($sql, $params, true);
	$oDb->Close();

	return $success ? [ 'result' => true ] : [ 'result' => false, 'message' => 'Error al guardar la configuración del emisor' ];
}
?>