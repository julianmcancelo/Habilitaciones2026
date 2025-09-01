<?php
/**
 * API para enviar un correo electrónico de prueba
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

// Ya no necesitamos importar PHPMailer porque usamos mail() nativo
error_log("Usando función mail() nativa de PHP");

// Importar el servicio de email
try {
    require_once find_project_file_path('/nucleo/email_service.php');
    error_log("Servicio de email cargado correctamente");
} catch (Exception $e) {
    error_log("Error al cargar servicio de email: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar servicio de email: ' . $e->getMessage()
    ]);
    exit;
}

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

// Verificar que se haya proporcionado un email de prueba
if (empty($config['test_email'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Debe proporcionar un email para la prueba']);
    exit;
}

try {
    // Inicializar el servicio de email
    $emailService = new EmailService();
    
    // Aplicar configuración temporal - solo los campos necesarios para mail() nativo
    $emailService->setConfig([
        'from_email' => $config['from_email'] ?? 'lanustransportepublico@gmail.com',
        'from_name' => $config['from_name'] ?? 'Sistema de Habilitaciones',
        'reply_to' => $config['reply_to'] ?? 'transportepublicolanus@gmail.com',
    ]);
    
    // Preparar contenido de prueba
    $subject = 'Correo de prueba - Sistema de Habilitaciones';
    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
            .header { background-color: #f8f9fa; padding: 15px; text-align: center; border-bottom: 2px solid #007bff; }
            .content { padding: 20px; }
            .footer { font-size: 12px; text-align: center; margin-top: 30px; color: #6c757d; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Prueba de Correo Electrónico</h2>
            </div>
            <div class='content'>
                <p>Este es un correo de prueba del Sistema de Habilitaciones.</p>
                <p>Si ha recibido este mensaje, la configuración de correo electrónico está funcionando correctamente.</p>
                <p><strong>Fecha y hora de envío:</strong> " . date('d/m/Y H:i:s') . "</p>
            </div>
            <div class='footer'>
                <p>Este es un mensaje automático. Por favor, no responda a este correo.</p>
                <p>© 2025 Transportes Lanus - Sistema de Habilitaciones</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Enviar el correo de prueba
    $result = $emailService->send($config['test_email'], $subject, $body);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Correo de prueba enviado correctamente a ' . $config['test_email']
        ]);
    } else {
        throw new Exception('No se pudo enviar el correo de prueba');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al enviar correo de prueba: ' . $e->getMessage()
    ]);
}
