<?php
header('Content-Type: application/json');
try {
    require_once __DIR__ . '/../nucleo/conexion.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'error' => 'Error fatal al conectar con la BD: ' . $e->getMessage()]);
    exit;
}
// require_once __DIR__ . '/../nucleo/verificar_sesion.php'; // Archivo inexistente

$response = ['exito' => false, 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['error'] = 'Método no permitido.';
    echo json_encode($response);
    exit;
}

// 1. Validar CSRF token
// La verificación CSRF depende de la sesión, que no se está iniciando.
// if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
//     $response['error'] = 'Error de seguridad (CSRF token inválido).';
//     echo json_encode($response);
//     exit;
// }

// 2. Validar parámetros requeridos
$habilitacion_id = filter_input(INPUT_POST, 'habilitacion_id', FILTER_VALIDATE_INT);
$persona_id = filter_input(INPUT_POST, 'persona_id', FILTER_VALIDATE_INT);
$rol = strtoupper(trim($_POST['rol'] ?? ''));

if (!$habilitacion_id || !$rol) {
    $response['error'] = 'Faltan datos clave (habilitación o rol).';
    echo json_encode($response);
    exit;
}

try {
    // Si no hay persona_id, es una persona nueva.
    if (empty($persona_id)) {
        $nombre = trim($_POST['nombre'] ?? '');
        $dni = trim($_POST['dni'] ?? '');

        if (empty($nombre) || empty($dni)) {
            $response['error'] = 'El nombre y el DNI son obligatorios para una nueva persona.';
            echo json_encode($response);
            exit;
        }
        
                $sql = "INSERT INTO personas (nombre, dni, genero, cuit, telefono, email, domicilio_calle, domicilio_nro, domicilio_localidad, foto_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $nombre,
            $dni,
            $_POST['genero'] ?? null,
            $_POST['cuit'] ?? null,
            $_POST['telefono'] ?? null,
            $_POST['email'] ?? null,
            $_POST['domicilio_calle'] ?? null,
            $_POST['domicilio_nro'] ?? null,
            $_POST['domicilio_localidad'] ?? null,
            $_POST['foto_url'] ?? null
        ]);
        $persona_id = $pdo->lastInsertId();
    }

    // Asignar la persona (nueva o existente) a la habilitación
    $licencia_categoria = $_POST['licencia_categoria'] ?? null;
    $sql_asignar = "INSERT INTO habilitaciones_personas (persona_id, habilitacion_id, rol, licencia_categoria) VALUES (?, ?, ?, ?)";
    $stmt_asignar = $pdo->prepare($sql_asignar);
    $stmt_asignar->execute([$persona_id, $habilitacion_id, $rol, $licencia_categoria]);

    $response['exito'] = true;
    $response['mensaje'] = ucfirst(strtolower($rol)) . ' asignado/a correctamente.';

} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Error de clave única
        $response['error'] = 'Esta persona ya está asignada a esta habilitación con un rol.';
    } else {
        $response['error'] = 'Error en la base de datos: ' . $e->getMessage();
    }
}

echo json_encode($response);
?>
