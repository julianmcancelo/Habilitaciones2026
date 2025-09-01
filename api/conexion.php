<?php
// api/conexion.php - Copia de seguridad del archivo de conexión
// Esta es una copia redundante de nucleo/conexion.php para mayor compatibilidad

// Configuración de credenciales
// Determinar entorno y configurar credenciales
$is_production = (strpos($_SERVER['HTTP_HOST'] ?? '', 'transportelanus.com.ar') !== false);

if ($is_production) {
    // Entorno de producción (servidor remoto)
    $host = 'localhost'; // En servidor remoto, la conexión MySQL es local
    $db_name = 'transpo1_credenciales';
    $username = 'transpo1_credenciales';
    $password_db = 'feelthesky1';
} else {
    // Entorno de desarrollo (localhost)
    $host = 'localhost';
    $db_name = 'transpo1_credenciales';
    $username = 'transpo1_credenciales';
    $password_db = 'feelthesky1';
}

// Configuración común
$charset = 'utf8mb4';

// Define una función para el manejo de errores de conexión
function handle_db_error($error, $tipo = 'PDO') {
    // Registrar el error detallado en el log del servidor
    error_log("Error de conexión $tipo: " . $error);
    
    // Si es una solicitud AJAX o API, devolver JSON
    $is_api = (isset($_SERVER['HTTP_ACCEPT']) && 
              (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false ||
               strpos($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'XMLHttpRequest') !== false ||
               strpos($_SERVER['REQUEST_URI'], '/api/') !== false));
    
    // Establecer el código de respuesta apropiado
    http_response_code(503); // Service Unavailable
    
    // Responder con JSON para APIs o HTML para web normal
    if ($is_api) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'No se pudo establecer conexión con la base de datos.',
            'error_code' => 'DB_CONNECTION_ERROR',
            'debug_info' => defined('DEBUG') && DEBUG ? $error : null
        ]);
    } else {
        // Para solicitudes web, mostrar un mensaje amigable
        echo "<div style='text-align:center; margin-top:50px; font-family:Arial,sans-serif;'>";  
        echo "<h2>Servicio temporalmente no disponible</h2>";  
        echo "<p>Lo sentimos, no se pudo establecer conexión con la base de datos.</p>";
        echo "<p>Por favor, intente nuevamente más tarde.</p>";
        echo "</div>";
    }
    exit;
}

// Conexión PDO para scripts modernos
try {
    $dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 5,     // Timeout de 5 segundos
        PDO::ATTR_PERSISTENT         => false, // No usar conexiones persistentes
    ];
    $pdo = new PDO($dsn, $username, $password_db, $options);
} catch (PDOException $e) {
    handle_db_error($e->getMessage());
}

// Función de compatibilidad para scripts antiguos que usan mysqli
function conectar() {
    global $host, $username, $password_db, $db_name, $charset;
    
    // Crear conexión mysqli para compatibilidad
    $conn = mysqli_connect($host, $username, $password_db, $db_name);

    // Verificación de la conexión
    if (!$conn) {
        error_log('Error de conexión mysqli: ' . mysqli_connect_error());
        http_response_code(503); // Service Unavailable
        echo json_encode(['success' => false, 'message' => 'No se pudo establecer conexión con la base de datos.']);
        exit;
    }

    // Establecer el charset
    mysqli_set_charset($conn, $charset);

    return $conn;
}
