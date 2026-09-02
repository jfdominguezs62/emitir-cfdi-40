<?php 
include ( '../constants.php' );
Constants::setpath_root  ("../");
Constants::create_filejs( true );

include ( Constants::getpath_root() . 'config.php' );
include ( Constants::getpath_tweb() . 'core.php' );
include ( Constants::getpath_root() . 'helpers.php' );

$nombreUsuario = '';
$rolUsuario = 'operador';
include('view_header.php');
?>

<h4 class="fw-bold text-dark mb-4"><i class="fas fa-file-invoice me-2"></i>Emisión de Facturas CFDI</h4>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex align-items-center gap-3">
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="chk-ocultar-canceladas" onchange="renderFacturas(facturasData)">
      <label class="form-check-label fw-bold text-muted" for="chk-ocultar-canceladas">Ocultar canceladas</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" id="chk-filtrar-fecha" onchange="renderFacturas(facturasData)">
      <label class="form-check-label fw-bold text-muted" for="chk-filtrar-fecha">Filtrar por fecha</label>
      <input type="date" class="form-control form-control-sm d-inline-block w-auto ms-2" id="filtro-fecha" onchange="if($('#chk-filtrar-fecha').is(':checked')) renderFacturas(facturasData)">
    </div>
  </div>
  <button class="btn btn-success btn-sm fw-bold" onclick="openNuevaFactura()"><i class="fas fa-plus me-1"></i>Nueva Factura</button>
</div>

<!-- Lista de Facturas -->
<div id="lista-facturas">
  <div class="card border-0 shadow-sm" style="border-radius: 12px;">
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead>
          <tr style="background: #f8f9fa;">
            <th style="width:30px;"></th>
            <th style="width:50px;">#</th>
            <th>Serie-Folio</th>
            <th>Cliente</th>
            <th>Fecha</th>
            <th class="text-end">Total</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="facturas-body"></tbody>
        <tfoot id="facturas-foot"></tfoot>
      </table>
    </div>
  </div>
</div>

<!-- Editor de Factura -->
<div id="editor-factura" style="display:none;">
  <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center" style="border-radius: 12px 12px 0 0;">
      <h5 class="mb-0 fw-bold"><i class="fas fa-file-invoice me-2"></i><span id="ed-titulo">Nueva Factura</span></h5>
      <button class="btn btn-sm btn-outline-light" onclick="cerrarEditor()"><i class="fas fa-arrow-left me-1"></i>Volver</button>
    </div>
    <div class="card-body">
      <div class="row mb-3">
        <div class="col-md-3">
          <label class="form-label">Serie *</label>
          <select class="form-select" id="ed-serie">
            <option value="A">A</option>
            <option value="B">B</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Forma de Pago *</label>
          <select class="form-select" id="ed-forma_pago"></select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Método de Pago *</label>
          <select class="form-select" id="ed-metodo_pago"></select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Cliente *</label>
          <select class="form-select" id="ed-cliente"></select>
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-12">
          <label class="form-label fw-bold">Folio: <span id="ed-folio-display" class="text-primary">--</span></label>
        </div>
      </div>
    </div>
  </div>

  <!-- Conceptos -->
  <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
      <h6 class="mb-0 fw-bold"><i class="fas fa-list me-1"></i>Conceptos</h6>
      <button class="btn btn-sm btn-success" onclick="openConceptoModal()"><i class="fas fa-plus me-1"></i>Agregar Concepto</button>
    </div>
    <div class="card-body p-0">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Descripción</th>
            <th>Cantidad</th>
            <th>V. Unitario</th>
            <th>Importe</th>
            <th>IVA</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="conceptos-body"></tbody>
      </table>
    </div>
  </div>

  <!-- Totales -->
  <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
    <div class="card-body">
      <div class="row justify-content-end">
        <div class="col-md-4">
          <table class="table table-sm mb-0">
            <tr><td class="text-end fw-bold">Subtotal:</td><td class="text-end" id="ed-subtotal">$0.00</td></tr>
            <tr><td class="text-end fw-bold">Descuento:</td><td class="text-end" id="ed-descuento">-$0.00</td></tr>
            <tr><td class="text-end fw-bold">IVA (16%):</td><td class="text-end" id="ed-iva">$0.00</td></tr>
            <tr class="table-primary"><td class="text-end fw-bold fs-5">Total:</td><td class="text-end fw-bold fs-5" id="ed-total">$0.00</td></tr>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Acciones -->
  <div class="d-flex justify-content-between">
    <button class="btn btn-danger" onclick="cancelarFactura()"><i class="fas fa-times me-1"></i>Cancelar Factura</button>
    <button class="btn btn-primary btn-lg fw-bold" onclick="emitirFactura()"><i class="fas fa-paper-plane me-1"></i>Emitir CFDI</button>
  </div>
