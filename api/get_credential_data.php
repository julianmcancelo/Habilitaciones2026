<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../nucleo/conexion.php';

$response = ['success' => false, 'message' => 'ID de habilitación no proporcionado.'];

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // The $pdo variable is now available from the included conexion.php file.
    if (isset($pdo)) {
        try {
            $sql = "
            SELECT 
                hg.id, hg.nro_licencia, hg.resolucion, hg.vigencia_inicio, hg.vigencia_fin, hg.estado, hg.tipo_transporte,
                p_titular.nombre AS titular_nombre, p_titular.dni AS titular_dni, p_titular.cuit AS titular_cuit, p_titular.foto_url AS titular_foto,
                p_conductor.nombre AS conductor_nombre, p_conductor.dni AS conductor_dni, p_conductor.foto_url AS conductor_foto,
                hc.licencia_categoria,
                p_celador.nombre AS celador_nombre, p_celador.dni AS celador_dni, p_celador.foto_url AS celador_foto,
                e.nombre AS escuela_nombre, e.domicilio AS escuela_domicilio, e.localidad AS escuela_localidad,
                v.marca, v.modelo, v.ano, v.motor, v.chasis, v.asientos, v.dominio, v.Aseguradora, v.poliza, v.Vencimiento_VTV, v.Vencimiento_Poliza
            FROM habilitaciones_generales hg
            LEFT JOIN habilitaciones_personas ht ON ht.habilitacion_id = hg.id AND ht.rol = 'TITULAR' LEFT JOIN personas p_titular ON p_titular.id = ht.persona_id
            LEFT JOIN habilitaciones_personas hc ON hc.habilitacion_id = hg.id AND hc.rol = 'CONDUCTOR' LEFT JOIN personas p_conductor ON p_conductor.id = hc.persona_id
            LEFT JOIN habilitaciones_personas hce ON hce.habilitacion_id = hg.id AND hce.rol = 'CELADOR' LEFT JOIN personas p_celador ON p_celador.id = hce.persona_id
            LEFT JOIN habilitaciones_establecimientos he ON he.habilitacion_id = hg.id LEFT JOIN establecimientos e ON e.id = he.establecimiento_id AND he.tipo = 'establecimiento'
            LEFT JOIN habilitaciones_vehiculos hv ON hv.habilitacion_id = hg.id LEFT JOIN vehiculos v ON v.id = hv.vehiculo_id
            WHERE hg.id = :id
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // The aliases in the SQL query match the keys expected by the frontend.
                // We can send the whole row.
                $response = ['success' => true, 'data' => $row];
            } else {
                $response['message'] = 'No se encontró la habilitación.';
            }
        } catch (PDOException $e) {
            // Log the error for debugging, don't show details to the user
            error_log('Database query failed: ' . $e->getMessage());
            $response['message'] = 'Error al consultar la base de datos.';
        }
    } else {
        $response['message'] = 'Error de conexión a la base de datos (PDO object not found).';
    }
} 

echo json_encode($response);
?>
