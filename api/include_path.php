<?php
/**
 * Archivo helper para gestionar la inclusión de archivos en entornos múltiples
 */

/**
 * Determina la ruta base del proyecto e incluye un archivo
 * Renombrada para evitar conflictos con la función nativa de PHP get_include_path()
 * 
 * @param string $relative_path Ruta relativa al archivo desde la raíz del proyecto
 * @return string|bool La ruta completa del archivo encontrado o false si no se encontró
 */
function find_project_file_path($relative_path) {
    // Registrar intento de inclusión para depuración
    error_log("Intentando incluir archivo: " . $relative_path);
    
    // PRIMERO: Comprobar si se está buscando conexion.php, y verificar si existe en la carpeta api local
    $basename = basename(ltrim($relative_path, '/'));
    if ($basename === 'conexion.php') {
        $local_copy = __DIR__ . '/conexion.php';
        if (file_exists($local_copy)) {
            error_log("¡Encontrado! Usando copia local de conexion.php en api/conexion.php");
            return $local_copy;
        }
    }
    
    // Detectar automáticamente entorno (versión mejorada)
    $server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
    $http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
    $script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
    
    // Verificar si estamos en el servidor de producción
    $is_production = (strpos($server_name, 'transportelanus.com.ar') !== false) || 
                      (strpos($http_host, 'transportelanus.com.ar') !== false) ||
                      (strpos($script_name, 'transportelanus.com.ar') !== false);
                      
    // Registro detallado para diagnóstico
    error_log("Detección de entorno - SERVER_NAME: {$server_name}");
    error_log("Detección de entorno - HTTP_HOST: {$http_host}");
    error_log("Detección de entorno - SCRIPT_NAME: {$script_name}");
                    
    // Posibles ubicaciones de la raíz del proyecto
    if ($is_production) {
        // En producción - servidor cPanel
        $document_root = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '/home17/transpo1/public_html';
        
        $possible_roots = [
            // Rutas específicas para el servidor
            '/home17/transpo1/apis.transportelanus.com.ar',
            '/home17/transpo1/public_html',
            
            // Rutas basadas en DOCUMENT_ROOT
            $document_root,
            dirname($document_root),
            dirname(dirname($document_root)),
            
            // Rutas relativas a la ubicación actual del script
            dirname(dirname(__FILE__)),
            dirname(__FILE__),
            
            // Rutas absolutas alternativas en el servidor
            '/home/transpo1/apis.transportelanus.com.ar',
            '/home/transpo1/public_html',
            '/var/www/html',
            '/var/www'
        ];
    } else {
        // En desarrollo
        $possible_roots = [
            dirname(dirname(__FILE__)), // Subir un nivel desde /api/ en local
            dirname(__FILE__), // Directorio actual, por si acaso
            $_SERVER['DOCUMENT_ROOT'] ?? ''
        ];
    }
    
    // Registrar información detallada para depuración
    error_log("Directorio actual: " . __DIR__);
    error_log("Entorno detectado: " . ($is_production ? "Producción" : "Desarrollo"));
    error_log("Raíces posibles: " . print_r($possible_roots, true));
    
    foreach ($possible_roots as $root) {
        $full_path = $root . '/' . ltrim($relative_path, '/');
        
        if (file_exists($full_path)) {
            error_log("Archivo encontrado en: " . $full_path);
            return $full_path;
        }
        error_log("Intentando ruta: " . $full_path . " - No encontrado");
    }
    
    // Si llegamos aquí, no se encontró el archivo
    error_log("ERROR: No se pudo encontrar el archivo {$relative_path} en ninguna ubicación");
    return false;
}

/**
 * Incluye un archivo desde la ruta raíz del proyecto
 * 
 * @param string $relative_path Ruta relativa al archivo desde la raíz del proyecto
 * @param bool $required Si es true, falla con error si no se encuentra el archivo
 * @return bool True si se incluyó el archivo, false si no
 */
function include_from_root($relative_path, $required = true) {
    $path = find_project_file_path($relative_path);
    
    if ($path === false) {
        if ($required) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error de configuración del servidor: archivo requerido no encontrado',
                'path' => $relative_path
            ]);
            exit;
        }
        return false;
    }
    
    if ($required) {
        require_once $path;
    } else {
        include_once $path;
    }
    
    return true;
}
