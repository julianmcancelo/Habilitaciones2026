<?php
require_once __DIR__ . '/../nucleo/conexion.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Error desconocido.'];

// Leer el cuerpo de la solicitud JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || empty($input['id'])) {
    $response['message'] = 'No se proporcionó el ID de la habilitación.';
    echo json_encode($response);
    exit;
}

$habilitacion_id = $input['id'];

try {
    // Eliminar la fila de la tabla de enlace para desvincular el vehículo
    $stmt = $pdo->prepare("DELETE FROM habilitaciones_vehiculos WHERE habilitacion_id = ?");
    
    if ($stmt->execute([(string)$habilitacion_id])) {
        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Vehículo desvinculado correctamente.';
        } else {
            $response['success'] = false;
            $response['message'] = 'No se encontró una habilitación con ese ID o el vehículo ya estaba desvinculado.';
        }
    } else {
        $response['message'] = 'Error al ejecutar la consulta para desvincular el vehículo.';
    }

} catch (PDOException $e) {
    http_response_code(500);
    $response['message'] = 'Error en la base de datos: ' . $e->getMessage();
}

echo json_encode($response);
?>
