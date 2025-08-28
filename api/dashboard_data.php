<?php
// 1. CONFIGURACIÓN INICIAL
// -----------------------------------------------------------------------------
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *'); // Permite el acceso desde cualquier origen.
ini_set('display_errors', 0); // Desactivar la visualización de errores en producción.
error_reporting(0);

session_start();

// 2. INCLUSIÓN DE ARCHIVOS Y VERIFICACIÓN DE CONEXIÓN
// -----------------------------------------------------------------------------
try {
    // Se asume que este archivo establece la conexión y crea el objeto $pdo.
    require_once __DIR__ . '/../nucleo/conexion.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error crítico: No se pudo cargar el archivo de conexión.']);
    exit;
}

// 3. VERIFICACIÓN DE SESIÓN DE USUARIO
// -----------------------------------------------------------------------------
// Descomentar esta sección cuando la gestión de sesiones esté integrada con la app Electron.
/*
if (!isset($_SESSION['usuario_id'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado.']);
    exit;
}
*/

// 4. LÓGICA DE OBTENCIÓN DE DATOS
// -----------------------------------------------------------------------------
try {
    $datos = [];
    $response = ['success' => true];

    // Definir el ámbito de transporte. En el futuro, podría depender del rol del usuario.
    $transporte_scope = "'Escolar', 'Remis'";

    // --- KPIs (Indicadores Clave de Rendimiento) ---
    $datos['kpi_activas'] = $pdo->query("SELECT COUNT(id) FROM habilitaciones_generales WHERE estado = 'HABILITADO' AND tipo_transporte IN ($transporte_scope) AND is_deleted = 0")->fetchColumn();
    $datos['kpi_en_tramite'] = $pdo->query("SELECT COUNT(id) FROM habilitaciones_generales WHERE estado = 'EN TRAMITE' AND tipo_transporte IN ($transporte_scope) AND is_deleted = 0")->fetchColumn();
    $datos['kpi_por_vencer'] = $pdo->query("SELECT COUNT(id) FROM habilitaciones_generales WHERE estado = 'HABILITADO' AND vigencia_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND tipo_transporte IN ($transporte_scope) AND is_deleted = 0")->fetchColumn();
    $datos['kpi_obleas_pendientes'] = $pdo->query("SELECT COUNT(id) FROM habilitaciones_generales WHERE estado = 'HABILITADO' AND oblea_colocada = FALSE AND tipo_transporte IN ($transporte_scope) AND is_deleted = 0")->fetchColumn();

    $response['kpis'] = [
        'activas' => (int) $datos['kpi_activas'],
        'en_tramite' => (int) $datos['kpi_en_tramite'],
        'por_vencer' => (int) $datos['kpi_por_vencer'],
        'obleas_pendientes' => (int) $datos['kpi_obleas_pendientes']
    ];

    // --- Tabla de Habilitaciones ---
    $sql_habilitaciones = "
        SELECT 
            hg.id AS habilitacion_id, 
            hg.nro_licencia, 
            hg.estado, 
            hg.vigencia_fin, 
            hg.tipo_transporte, 
            (SELECT p.nombre FROM habilitaciones_personas hp JOIN personas p ON p.id = hp.persona_id WHERE hp.habilitacion_id = hg.id AND hp.rol = 'TITULAR' LIMIT 1) AS titular
        FROM habilitaciones_generales hg 
        WHERE hg.is_deleted = 0 AND hg.tipo_transporte IN ($transporte_scope)
        ORDER BY hg.id DESC
        LIMIT 100; -- Limitar a los 100 registros más recientes para no sobrecargar la app
    ";

    $stmt = $pdo->prepare($sql_habilitaciones);
    $stmt->execute();
    $response['habilitaciones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. RESPUESTA FINAL
    // -----------------------------------------------------------------------------
    echo json_encode($response);

} catch (PDOException $e) {
    http_response_code(500);
    // Enviar un error genérico al cliente y registrar el error real para depuración.
    error_log('Error de base de datos en dashboard_data.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al consultar la base de datos.']);
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
}
