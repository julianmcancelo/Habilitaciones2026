<?php
// nucleo/conexion.php

function conectar() {
    // 1. CONFIGURACIÓN DE CREDENCIALES
    $host = 'localhost';
    $db_name = 'transpo1_credenciales';
    $username = 'transpo1_credenciales';
    $password_db = 'feelthesky1';
    $charset = 'utf8mb4';

    // 2. CREACIÓN DE LA CONEXIÓN mysqli
    $conn = mysqli_connect($host, $username, $password_db, $db_name);

    // 3. VERIFICACIÓN DE LA CONEXIÓN
    if (!$conn) {
        error_log('Error de conexión a la base de datos: ' . mysqli_connect_error());
        http_response_code(503); // Service Unavailable
        echo json_encode(['success' => false, 'message' => 'No se pudo establecer conexión con la base de datos.']);
        exit;
    }

    // 4. ESTABLECER EL CHARSET
    mysqli_set_charset($conn, $charset);

    // 5. DEVOLVER LA CONEXIÓN
    return $conn;
}
