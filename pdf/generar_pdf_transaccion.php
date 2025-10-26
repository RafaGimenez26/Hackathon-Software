<?php
session_start();
require_once('../conexion.php');
require_once('funciones_pdf_comunes.php');

if (!isset($_SESSION['ProductorID']) || !isset($_GET['id'])) {
    die('Acceso no autorizado');
}

$productor_id = $_SESSION['ProductorID'];
$transaction_id = $_GET['id'];

try {
    // Obtener datos del productor desde MySQL
    $sql = "SELECT NombreRazonSocial, CUIT_CUIL, TelefonoContacto, CorreoElectronico, DireccionEstablecimiento 
            FROM productores WHERE ProductorID = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $productor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $productor = $result->fetch_assoc();
    $stmt->close();
    
    if (!$productor) {
        die('Productor no encontrado');
    }
    
    // Obtener transacción desde MongoDB
    $transaccionesCollection = $database->transacciones;
    $transaccion = $transaccionesCollection->findOne([
        'transaction_id' => $transaction_id,
        'productor_id' => $productor_id
    ]);
    
    if (!$transaccion) {
        die('Transacción no encontrada');
    }
    
    // Crear PDF
    $pdf = new PDFComun(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Configurar datos del productor
    $pdf->setDatosProductor([
        'nombre' => $productor['NombreRazonSocial'],
        'cuit' => $productor['CUIT_CUIL'],
        'telefono' => $productor['TelefonoContacto'],
        'correo' => $productor['CorreoElectronico'],
        'direccion' => $productor['DireccionEstablecimiento']
    ]);
    
    // Configuración del documento
    $tipo_doc = strtoupper($transaccion['tipo_operacion']) === 'ALTA' ? 'ALTA' : 'BAJA';
    $pdf->SetCreator('AgroHub Misiones');
    $pdf->SetAuthor($productor['NombreRazonSocial']);
    $pdf->SetTitle('Boleta de ' . $tipo_doc . ' de Stock - ' . $transaction_id);
    $pdf->SetSubject('Movimiento de Stock');
    
    // Configurar márgenes
    $pdf->SetMargins(15, 50, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(TRUE, 20);
    
    // Agregar página
    $pdf->AddPage();
    
    // Título del documento según tipo
    $pdf->SetFont('helvetica', 'B', 16);
    if (strtoupper($transaccion['tipo_operacion']) === 'ALTA') {
        $pdf->SetTextColor(25, 135, 84); // Verde
        $pdf->Cell(0, 10, utf8_decode('BOLETA DE ALTA DE STOCK'), 0, 1, 'C');
    } else {
        $pdf->SetTextColor(220, 53, 69); // Rojo
        $pdf->Cell(0, 10, utf8_decode('BOLETA DE BAJA DE STOCK'), 0, 1, 'C');
    }
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(3);
    
    // Información de la transacción
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(50, 6, utf8_decode('ID Transacción:'), 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, $transaction_id, 0, 1, 'L');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(50, 6, utf8_decode('Fecha y Hora:'), 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, formatearFecha($transaccion['fecha_transaccion']), 0, 1, 'L');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(50, 6, utf8_decode('N° Factura/Remito:'), 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, utf8_decode($transaccion['numero_factura']), 0, 1, 'L');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(50, 6, utf8_decode('Cantidad de Productos:'), 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, $transaccion['cantidad_productos'], 0, 1, 'L');
    
    $pdf->Ln(5);
    
    // Tabla de productos
    if (strtoupper($transaccion['tipo_operacion']) === 'ALTA') {
        // === TABLA PARA ALTA ===
        $pdf->SetFillColor(25, 135, 84);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 9);
        
        $pdf->Cell(60, 7, 'PRODUCTO', 1, 0, 'L', true);
        $pdf->Cell(20, 7, 'CANTIDAD', 1, 0, 'C', true);
        $pdf->Cell(30, 7, 'COSTO UNIT.', 1, 0, 'R', true);
        $pdf->Cell(30, 7, 'PRECIO VENTA', 1, 0, 'R', true);
        $pdf->Cell(40, 7, 'SUBTOTAL COSTO', 1, 1, 'R', true);
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 9);
        $fill = false;
        
        foreach ($transaccion['items'] as $item) {
            if ($fill) {
                $pdf->SetFillColor(250, 250, 250);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }
            
            $pdf->Cell(60, 6, utf8_decode($item['nombre']), 1, 0, 'L', true);
            $pdf->Cell(20, 6, $item['cantidad'] . ' ' . $item['unidad'], 1, 0, 'C', true);
            $pdf->Cell(30, 6, formatearMoneda($item['costo_unitario']), 1, 0, 'R', true);
            $pdf->Cell(30, 6, formatearMoneda($item['precio_venta']), 1, 0, 'R', true);
            $pdf->Cell(40, 6, formatearMoneda($item['subtotal_costo']), 1, 1, 'R', true);
            
            $fill = !$fill;
        }
        
        // Totales
        $pdf->Ln(2);
        $pdf->SetFont('helvetica', 'B', 10);
        
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(140, 7, 'TOTAL COSTO (Inversión)', 1, 0, 'R', true);
        $pdf->Cell(40, 7, formatearMoneda($transaccion['total_costo']), 1, 1, 'R', true);
        
        $pdf->SetFillColor(25, 135, 84);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(140, 7, 'VALOR EN VENTA', 1, 0, 'R', true);
        $pdf->Cell(40, 7, formatearMoneda($transaccion['total_venta']), 1, 1, 'R', true);
        
        $pdf->SetTextColor(0, 0, 0);
        
        // Ganancia potencial
        $ganancia_potencial = $transaccion['total_venta'] - $transaccion['total_costo'];
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(140, 6, utf8_decode('Ganancia potencial (si se vende todo):'), 0, 0, 'R');
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(40, 6, formatearMoneda($ganancia_potencial), 0, 1, 'R');
        
    } else {
        // === TABLA PARA BAJA ===
        $pdf->SetFillColor(220, 53, 69);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 8);
        
        $pdf->Cell(45, 7, 'PRODUCTO', 1, 0, 'L', true);
        $pdf->Cell(18, 7, 'CANTIDAD', 1, 0, 'C', true);
        $pdf->Cell(25, 7, 'COSTO UNIT.', 1, 0, 'R', true);
        $pdf->Cell(25, 7, 'PRECIO VENTA', 1, 0, 'R', true);
        $pdf->Cell(30, 7, utf8_decode('PÉRDIDA'), 1, 0, 'R', true);
        $pdf->Cell(37, 7, 'MOTIVO', 1, 1, 'L', true);
        
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 8);
        $fill = false;
        
        // Mapeo de motivos en español
        $motivos_es = [
            'vencimiento' => 'Vencimiento',
            'daño' => utf8_decode('Daño físico'),
            'robo' => 'Robo',
            'clima' => 'Clima',
            'transporte' => 'Transporte',
            'plagas' => 'Plagas',
            'otro' => 'Otro'
        ];
        
        foreach ($transaccion['items'] as $item) {
            if ($fill) {
                $pdf->SetFillColor(250, 250, 250);
            } else {
                $pdf->SetFillColor(255, 255, 255);
            }
            
            $motivo_texto = $motivos_es[$item['motivo']] ?? ucfirst($item['motivo']);
            
            $pdf->Cell(45, 6, utf8_decode($item['nombre']), 1, 0, 'L', true);
            $pdf->Cell(18, 6, $item['cantidad'] . ' ' . $item['unidad'], 1, 0, 'C', true);
            $pdf->Cell(25, 6, formatearMoneda($item['costo_unitario']), 1, 0, 'R', true);
            $pdf->Cell(25, 6, formatearMoneda($item['precio_venta']), 1, 0, 'R', true);
            $pdf->Cell(30, 6, formatearMoneda($item['valor_venta_perdido']), 1, 0, 'R', true);
            $pdf->Cell(37, 6, $motivo_texto, 1, 1, 'L', true);
            
            // Si hay descripción, agregar en siguiente línea
            if (!empty($item['descripcion'])) {
                $pdf->SetFont('helvetica', 'I', 7);
                $pdf->SetTextColor(100, 100, 100);
                $pdf->Cell(10, 4, '', 0, 0, 'L');
                $pdf->Cell(170, 4, utf8_decode('Detalle: ' . $item['descripcion']), 0, 1, 'L');
                $pdf->SetFont('helvetica', '', 8);
                $pdf->SetTextColor(0, 0, 0);
            }
            
            $fill = !$fill;
        }
        
        // Totales
        $pdf->Ln(2);
        $pdf->SetFont('helvetica', 'B', 10);
        
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(143, 7, utf8_decode('TOTAL COSTO PERDIDO'), 1, 0, 'R', true);
        $pdf->Cell(37, 7, formatearMoneda($transaccion['total_costo']), 1, 1, 'R', true);
        
        $pdf->SetFillColor(220, 53, 69);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(143, 7, utf8_decode('TOTAL PÉRDIDA (VALOR VENTA)'), 1, 0, 'R', true);
        $pdf->Cell(37, 7, formatearMoneda($transaccion['total_perdida']), 1, 1, 'R', true);
        
        $pdf->SetTextColor(0, 0, 0);
    }
    
    $pdf->Ln(8);
    
    // Nota informativa
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetFont('helvetica', 'I', 8);
    
    if (strtoupper($transaccion['tipo_operacion']) === 'ALTA') {
        $pdf->MultiCell(0, 4, utf8_decode("NOTA: Esta boleta certifica el ingreso de mercadería al inventario. Se actualizaron los precios de costo y venta de los productos listados."), 0, 'L');
    } else {
        $pdf->MultiCell(0, 4, utf8_decode("NOTA: Esta boleta certifica la baja de mercadería del inventario. Los productos listados fueron dados de baja por los motivos indicados y se registraron en el sistema de pérdidas."), 0, 'L');
    }
    
    $pdf->Ln(10);
    
    // Firma
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 4, '________________________________________', 0, 1, 'C');
    $pdf->Cell(0, 4, 'Firma y Aclaración', 0, 1, 'C');
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 4, utf8_decode('Responsable de la operación'), 0, 1, 'C');
    
    // Salida del PDF
    $nombreArchivo = 'Boleta_' . $tipo_doc . '_' . $transaction_id . '.pdf';
    $pdf->Output($nombreArchivo, 'I'); // 'I' para abrir en el navegador
    
} catch (Exception $e) {
    die('Error al generar PDF: ' . $e->getMessage());
}
?>