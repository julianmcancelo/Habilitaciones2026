<?php
// Activar reportes de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en la salida pero sí registrarlos

// Configurar cabeceras para API JSON desde el principio
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Sistema de rutas para compatibilidad con diferentes entornos
require_once __DIR__ . '/include_path.php';

// Intentar incluir la conexión a la base de datos usando el helper
try {
    // Intentar primero con el sistema de rutas
    include_from_root('/nucleo/conexion.php');
} catch (Exception $e) {
    // Si falla, intentar cargar la copia local directamente
    $local_conexion = __DIR__ . '/conexion.php';
    
    if (file_exists($local_conexion)) {
        require_once $local_conexion;
        error_log("Se usó la copia local de conexion.php como fallback en asignar_turno.php");
    } else {
        // Error fatal si no podemos cargar ninguna versión
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Error crítico: No se pudo cargar el archivo de conexión.',
            'debug' => 'No se encontró la copia local en: ' . $local_conexion
        ]);
        exit;
    }
}

// Configurar cabeceras para API JSON
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar la solicitud OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Verificar que la solicitud sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Método no permitido
    echo json_encode(['success' => false, 'message' => 'Método no permitido. Use POST para esta API.']);
    exit;
}

// Leer el cuerpo de la solicitud
$input_json = file_get_contents('php://input');

// Verificar que se recibieron datos
if (empty($input_json)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'No se recibieron datos en el cuerpo de la solicitud.']);
    exit;
}

// Decodificar JSON
try {
    $input = json_decode($input_json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Error al decodificar JSON: ' . json_last_error_msg());
    }
    
    // Registrar los datos recibidos para depuración
    error_log('Datos recibidos en asignar_turno.php: ' . print_r($input, true));
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error en el formato de los datos: ' . $e->getMessage()]);
    exit;
}

$habilitacion_id = $input['habilitacion_id'] ?? null;
$fecha = $input['fecha'] ?? null;
$hora = $input['hora'] ?? null;
$observaciones = $input['observaciones'] ?? '';

// Validaciones más estrictas
if (!$habilitacion_id || !is_numeric($habilitacion_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de habilitación inválido o faltante.']);
    exit;
}

if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido. Use YYYY-MM-DD.']);
    exit;
}

if (!$hora || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Formato de hora inválido. Use HH:MM o HH:MM:SS.']);
    exit;
}

// Validar que la fecha no sea anterior a hoy
$today = new DateTime('today');
$selectedDate = new DateTime($fecha);
if ($selectedDate < $today) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se puede asignar un turno en una fecha pasada.']);
    exit;
}

// Verificar la conexión antes de proceder
if (!isset($pdo) || empty($pdo)) {
    error_log('Error: No hay conexión PDO disponible en asignar_turno.php');
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error de conexión a la base de datos.',
        'error_code' => 'DB_CONNECTION_MISSING'
    ]);
    exit;
}

