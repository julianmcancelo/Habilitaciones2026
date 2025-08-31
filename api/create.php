<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204);
    exit;
}

ini_set('display_errors', 0);
error_reporting(0);

try {
    require_once __DIR__ . '/nucleo/conexion.php'; // Usa el objeto $pdo
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Error crítico: No se pudo conectar a la base de datos.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error: JSON mal formado.']);
    exit;
}

// Validación básica de campos requeridos
$required_fields = ['nro_licencia', 'expte', 'tipo_transporte', 'vigencia_inicio', 'vigencia_fin', 'tipo', 'estado'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "El campo '{$field}' es obligatorio."]);
        exit;
    }
}

try {
    $query = "INSERT INTO habilitaciones_generales (anio, nro_licencia, resolucion, vigencia_inicio, vigencia_fin, estado, tipo, observaciones, expte, tipo_transporte) VALUES (:anio, :nro_licencia, :resolucion, :vigencia_inicio, :vigencia_fin, :estado, :tipo, :observaciones, :expte, :tipo_transporte)";
    
    $stmt = $pdo->prepare($query);

    $current_year = date('Y');
    $stmt->bindParam(':anio', $current_year);
    // Asegurar que los campos opcionales tengan un valor por defecto si no se proporcionan
    $resolucion = $input['resolucion'] ?? '';
    $observaciones = $input['observaciones'] ?? '';

    $stmt->bindParam(':nro_licencia', $input['nro_licencia']);
    $stmt->bindParam(':resolucion', $resolucion);
    $stmt->bindParam(':vigencia_inicio', $input['vigencia_inicio']);
    $stmt->bindParam(':vigencia_fin', $input['vigencia_fin']);
    $stmt->bindParam(':estado', $input['estado']);
    $stmt->bindParam(':tipo', $input['tipo']);
    $stmt->bindParam(':observaciones', $observaciones);
    $stmt->bindParam(':expte', $input['expte']);
    $stmt->bindParam(':tipo_transporte', $input['tipo_transporte']);

    if ($stmt->execute()) {
        $lastId = $pdo->lastInsertId();
        http_response_code(201); // Created
        echo json_encode(['success' => true, 'message' => 'Habilitación creada con éxito.', 'id' => $lastId]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar la habilitación en la base de datos.']);
    }

} catch (PDOException $e) {
    error_log('Error en la creación de habilitación: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor al procesar la solicitud.']);
}
