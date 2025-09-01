<?php
/**
 * API para guardar las preferencias de notificaciones
 * 
 * Este script guarda las preferencias relacionadas con el envío de emails
 * - send_confirmacion: Si se debe enviar confirmación al asignar turno
 * - send_recordatorio: Si se deben enviar recordatorios de turnos
 */

// Configurar headers para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Si es una solicitud OPTIONS (CORS preflight), terminar aquí
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Verificar que sea un POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido. Utilice POST.'
    ]);
    exit;
}

// Obtener los datos enviados
$data = json_decode(file_get_contents('php://input'), true);

// Validar que se hayan enviado los datos requeridos
if (!isset($data['send_confirmacion']) || !isset($data['send_recordatorio'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Faltan parámetros requeridos'
    ]);
    exit;
}

// Preparar datos para guardar
$config = [
    'send_confirmacion' => (bool)$data['send_confirmacion'],
    'send_recordatorio' => (bool)$data['send_recordatorio'],
];

// Ruta del archivo de configuración
$configFile = __DIR__ . '/../nucleo/config/notification_preferences.json';
$configDir = dirname($configFile);

// Crear directorio si no existe
if (!is_dir($configDir)) {
    if (!mkdir($configDir, 0755, true)) {
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo crear el directorio de configuración'
        ]);
        exit;
    }
}

// Guardar configuración en archivo
$result = file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));

if ($result === false) {
    echo json_encode([
        'success' => false,
        'message' => 'No se pudieron guardar las preferencias'
    ]);
    exit;
}

// Responder éxito
echo json_encode([
    'success' => true,
    'message' => 'Preferencias guardadas correctamente'
]);