</div>

<!-- Modal Concepto -->
<div class="modal fade" id="conceptoModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius: 12px;">
      <div class="modal-header" style="background: linear-gradient(135deg, #1b5e20, #2e7d32); color: white;">
        <h5 class="modal-title fw-bold">Agregar Concepto</h5>
        <button type="button" class="btn-close btn-close-white" onclick="$('#conceptoModal').modal('hide')"></button>
      </div>
      <div class="modal-body">
        <form id="form-concepto">
          <div class="mb-3">
            <label class="form-label">Descripción *</label>
            <input type="text" class="form-control" id="c-descripcion" required placeholder="Descripción del producto o servicio">
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Cantidad *</label>
              <input type="number" class="form-control" id="c-cantidad" value="1" min="0.001" step="0.001" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Valor Unitario *</label>
              <input type="number" class="form-control" id="c-valor_unitario" value="0" min="0" step="0.01" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Descuento</label>
              <input type="number" class="form-control" id="c-descuento" value="0" min="0" step="0.01">
            </div>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Clave Prod/Serv</label>
              <select class="form-select" id="c-clave_prod_serv">
                <option value="84111506">84111506 - Servicios de facturación</option>
                <option value="43232300">43232300 - Software de aplicación</option>
                <option value="80101500">80101500 - Servicios de consultoría</option>
                <option value="80101600">80101600 - Servicios de administración</option>
                <option value="80101800">80101800 - Servicios de gestión empresarial</option>
                <option value="82101500">82101500 - Servicios de publicidad</option>
                <option value="82121500">82121500 - Servicios de impresión</option>
                <option value="86101500">86101500 - Servicios de transporte</option>
                <option value="93151500">93151500 - Servicios de mantenimiento</option>
                <option value="81112200">81112200 - Servicios de desarrollo de software</option>
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Clave Unidad</label>
              <select class="form-select" id="c-clave_unidad"></select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Importe:</label>
              <div class="form-control bg-light" id="c-importe">$0.00</div>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">IVA (16%):</label>
              <div class="form-control bg-light" id="c-iva">$0.00</div>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Total Concepto:</label>
              <div class="form-control bg-light fw-bold" id="c-total_concepto">$0.00</div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" onclick="$('#conceptoModal').modal('hide')"><i class="fas fa-times me-1"></i>Cancelar</button>
        <button class="btn btn-primary" onclick="saveConcepto()"><i class="fas fa-save me-1"></i>Agregar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Ver XML -->
<div class="modal fade" id="xmlModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius: 12px;">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title fw-bold">XML CFDI Generado</h5>
        <button type="button" class="btn-close btn-close-white" onclick="$('#xmlModal').modal('hide')"></button>
      </div>
      <div class="modal-body">
        <pre id="xml-content" style="max-height: 500px; overflow: auto; background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 8px; font-size: 12px;"></pre>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" onclick="$('#xmlModal').modal('hide')">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
var facturasData = [];
var currentFacturaId = null;
var currentConceptos = [];

function loadFacturas() {
    MsgServer( path.model + 'facturas.php', function(dat) {
        if (dat.result) {
            facturasData = dat.data;
            renderFacturas(dat.data);
        }
    }, { action: 'getfacturas' });
}

