<?php
// Activar reportes de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en la salida pero sí registrarlos

// Configurar cabeceras para API JSON desde el principio
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Registrar información del entorno para diagnóstico
error_log("=== GET_TURNOS_POR_FECHA.PHP INICIADO ====");
error_log("SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'No definido'));
error_log("HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'No definido'));
error_log("DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'No definido'));
error_log("SCRIPT_FILENAME: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'No definido'));

// Paso 1: Verificar si existe una conexión directa en este archivo
$pdo = null;

// Intentar cargar la conexión usando el método más directo primero
$local_conexion = __DIR__ . '/conexion.php';
if (file_exists($local_conexion)) {
    error_log("Usando conexion.php local en " . $local_conexion);
    require_once $local_conexion;
    if (isset($pdo)) {
        error_log("Conexión PDO obtenida desde archivo local");
    } else {
        error_log("ERROR: No se pudo obtener objeto PDO desde archivo local");
    }
} else {
    error_log("Archivo conexion.php local no encontrado, intentando con sistema de rutas");
    
    // Si no existe localmente, intentar con el sistema de rutas
    try {
        require_once __DIR__ . '/include_path.php';
        include_from_root('/nucleo/conexion.php');
        error_log("Conexión incluida desde include_from_root");
    } catch (Exception $e) {
        error_log("ERROR al usar include_from_root: " . $e->getMessage());
        
        // Último intento - usar configuración hardcoded si todo lo demás falla
        try {
            // Determinar entorno
            $is_production = (strpos($_SERVER['HTTP_HOST'] ?? '', 'transportelanus.com.ar') !== false);
            
            // Configuración de BD según entorno
            if ($is_production) {
                // Entorno de producción
                $host = 'localhost'; 
                $db_name = 'transpo1_credenciales';
                $username = 'transpo1_credenciales';
                $password_db = 'feelthesky1';
            } else {
                // Entorno de desarrollo local
                $host = 'localhost';
                $db_name = 'transpo1_credenciales';
                $username = 'transpo1_credenciales';
                $password_db = 'feelthesky1';
            }
            $charset = 'utf8mb4';
            
            // Conexión directa a la base de datos
            $dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            
            error_log("Intentando crear conexión PDO directa a $host, $db_name");
            $pdo = new PDO($dsn, $username, $password_db, $options);
            error_log("Conexión PDO creada directamente con éxito");
        } catch (PDOException $e) {
            error_log("ERROR al crear conexión PDO directa: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Error crítico: No se pudo establecer conexión con la base de datos.',
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }
}

// Configurar cabeceras para API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Verificar que tenemos PDO antes de continuar
if (!isset($pdo) || !($pdo instanceof PDO)) {
    error_log("FATAL ERROR: No se tiene objeto PDO disponible después de intentar todos los métodos de conexión");
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error crítico: No se pudo establecer conexión con la base de datos.',
        'debug' => 'PDO no está disponible después de intentar todos los métodos de conexión'
    ]);
    exit;
}

// Obtener fecha del request
if (!isset($_GET['fecha'])) {
    $fecha = null;
} else {
    $fecha = $_GET['fecha'];
}

// Validar fecha
if (!$fecha) {
    http_response_code(400);
    echo json_encode(['error' => 'No se proporcionó una fecha.']);
    exit;
}

// Validar formato de fecha
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    error_log("Formato de fecha inválido recibido: " . $fecha);
    http_response_code(400);
    echo json_encode(['error' => 'Formato de fecha inválido. Use YYYY-MM-DD.']);
    exit;
}

try {
    error_log("Ejecutando consulta de turnos para la fecha: " . $fecha);
    
    // Consultar turnos ocupados para esta fecha
    $stmt = $pdo->prepare("SELECT hora FROM turnos WHERE fecha = ?");
    $stmt->execute([$fecha]);
    $turnosOcupados = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    
    error_log("Consulta exitosa. Turnos encontrados: " . count($turnosOcupados));
    echo json_encode($turnosOcupados);

} catch (PDOException $e) {
    error_log("ERROR en la consulta SQL: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Error al consultar la base de datos.',
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("ERROR general: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error interno del servidor.']);
}
?>
