<?php
/**
 * API para obtener la configuración de correo electrónico
 */

// Configurar cabeceras para API JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar la solicitud OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Verificar que la solicitud sea GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Método no permitido
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Use GET para esta API.']);
    exit;
}

// Sistema de rutas para compatibilidad con diferentes entornos
require_once __DIR__ . '/include_path.php';

// Nombre del archivo de configuración
$config_file = __DIR__ . '/../nucleo/email_config.php';

// Valores por defecto para Gmail
$default_config = [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_auth' => true,
    'username' => 'habilitaciones.sistema@gmail.com',
    'password_masked' => '********',
    'from_email' => 'habilitaciones.sistema@gmail.com',
    'from_name' => 'Sistema de Habilitaciones',
    'reply_to' => 'habilitaciones.sistema@gmail.com',
    'send_confirmacion' => true,
    'send_recordatorio' => true
];

// Cargar configuración si existe
if (file_exists($config_file)) {
    try {
        $saved_config = include $config_file;
        
        // Asegurar que sea un array
        if (is_array($saved_config)) {
            // Combinar con valores por defecto, manteniendo los guardados
            $config = array_merge($default_config, $saved_config);
            
            // No devolver la contraseña real, usar versión enmascarada
            if (isset($config['password'])) {
                $config['password_masked'] = '********';
                unset($config['password']);
            }
            
            echo json_encode($config);
        } else {
            // Devolver valores por defecto si el archivo no tiene formato correcto
            echo json_encode($default_config);
        }
    } catch (Exception $e) {
        // En caso de error, devolver valores por defecto
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al cargar la configuración',
            'config' => $default_config
        ]);
    }
} else {
    // Si no existe archivo, devolver valores por defecto
    echo json_encode($default_config);
}
