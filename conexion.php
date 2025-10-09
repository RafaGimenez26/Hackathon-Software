<?php
require 'vendor/autoload.php';

// Cargar variables de entorno desde .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Usar las variables de entorno
$mongoUser = $_ENV['MONGO_USER'];
$mongoPass = $_ENV['MONGO_PASS'];
$mongoCluster = $_ENV['MONGO_CLUSTER'];

try {
    $client = new MongoDB\Client("mongodb+srv://{$mongoUser}:{$mongoPass}@{$mongoCluster}/");
    $database = $client->AgroHub_Misiones;
    $collection = $database->Productos;
    
    // Verificar conexión
    $client->listDatabases();
    
} catch (Exception $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>