function renderFacturas(data) {
    var ocultarCanceladas = $('#chk-ocultar-canceladas').is(':checked');
    var filtrarFecha = $('#chk-filtrar-fecha').is(':checked');
    var fechaFiltro = $('#filtro-fecha').val();
    var filtered = data.filter(function(f) {
        if (ocultarCanceladas && f.estado === 'cancelada') return false;
        if (filtrarFecha && fechaFiltro && f.fecha.indexOf(fechaFiltro) !== 0) return false;
        return true;
    });
    var html = '';
    filtered.forEach(function(f) {
        var estadoBadge = '';
        if (f.estado === 'borrador') estadoBadge = '<span class="badge bg-warning text-dark">Borrador</span>';
        else if (f.estado === 'timbrada') estadoBadge = '<span class="badge bg-success">Timbrada</span>';
        else if (f.estado === 'cancelada') estadoBadge = '<span class="badge bg-danger">Cancelada</span>';

        html += '<tr>';
        html += '<td>' + f.id + '</td>';
        html += '<td><strong>' + f.serie + '-' + f.folio + '</strong></td>';
        html += '<td>' + f.fecha + '</td>';
        html += '<td>' + f.rfc_cliente + ' - ' + f.razon_social + '</td>';
        html += '<td class="fw-bold">$' + parseFloat(f.total).toFixed(2) + '</td>';
        html += '<td>' + estadoBadge + '</td>';
        html += '<td><code style="font-size:10px;">' + (f.uuid || '-') + '</code></td>';
        html += '<td>';
        html += '<button class="btn btn-sm btn-info me-1" onclick="verFactura(' + f.id + ')" title="Ver detalle"><i class="fas fa-eye"></i></button>';
        if (f.estado === 'borrador') {
            html += '<button class="btn btn-sm btn-success me-1" onclick="editarFactura(' + f.id + ')" title="Editar"><i class="fas fa-edit"></i></button>';
        }
        html += '</td></tr>';
    });
    $('#facturas-body').html(html || '<tr><td colspan="8" class="text-center text-muted">Sin facturas</td></tr>');
}

function openNuevaFactura() {
    currentFacturaId = null;
    currentConceptos = [];
    $('#lista-facturas').hide();
    $('#editor-factura').show();
    $('#conceptos-body').html('');
    actualizarTotalesUI();
    loadEditorCatalogos(function() {
        $('#ed-cliente').val('');
        $('#ed-forma_pago').val('03');
        $('#ed-metodo_pago').val('PUE');
        $('#ed-serie').val('A');
        $('#ed-folio-display').text('--');
        $('#ed-titulo').text('Nueva Factura');
    });
}

function editarFactura(id) {
    MsgServer( path.model + 'facturas.php', function(dat) {
        if (dat.result) {
            var f = dat.data;
            currentFacturaId = f.id;
            currentConceptos = f.conceptos || [];
            $('#lista-facturas').hide();
            $('#editor-factura').show();
            $('#ed-titulo').text('Factura ' + f.serie + '-' + f.folio);
            $('#ed-folio-display').text(f.serie + '-' + f.folio);

            loadEditorCatalogos(function() {
                $('#ed-serie').val(f.serie);
                $('#ed-forma_pago').val(f.forma_pago);
                $('#ed-metodo_pago').val(f.metodo_pago);
                $('#ed-cliente').val(f.cliente_id);
                renderConceptos();
                actualizarTotalesUI();
            });
        }
    }, { action: 'getfactura', id: id });
}

function verFactura(id) {
    MsgServer( path.model + 'facturas.php', function(dat) {
        if (dat.result) {
            var f = dat.data;
            currentFacturaId = f.id;
            currentConceptos = f.conceptos || [];
            $('#lista-facturas').hide();
            $('#editor-factura').show();
            $('#ed-titulo').text('Factura ' + f.serie + '-' + f.folio);
            $('#ed-folio-display').text(f.serie + '-' + f.folio);

            loadEditorCatalogos(function() {
                $('#ed-serie').val(f.serie).prop('disabled', true);
                $('#ed-forma_pago').val(f.forma_pago).prop('disabled', true);
                $('#ed-metodo_pago').val(f.metodo_pago).prop('disabled', true);
                $('#ed-cliente').val(f.cliente_id).prop('disabled', true);
                renderConceptos();
                actualizarTotalesUI();
            });
        }
    }, { action: 'getfactura', id: id });
}

function cerrarEditor() {
    $('#lista-facturas').show();
    $('#editor-factura').hide();
    $('#ed-serie, #ed-forma_pago, #ed-metodo_pago, #ed-cliente').prop('disabled', false);
    currentFacturaId = null;
    loadFacturas();
}

