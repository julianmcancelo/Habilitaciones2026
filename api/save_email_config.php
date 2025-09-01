<?php
/**
 * API para guardar la configuración de correo electrónico
 */

// Activar reportes de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores pero sí registrarlos

// Configurar cabeceras para API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar la solicitud OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Método no permitido
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Use POST para esta API.']);
    exit;
}

// Sistema de rutas para compatibilidad con diferentes entornos
require_once __DIR__ . '/include_path.php';

// Leer el cuerpo de la solicitud
$input_json = file_get_contents('php://input');

// Verificar que se recibieron datos
if (empty($input_json)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos en el cuerpo de la solicitud.']);
    exit;
}

// Decodificar JSON
try {
    $config = json_decode($input_json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error en el formato de los datos: ' . $e->getMessage()]);
    exit;
}

// Verificar campos requeridos
$required_fields = ['smtp_host', 'smtp_port', 'from_email', 'from_name'];
foreach ($required_fields as $field) {
    if (empty($config[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "El campo '{$field}' es obligatorio"]);
        exit;
    }
}

// Nombre del archivo de configuración
$config_file = __DIR__ . '/../nucleo/email_config.php';

// Formato para el archivo de configuración
$config_content = "<?php\n";
$config_content .= "/**\n";
$config_content .= " * Configuración de correo electrónico\n";
$config_content .= " * Generado automáticamente el " . date('Y-m-d H:i:s') . "\n";
$config_content .= " */\n\n";
$config_content .= "return [\n";

// Agregar cada configuración
foreach ($config as $key => $value) {
    // Tratar el valor según su tipo
    if (is_bool($value)) {
        $value_str = $value ? 'true' : 'false';
    } elseif (is_numeric($value)) {
        $value_str = $value;
    } elseif (is_null($value)) {
        $value_str = 'null';
    } elseif (empty($value) && $value !== '0') {
        $value_str = "''";
    } else {
        // Escapar comillas en strings
        $value = str_replace("'", "\\'", $value);
        $value_str = "'{$value}'";
    }
    
    // Para la contraseña, solo guardarla si no está vacía
    if ($key === 'password' && empty($value)) {
        continue;
    }
    
    $config_content .= "    '{$key}' => {$value_str},\n";
}

$config_content .= "];\n";

// Guardar el archivo
try {
    if (file_put_contents($config_file, $config_content)) {
        echo json_encode([
            'success' => true,
            'message' => 'Configuración guardada correctamente'
        ]);
    } else {
        throw new Exception('No se pudo escribir el archivo de configuración');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar la configuración: ' . $e->getMessage()
    ]);
}
