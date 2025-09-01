<?php
// Script para verificar la estructura de la tabla turnos
// Activar reportes de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1); 

// Configurar cabeceras para API JSON
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
        error_log("Se usó la copia local de conexion.php como fallback");
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

// Resultado final
$result = [
    'success' => false,
    'message' => 'Verificación de tabla turnos',
    'entorno' => $is_production ? 'Producción' : 'Desarrollo',
    'tests' => []
];

// Test 1: Verificar conexión PDO
if (isset($pdo) && $pdo instanceof PDO) {
    $result['tests'][] = [
        'nombre' => 'Conexión PDO',
        'resultado' => 'OK',
        'detalle' => 'La conexión PDO está disponible'
    ];
} else {
    $result['tests'][] = [
        'nombre' => 'Conexión PDO',
        'resultado' => 'ERROR',
        'detalle' => 'No hay conexión PDO disponible'
    ];
    echo json_encode($result);
    exit;
}

// Test 2: Verificar si la tabla turnos existe
try {
    $table_check = $pdo->query("SHOW TABLES LIKE 'turnos'");
    $table_exists = $table_check->rowCount() > 0;
    
    if ($table_exists) {
        $result['tests'][] = [
            'nombre' => 'Existencia de tabla',
            'resultado' => 'OK',
            'detalle' => 'La tabla turnos existe'
        ];
    } else {
        $result['tests'][] = [
            'nombre' => 'Existencia de tabla',
            'resultado' => 'ERROR',
            'detalle' => 'La tabla turnos NO existe'
        ];
        // Si la tabla no existe, crear recomendación para crearla
        $result['sql_recomendado'] = "
CREATE TABLE `turnos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `habilitacion_id` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `observaciones` text,
  `estado` enum('PENDIENTE','CONFIRMADO','CANCELADO','AUSENTE') NOT NULL DEFAULT 'PENDIENTE',
  `recordatorio_enviado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `habilitacion_id` (`habilitacion_id`),
  KEY `fecha_hora` (`fecha_hora`),
  KEY `fecha` (`fecha`),
  CONSTRAINT `turnos_ibfk_1` FOREIGN KEY (`habilitacion_id`) REFERENCES `habilitaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
    }
} catch (Exception $e) {
    $result['tests'][] = [
        'nombre' => 'Existencia de tabla',
        'resultado' => 'ERROR',
        'detalle' => 'Error al verificar tabla: ' . $e->getMessage()
    ];
}

// Si la tabla existe, verificar su estructura
if ($table_exists) {
    try {
        // Test 3: Verificar columnas de la tabla
        $structure = $pdo->query("DESCRIBE turnos");
        $columns = $structure->fetchAll(PDO::FETCH_ASSOC);
        $column_names = array_column($columns, 'Field');
        
        $result['tests'][] = [
            'nombre' => 'Estructura de tabla',
            'resultado' => 'OK',
            'detalle' => 'Columnas encontradas: ' . implode(', ', $column_names)
        ];
        
        // Verificar columnas específicas requeridas
        $required_columns = ['id', 'habilitacion_id', 'fecha_hora', 'fecha', 'hora', 'estado'];
        $missing_columns = array_diff($required_columns, $column_names);
        
        if (empty($missing_columns)) {
            $result['tests'][] = [
                'nombre' => 'Columnas requeridas',
                'resultado' => 'OK',
                'detalle' => 'Todas las columnas requeridas existen'
            ];
        } else {
            $result['tests'][] = [
                'nombre' => 'Columnas requeridas',
                'resultado' => 'ERROR',
                'detalle' => 'Faltan columnas: ' . implode(', ', $missing_columns)
            ];
        }
        
        // Guardar estructura completa para referencia
        $result['estructura'] = $columns;
        
        // Test 4: Intentar insertar un registro de prueba
        try {
            $pdo->beginTransaction();
            
            $fecha_test = date('Y-m-d');
            $hora_test = '10:00:00';
            $fecha_hora_test = $fecha_test . ' ' . $hora_test;
            
            $stmt = $pdo->prepare("INSERT INTO turnos 
                (habilitacion_id, fecha_hora, fecha, hora, observaciones, estado, recordatorio_enviado) 
                VALUES (1, ?, ?, ?, 'PRUEBA - IGNORAR', 'PENDIENTE', 0)");
                
            $insert_result = $stmt->execute([$fecha_hora_test, $fecha_test, $hora_test]);
            
            if ($insert_result) {
                $test_id = $pdo->lastInsertId();
                
                $result['tests'][] = [
                    'nombre' => 'Inserción de prueba',
                    'resultado' => 'OK',
                    'detalle' => "Se insertó un registro de prueba con ID: {$test_id}"
                ];
                
                // Eliminar el registro de prueba para no dejar basura
                $pdo->exec("DELETE FROM turnos WHERE id = {$test_id}");
            } else {
                $error_info = $stmt->errorInfo();
                throw new Exception("Error en la inserción: " . $error_info[2]);
            }
            
            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            $result['tests'][] = [
                'nombre' => 'Inserción de prueba',
                'resultado' => 'ERROR',
                'detalle' => 'No se pudo insertar: ' . $e->getMessage()
            ];
        }
        
        // Test 5: Verificar permisos del usuario en la tabla
        try {
            $permisos = $pdo->query("SHOW GRANTS FOR CURRENT_USER()")->fetchAll(PDO::FETCH_COLUMN);
            
            $result['tests'][] = [
                'nombre' => 'Permisos de usuario',
                'resultado' => 'INFO',
                'detalle' => $permisos
            ];
        } catch (Exception $e) {
            $result['tests'][] = [
                'nombre' => 'Permisos de usuario',
                'resultado' => 'ERROR',
                'detalle' => 'No se pudieron verificar permisos: ' . $e->getMessage()
            ];
        }
    } catch (Exception $e) {
        $result['tests'][] = [
            'nombre' => 'Verificación de estructura',
            'resultado' => 'ERROR',
            'detalle' => 'Error al analizar estructura: ' . $e->getMessage()
        ];
    }
}

// Calcular resultado final
$error_tests = array_filter($result['tests'], function($test) {
    return $test['resultado'] === 'ERROR';
});

$result['success'] = empty($error_tests);
$result['message'] = $result['success'] 
    ? 'La tabla turnos existe y funciona correctamente' 
    : 'Se encontraron problemas con la tabla turnos';

echo json_encode($result, JSON_PRETTY_PRINT);
?>
