<?php
// 1. CONFIGURACIÓN INICIAL
// -----------------------------------------------------------------------------
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // Idealmente, restringe esto en producción.
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Manejar la solicitud preflight de CORS (método OPTIONS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(204); // No Content
    exit;
}
ini_set('display_errors', 0); // No mostrar errores de PHP en la respuesta.
error_reporting(0);

session_start(); // Iniciar la sesión para uso futuro.

// 2. INCLUSIÓN DE ARCHIVOS Y CONEXIÓN
// -----------------------------------------------------------------------------
try {
    require_once __DIR__ . '/nucleo/conexion.php'; // Usa el objeto $pdo
} catch (Exception $e) {
    http_response_code(503); // Service Unavailable
    echo json_encode(['success' => false, 'message' => 'Error crítico: No se pudo establecer la conexión con la base de datos.']);
    exit;
}

// 3. PROCESAMIENTO DE LA ENTRADA
// -----------------------------------------------------------------------------
$email = null;
$password = null;
$input = [];

// Leer el cuerpo de la solicitud y parsearlo manualmente.
// Esto es más confiable que depender de la populación automática de $_POST.
parse_str(file_get_contents("php://input"), $input);

if (isset($input['email']) && isset($input['password'])) {
    $email = $input['email'];
    $password = $input['password'];
}

if (empty($email) || empty($password)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Correo y contraseña son requeridos.']);
    exit;
}

// 4. LÓGICA DE AUTENTICACIÓN
// -----------------------------------------------------------------------------
try {
    $query = "SELECT id, nombre, password FROM admin WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($query);
    
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password'])) {
        // Contraseña correcta: Iniciar sesión y devolver éxito
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario_nombre'] = $user['nombre'];

        http_response_code(200); // OK
        echo json_encode(['success' => true, 'name' => $user['nombre']]);

    } else {
        // Usuario no encontrado o contraseña incorrecta
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas.']);
    }

} catch (PDOException $e) {
    // Error durante la consulta a la base de datos
    error_log('Error en la consulta de login: ' . $e->getMessage()); // Registrar el error
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error en el servidor.']);
}

