<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../nucleo/conexion.php';

// 1. Validar el ID de entrada
// -----------------------------------------------------------------------------
$habilitacion_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$habilitacion_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'ID de habilitación no válido o no proporcionado.']);
    exit;
}

// 2. Obtener los datos de la base de datos
// -----------------------------------------------------------------------------
try {
    $response = ['success' => true];

    // Consulta principal para los datos de la habilitación
    $stmt = $pdo->prepare("SELECT * FROM habilitaciones_generales WHERE id = ? AND is_deleted = 0");
    $stmt->execute([$habilitacion_id]);
    $habilitacion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$habilitacion) {
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'message' => 'Habilitación no encontrada.']);
        exit;
    }

    $response['details'] = $habilitacion;

    // Consultas adicionales para datos relacionados (titular, vehículo, etc.)
    // --- Titular ---
    $stmt_titular = $pdo->prepare("SELECT p.* FROM habilitaciones_personas hp JOIN personas p ON p.id = hp.persona_id WHERE hp.habilitacion_id = ? AND hp.rol = 'TITULAR' LIMIT 1");
    $stmt_titular->execute([$habilitacion_id]);
    $response['titular'] = $stmt_titular->fetch(PDO::FETCH_ASSOC) ?: null;

    // --- Vehículo ---
    $stmt_vehiculo = $pdo->prepare("SELECT v.* FROM habilitaciones_vehiculos hv JOIN vehiculos v ON v.id = hv.vehiculo_id WHERE hv.habilitacion_id = ? LIMIT 1");
    $stmt_vehiculo->execute([$habilitacion_id]);
    $response['vehiculo'] = $stmt_vehiculo->fetch(PDO::FETCH_ASSOC) ?: null;

    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    error_log('Error de base de datos en details.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al consultar la base de datos.']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
}