function loadEditorCatalogos(callback) {
    var loaded = 0;
    var total = 3;
    var clientes = [];
    var formas = [];
    var metodos = [];
    var unidades = [];

    MsgServer(path.model + 'clientes.php', function(dat) {
        if (dat.result) clientes = dat.data;
        if (++loaded === total) build();
    }, { action: 'getclientes' });

    MsgServer(path.model + 'catalogos.php', function(dat) {
        if (dat.result) formas = dat.data;
        if (++loaded === total) build();
    }, { action: 'getformas_pago' });

    MsgServer(path.model + 'catalogos.php', function(dat) {
        if (dat.result) metodos = dat.data;
        if (++loaded === total) build();
    }, { action: 'getmetodos_pago' });

    function build() {
        var selCli = $('#ed-cliente');
        selCli.empty().append('<option value="">-- Seleccionar Cliente --</option>');
        clientes.forEach(function(c) {
            selCli.append('<option value="' + c.id + '">' + c.rfc + ' - ' + c.razon_social + '</option>');
        });

        var selForma = $('#ed-forma_pago');
        selForma.empty();
        formas.forEach(function(f) {
            selForma.append('<option value="' + f.clave + '">' + f.clave + ' - ' + f.descripcion + '</option>');
        });

        var selMet = $('#ed-metodo_pago');
        selMet.empty();
        metodos.forEach(function(m) {
            selMet.append('<option value="' + m.clave + '">' + m.clave + ' - ' + m.descripcion + '</option>');
        });

        // Load unidades for concepto modal
        MsgServer(path.model + 'catalogos.php', function(dat) {
            if (dat.result) {
                unidades = dat.data;
                var selUn = $('#c-clave_unidad');
                selUn.empty();
                unidades.forEach(function(u) {
                    selUn.append('<option value="' + u.clave + '">' + u.clave + ' - ' + u.descripcion + '</option>');
                });
            }
            if (callback) callback();
        }, { action: 'getunidades' });
    }
}

function renderConceptos() {
    var html = '';
    var idx = 1;
    currentConceptos.forEach(function(c) {
        html += '<tr>';
        html += '<td>' + idx++ + '</td>';
        html += '<td>' + c.descripcion + '</td>';
        html += '<td>' + parseFloat(c.cantidad).toFixed(2) + '</td>';
        html += '<td>$' + parseFloat(c.valor_unitario).toFixed(2) + '</td>';
        html += '<td>$' + parseFloat(c.importe).toFixed(2) + '</td>';
        html += '<td>$' + parseFloat(c.importe_iva).toFixed(2) + '</td>';
        html += '<td>';
        if (!currentFacturaId || $('#ed-serie').prop('disabled') === false) {
            html += '<button class="btn btn-sm btn-danger" onclick="deleteConcepto(' + c.id + ')"><i class="fas fa-trash"></i></button>';
        }
        html += '</td></tr>';
    });
    $('#conceptos-body').html(html || '<tr><td colspan="7" class="text-center text-muted">Sin conceptos</td></tr>');
}

function openConceptoModal() {
    if (!currentFacturaId) {
        MsgNotify('Primero debe crear la factura (se creará al guardar el primer concepto)', 'info');
        crearFacturaYAgregarConcepto();
        return;
    }
    $('#form-concepto')[0].reset();
    $('#c-cantidad').val(1);
    $('#c-valor_unitario').val(0);
    $('#c-descuento').val(0);
    calcConceptoImportes();
    $('#conceptoModal').modal('show');
}

function crearFacturaYAgregarConcepto() {
    var aPar = {
        action: 'nueva',
        cliente_id: $('#ed-cliente').val(),
        serie: $('#ed-serie').val(),
        forma_pago: $('#ed-forma_pago').val(),
        metodo_pago: $('#ed-metodo_pago').val()
    };
    MsgServer(path.model + 'facturas.php', function(dat) {
        if (dat.result) {
            currentFacturaId = dat.factura_id;
            $('#ed-folio-display').text($('#ed-serie').val() + '-' + dat.folio);
            $('#ed-titulo').text('Factura ' + $('#ed-serie').val() + '-' + dat.folio);
            MsgNotify('Factura creada. Ahora agregue los conceptos.', 'success');
            calcConceptoImportes();
            $('#conceptoModal').modal('show');
        } else {
            MsgNotify(dat.message || 'Error al crear factura', 'danger');
        }
    }, aPar);
}

function calcConceptoImportes() {
    var cant = parseFloat($('#c-cantidad').val()) || 0;
    var vu   = parseFloat($('#c-valor_unitario').val()) || 0;
    var desc = parseFloat($('#c-descuento').val()) || 0;
    var imp  = cant * vu;
    var base = imp - desc;
    var iva  = base * 0.16;
    var tot  = base + iva;
    $('#c-importe').text('$' + imp.toFixed(2));
    $('#c-iva').text('$' + iva.toFixed(2));
    $('#c-total_concepto').text('$' + tot.toFixed(2));
}

$('#c-cantidad, #c-valor_unitario, #c-descuento').on('input', calcConceptoImportes);