// Iniciar transacción
try {
    $pdo->beginTransaction();
    error_log('Transacción iniciada correctamente');

    // 1. Verificar disponibilidad para evitar duplicados (race condition)
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM turnos WHERE fecha = ? AND hora = ?");
        $stmt->execute([$fecha, $hora]);
        $count = $stmt->fetchColumn();
        error_log("Verificación de disponibilidad: {$count} turnos encontrados para fecha {$fecha}, hora {$hora}");
        
        if ($count > 0) {
            http_response_code(409); // Conflict
            echo json_encode(['success' => false, 'message' => 'El turno seleccionado ya no está disponible. Por favor, elija otro horario.']);
            $pdo->rollBack();
            exit;
        }
    } catch (PDOException $e) {
        error_log('Error al verificar disponibilidad: ' . $e->getMessage());
        throw new Exception('Error al verificar disponibilidad del turno: ' . $e->getMessage());
    }

    // 2. Insertar el nuevo turno
    try {
        // Combinar fecha y hora para el campo fecha_hora
        $fecha_hora = $fecha . ' ' . $hora;
        
        // Registrar información detallada para diagnóstico
        error_log("=== COMENZANDO INSERCIÓN DE TURNO ====");
        error_log("Parámetros de inserción: habilitacion_id={$habilitacion_id}, fecha_hora={$fecha_hora}, fecha={$fecha}, hora={$hora}");
        error_log("Estado de la conexión PDO: " . (isset($pdo) ? 'Disponible' : 'No disponible'));
        
        // Verificar si la tabla existe
        try {
            $table_check = $pdo->query("SHOW TABLES LIKE 'turnos'");
            $table_exists = $table_check->rowCount() > 0;
            error_log("Verificación de tabla turnos: " . ($table_exists ? 'EXISTE' : 'NO EXISTE'));
            
            if ($table_exists) {
                // Verificar estructura de la tabla
                $structure = $pdo->query("DESCRIBE turnos");
                $columns = $structure->fetchAll(PDO::FETCH_COLUMN);
                error_log("Columnas en tabla turnos: " . implode(', ', $columns));
            }
        } catch (Exception $table_e) {
            error_log("Error al verificar estructura de tabla: " . $table_e->getMessage());
        }
        
        // Preparar la consulta SQL con todos los campos requeridos
        $sql = "INSERT INTO turnos 
            (habilitacion_id, fecha_hora, fecha, hora, observaciones, estado, recordatorio_enviado) 
            VALUES (?, ?, ?, ?, ?, 'PENDIENTE', 0)";
            
        error_log("SQL a ejecutar: {$sql}");
        $stmt = $pdo->prepare($sql);
        
        // Ejecutar con parámetros
        $result = $stmt->execute([
            $habilitacion_id, 
            $fecha_hora,    // fecha_hora combinada
            $fecha,         // fecha
            $hora,          // hora
            $observaciones  // observaciones
        ]);
        
        if (!$result) {
            $error_info = $stmt->errorInfo();
            error_log("Error en la ejecución SQL: " . print_r($error_info, true));
            throw new Exception("Error en insert: " . $error_info[2]);
        }
        
        $turno_id = $pdo->lastInsertId();
        error_log("Turno insertado correctamente con ID: {$turno_id} para fecha_hora: {$fecha_hora}");
        
        // Verificar que el registro realmente se insertó
        $verify = $pdo->prepare("SELECT COUNT(*) FROM turnos WHERE id = ?");
        $verify->execute([$turno_id]);
        $count = $verify->fetchColumn();
        error_log("Verificación post-inserción: " . ($count > 0 ? "ENCONTRADO" : "NO ENCONTRADO") . " - ID {$turno_id}");
    } catch (PDOException $e) {
        error_log('Error al insertar turno: ' . $e->getMessage());
        throw new Exception('Error al registrar el turno en la base de datos: ' . $e->getMessage());
    }

    /*
    // 3. Obtener datos para el email
    try {
        $stmt = $pdo->prepare("SELECT p.email, p.nombre, p.apellido, h.nro_licencia FROM habilitaciones h JOIN personas p ON h.titular_id = p.id WHERE h.id = ?");
        $stmt->execute([$habilitacion_id]);
        $destinatario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($destinatario && !empty($destinatario['email'])) {
            error_log("Datos de destinatario obtenidos para notificación: {$destinatario['email']}");
            // El envío de email está desactivado temporalmente.
            // Para activarlo, instale PHPMailer y descomente este bloque
        }
    } catch (PDOException $e) {
        error_log('Error al obtener datos para notificación: ' . $e->getMessage());
        // No interrumpimos la transacción si falla la obtención de datos de email
    }
    */
    
    // Confirmar la transacción
    $pdo->commit();
    error_log('Transacción completada y confirmada');
    
    // Devolver respuesta exitosa
    echo json_encode([
        'success' => true, 
        'message' => 'Turno asignado correctamente.', 
        'turno_id' => $turno_id,
        'fecha' => $fecha,
        'hora' => $hora
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        error_log('Transacción revertida debido a error: ' . $e->getMessage());
    }
    
    // Responder con error
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error al procesar el turno. Intente nuevamente o contacte al administrador.',
        'error_detail' => $e->getMessage()
    ]);
}
?>
