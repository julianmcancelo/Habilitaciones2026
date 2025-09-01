<?php
// Versión simplificada de asignar_turno.php sin transacciones
// Activar reportes de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en la salida pero sí registrarlos

// Configurar cabeceras para API JSON desde el principio
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
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

// Sistema de rutas para compatibilidad con diferentes entornos
require_once __DIR__ . '/include_path.php';

// Logging detallado para diagnóstico
error_log("=== INICIO asignar_turno_simple.php ===");

// Importar PHPMailer
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    error_log("PHPMailer autoload cargado correctamente");
} catch (Exception $e) {
    error_log("Error al cargar PHPMailer: " . $e->getMessage());
    // Continuar sin PHPMailer
}

// Importar el servicio de email
try {
    require_once find_project_file_path('/nucleo/email_service.php');
    error_log("Servicio de email cargado correctamente");
} catch (Exception $e) {
    error_log("Error al cargar servicio de email: " . $e->getMessage());
    // Continuar sin servicio de email
}

// CONFIGURACIÓN MANUAL DE LA CONEXIÓN (no depende de include_path)
try {
    // Determinar entorno
    $is_production = (strpos($_SERVER['HTTP_HOST'] ?? '', 'transportelanus.com.ar') !== false);
    error_log("Entorno detectado: " . ($is_production ? "PRODUCCIÓN" : "DESARROLLO"));

    // Configuración de BD según entorno
    if ($is_production) {
        // Entorno de producción
        $host = 'localhost';
        $db_name = 'transpo1_credenciales';
        $username = 'transpo1_credenciales';
        $password_db = 'feelthesky1';
    } else {
        // Entorno de desarrollo local
        $host = 'localhost';
        $db_name = 'transpo1_credenciales';
        $username = 'transpo1_credenciales';
        $password_db = 'feelthesky1';
    }
    
    // Opciones para PDO
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Conectar directamente (sin usar include)
    $dsn = "mysql:host=$host;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password_db, $options);
    error_log("Conexión PDO creada exitosamente");
} catch (PDOException $e) {
    error_log("Error de conexión PDO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error de conexión a la base de datos.',
        'error' => $e->getMessage()
    ]);
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
    error_log('Datos recibidos: ' . print_r($input, true));
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Error en el formato de los datos: ' . $e->getMessage()]);
    exit;
}

// Extraer y validar datos
$habilitacion_id = $input['habilitacion_id'] ?? null;
$fecha = $input['fecha'] ?? null;
$hora = $input['hora'] ?? null;
$observaciones = $input['observaciones'] ?? '';

// Validaciones
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

// 1. Verificar disponibilidad (sin transacción)
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM turnos WHERE fecha = ? AND hora = ?");
    $stmt->execute([$fecha, $hora]);
    $count = $stmt->fetchColumn();
    error_log("Verificación de disponibilidad: {$count} turnos encontrados para fecha {$fecha}, hora {$hora}");
    
    if ($count > 0) {
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => 'El turno seleccionado ya no está disponible.']);
        exit;
    }
} catch (PDOException $e) {
    error_log('Error al verificar disponibilidad: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al verificar disponibilidad.', 'error' => $e->getMessage()]);
    exit;
}

// 2. Insertar el turno (sin transacción)
try {
    // Combinar fecha y hora para el campo fecha_hora
    $fecha_hora = $fecha . ' ' . $hora;
    
    // Verificar la estructura de la tabla
    try {
        $structure = $pdo->query("DESCRIBE turnos");
        $columns = $structure->fetchAll(PDO::FETCH_COLUMN);
        error_log("Columnas en tabla turnos: " . implode(', ', $columns));
    } catch (Exception $e) {
        error_log("Error al verificar estructura: " . $e->getMessage());
    }
    
    // Preparar la consulta SQL con todos los campos requeridos
    $sql = "INSERT INTO turnos 
        (habilitacion_id, fecha_hora, fecha, hora, observaciones, estado, recordatorio_enviado) 
        VALUES (?, ?, ?, ?, ?, 'PENDIENTE', 0)";
        
    error_log("SQL a ejecutar: {$sql} con parámetros: " . 
              "habilitacion_id={$habilitacion_id}, fecha_hora={$fecha_hora}, fecha={$fecha}, hora={$hora}");
    
    $stmt = $pdo->prepare($sql);
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
    error_log("Turno insertado con ID: {$turno_id}");
    
    // Verificar que el registro realmente se insertó
    $verify = $pdo->prepare("SELECT COUNT(*) FROM turnos WHERE id = ?");
    $verify->execute([$turno_id]);
    $count = $verify->fetchColumn();
    
    if ($count > 0) {
        error_log("Verificación post-inserción: ENCONTRADO - ID {$turno_id}");
        
        // Intentar enviar correo electrónico de confirmación
        try {
            // Obtener email de la persona con esta habilitación
            $stmt_email = $pdo->prepare("SELECT email, nombre, apellido FROM habilitaciones WHERE id = ?");
            $stmt_email->execute([$habilitacion_id]);
            $habilitacion_data = $stmt_email->fetch(PDO::FETCH_ASSOC);
            $email = $habilitacion_data['email'] ?? null;
            
            if (!empty($email)) {
                // Enviar correo de confirmación
                error_log("Enviando confirmación por email a {$email}");
                
                // Inicializar servicio de email
                $emailService = new EmailService();
                
                // Configurar el servicio con contraseña (actualizar según corresponda)
                $emailService->setConfig([
                    'password' => 'password_del_correo_electronico', // Actualizar con la contraseña real
                ]);
                
                // Preparar datos del turno para el email
                $turno_data = [
                    'habilitacion_id' => $habilitacion_id,
                    'fecha' => $fecha,
                    'hora' => $hora,
                    'id' => $turno_id,
                    'nombre' => $habilitacion_data['nombre'] ?? '',
                    'apellido' => $habilitacion_data['apellido'] ?? ''
                ];
                
                // Enviar email
                $email_sent = $emailService->sendTurnoConfirmacion($email, $turno_data);
                
                if ($email_sent) {
                    error_log("Email de confirmación enviado con éxito a {$email}");
                } else {
                    error_log("Error al enviar email de confirmación a {$email}");
                }
            } else {
                error_log("No se encontró email para habilitación ID {$habilitacion_id}");
            }
        } catch (Exception $e) {
            error_log("Error al procesar envío de email: " . $e->getMessage());
            // Continuar con la respuesta normal a pesar del error de email
        }
        
        // Respuesta exitosa
        echo json_encode([
            'success' => true, 
            'message' => 'Turno asignado correctamente.', 
            'turno_id' => $turno_id,
            'fecha' => $fecha,
            'hora' => $hora,
            'version' => 'simple',
            'email_sent' => $email_sent ?? false
        ]);
    } else {
        error_log("Verificación post-inserción: NO ENCONTRADO - ID {$turno_id}");
        throw new Exception("El registro no aparece después de la inserción");
    }

} catch (Exception $e) {
    error_log('Error al insertar turno: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error al registrar el turno: ' . $e->getMessage()
    ]);
}
?>
