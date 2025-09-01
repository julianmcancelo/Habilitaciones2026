<?php
/**
 * Script para envío automático de recordatorios de turnos para el día siguiente
 * Debe ser ejecutado mediante un cron job o tarea programada diariamente
 */

// Activar reportes de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores pero sí registrarlos

// Sistema de rutas para compatibilidad con diferentes entornos
require_once __DIR__ . '/include_path.php';

// Importar PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

// Importar el servicio de email
require_once find_project_file_path('/nucleo/email_service.php');

// Logging para diagnóstico
error_log("=== INICIO enviar_recordatorios.php ===");

// Intentar incluir la conexión a la base de datos
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
        error_log("Error crítico: No se pudo cargar el archivo de conexión");
        exit(1);
    }
}

try {
    // Obtener turnos para mañana
    $manana = date('Y-m-d', strtotime('+1 day'));
    error_log("Buscando turnos para la fecha: {$manana}");
    
    $sql = "SELECT t.id, t.habilitacion_id, t.fecha, t.hora, t.observaciones, 
                   h.email, h.nombre, h.apellido
            FROM turnos t 
            LEFT JOIN habilitaciones h ON t.habilitacion_id = h.id
            WHERE t.fecha = ? 
            AND t.recordatorio_enviado = 0";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$manana]);
    $turnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    error_log("Se encontraron " . count($turnos) . " turnos para enviar recordatorios");
    
    // Inicializar el servicio de email
    $emailService = new EmailService();
    
    // Configurar el servicio con contraseña (actualizar según corresponda)
    $emailService->setConfig([
        'password' => 'password_del_correo_electronico', // Actualizar con la contraseña real
    ]);
    
    // Contador para estadísticas
    $enviados = 0;
    $fallidos = 0;
    
    // Enviar recordatorios
    foreach ($turnos as $turno) {
        // Verificar que tenga email
        if (empty($turno['email'])) {
            error_log("Turno ID {$turno['id']} no tiene email asociado");
            continue;
        }
        
        // Enviar recordatorio
        $resultado = $emailService->sendTurnoRecordatorio($turno['email'], $turno);
        
        if ($resultado) {
            // Marcar como enviado en la base de datos
            $update = $pdo->prepare("UPDATE turnos SET recordatorio_enviado = 1 WHERE id = ?");
            $update->execute([$turno['id']]);
            
            error_log("Recordatorio enviado con éxito para turno ID: {$turno['id']} - Email: {$turno['email']}");
            $enviados++;
        } else {
            error_log("Error al enviar recordatorio para turno ID: {$turno['id']} - Email: {$turno['email']}");
            $fallidos++;
        }
    }
    
    // Resumen del proceso
    error_log("Resumen de envío de recordatorios: Enviados: {$enviados}, Fallidos: {$fallidos}");
    
} catch (Exception $e) {
    error_log("Error en el envío de recordatorios: " . $e->getMessage());
}

error_log("=== FIN enviar_recordatorios.php ===");
?>
