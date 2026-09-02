<?php

class CfdiBuilder {

    private $namespaces = [
        'cfdi'  => 'http://www.sat.gob.mx/cfd/4',
        'tfd'   => 'http://www.sat.gob.mx/TimbreFiscalDigital',
        'xsi'   => 'http://www.w3.org/2001/XMLSchema-instance',
        'xs'    => 'http://www.w3.org/2001/XMLSchema',
    ];

    public function generar( $factura, $conceptos, $emisor ) {
        $version = '4.0';
        $fecha = date('Y-m-d\TH:i:s');
        $lugarExpedicion = $emisor['codigo_postal'];
        $noCertificado = $emisor['no_certificado'];

        $subtotal  = number_format($factura['subtotal'], 6, '.', '');
        $descuento = number_format($factura['descuento'], 6, '.', '');
        $total     = number_format($factura['total'], 6, '.', '');
        $totalImpuestos = number_format($factura['total_iva'], 6, '.', '');

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<cfdi:Comprobante' . "\n";
        $xml .= '    xmlns:cfdi="' . $this->namespaces['cfdi'] . '"' . "\n";
        $xml .= '    xmlns:xsi="' . $this->namespaces['xsi'] . '"' . "\n";
        $xml .= '    xsi:schemaLocation="' . $this->namespaces['cfdi'] . ' http://www.sat.gob.mx/sitio_internet/cfd/4/cfdv40.xsd"' . "\n";
        $xml .= '    Version="' . $version . '"' . "\n";
        $xml .= '    Serie="' . htmlspecialchars($factura['serie']) . '"' . "\n";
        $xml .= '    Folio="' . $factura['folio'] . '"' . "\n";
        $xml .= '    Fecha="' . $fecha . '"' . "\n";
        $xml .= '    FormaPago="' . $factura['forma_pago'] . '"' . "\n";
        $xml .= '    NoCertificado="' . $noCertificado . '"' . "\n";
        $xml .= '    SubTotal="' . $subtotal . '"' . "\n";
        if ( $factura['descuento'] > 0 ) {
            $xml .= '    Descuento="' . $descuento . '"' . "\n";
        }
        $xml .= '    Total="' . $total . '"' . "\n";
        $xml .= '    TipoDeComprobante="I"' . "\n";
        $xml .= '    MetodoPago="' . $factura['metodo_pago'] . '"' . "\n";
        $xml .= '    LugarExpedicion="' . $lugarExpedicion . '"' . "\n";
        $xml .= '    Exportacion="01"' . "\n";
        $xml .= '    Moneda="MXN"' . "\n";
        $xml .= '    Sello=""' . "\n";
        $xml .= '>' . "\n";

        $xml .= $this->buildEmisor( $emisor );
        $xml .= $this->buildReceptor( $factura );
        $xml .= $this->buildConceptos( $conceptos );
        $xml .= $this->buildImpuestos( $factura, $conceptos );
        $xml .= $this->buildComplemento();

        $xml .= '</cfdi:Comprobante>';

        return $xml;
    }

    private function buildEmisor( $emisor ) {
        $xml  = '  <cfdi:Emisor' . "\n";
        $xml .= '      Rfc="' . htmlspecialchars($emisor['rfc']) . '"' . "\n";
        $xml .= '      Nombre="' . htmlspecialchars($emisor['razon_social']) . '"' . "\n";
        $xml .= '      RegimenFiscal="' . $emisor['regimen_fiscal'] . '"' . "\n";
        $xml .= '  />' . "\n";
        return $xml;
    }

    private function buildReceptor( $factura ) {
        $xml  = '  <cfdi:Receptor' . "\n";
        $xml .= '      Rfc="' . htmlspecialchars($factura['rfc_cliente']) . '"' . "\n";
        $xml .= '      Nombre="' . htmlspecialchars($factura['nombre_cliente']) . '"' . "\n";
        $xml .= '      RegimenFiscalReceptor="' . $factura['regimen_fiscal_receptor'] . '"' . "\n";
        $xml .= '      UsoCFDI="' . $factura['uso_cfdi'] . '"' . "\n";
        $xml .= '      DomicilioFiscalReceptor="' . ($factura['cli_cp'] ?? '') . '"' . "\n";
        $xml .= '  />' . "\n";
        return $xml;
    }

