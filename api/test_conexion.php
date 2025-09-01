<?php
// Test de conexión a base de datos
require_once '../nucleo/conexion.php';

header('Content-Type: application/json');

try {
    // Intentar una consulta simple para verificar conexión PDO
    $stmt = $pdo->query('SELECT NOW() as time');
    $now = $stmt->fetch();
    
    // Intentar también la conexión mysqli para pruebas de compatibilidad
    $mysqli = conectar();
    $mysqli_result = mysqli_query($mysqli, 'SELECT VERSION() as version');
    $version = mysqli_fetch_assoc($mysqli_result);
    
    // Mostrar información de diagnóstico
    echo json_encode([
        'success' => true,
        'message' => 'Conexión exitosa a la base de datos',
        'pdo_time' => $now['time'] ?? 'No disponible',
        'mysqli_version' => $version['version'] ?? 'No disponible',
        'server_info' => [
            'php_version' => phpversion(),
            'mysql_client_info' => mysqli_get_client_info(),
            'remote_addr' => $_SERVER['REMOTE_ADDR'],
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'No disponible',
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Capturar cualquier error y devolverlo como JSON
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al conectar a la base de datos',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
