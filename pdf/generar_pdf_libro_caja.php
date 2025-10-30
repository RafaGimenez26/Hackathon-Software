<?php
session_start();
require_once('../conexion.php');
require_once('funciones_pdf_comunes.php');

if (!isset($_SESSION['ProductorID'])) {
    die('Acceso no autorizado');
}

$productor_id = $_SESSION['ProductorID'];

// Obtener fechas
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : date('Y-m-01');
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : date('Y-m-d');

// Convertir a timestamps para MongoDB
$timestamp_desde = strtotime($fecha_desde . ' 00:00:00') * 1000;
$timestamp_hasta = strtotime($fecha_hasta . ' 23:59:59') * 1000;

$fecha_desde_mongo = new MongoDB\BSON\UTCDateTime($timestamp_desde);
$fecha_hasta_mongo = new MongoDB\BSON\UTCDateTime($timestamp_hasta);

try {
    // Obtener datos del productor desde MySQL
    $sql = "SELECT * FROM productores WHERE ProductorID = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $productor_id);
    $stmt->execute();
    $productor = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$productor) {
        die('Productor no encontrado');
    }
    
    // Colecciones MongoDB
    $ventasCollection = $database->productos_vendidos;
    $gastosCollection = $database->gastos;
    
    // === SALDO ANTERIOR ===
    $ventas_anteriores = $ventasCollection->aggregate([
        [
            '$match' => [
                'vendedor.productor_id' => (int)$productor_id,
                'fecha_venta' => ['$lt' => $fecha_desde_mongo]
            ]
        ],
        [
            '$group' => [
                '_id' => null,
                'total' => ['$sum' => '$monto_total']
            ]
        ]
    ])->toArray();
    
    $gastos_anteriores = $gastosCollection->aggregate([
        [
            '$match' => [
                'productor_id' => (int)$productor_id,
                'fecha_gasto' => ['$lt' => $fecha_desde_mongo]
            ]
        ],
        [
            '$group' => [
                '_id' => null,
                'total' => ['$sum' => '$monto']
            ]
        ]
    ])->toArray();
    
    $total_ventas_anteriores = !empty($ventas_anteriores) ? $ventas_anteriores[0]['total'] : 0;
    $total_gastos_anteriores = !empty($gastos_anteriores) ? $gastos_anteriores[0]['total'] : 0;
    $saldo_anterior = $total_ventas_anteriores - $total_gastos_anteriores;
    
    // === MOVIMIENTOS DEL PERÍODO ===
    $ventas_periodo = $ventasCollection->find([
        'vendedor.productor_id' => (int)$productor_id,
        'fecha_venta' => [
            '$gte' => $fecha_desde_mongo,
            '$lte' => $fecha_hasta_mongo
        ]
    ], [
        'sort' => ['fecha_venta' => 1]
    ])->toArray();
    
    $gastos_periodo = $gastosCollection->find([
        'productor_id' => (int)$productor_id,
        'fecha_gasto' => [
            '$gte' => $fecha_desde_mongo,
            '$lte' => $fecha_hasta_mongo
        ]
    ], [
        'sort' => ['fecha_gasto' => 1]
    ])->toArray();
    
    // Calcular totales
    $total_ventas_periodo = 0;
    foreach ($ventas_periodo as $venta) {
        $total_ventas_periodo += $venta['monto_total'];
    }
    
    $total_gastos_periodo = 0;
    foreach ($gastos_periodo as $gasto) {
        $total_gastos_periodo += $gasto['monto'];
    }
    
    $saldo_periodo = $total_ventas_periodo - $total_gastos_periodo;
    $saldo_final = $saldo_anterior + $saldo_periodo;
    
    // Unificar movimientos
    $movimientos = [];
    
    foreach ($ventas_periodo as $venta) {
        $movimientos[] = [
            'fecha' => $venta['fecha_venta']->toDateTime(),
            'tipo' => 'ingreso',
            'concepto' => 'Venta: ' . ($venta['producto']['nombre'] ?? 'Producto'),
            'comprobante' => 'Pedido #' . substr((string)$venta['pedido_id'], -8),
            'cliente' => $venta['cliente']['nombre'] ?? 'Cliente',
            'debe' => $venta['monto_total'],
            'haber' => 0
        ];
    }
    
    foreach ($gastos_periodo as $gasto) {
        $movimientos[] = [
            'fecha' => $gasto['fecha_gasto']->toDateTime(),
            'tipo' => 'egreso',
            'concepto' => $gasto['concepto'],
            'comprobante' => $gasto['numero_comprobante'] ?? 'S/C',
            'cliente' => $gasto['proveedor'] ?? '-',
            'debe' => 0,
            'haber' => $gasto['monto']
        ];
    }
    
    // Ordenar por fecha
    usort($movimientos, function($a, $b) {
        return $a['fecha'] <=> $b['fecha'];
    });
    
    // Crear PDF
    $pdf = new PDFComun('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Configurar datos del productor
    $pdf->setDatosProductor([
        'nombre' => $productor['NombreRazonSocial'],
        'cuit' => $productor['CUIT_CUIL'],
        'telefono' => $productor['TelefonoContacto'],
        'correo' => $productor['CorreoElectronico'],
        'direccion' => $productor['DireccionEstablecimiento']
    ]);
    
    // Configuración del documento
    $pdf->SetCreator('AgroHub Misiones');
    $pdf->SetAuthor($productor['NombreRazonSocial']);
    $pdf->SetTitle('Libro de Caja - ' . date('d/m/Y', strtotime($fecha_desde)) . ' al ' . date('d/m/Y', strtotime($fecha_hasta)));
    $pdf->SetSubject('Libro Diario de Caja');
    
    // Configurar márgenes
    $pdf->SetMargins(10, 50, 10);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Agregar página
    $pdf->AddPage();
    
    // Título del documento
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(40, 116, 73);
    $pdf->Cell(0, 10, utf8_decode('LIBRO DE CAJA'), 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(2);
    
    // Período
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 6, utf8_decode('Período: ' . date('d/m/Y', strtotime($fecha_desde)) . ' al ' . date('d/m/Y', strtotime($fecha_hasta))), 0, 1, 'C');
    $pdf->Ln(5);
    
    // Resumen de saldos
    $pdf->SetFillColor(245, 245, 245);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 7, 'RESUMEN DEL PERIODO', 0, 1, 'L', true);
    $pdf->Ln(2);
    
    $pdf->SetFont('helvetica', '', 9);
    
    // Tabla de resumen
    $pdf->Cell(95, 6, 'Saldo Anterior:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetTextColor($saldo_anterior >= 0 ? 40 : 220, $saldo_anterior >= 0 ? 116 : 53, $saldo_anterior >= 0 ? 73 : 69);
    $pdf->Cell(0, 6, formatearMoneda($saldo_anterior), 0, 1, 'R');
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(95, 6, utf8_decode('Total Ingresos del Período:'), 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetTextColor(40, 116, 73);
    $pdf->Cell(0, 6, formatearMoneda($total_ventas_periodo), 0, 1, 'R');
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(95, 6, utf8_decode('Total Egresos del Período:'), 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetTextColor(220, 53, 69);
    $pdf->Cell(0, 6, formatearMoneda($total_gastos_periodo), 0, 1, 'R');
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(95, 6, utf8_decode('Resultado del Período:'), 0, 0, 'L');
    $pdf->SetTextColor($saldo_periodo >= 0 ? 40 : 220, $saldo_periodo >= 0 ? 116 : 53, $saldo_periodo >= 0 ? 73 : 69);
    $pdf->Cell(0, 6, formatearMoneda($saldo_periodo), 0, 1, 'R');
    
    $pdf->Ln(3);
    $pdf->SetDrawColor(40, 116, 73);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(2);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(95, 7, 'SALDO FINAL:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor($saldo_final >= 0 ? 40 : 220, $saldo_final >= 0 ? 116 : 53, $saldo_final >= 0 ? 73 : 69);
    $pdf->Cell(0, 7, formatearMoneda($saldo_final), 0, 1, 'R');
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(5);
    
    // Tabla de movimientos
    $pdf->SetFillColor(40, 116, 73);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 8);
    
    $pdf->Cell(20, 7, 'FECHA', 1, 0, 'C', true);
    $pdf->Cell(55, 7, 'CONCEPTO', 1, 0, 'C', true);
    $pdf->Cell(25, 7, 'COMPROB.', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'CLIENTE/PROV.', 1, 0, 'C', true);
    $pdf->Cell(22, 7, 'DEBE', 1, 0, 'C', true);
    $pdf->Cell(22, 7, 'HABER', 1, 0, 'C', true);
    $pdf->Cell(22, 7, 'SALDO', 1, 1, 'C', true);
    
    // Saldo anterior
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(135, 6, 'SALDO ANTERIOR', 1, 0, 'L', true);
    $pdf->Cell(22, 6, '-', 1, 0, 'C', true);
    $pdf->Cell(22, 6, '-', 1, 0, 'C', true);
    $pdf->SetTextColor($saldo_anterior >= 0 ? 40 : 220, $saldo_anterior >= 0 ? 116 : 53, $saldo_anterior >= 0 ? 73 : 69);
    $pdf->Cell(22, 6, formatearMoneda($saldo_anterior), 1, 1, 'R', true);
    
    // Movimientos
    $pdf->SetFont('helvetica', '', 7);
    $saldo_acumulado = $saldo_anterior;
    $fill = false;
    
    foreach ($movimientos as $mov) {
        if ($fill) {
            $pdf->SetFillColor(250, 250, 250);
        } else {
            $pdf->SetFillColor(255, 255, 255);
        }
        
        $saldo_acumulado += $mov['debe'] - $mov['haber'];
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(20, 5, $mov['fecha']->format('d/m/Y'), 1, 0, 'C', true);
        $pdf->Cell(55, 5, utf8_decode(substr($mov['concepto'], 0, 35)), 1, 0, 'L', true);
        $pdf->Cell(25, 5, utf8_decode(substr($mov['comprobante'], 0, 15)), 1, 0, 'C', true);
        $pdf->Cell(35, 5, utf8_decode(substr($mov['cliente'], 0, 20)), 1, 0, 'L', true);
        
        $pdf->SetTextColor(40, 116, 73);
        $pdf->Cell(22, 5, $mov['debe'] > 0 ? formatearMoneda($mov['debe']) : '-', 1, 0, 'R', true);
        
        $pdf->SetTextColor(220, 53, 69);
        $pdf->Cell(22, 5, $mov['haber'] > 0 ? formatearMoneda($mov['haber']) : '-', 1, 0, 'R', true);
        
        $pdf->SetTextColor($saldo_acumulado >= 0 ? 0 : 220, $saldo_acumulado >= 0 ? 0 : 53, $saldo_acumulado >= 0 ? 0 : 69);
        $pdf->Cell(22, 5, formatearMoneda($saldo_acumulado), 1, 1, 'R', true);
        
        $fill = !$fill;
    }
    
    // Totales
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(135, 6, 'TOTALES DEL PERIODO:', 1, 0, 'R', true);
    $pdf->SetTextColor(40, 116, 73);
    $pdf->Cell(22, 6, formatearMoneda($total_ventas_periodo), 1, 0, 'R', true);
    $pdf->SetTextColor(220, 53, 69);
    $pdf->Cell(22, 6, formatearMoneda($total_gastos_periodo), 1, 0, 'R', true);
    $pdf->SetTextColor($saldo_periodo >= 0 ? 40 : 220, $saldo_periodo >= 0 ? 116 : 53, $saldo_periodo >= 0 ? 73 : 69);
    $pdf->Cell(22, 6, formatearMoneda($saldo_periodo), 1, 1, 'R', true);
    
    // Saldo final
    $pdf->SetFillColor(52, 58, 64);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(179, 7, 'SALDO FINAL:', 1, 0, 'R', true);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor($saldo_final >= 0 ? 144 : 255, $saldo_final >= 0 ? 238 : 193, $saldo_final >= 0 ? 144 : 193);
    $pdf->Cell(22, 7, formatearMoneda($saldo_final), 1, 1, 'R', true);
    
    $pdf->Ln(8);
    
    // Nota informativa
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetFont('helvetica', 'I', 7);
    $pdf->MultiCell(0, 3, utf8_decode("NOTA: Este libro de caja refleja todos los movimientos de ingresos (ventas) y egresos (gastos) registrados en el sistema para el período indicado. Los saldos se calculan de forma acumulativa considerando el saldo anterior al período consultado."), 0, 'J');
    
    $pdf->Ln(8);
    
    // Línea de firma
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 4, '________________________________________', 0, 1, 'C');
    $pdf->Cell(0, 4, utf8_decode('Firma y Aclaración del Productor'), 0, 1, 'C');
    $pdf->SetFont('helvetica', 'I', 7);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 4, utf8_decode('Documento generado electrónicamente'), 0, 1, 'C');
    
    // Salida del PDF
    $nombreArchivo = 'Libro_Caja_' . date('Ymd', strtotime($fecha_desde)) . '_' . date('Ymd', strtotime($fecha_hasta)) . '.pdf';
    $pdf->Output($nombreArchivo, 'I');
    
} catch (Exception $e) {
    die('Error al generar PDF: ' . $e->getMessage());
}
?>