<?php
// crear.php
// API REST para crear usuarios desde MIT App Inventor

// Headers para permitir peticiones desde App Inventor
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de la base de datos
$host = 'mysql-j-et.railway.internal';
$dbname = 'railway';
$user = 'root';
$pass = 'AHctqRzHOQlDbhpMdwQPQcDCwMEvCBWd';

// Respuesta por defecto
$response = array();

try {
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener datos desde GET
    $nombre = isset($_GET['nombre']) ? trim($_GET['nombre']) : '';
    $dni = isset($_GET['dni']) ? trim($_GET['dni']) : '';
    $numero = isset($_GET['numero']) ? trim($_GET['numero']) : '';
    $email = isset($_GET['email']) ? trim($_GET['email']) : '';
    
    // Validar que los campos obligatorios no estén vacíos
    if (empty($nombre)) {
        $response['success'] = false;
        $response['message'] = 'El nombre es obligatorio';
        $response['received_data'] = array(
            'nombre' => $nombre,
            'dni' => $dni,
            'numero' => $numero,
            'email' => $email
        );
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (empty($dni)) {
        $response['success'] = false;
        $response['message'] = 'El DNI es obligatorio';
        $response['received_data'] = array(
            'nombre' => $nombre,
            'dni' => $dni,
            'numero' => $numero,
            'email' => $email
        );
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Insertar usuario
    $sql = "INSERT INTO personas (nombre, dni, numero, email) VALUES (:nombre, :dni, :numero, :email)";
    $stmt = $pdo->prepare($sql);
    
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':dni', $dni);
    $stmt->bindParam(':numero', $numero);
    $stmt->bindParam(':email', $email);
    
    $stmt->execute();
    
    $lastId = $pdo->lastInsertId();
    
    // Respuesta exitosa
    $response['success'] = true;
    $response['message'] = 'Usuario creado exitosamente';
    $response['id'] = $lastId;
    $response['data'] = array(
        'id' => $lastId,
        'nombre' => $nombre,
        'dni' => $dni,
        'numero' => $numero,
        'email' => $email
    );
    
} catch (PDOException $e) {
    // Error en la base de datos
    $response['success'] = false;
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
}

// Enviar respuesta JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>