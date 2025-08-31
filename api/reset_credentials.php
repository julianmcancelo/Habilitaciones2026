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
$persona_id = $input['id'] ?? null;

if (!$persona_id || !is_numeric($persona_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de persona no válido.']);
    exit;
}

// Generar una contraseña temporal segura
$new_password = bin2hex(random_bytes(8)); // 16 caracteres hexadecimales
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    // La tabla de usuarios con credenciales es 'admin'. Se usa el ID de la persona para encontrar al usuario admin.
    // Esta lógica asume una relación entre 'personas' y 'admin', por ejemplo, a través del email o un campo user_id.
    // Por ahora, se actualizará usando el ID directamente, asumiendo que el ID de persona es el mismo que el de admin.
    $stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?");
    $stmt->execute([$hashed_password, $persona_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'La contraseña ha sido reseteada.', 'new_password' => $new_password]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No se encontró un usuario administrador con el ID proporcionado para resetear la contraseña.']);
    }

} catch (PDOException $e) {
    error_log('Error en API reset_credentials: ' . $e->getMessage());
    http_response_code(500);
    // Devuelve un mensaje genérico para no exponer detalles de la BD
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos al intentar resetear la contraseña. Verifique que la tabla `personas` y la columna `password` existan.']);
}
