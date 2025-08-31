<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../nucleo/conexion.php';

$input = json_decode(file_get_contents('php://input'), true);

$persona_habilitacion_id = $input['id'] ?? null;

if (!$persona_habilitacion_id || !is_numeric($persona_habilitacion_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de la relación no válido.']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM habilitaciones_personas WHERE id = ?");
    $stmt->execute([$persona_habilitacion_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Persona desvinculada con éxito.']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No se encontró la relación para desvincular.']);
    }

} catch (PDOException $e) {
    error_log('Error en API unlink_person: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos.']);
}
