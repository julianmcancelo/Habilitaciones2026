<?php
// VERSIÓN DE EMERGENCIA: get_turnos_por_fecha_emergency.php 
// Script totalmente independiente que no usa el sistema de includes
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en producción

// Registro para depuración
error_log("Ejecutando versión de emergencia de get_turnos_por_fecha.php");

// Obtener la fecha del request
$fecha = $_GET['fecha'] ?? null;
if (!$fecha) {
    http_response_code(400);
    echo json_encode(['error' => 'No se proporcionó una fecha.']);
    exit;
}

// Validar formato de fecha
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    http_response_code(400);
    echo json_encode(['error' => 'Formato de fecha inválido. Use YYYY-MM-DD.']);
    exit;
}

// Determinar entorno
$is_production = (strpos($_SERVER['HTTP_HOST'] ?? '', 'transportelanus.com.ar') !== false);
error_log("Ambiente detectado: " . ($is_production ? 'PRODUCCIÓN' : 'DESARROLLO'));

// Configuración de BD según entorno
if ($is_production) {
    // Entorno de producción
    $host = 'localhost'; 
    $db_name = 'transpo1_credenciales';
    $username = 'transpo1_credenciales';
    $password_db = 'feelthesky1';
} else {
    // Entorno de desarrollo
    $host = 'localhost';
    $db_name = 'transpo1_credenciales';
    $username = 'transpo1_credenciales';
    $password_db = 'feelthesky1';
}
$charset = 'utf8mb4';

try {
    // Conexión directa a la base de datos
    $dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5,
    ];
    
    error_log("Intentando conectar a la BD en: $host, $db_name");
    $pdo = new PDO($dsn, $username, $password_db, $options);
    error_log("Conexión exitosa a la BD");
    
    // Consultar turnos para la fecha proporcionada
    $stmt = $pdo->prepare("SELECT hora FROM turnos WHERE fecha = ?");
    $stmt->execute([$fecha]);
    $turnosOcupados = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    error_log("Consulta exitosa. Turnos encontrados: " . count($turnosOcupados));
    echo json_encode($turnosOcupados);
    
} catch (PDOException $e) {
    error_log("Error PDO en get_turnos_por_fecha_emergency: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al consultar la base de datos.',
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error general en get_turnos_por_fecha_emergency: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor.']);
}
?>
