<?php
/**
 * Servicio de envío de correos electrónicos utilizando mail() nativo
 * Versión simplificada para el sistema de Habilitaciones2026
 */

// Sistema de rutas para compatibilidad con diferentes entornos
require_once __DIR__ . '/../api/include_path.php';

/**
 * Clase para gestionar el envío de correos electrónicos
 */
class EmailService {
    private $config;
    private $is_production;
    private $log_enabled;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->is_production = (strpos($_SERVER['HTTP_HOST'] ?? '', 'transportelanus.com.ar') !== false);
        $this->log_enabled = true;
        
        // Inicializar con configuración por defecto para Gmail
        $this->config = [
            'from_email' => 'lanustransportepublico@gmail.com',
            'from_name' => 'Sistema de Habilitaciones',
            'reply_to' => 'transportepublicolanus@gmail.com',
        ];
    }
    
    /**
     * Establece la configuración del servicio de correo
     * @param array $config Configuración del servicio
     * @return bool Éxito de la operación
     */
    public function setConfig($config) {
        if (!is_array($config)) {
            return false;
        }
        
        // Actualizar configuración
        foreach ($config as $key => $value) {
            if (isset($this->config[$key])) {
                $this->config[$key] = $value;
            }
        }
        
        return true;
    }
    
    /**
     * Envía un correo electrónico usando mail() nativo
     * @param string $to Correo del destinatario
     * @param string $subject Asunto del correo
     * @param string $body Cuerpo HTML del correo
     * @param array $attachments (opcional) Archivos adjuntos (no implementado en esta versión simple)
     * @return bool Éxito del envío
     */
    public function send($to, $subject, $body, $attachments = []) {
        try {
            // Headers para el correo
            $headers = [];
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=UTF-8';
            $headers[] = 'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>';
            $headers[] = 'Reply-To: ' . $this->config['reply_to'];
            $headers[] = 'X-Mailer: PHP/' . phpversion();
            
            // Convertir el array de headers en string
            $headers_str = implode("\r\n", $headers);
            
            // Enviar el correo usando mail() nativo
            $result = mail($to, $subject, $body, $headers_str);
            
            if ($result) {
                $this->log("Correo enviado con éxito a: {$to}");
                return true;
            } else {
                $this->log("No se pudo enviar el correo a: {$to}");
                return false;
            }
            
        } catch (Exception $e) {
            $this->log("Error al enviar correo a {$to}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envía una notificación de confirmación de turno
     * @param string $email Correo del destinatario
     * @param array $turno_data Datos del turno
     * @return bool Éxito del envío
     */
    public function sendTurnoConfirmacion($email, $turno_data) {
        // Formatear fecha y hora para mostrar
        $fecha = date('d/m/Y', strtotime($turno_data['fecha']));
        $hora = $turno_data['hora'];
        
        // Construir asunto
        $subject = "Confirmación de Turno - Habilitaciones";
        
        // Construir cuerpo del correo
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                .header { background-color: #f8f9fa; padding: 15px; text-align: center; border-bottom: 2px solid #007bff; }
                .content { padding: 20px; }
                .footer { font-size: 12px; text-align: center; margin-top: 30px; color: #6c757d; }
                .important { color: #007bff; font-weight: bold; }
                .note { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Confirmación de Turno</h2>
                </div>
                <div class='content'>
                    <p>Estimado/a,</p>
                    <p>Su turno ha sido registrado exitosamente en nuestro sistema de habilitaciones.</p>
                    
                    <h3>Detalles del turno:</h3>
                    <ul>
                        <li><strong>Fecha:</strong> {$fecha}</li>
                        <li><strong>Hora:</strong> {$hora}</li>
                        <li><strong>ID Habilitación:</strong> {$turno_data['habilitacion_id']}</li>
                    </ul>
                    
                    <div class='note'>
                        <p><strong>Importante:</strong> Por favor, recuerde traer toda la documentación necesaria.</p>
                    </div>
                    
                    <p>Si necesita cambiar o cancelar su turno, por favor contáctenos con anticipación.</p>
                    <p>Gracias por utilizar nuestro sistema de habilitaciones.</p>
                </div>
                <div class='footer'>
                    <p>Este es un mensaje automático. Por favor, no responda a este correo.</p>
                    <p>© 2025 Transportes Lanus - Sistema de Habilitaciones</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Enviar correo
        return $this->send($email, $subject, $body);
    }
    
    /**
     * Envía un recordatorio de turno
     * @param string $email Correo del destinatario
     * @param array $turno_data Datos del turno
     * @return bool Éxito del envío
     */
    public function sendTurnoRecordatorio($email, $turno_data) {
        // Formatear fecha y hora para mostrar
        $fecha = date('d/m/Y', strtotime($turno_data['fecha']));
        $hora = $turno_data['hora'];
        
        // Construir asunto
        $subject = "Recordatorio de Turno - Habilitaciones";
        
        // Construir cuerpo del correo
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                .header { background-color: #f8f9fa; padding: 15px; text-align: center; border-bottom: 2px solid #007bff; }
                .content { padding: 20px; }
                .footer { font-size: 12px; text-align: center; margin-top: 30px; color: #6c757d; }
                .important { color: #dc3545; font-weight: bold; }
                .note { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #ffc107; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Recordatorio de Turno</h2>
                </div>
                <div class='content'>
                    <p>Estimado/a,</p>
                    <p>Le recordamos que tiene un turno programado para mañana en nuestro sistema de habilitaciones.</p>
                    
                    <h3>Detalles del turno:</h3>
                    <ul>
                        <li><strong>Fecha:</strong> <span class='important'>{$fecha}</span></li>
                        <li><strong>Hora:</strong> <span class='important'>{$hora}</span></li>
                        <li><strong>ID Habilitación:</strong> {$turno_data['habilitacion_id']}</li>
                    </ul>
                    
                    <div class='note'>
                        <p><strong>Importante:</strong> Por favor, recuerde traer toda la documentación necesaria.</p>
                    </div>
                    
                    <p>Si necesita cambiar o cancelar su turno, por favor contáctenos lo antes posible.</p>
                </div>
                <div class='footer'>
                    <p>Este es un mensaje automático. Por favor, no responda a este correo.</p>
                    <p>© 2025 Transportes Lanus - Sistema de Habilitaciones</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Enviar correo
        return $this->send($email, $subject, $body);
    }
    
    /**
     * Registra un mensaje en el log
     * @param string $message Mensaje a registrar
     */
    private function log($message) {
        if ($this->log_enabled) {
            error_log('[EmailService] ' . $message);
        }
    }
}
