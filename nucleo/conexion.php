<?php
// nucleo/conexion.php

// 1. CONFIGURACIÓN DE CREDENCIALES
// -----------------------------------------------------------------------------
// IMPORTANTE: Reemplaza estos valores con tus credenciales reales.
$host = 'localhost';
$db_name = 'transpo1_credenciales';
$username = 'transpo1_credenciales';
$password_db = 'feelthesky1';

$charset = 'utf8mb4';

// 2. CREACIÓN DE LA CONEXIÓN PDO
// -----------------------------------------------------------------------------
$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lanza excepciones en errores
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve arrays asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Usa preparaciones nativas de la BD
];

try {
    $pdo = new PDO($dsn, $username, $password_db, $options);
} catch (\PDOException $e) {
    // En un entorno de producción, no deberías mostrar el error detallado.
    // Lo ideal sería registrar el error y mostrar un mensaje genérico.
    error_log('Error de conexión a la base de datos: ' . $e->getMessage());
    http_response_code(503); // Service Unavailable
    // Devolver una respuesta JSON para que la aplicación la pueda interpretar.
    echo json_encode(['success' => false, 'message' => 'No se pudo establecer conexión con la base de datos.']);
    exit;
}