function saveConcepto() {
    if (!currentFacturaId) {
        MsgNotify('Error: No se ha creado la factura todavía', 'danger');
        return;
    }
    var desc = $('#c-descripcion').val();
    var cant = $('#c-cantidad').val();
    var vu   = $('#c-valor_unitario').val();
    if (!desc || !cant || !vu) {
        MsgNotify('Complete todos los campos obligatorios', 'warning');
        return;
    }
    var aPar = {
        action: 'saveconcepto',
        factura_id: currentFacturaId,
        clave_prod_serv: $('#c-clave_prod_serv').val(),
        clave_unidad: $('#c-clave_unidad').val(),
        descripcion: desc,
        cantidad: cant,
        valor_unitario: vu,
        descuento: $('#c-descuento').val()
    };
    MsgServer(path.model + 'facturas.php', function(dat) {
        if (dat.result) {
            MsgNotify('Concepto agregado', 'success');
            $('#conceptoModal').modal('hide');
            verFactura(currentFacturaId);
        } else {
            MsgNotify(dat.message || 'Error', 'danger');
        }
    }, aPar);
}

function deleteConcepto(conceptoId) {
    Swal.fire({
        title: '¿Eliminar este concepto?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar'
    }).then(function(result) {
        if (result.isConfirmed) {
            MsgServer(path.model + 'facturas.php', function(dat) {
                if (dat.result) {
                    MsgNotify('Concepto eliminado', 'success');
                    verFactura(currentFacturaId);
                } else {
                    MsgNotify(dat.message || 'Error', 'danger');
                }
            }, { action: 'deleteconcepto', concepto_id: conceptoId, factura_id: currentFacturaId });
        }
    });
}

function actualizarTotalesUI() {
    var subtotal = 0, descuento = 0, iva = 0;
    currentConceptos.forEach(function(c) {
        subtotal += parseFloat(c.importe) || 0;
        descuento += parseFloat(c.descuento) || 0;
        iva += parseFloat(c.importe_iva) || 0;
    });
    var total = subtotal - descuento + iva;
    $('#ed-subtotal').text('$' + subtotal.toFixed(2));
    $('#ed-descuento').text('-$' + descuento.toFixed(2));
    $('#ed-iva').text('$' + iva.toFixed(2));
    $('#ed-total').text('$' + total.toFixed(2));
}

function emitirFactura() {
    if (!currentFacturaId) {
        MsgNotify('No hay factura para emitir', 'error');
        return;
    }
    if (currentConceptos.length === 0) {
        MsgNotify('Agregue al menos un concepto', 'error');
        return;
    }

    Swal.fire({
        title: '¿Emitir este CFDI?',
        text: 'Se generará el XML y se enviará al PAC para timbrado',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1b5e20',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, emitir'
    }).then(function(result) {
        if (result.isConfirmed) {
            MsgServer(path.model + 'facturas.php', function(dat) {
                if (dat.result) {
                    MsgNotify('CFDI emitido correctamente', 'success');
                    if (dat.xml) {
                        var formatted = formatXml(dat.xml);
                        $('#xml-content').text(formatted);
                        $('#xmlModal').modal('show');
                    }
                    cerrarEditor();
                } else {
                    MsgNotify(dat.message || 'Error al emitir', 'danger');
                }
            }, { action: 'emitir', factura_id: currentFacturaId });
        }
    });
}

function cancelarFactura() {
    if (!currentFacturaId) {
        cerrarEditor();
        return;
    }
    Swal.fire({
        title: '¿Cancelar esta factura?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, cancelar'
    }).then(function(result) {
        if (result.isConfirmed) {
            MsgServer(path.model + 'facturas.php', function(dat) {
                if (dat.result) {
                    MsgNotify('Factura cancelada', 'success');
                    cerrarEditor();
                } else {
                    MsgNotify(dat.message || 'Error', 'danger');
                }
            }, { action: 'cancelar', factura_id: currentFacturaId });
        }
    });
}

function formatXml(xml) {
    var formatted = '';
    var indent = '';
    xml.split(/>\s*</).forEach(function(node) {
        if (node.match(/^\/\w/)) indent = indent.substring(2);
        formatted += indent + '<' + node + '>\n';
        if (node.match(/^<?\w[^>]*[^\/]$/) && !node.startsWith('?')) indent += '  ';
    });
    return formatted.substring(1, formatted.length - 2);
}

$(function() { loadFacturas(); });
</script>

<?php include('view_footer.php'); ?>
