<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
ini_set('display_errors', 0);
error_reporting(0);

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../nucleo/conexion.php';

// 1. Validar el ID de entrada (debe ser POST)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$habilitacion_id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);

if (!$habilitacion_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'ID de habilitación no válido o no proporcionado.']);
    exit;
}

// 2. Lógica de Renovación
// -----------------------------------------------------------------------------
try {
    // Primero, obtenemos la fecha de vigencia actual
    $stmt_select = $pdo->prepare("SELECT vigencia_fin FROM habilitaciones_generales WHERE id = ?");
    $stmt_select->execute([$habilitacion_id]);
    $habilitacion = $stmt_select->fetch(PDO::FETCH_ASSOC);

    if (!$habilitacion) {
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'message' => 'Habilitación no encontrada.']);
        exit;
    }

    // Calculamos la nueva fecha de vencimiento (1 año desde la fecha actual de vencimiento)
    $current_vigencia_fin = new DateTime($habilitacion['vigencia_fin']);
    $current_vigencia_fin->add(new DateInterval('P1Y')); // Añade 1 año
    $new_vigencia_fin = $current_vigencia_fin->format('Y-m-d');

    // Actualizamos la base de datos
    $stmt_update = $pdo->prepare("UPDATE habilitaciones_generales SET vigencia_fin = ?, estado = 'HABILITADO' WHERE id = ?");
    $success = $stmt_update->execute([$new_vigencia_fin, $habilitacion_id]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Habilitación renovada correctamente.']);
    } else {
        throw new Exception('No se pudo actualizar la base de datos.');
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log('Error de base de datos en renew.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al renovar en la base de datos.']);
} catch (Exception $e) {
    http_response_code(500);
    error_log('Error en renew.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor al renovar.']);
}
