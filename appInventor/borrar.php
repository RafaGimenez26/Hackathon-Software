<?php
// borrar.php
// API REST para borrar usuarios desde MIT App Inventor

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
    
    // Obtener ID desde GET
    $id = isset($_GET['id']) ? trim($_GET['id']) : '';
    
    // Validar que el ID sea proporcionado
    if (empty($id)) {
        $response['success'] = false;
        $response['message'] = 'El ID es obligatorio para borrar';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Verificar si el usuario existe y obtener sus datos antes de borrar
    $checkSql = "SELECT * FROM personas WHERE id = :id";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $checkStmt->execute();
    
    $usuario = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        $response['success'] = false;
        $response['message'] = 'No existe un usuario con ese ID';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Eliminar usuario
    $sql = "DELETE FROM personas WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    // Respuesta exitosa
    $response['success'] = true;
    $response['message'] = 'Usuario eliminado exitosamente';
    $response['deleted_user'] = array(
        'id' => $usuario['id'],
        'nombre' => $usuario['nombre'],
        'dni' => $usuario['dni'],
        'numero' => $usuario['numero'],
        'email' => $usuario['email']
    );
    
} catch (PDOException $e) {
    // Error en la base de datos
    $response['success'] = false;
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
}

// Enviar respuesta JSON
echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>