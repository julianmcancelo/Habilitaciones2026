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
$habilitacion_id = $input['id'] ?? null;

if (!$habilitacion_id || !is_numeric($habilitacion_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de habilitación no válido.']);
    exit;
}

// TODO: Añadir una capa de autorización aquí. Solo ciertos usuarios (ej. admin) deberían poder ejecutar esto.

$pdo->beginTransaction();

try {
    // 1. Obtener y eliminar los archivos de documentos asociados
    $stmt = $pdo->prepare("SELECT id, ruta_archivo_guardado FROM habilitaciones_documentos WHERE habilitacion_id = ?");
    $stmt->execute([$habilitacion_id]);
    $documentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($documentos as $doc) {
        // La ruta ya viene como 'uploads/...' así que la usamos directamente
        $filePath = __DIR__ . '/../' . $doc['ruta_archivo_guardado'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    // 2. Eliminar registros de la tabla 'habilitaciones_documentos'
    $stmt = $pdo->prepare("DELETE FROM habilitaciones_documentos WHERE habilitacion_id = ?");
    $stmt->execute([$habilitacion_id]);

    // 3. Eliminar registros de la tabla 'habilitaciones_personas'
    $stmt = $pdo->prepare("DELETE FROM habilitaciones_personas WHERE habilitacion_id = ?");
    $stmt->execute([$habilitacion_id]);

    // 4. Eliminar registros de la tabla 'habilitaciones_vehiculos'
    $stmt = $pdo->prepare("DELETE FROM habilitaciones_vehiculos WHERE habilitacion_id = ?");
    $stmt->execute([$habilitacion_id]);

    // 5. Finalmente, eliminar la habilitación principal
    $stmt = $pdo->prepare("DELETE FROM habilitaciones_generales WHERE id = ?");
    $stmt->execute([$habilitacion_id]);

    if ($stmt->rowCount() > 0) {
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Habilitación y todos sus datos asociados han sido eliminados permanentemente.']);
    } else {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No se encontró la habilitación para eliminar.']);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Error en API delete_habilitation: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos durante la eliminación.']);
}
