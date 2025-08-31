<?php
header('Content-Type: application/json');

$response = ['exito' => false, 'vehiculos' => [], 'error' => ''];

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
    $sql = "SELECT id, dominio, marca, modelo, tipo, chasis, ano, motor, asientos, inscripcion_inicial, Aseguradora, poliza, Vencimiento_Poliza, Vencimiento_VTV 
            FROM vehiculos 
            WHERE dominio LIKE ? 
            LIMIT 10";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$busqueda]);
    
    $vehiculos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['exito'] = true;
    $response['vehiculos'] = $vehiculos;

} catch (PDOException $e) {
    http_response_code(500);
    $response['error'] = 'Error en la consulta a la base de datos: ' . $e->getMessage();
}

echo json_encode($response);
?>
