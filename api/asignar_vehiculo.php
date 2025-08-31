<?php
header('Content-Type: application/json');

$response = ['exito' => false, 'error' => ''];

try {
    require_once __DIR__ . '/nucleo/conexion.php';
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['exito' => false, 'error' => 'Error fatal al conectar con la BD: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['error'] = 'Método no permitido.';
    echo json_encode($response);
    exit;
}

$habilitacion_id = filter_input(INPUT_POST, 'habilitacion_id', FILTER_VALIDATE_INT);
$vehiculo_id = filter_input(INPUT_POST, 'vehiculo_id', FILTER_VALIDATE_INT);

if (!$habilitacion_id) {
    $response['error'] = 'Falta el ID de la habilitación.';
    echo json_encode($response);
    exit;
}

try {
    $pdo->beginTransaction();

    // Si no hay vehiculo_id, podría ser un vehículo nuevo o uno existente no seleccionado.
    if (empty($vehiculo_id)) {
        $dominio = strtoupper(trim($_POST['dominio'] ?? ''));
        if (empty($dominio)) {
            throw new Exception('El dominio es obligatorio.');
        }

        // 1. Buscar si el vehículo ya existe por dominio
        $sql_find = "SELECT id FROM vehiculos WHERE dominio = ?";
        $stmt_find = $pdo->prepare($sql_find);
        $stmt_find->execute([$dominio]);
        $existing_vehicle = $stmt_find->fetch(PDO::FETCH_ASSOC);

        if ($existing_vehicle) {
            // Si ya existe, usar su ID
            $vehiculo_id = $existing_vehicle['id'];
        } else {
            // Si no existe, crearlo
            $sql_insert_vehiculo = "INSERT INTO vehiculos (dominio, marca, modelo, tipo, chasis, ano, motor, asientos, inscripcion_inicial, Aseguradora, poliza, Vencimiento_Poliza, Vencimiento_VTV) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert_vehiculo);
            $stmt_insert->execute([
                $dominio,
                $_POST['marca'] ?? null,
                $_POST['modelo'] ?? null,
                $_POST['tipo'] ?? null,
                $_POST['chasis'] ?? null,
                empty($_POST['ano']) ? null : $_POST['ano'],
                $_POST['motor'] ?? null,
                empty($_POST['asientos']) ? null : $_POST['asientos'],
                empty($_POST['inscripcion_inicial']) ? null : $_POST['inscripcion_inicial'],
                $_POST['Aseguradora'] ?? null,
                $_POST['poliza'] ?? null,
                empty($_POST['Vencimiento_Poliza']) ? null : $_POST['Vencimiento_Poliza'],
                empty($_POST['Vencimiento_VTV']) ? null : $_POST['Vencimiento_VTV']
            ]);
            $vehiculo_id = $pdo->lastInsertId();
        }
    }

    // Verificar si ya existe una asignación para esta habilitación
    $sql_check = "SELECT id FROM habilitaciones_vehiculos WHERE habilitacion_id = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$habilitacion_id]);

    if ($stmt_check->fetch()) {
        // Si existe, actualizar el vehículo asignado
        $sql_asignar = "UPDATE habilitaciones_vehiculos SET vehiculo_id = ? WHERE habilitacion_id = ?";
        $stmt_asignar = $pdo->prepare($sql_asignar);
        $stmt_asignar->execute([$vehiculo_id, $habilitacion_id]);
    } else {
        // Si no existe, crear una nueva asignación
        $sql_asignar = "INSERT INTO habilitaciones_vehiculos (habilitacion_id, vehiculo_id) VALUES (?, ?)";
        $stmt_asignar = $pdo->prepare($sql_asignar);
        $stmt_asignar->execute([$habilitacion_id, $vehiculo_id]);
    }

    $pdo->commit();

    $response['exito'] = true;

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);

    // Manejo de errores para producción
    if ($e->getCode() == 23000) { 
        $response['error'] = 'Este vehículo ya está asignado o el dominio ya está en uso.';
    } else {
        $response['error'] = 'Ocurrió un error inesperado en el servidor.';
        // Opcional: Registrar el error real en un log del servidor para el administrador
        // error_log($e->getMessage());
    }
}
}

echo json_encode($response);
?>
