<?php
header('Content-Type: application/json');
require_once __DIR__ . '/nucleo/conexion.php';

$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error: JSON mal formado.']);
    exit;
}

$id = $input['id'] ?? null;

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El ID de la habilitación es obligatorio.']);
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
    $query = "UPDATE habilitaciones_generales SET 
                nro_licencia = :nro_licencia, 
                expte = :expte, 
                tipo_transporte = :tipo_transporte, 
                resolucion = :resolucion, 
                vigencia_inicio = :vigencia_inicio, 
                vigencia_fin = :vigencia_fin, 
                tipo = :tipo, 
                estado = :estado, 
                observaciones = :observaciones
              WHERE id = :id";
    
    $stmt = $pdo->prepare($query);

    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':nro_licencia', $input['nro_licencia']);
    $stmt->bindParam(':expte', $input['expte']);
    $stmt->bindParam(':tipo_transporte', $input['tipo_transporte']);
    $stmt->bindParam(':resolucion', $input['resolucion']);
    $stmt->bindParam(':vigencia_inicio', $input['vigencia_inicio']);
    $stmt->bindParam(':vigencia_fin', $input['vigencia_fin']);
    $stmt->bindParam(':tipo', $input['tipo']);
    $stmt->bindParam(':estado', $input['estado']);
    $stmt->bindParam(':observaciones', $input['observaciones']);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Habilitación actualizada con éxito.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró la habilitación o no hubo cambios para guardar.']);
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar la habilitación.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