    private function buildConceptos( $conceptos ) {
        $xml  = '  <cfdi:Conceptos>' . "\n";

        foreach ( $conceptos as $c ) {
            $claveProdServ = $c['clave_prod_serv'];
            $claveUnidad   = $c['clave_unidad'];
            $descripcion   = htmlspecialchars($c['descripcion']);
            $cantidad      = number_format($c['cantidad'], 6, '.', '');
            $valorUnitario = number_format($c['valor_unitario'], 6, '.', '');
            $importe       = number_format($c['importe'], 6, '.', '');
            $descuento     = number_format($c['descuento'], 6, '.', '');

            $xml .= '    <cfdi:Concepto' . "\n";
            $xml .= '        ClaveProdServ="' . $claveProdServ . '"' . "\n";
            $xml .= '        Cantidad="' . $cantidad . '"' . "\n";
            $xml .= '        ClaveUnidad="' . $claveUnidad . '"' . "\n";
            $xml .= '        Descripcion="' . $descripcion . '"' . "\n";
            $xml .= '        ValorUnitario="' . $valorUnitario . '"' . "\n";
            $xml .= '        Importe="' . $importe . '"' . "\n";
            if ( $c['descuento'] > 0 ) {
                $xml .= '        Descuento="' . $descuento . '"' . "\n";
            }
            $xml .= '        ObjetoImp="002"' . "\n";
            $xml .= '    >' . "\n";

            $xml .= '      <cfdi:Impuestos>' . "\n";
            $xml .= '        <cfdi:Traslados>' . "\n";
            $xml .= '          <cfdi:Traslado' . "\n";
            $xml .= '              Base="' . number_format($c['base_iva'], 6, '.', '') . '"' . "\n";
            $xml .= '              Impuesto="002"' . "\n";
            $xml .= '              TipoFactor="Tasa"' . "\n";
            $xml .= '              TasaOCuota="' . number_format($c['tasa_iva'], 6, '.', '') . '"' . "\n";
            $xml .= '              Importe="' . number_format($c['importe_iva'], 6, '.', '') . '"' . "\n";
            $xml .= '          />' . "\n";
            $xml .= '        </cfdi:Traslados>' . "\n";
            $xml .= '      </cfdi:Impuestos>' . "\n";

            $xml .= '    </cfdi:Concepto>' . "\n";
        }

        $xml .= '  </cfdi:Conceptos>' . "\n";
        return $xml;
    }

    private function buildImpuestos( $factura, $conceptos ) {
        $totalImpuestosTrasladados = number_format($factura['total_iva'], 6, '.', '');

        $xml  = '  <cfdi:Impuestos' . "\n";
        $xml .= '      TotalImpuestosTrasladados="' . $totalImpuestosTrasladados . '"' . "\n";
        $xml .= '  >' . "\n";
        $xml .= '    <cfdi:Traslados>' . "\n";
        $xml .= '      <cfdi:Traslado' . "\n";
        $xml .= '          Base="' . number_format($factura['subtotal'] - $factura['descuento'], 6, '.', '') . '"' . "\n";
        $xml .= '          Impuesto="002"' . "\n";
        $xml .= '          TipoFactor="Tasa"' . "\n";
        $xml .= '          TasaOCuota="0.160000"' . "\n";
        $xml .= '          Importe="' . $totalImpuestosTrasladados . '"' . "\n";
        $xml .= '      />' . "\n";
        $xml .= '    </cfdi:Traslados>' . "\n";
        $xml .= '  </cfdi:Impuestos>' . "\n";
        return $xml;
    }

    private function buildComplemento() {
        $uuid = strtoupper( sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        ));

        $xml  = '  <cfdi:Complemento>' . "\n";
        $xml .= '    <tfd:TimbreFiscalDigital' . "\n";
        $xml .= '        xmlns:tfd="' . $this->namespaces['tfd'] . '"' . "\n";
        $xml .= '        xsi:schemaLocation="' . $this->namespaces['tfd'] . ' http://www.sat.gob.mx/sitio_internet/cfd/TimbreFiscalDigital/TimbreFiscalDigitalv11.xsd"' . "\n";
        $xml .= '        Version="1.1"' . "\n";
        $xml .= '        UUID="' . $uuid . '"' . "\n";
        $xml .= '        FechaTimbrado="' . date('Y-m-d\TH:i:s') . '"' . "\n";
        $xml .= '        RfcProvCertif="EKU9003173C9"' . "\n";
        $xml .= '        SelloCFD=""' . "\n";
        $xml .= '        SelloSAT=""' . "\n";
        $xml .= '    />' . "\n";
        $xml .= '  </cfdi:Complemento>' . "\n";
        return $xml;
    }

    public function firmarCsd( $xml, $emisor ) {
        if ( empty($emisor['csd_path']) || empty($emisor['csd_key_path']) ) {
            return base64_encode( 'SIN_FIRMA_CSD_CONFIGURADA' );
        }

        $certFile = $emisor['csd_path'];
        $keyFile  = $emisor['csd_key_path'];
        $password = $emisor['csd_password'];

        if ( !file_exists($certFile) || !file_exists($keyFile) ) {
            return base64_encode( 'ARCHIVOS_CSD_NO_ENCONTRADOS' );
        }

        $fp = fopen($keyFile, 'r');
        $key = fread($fp, filesize($keyFile));
        fclose($fp);

        $key = openssl_pkey_get_private($key, $password);

        $signature = '';
        openssl_sign($xml, $signature, $key, OPENSSL_ALGO_SHA256);
        openssl_free_key($key);

        return base64_encode($signature);
    }

    public function xmlToDOM( $xmlString ) {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xmlString);
        return $dom;
    }

    public function getUuidFromXml( $xmlString ) {
        $dom = new DOMDocument();
        $dom->loadXML($xmlString);
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//tfd:TimbreFiscalDigital/@UUID');
        if ( $nodes->length > 0 ) {
            return $nodes->item(0)->nodeValue;
        }
        return null;
    }
}
