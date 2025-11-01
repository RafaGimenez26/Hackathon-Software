<?php
// editar.php
// API REST para editar usuarios desde MIT App Inventor

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
    $id = isset($_GET['id']) ? trim($_GET['id']) : '';
    $nombre = isset($_GET['nombre']) ? trim($_GET['nombre']) : '';
    $dni = isset($_GET['dni']) ? trim($_GET['dni']) : '';
    $numero = isset($_GET['numero']) ? trim($_GET['numero']) : '';
    $email = isset($_GET['email']) ? trim($_GET['email']) : '';
    
    // Validar que el ID sea proporcionado
    if (empty($id)) {
        $response['success'] = false;
        $response['message'] = 'El ID es obligatorio para editar';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Validar que los campos obligatorios no estén vacíos
    if (empty($nombre)) {
        $response['success'] = false;
        $response['message'] = 'El nombre es obligatorio';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (empty($dni)) {
        $response['success'] = false;
        $response['message'] = 'El DNI es obligatorio';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Verificar si el usuario existe
    $checkSql = "SELECT * FROM personas WHERE id = :id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() == 0) {
        $response['success'] = false;
        $response['message'] = 'No existe un usuario con ese ID';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Actualizar usuario
    $sql = "UPDATE personas SET nombre = :nombre, dni = :dni, numero = :numero, email = :email WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':nombre', $nombre);
    $stmt->bindParam(':dni', $dni);
    $stmt->bindParam(':numero', $numero);
    $stmt->bindParam(':email', $email);
    
    $stmt->execute();
    
    // Respuesta exitosa
    $response['success'] = true;
    $response['message'] = 'Usuario actualizado exitosamente';
    $response['data'] = array(
        'id' => $id,
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