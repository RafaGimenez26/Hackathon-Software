<?php
session_start();
require 'conexion.php';

header('Content-Type: application/json');

$termino_busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($termino_busqueda) || strlen($termino_busqueda) < 2) {
    echo json_encode(['productos' => [], 'mensaje' => 'Escribe al menos 2 caracteres']);
    exit;
}

// Construir query de búsqueda con texto
$query = [
    'activo' => true,
    '$or' => [
        ['nombre' => new MongoDB\BSON\Regex($termino_busqueda, 'i')],
        ['descripcion' => new MongoDB\BSON\Regex($termino_busqueda, 'i')],
        ['punto_venta' => new MongoDB\BSON\Regex($termino_busqueda, 'i')],
        ['categoria' => new MongoDB\BSON\Regex($termino_busqueda, 'i')]
    ]
];

try {
    $productos = $collection->find($query, [
        'limit' => 20,
        'sort' => ['fecha_creacion' => -1]
    ])->toArray();

    // Obtener información de productores
    $productores_cache = [];
    if (!empty($productos)) {
        $productores_ids = array_unique(array_column($productos, 'productor_id'));
        $productores_ids = array_filter($productores_ids); // Eliminar nulls
        
        if (!empty($productores_ids)) {
            $placeholders = implode(',', array_fill(0, count($productores_ids), '?'));
            $types = str_repeat('i', count($productores_ids));
            
            $stmt = $conexion->prepare("SELECT ProductorID, NombreRazonSocial, TelefonoContacto FROM productores WHERE ProductorID IN ($placeholders)");
            $stmt->bind_param($types, ...$productores_ids);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($productor = $result->fetch_assoc()) {
                $productores_cache[$productor['ProductorID']] = $productor;
            }
            $stmt->close();
        }
    }

    // Formatear resultados
    $resultados = [];
    foreach ($productos as $producto) {
        $productor = $productores_cache[$producto['productor_id']] ?? null;
        
        $resultados[] = [
            '_id' => (string)$producto['_id'],
            'nombre' => $producto['nombre'],
            'descripcion' => $producto['descripcion'] ?? '',
            'categoria' => $producto['categoria'],
            'precio' => $producto['precio'],
            'unidad' => $producto['unidad'] ?? 'u',
            'imagen' => $producto['imagen'] ?? 'img/default.jpg',
            'punto_venta' => $producto['punto_venta'],
            'direccion' => $producto['direccion'] ?? '',
            'zona' => $producto['zona'] ?? '',
            'stock_disponible' => $producto['stock_disponible'] ?? 0,
            'organico' => $producto['organico'] ?? false,
            'sin_agrotoxicos' => $producto['sin_agrotoxicos'] ?? false,
            'dias_disponibles' => $producto['dias_disponibles'] ?? [],
            'horario' => $producto['horario'] ?? '',
            'productor' => [
                'nombre' => $productor ? $productor['NombreRazonSocial'] : 'Productor local',
                'telefono' => $productor ? $productor['TelefonoContacto'] : ''
            ]
        ];
    }

    echo json_encode([
        'productos' => $resultados,
        'total' => count($resultados),
        'mensaje' => count($resultados) > 0 ? '' : 'No se encontraron productos'
    ]);

} catch (Exception $e) {
    error_log("Error en búsqueda: " . $e->getMessage());
    echo json_encode([
        'productos' => [],
        'error' => 'Error en la búsqueda',
        'mensaje' => 'Ocurrió un error al buscar productos'
    ]);
}
?>