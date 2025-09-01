<?php
// Activar reportes de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1); // Mostrar errores para diagnóstico
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt'); // Registrar errores en un archivo local

// Sistema de rutas para compatibilidad con diferentes entornos
require_once __DIR__ . '/include_path.php';

// Incluir directamente el archivo de conexión local para evitar problemas con el helper
$local_conexion = __DIR__ . '/conexion.php';

if (file_exists($local_conexion)) {
    require_once $local_conexion;
    error_log("Usando conexion.php local en la carpeta api");
} else {
    // Intentar cargar desde nucleo como respaldo
    $nucleo_conexion = __DIR__ . '/../nucleo/conexion.php';
    
    if (file_exists($nucleo_conexion)) {
        require_once $nucleo_conexion;
        error_log("Usando conexion.php desde la carpeta nucleo");
    } else {
        // Error fatal si no podemos cargar ninguna versión
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Error crítico: No se pudo cargar el archivo de conexión.',
            'debug' => 'No se encontraron archivos de conexión en las rutas esperadas'
        ]);
        exit;
    }
}

// Configurar cabeceras para API JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $turno_id = $data['turno_id'] ?? null;

    if (!$turno_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID de turno no proporcionado.']);
        exit;
    }

    try {
        // Primero verificamos si la tabla existe
        $tablas = $pdo->query("SHOW TABLES LIKE 'turnos'")->fetchAll();
        if (count($tablas) === 0) {
            throw new PDOException("La tabla 'turnos' no existe en la base de datos");
        }
        
        // Verificamos si el turno existe antes de actualizarlo
        $check = $pdo->prepare("SELECT id, estado FROM turnos WHERE id = ? LIMIT 1");
        $check->execute([$turno_id]);
        $turno = $check->fetch();
        
        if (!$turno) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'No se encontró el turno con ID: ' . $turno_id]);
            exit;
        }
        
        // Realizamos la actualización
        $stmt = $pdo->prepare("UPDATE turnos SET estado = 'CONFIRMADO' WHERE id = ?");
        $stmt->execute([$turno_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Turno confirmado con éxito.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'El turno ya estaba confirmado o no requirió cambios.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        $error_msg = 'Error al confirmar turno: ' . $e->getMessage();
        error_log($error_msg);
        echo json_encode([
            'success' => false, 
            'message' => 'Error en la base de datos.',
            'debug' => $error_msg  // Mostrar detalle del error para diagnóstico
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
