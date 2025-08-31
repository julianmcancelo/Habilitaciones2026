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

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error: JSON mal formado.']);
    exit;
}

$vehiculo_id = $input['id'] ?? null;
if (!$vehiculo_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error: No se proporcionó el ID del vehículo.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "UPDATE vehiculos SET 
            dominio = ?, marca = ?, modelo = ?, chasis = ?, ano = ?, motor = ?, asientos = ?, 
            inscripcion_inicial = ?, Aseguradora = ?, poliza = ?, Vencimiento_Poliza = ?, Vencimiento_VTV = ? 
        WHERE id = ?"
    );

    $stmt->execute([
        $input['dominio'] ?? null,
        $input['marca'] ?? null,
        $input['modelo'] ?? null,
        $input['chasis'] ?? null,
        !empty($input['ano']) ? intval($input['ano']) : null,
        $input['motor'] ?? null,
        !empty($input['asientos']) ? intval($input['asientos']) : null,
        !empty($input['inscripcion_inicial']) ? $input['inscripcion_inicial'] : null,
        $input['aseguradora'] ?? null,
        $input['poliza'] ?? null,
        !empty($input['vencimiento_poliza']) ? $input['vencimiento_poliza'] : null,
        !empty($input['vencimiento_vtv']) ? $input['vencimiento_vtv'] : null,
        $vehiculo_id
    ]);

    $pdo->commit();

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Datos del vehículo actualizados con éxito.']);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Error en API update_vehicle: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al actualizar los datos del vehículo en la base de datos.']);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
