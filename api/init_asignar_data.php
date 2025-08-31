<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../nucleo/conexion.php';
require_once __DIR__ . '/../nucleo/verificar_sesion.php';

$response = ['exito' => false];

if (!isset($_GET['id']) || !isset($_GET['rol'])) {
    $response['error'] = 'Parámetros incompletos.';
    echo json_encode($response);
    exit;
}

$habilitacion_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
$rol_param = strtolower(trim($_GET['rol']));
$roles_permitidos = ['titular', 'chofer', 'celador'];

if (!$habilitacion_id || !in_array($rol_param, $roles_permitidos)) {
    $response['error'] = 'Parámetros no válidos.';
    echo json_encode($response);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT nro_licencia FROM habilitaciones_generales WHERE id = ?");
    $stmt->execute([$habilitacion_id]);
    $nro_licencia = $stmt->fetchColumn();

    if (!$nro_licencia) {
        $response['error'] = 'Habilitación no encontrada.';
        echo json_encode($response);
        exit;
    }

    $response['exito'] = true;
    $response['nro_licencia'] = $nro_licencia;
    $response['label'] = ucfirst($rol_param);
    $response['csrf_token'] = $_SESSION['csrf_token'];

} catch (PDOException $e) {
    $response['error'] = 'Error de base de datos: ' . $e->getMessage();
}

echo json_encode($response);
?>
