<?php
header('Content-Type: application/json');

// Comentado para depuración, el archivo no existe.
// require_once __DIR__ . '/../nucleo/verificar_sesion.php';

$response = ['exito' => false, 'personas' => [], 'error' => ''];

try {
    require_once __DIR__ . '/../nucleo/conexion.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'error' => 'Error fatal al conectar con la BD: ' . $e->getMessage()]);
    exit;
}

if (!isset($_GET['q']) || strlen($_GET['q']) < 2) {
    $response['error'] = 'El término de búsqueda es muy corto.';
    echo json_encode($response);
    exit;
}

$busqueda = '%' . trim($_GET['q']) . '%';

try {
    $sql = "SELECT id, nombre, dni, genero, cuit, telefono, email, 
                   CONCAT_WS(' ', domicilio_calle, domicilio_nro, domicilio_localidad) AS domicilio, 
                   foto_url 
            FROM personas 
            WHERE nombre LIKE ? OR dni LIKE ? 
            LIMIT 10";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$busqueda, $busqueda]);
    
    $personas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['exito'] = true;
    $response['personas'] = $personas;

} catch (PDOException $e) {
    http_response_code(500);
    $response['error'] = 'Error en la consulta a la base de datos: ' . $e->getMessage();
}

echo json_encode($response);
?>
