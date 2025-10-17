<?php
require_once('../vendor/autoload.php');

class PDFComun extends TCPDF {
    private $datosProductor;
    
    public function setDatosProductor($datos) {
        $this->datosProductor = $datos;
    }
    
    // Encabezado del PDF
    public function Header() {
        if ($this->datosProductor) {
            $this->SetFont('helvetica', 'B', 16);
            $this->SetTextColor(40, 116, 73); // Verde
            $this->Cell(0, 10, 'AgroHub Misiones', 0, 1, 'C');
            
            $this->SetFont('helvetica', '', 10);
            $this->SetTextColor(100, 100, 100);
            $this->Cell(0, 5, 'Plataforma de Comercio Local', 0, 1, 'C');
            
            $this->Ln(3);
            
            // Datos del productor
            $this->SetFont('helvetica', 'B', 11);
            $this->SetTextColor(0, 0, 0);
            $this->Cell(0, 6, utf8_decode($this->datosProductor['nombre']), 0, 1, 'L');
            
            $this->SetFont('helvetica', '', 9);
            $this->SetTextColor(80, 80, 80);
            
            if (!empty($this->datosProductor['cuit'])) {
                $this->Cell(0, 5, 'CUIT/CUIL: ' . $this->datosProductor['cuit'], 0, 1, 'L');
            }
            
            $linea2 = '';
            if (!empty($this->datosProductor['telefono'])) {
                $linea2 .= 'Tel: ' . $this->datosProductor['telefono'];
            }
            if (!empty($this->datosProductor['correo'])) {
                $linea2 .= ($linea2 ? ' | ' : '') . 'Email: ' . $this->datosProductor['correo'];
            }
            if ($linea2) {
                $this->Cell(0, 5, utf8_decode($linea2), 0, 1, 'L');
            }
            
            if (!empty($this->datosProductor['direccion'])) {
                $this->Cell(0, 5, utf8_decode('Dirección: ' . $this->datosProductor['direccion']), 0, 1, 'L');
            }
            
            $this->Ln(2);
            $this->SetDrawColor(40, 116, 73);
            $this->SetLineWidth(0.5);
            $this->Line(10, $this->GetY(), 200, $this->GetY());
            $this->Ln(5);
        }
    }
    
    // Pie de página
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->getAliasNumPage() . ' de ' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

/**
 * Formatea un monto como moneda argentina
 */
function formatearMoneda($monto) {
    return '$' . number_format($monto, 2, ',', '.');
}

/**
 * Formatea una fecha desde MongoDB UTCDateTime
 */
function formatearFecha($mongoDate) {
    if ($mongoDate instanceof MongoDB\BSON\UTCDateTime) {
        return $mongoDate->toDateTime()->format('d/m/Y H:i');
    }
    return date('d/m/Y H:i', strtotime($mongoDate));
}

/**
 * Obtiene la clase CSS del estado
 */
function obtenerEstadoInfo($estado) {
    $estados = [
        'listo' => ['texto' => 'Listo para retiro', 'color' => [25, 135, 84]],
        'entregado' => ['texto' => 'Entregado', 'color' => [32, 201, 151]]
    ];
    return $estados[$estado] ?? ['texto' => ucfirst($estado), 'color' => [108, 117, 125]];
}

?>
