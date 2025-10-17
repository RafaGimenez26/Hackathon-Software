<?php
session_start();
require_once('../conexion.php');
require_once('funciones_pdf_comunes.php');

if (!isset($_SESSION['ProductorID']) || !isset($_GET['pedido_id'])) {
    die('Acceso no autorizado');
}

$productor_id = $_SESSION['ProductorID'];
$pedido_id = $_GET['pedido_id'];

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
    
    // Obtener pedido desde MongoDB
    $pedidosCollection = $database->Pedidos;
    $pedido = $pedidosCollection->findOne([
        '_id' => new MongoDB\BSON\ObjectId($pedido_id),
        'items.productor_id' => $productor_id
    ]);
    
    if (!$pedido) {
        die('Pedido no encontrado');
    }
    
    // Filtrar solo items del productor que están listos o entregados
    $itemsFiltrados = [];
    $total = 0;
    
    foreach ($pedido['items'] as $item) {
        if ((int)$item['productor_id'] === (int)$productor_id) {
            $estado = $item['estado'] ?? 'pendiente';
            if ($estado === 'listo' || $estado === 'entregado') {
                $itemsFiltrados[] = $item;
                $total += $item['precio_unitario'] * $item['cantidad'];
            }
        }
    }
    
    // Si no hay items listos/entregados, no generar PDF
    if (empty($itemsFiltrados)) {
        die('No hay productos listos para generar el PDF');
    }
    
    // Obtener datos del cliente desde MySQL
    $usuario_id = $pedido['usuario_id'];
    $sql = "SELECT nombre_usuario, correo, telefono FROM usuarios WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cliente = $result->fetch_assoc();
    $stmt->close();
    
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
    $pdf->SetCreator('AgroHub Misiones');
    $pdf->SetAuthor($productor['NombreRazonSocial']);
    $pdf->SetTitle('Remito de Pedido #' . substr((string)$pedido['_id'], -8));
    $pdf->SetSubject('Remito de productos');
    
    // Configurar márgenes
    $pdf->SetMargins(15, 50, 15);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    $pdf->SetAutoPageBreak(TRUE, 20);
    
    // Agregar página
    $pdf->AddPage();
    
    // Título del documento
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetTextColor(40, 116, 73);
    $pdf->Cell(0, 8, 'NOTA DE PEDIDO', 0, 1, 'C');
    $pdf->Ln(3);
    
    // Información del pedido
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(95, 6, 'Número de Pedido:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, '#' . substr((string)$pedido['_id'], -8), 0, 1, 'L');
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(95, 6, 'Fecha:', 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, formatearFecha($pedido['fecha_creacion']), 0, 1, 'L');
    
    $pdf->Ln(5);
    
    // Datos del cliente
    $pdf->SetFillColor(245, 245, 245);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 7, utf8_decode('DATOS DEL CLIENTE'), 0, 1, 'L', true);
    $pdf->Ln(2);
    
    if ($cliente) {
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(40, 5, 'Cliente:', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, utf8_decode($cliente['nombre_usuario']), 0, 1, 'L');
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(40, 5, utf8_decode('Teléfono:'), 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, $cliente['telefono'], 0, 1, 'L');
        
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(40, 5, 'Correo:', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, $cliente['correo'], 0, 1, 'L');
    } else {
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 5, 'Datos del cliente no disponibles', 0, 1, 'L');
        $pdf->SetTextColor(0, 0, 0);
    }
    
    $pdf->Ln(5);
    
    // Tabla de productos
    $pdf->SetFillColor(40, 116, 73);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 9);
    
    $pdf->Cell(80, 7, 'PRODUCTO', 1, 0, 'L', true);
    $pdf->Cell(25, 7, 'CANTIDAD', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'PRECIO UNIT.', 1, 0, 'R', true);
    $pdf->Cell(40, 7, 'SUBTOTAL', 1, 1, 'R', true);
    
    // Productos
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 9);
    $fill = false;
    
    foreach ($itemsFiltrados as $item) {
        $subtotal = $item['precio_unitario'] * $item['cantidad'];
        $estadoInfo = obtenerEstadoInfo($item['estado'] ?? 'listo');
        
        if ($fill) {
            $pdf->SetFillColor(250, 250, 250);
        } else {
            $pdf->SetFillColor(255, 255, 255);
        }
        
        // Nombre del producto con estado
        $nombreConEstado = utf8_decode($item['nombre']) . ' [' . utf8_decode($estadoInfo['texto']) . ']';
        $pdf->Cell(80, 6, $nombreConEstado, 1, 0, 'L', true);
        
        // Cantidad con unidad
        $cantidadTexto = $item['cantidad'] . ' ' . ($item['unidad'] ?? 'u');
        $pdf->Cell(25, 6, $cantidadTexto, 1, 0, 'C', true);
        
        // Precio unitario
        $pdf->Cell(35, 6, formatearMoneda($item['precio_unitario']), 1, 0, 'R', true);
        
        // Subtotal
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(40, 6, formatearMoneda($subtotal), 1, 1, 'R', true);
        $pdf->SetFont('helvetica', '', 9);
        
        $fill = !$fill;
    }
    
    // Total
    $pdf->Ln(2);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetFillColor(40, 116, 73);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(140, 8, 'TOTAL', 1, 0, 'R', true);
    $pdf->Cell(40, 8, formatearMoneda($total), 1, 1, 'R', true);
    
    $pdf->Ln(8);
    
    // Nota informativa
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->MultiCell(0, 4, utf8_decode("NOTA: Este remito incluye únicamente los productos que están listos para entregar o ya fueron entregados al momento de generar este documento."), 0, 'L');
    
    $pdf->Ln(5);
    
    
    // Salida del PDF
    $nombreArchivo = 'Remito_Pedido_' . substr((string)$pedido['_id'], -8) . '.pdf';
    $pdf->Output($nombreArchivo, 'I'); // 'I' para abrir en el navegador
    
} catch (Exception $e) {
    die('Error al generar PDF: ' . $e->getMessage());
}
?>
