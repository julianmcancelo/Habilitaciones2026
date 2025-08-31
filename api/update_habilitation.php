<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/nucleo/conexion.php';

$input = json_decode(file_get_contents('php://input'), true);

$id = $input['id'] ?? null;

if (!$id || !is_numeric($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de habilitación no válido.']);
    exit;
}

// Lista de campos permitidos para actualizar
$allowed_fields = [
    'nro_licencia',
    'expte',
    'tipo_transporte',
    'vigencia_inicio',
    'vigencia_fin',
    'tipo',
    'estado',
    'resolucion',
    'observaciones'
];

$fields_to_update = [];
$params = [];

foreach ($allowed_fields as $field) {
    if (array_key_exists($field, $input)) {
        $fields_to_update[] = "`{$field}` = ?";
        $params[] = $input[$field];
    }
}

if (empty($fields_to_update)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se proporcionaron campos para actualizar.']);
    exit;
}

$params[] = $id; // Añadir el ID al final para el WHERE

$sql = "UPDATE `habilitaciones_generales` SET " . implode(', ', $fields_to_update) . " WHERE `id` = ?";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Habilitación actualizada con éxito.']);
    } else {
        // Esto puede ocurrir si los datos enviados son idénticos a los existentes
        echo json_encode(['success' => true, 'message' => 'No se realizaron cambios (los datos eran los mismos).']);
    }

} catch (PDOException $e) {
    error_log('Error en API update_habilitation: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos al actualizar la habilitación.']);
}
