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
$documento_id = $input['id'] ?? null;

if (!$documento_id || !is_numeric($documento_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de documento no válido.']);
    exit;
}

$pdo->beginTransaction();

try {
    // 1. Obtener la ruta del archivo para poder eliminarlo
    $stmt = $pdo->prepare("SELECT ruta_archivo_guardado FROM habilitaciones_documentos WHERE id = ?");
    $stmt->execute([$documento_id]);
    $documento = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$documento || !isset($documento['ruta_archivo_guardado'])) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Documento no encontrado.']);
        $pdo->rollBack();
        exit;
    }

    // Eliminar el registro de la base de datos
    $stmt = $pdo->prepare("DELETE FROM habilitaciones_documentos WHERE id = ?");
    $stmt->execute([$documento_id]);

    if ($stmt->rowCount() > 0) {
        // 3. Si se eliminó de la BD, eliminar el archivo físico
        // La ruta en la BD es relativa al script, ej: 'uploads/documento.pdf'
        $filePath = __DIR__ . '/../' . $documento['ruta_archivo_guardado'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Documento eliminado con éxito.']);
    } else {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el documento de la base de datos.']);
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Error en API delete_document: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos.']);
}
