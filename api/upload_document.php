<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../nucleo/conexion.php';

// Los datos del formulario vienen como POST, no como JSON
$habilitacion_id = $_POST['habilitacion_id'] ?? null;
$tipo = $_POST['tipo'] ?? null;

if (!$habilitacion_id || !$tipo || !isset($_FILES['documento'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Faltan datos requeridos (ID de habilitación, tipo o archivo).']);
    exit;
}

if ($_FILES['documento']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al subir el archivo. Código: ' . $_FILES['documento']['error']]);
    exit;
}

$uploadDir = __DIR__ . '/../uploads/';

// Intentar crear el directorio si no existe
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error crítico: No se pudo crear el directorio de subidas. Verifique los permisos del servidor.']);
        exit;
    }
}

// Verificar si el directorio tiene permisos de escritura
if (!is_writable($uploadDir)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error crítico: El directorio de subidas no tiene permisos de escritura.']);
    exit;
}

$fileName = uniqid() . '-' . basename($_FILES['documento']['name']);
$uploadFile = $uploadDir . $fileName;
$db_path = 'uploads/' . $fileName; // Ruta relativa para la BD
$nombre_original = $_FILES['documento']['name'];

if (move_uploaded_file($_FILES['documento']['tmp_name'], $uploadFile)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO habilitaciones_documentos (habilitacion_id, tipo_documento, ruta_archivo_guardado, nombre_archivo_original, fecha_subida) VALUES (?, ?, ?, ?, NOW())");
        if ($stmt->execute([$habilitacion_id, $tipo, $db_path, $nombre_original])) {
            echo json_encode(['success' => true, 'message' => 'Documento subido con éxito.']);
        }
    } catch (PDOException $e) {
        // Si falla la BD, eliminar el archivo subido para no dejar basura
        unlink($uploadFile);
        error_log('Error en API upload_document: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al guardar el documento en la base de datos.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo mover el archivo subido.']);
}
