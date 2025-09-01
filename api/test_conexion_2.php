<?php
// Script de diagnóstico para verificar rutas y conexión a la base de datos
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1); // Mostrar errores para depuración

// Primero intentamos cargar nuestro sistema de rutas
$include_path_file = __DIR__ . '/include_path.php';
$include_path_loaded = false;

if (file_exists($include_path_file)) {
    try {
        require_once $include_path_file;
        $include_path_loaded = true;
    } catch (Exception $e) {
        // Si hay error lo registramos pero seguimos
        error_log('Error al cargar include_path.php: ' . $e->getMessage());
    }
}

// Información del servidor
$server_info = [
    'date_time' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconocido',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'No disponible',
    'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'No disponible',
    'server_name' => $_SERVER['SERVER_NAME'] ?? 'No disponible',
    'http_host' => $_SERVER['HTTP_HOST'] ?? 'No disponible',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'No disponible',
    'php_include_path' => \get_include_path() // Usar operador de resolución de ámbito para la función nativa
];

// Información de rutas
$path_info = [
    'current_dir' => __DIR__,
    'parent_dir' => dirname(__DIR__),
    'file' => __FILE__
];

// Estado del sistema de rutas
$response = [
    'success' => true,
    'server_info' => $server_info,
    'path_info' => $path_info,
    'include_path_system' => [
        'file_exists' => file_exists($include_path_file),
        'loaded' => $include_path_loaded
    ]
];

// Usar el nuevo sistema de rutas si está disponible
if ($include_path_loaded && function_exists('find_project_file_path')) {
    $response['include_path_system']['functions_available'] = true;
    $conexion_path = find_project_file_path('/nucleo/conexion.php');
    $response['archivo_conexion'] = [
        'method' => 'find_project_file_path',
        'ruta_encontrada' => $conexion_path,
        'exists' => $conexion_path !== false,
        'readable' => $conexion_path !== false ? is_readable($conexion_path) : false
    ];
    
    $archivo_exists = ($conexion_path !== false);
    $archivo_readable = $archivo_exists ? is_readable($conexion_path) : false;
    $ruta_encontrada = $conexion_path;
} else {
    // Método de búsqueda anterior como fallback
    $response['include_path_system']['functions_available'] = false;
    $posibles_rutas = [
        __DIR__ . '/../nucleo/conexion.php',
        dirname(__DIR__) . '/nucleo/conexion.php',
        '/home17/transpo1/apis.transportelanus.com.ar/nucleo/conexion.php',
        '/home17/transpo1/public_html/nucleo/conexion.php'
    ];
    
    $ruta_encontrada = null;
    foreach ($posibles_rutas as $ruta) {
        if (file_exists($ruta)) {
            $ruta_encontrada = $ruta;
            break;
        }
    }
    
    $archivo_exists = ($ruta_encontrada !== null);
    $archivo_readable = $archivo_exists ? is_readable($ruta_encontrada) : false;
    
    $response['archivo_conexion'] = [
        'method' => 'fallback',
        'rutas_probadas' => $posibles_rutas,
        'ruta_encontrada' => $ruta_encontrada,
        'exists' => $archivo_exists,
        'readable' => $archivo_readable
    ];
}

// La respuesta ya fue creada antes, no necesitamos recrearla

// Intentar incluir conexion.php si se encontró
if ($archivo_exists && $archivo_readable) {
    try {
        require_once $ruta_encontrada;
        $response['conexion_incluida'] = true;
        
        // Probar si $pdo está disponible (conexión PDO)
        if (isset($pdo)) {
            try {
                $stmt = $pdo->query("SELECT 'Conexión PDO exitosa' AS test");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $response['pdo_test'] = $result['test'];
            } catch (PDOException $e) {
                $response['pdo_error'] = $e->getMessage();
            }
        } else {
            $response['pdo_disponible'] = false;
        }
        
        // Probar si $conn está disponible (conexión mysqli)
        if (isset($conn)) {
            try {
                $result = $conn->query("SELECT 'Conexión MySQLi exitosa' AS test");
                if ($result) {
                    $row = $result->fetch_assoc();
                    $response['mysqli_test'] = $row['test'];
                }
            } catch (Exception $e) {
                $response['mysqli_error'] = $e->getMessage();
            }
        } else {
            $response['mysqli_disponible'] = false;
        }
    } catch (Exception $e) {
        $response['conexion_error'] = $e->getMessage();
    }
} else {
    $response['conexion_incluida'] = false;
}

// Listar directorio para ver qué hay
try {
    $dir_content = scandir(__DIR__);
    $response['dir_content'] = $dir_content;
    
    $parent_dir_content = scandir(dirname(__DIR__));
    $response['parent_dir_content'] = $parent_dir_content;
    
    // Verificar si existe 'nucleo' en el directorio padre
    $nucleo_path = dirname(__DIR__) . '/nucleo';
    $response['nucleo_exists'] = file_exists($nucleo_path);
    if ($response['nucleo_exists']) {
        $response['nucleo_content'] = scandir($nucleo_path);
    }
    
    // Verificar la copia local de conexion.php en api/
    $local_conexion = __DIR__ . '/conexion.php';
    $response['local_conexion'] = [
        'path' => $local_conexion,
        'exists' => file_exists($local_conexion),
        'readable' => is_readable($local_conexion),
        'size' => file_exists($local_conexion) ? filesize($local_conexion) : 0,
        'modified' => file_exists($local_conexion) ? date('Y-m-d H:i:s', filemtime($local_conexion)) : null
    ];
    
    // Si la copia local existe, intentar incluirla directamente
    if (file_exists($local_conexion) && !isset($pdo)) {
        try {
            require_once $local_conexion;
            $response['local_conexion']['included'] = true;
            
            // Probar conexión PDO usando la copia local
            if (isset($pdo)) {
                try {
                    $stmt = $pdo->query("SELECT 'Conexión PDO local exitosa' AS test");
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $response['local_conexion']['pdo_test'] = $result['test'];
                } catch (PDOException $e) {
                    $response['local_conexion']['pdo_error'] = $e->getMessage();
                }
            } else {
                $response['local_conexion']['pdo_disponible'] = false;
            }
        } catch (Exception $e) {
            $response['local_conexion']['error'] = $e->getMessage();
        }
    }
} catch (Exception $e) {
    $response['dir_scan_error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
