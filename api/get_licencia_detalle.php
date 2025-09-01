<?php
// Activar reportes de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1); // Mostrar errores para diagnóstico
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt'); // Registrar errores en un archivo local

// Incluir archivo de conexión a la base de datos
require_once __DIR__ . '/nucleo/conexion.php';

// Configurar cabeceras para API JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Verificar que se ha proporcionado un ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Se requiere un ID de licencia válido'
    ]);
    exit;
}

$id = $_GET['id'];

// Función para sanear salida
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Función para determinar tipo de transporte basado en nro_licencia
function getTipoTransporteFromLicencia($nroLicencia) { 
    return (is_string($nroLicencia) && strpos($nroLicencia, '068-') === 0) ? 'Escolar' : 'Remis'; 
}

// Verificar si el ID es válido
if (!is_numeric($id)) {
    echo json_encode([
        'success' => false,
        'error' => 'El ID de licencia debe ser un valor numérico'
    ]);
    exit;
}

try {
    // Verificar si la tabla existe
    $table_exists_query = $pdo->query("SHOW TABLES LIKE 'habilitaciones_generales'");
    if ($table_exists_query->rowCount() === 0) {
        throw new Exception("La tabla 'habilitaciones_generales' no existe en la base de datos");
    }
    
    // Consulta principal para obtener datos de la habilitación
    $query = "
        SELECT 
            hg.* 
        FROM 
            habilitaciones_generales hg
        WHERE 
            hg.id = :id
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("No se encontró ninguna licencia con el ID especificado");
    }
    
    $licencia = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Agregar tipo de transporte detectado
    $licencia['tipo_transporte_detectado'] = getTipoTransporteFromLicencia($licencia['nro_licencia']);
    
    // Obtener personas asociadas (titular y otros roles)
    $personas_query = "
        SELECT 
            p.id as persona_id, 
            p.nombre, 
            p.dni, 
            p.domicilio, 
            p.telefono, 
            p.email,
            hp.rol,
            hp.licencia_categoria
        FROM 
            habilitaciones_personas hp
        JOIN 
            personas p ON p.id = hp.persona_id
        WHERE 
            hp.habilitacion_id = :id
    ";
    
    $stmt = $pdo->prepare($personas_query);
    $stmt->execute(['id' => $id]);
    $personas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $licencia['personas'] = $personas;
    
    // Identificar al titular para datos principales
    foreach ($personas as $persona) {
        if ($persona['rol'] === 'TITULAR') {
            $licencia['titular'] = $persona['nombre']; // Solo nombre, no hay apellido
            $licencia['titular_dni'] = $persona['dni'];
            $licencia['titular_direccion'] = $persona['domicilio'];
            $licencia['titular_telefono'] = $persona['telefono'];
            $licencia['titular_email'] = $persona['email'];
            break;
        }
    }
    
    // Obtener datos del vehículo
    $vehiculo_query = "
        SELECT 
            v.id as vehiculo_id,
            v.dominio, 
            v.marca, 
            v.modelo, 
            v.chasis, 
            v.ano, 
            v.motor, 
            v.asientos, 
            v.inscripcion_inicial, 
            v.Aseguradora, 
            v.poliza,
            v.Vencimiento_Poliza,
            v.Vencimiento_VTV
        FROM 
            habilitaciones_vehiculos hv
        JOIN 
            vehiculos v ON v.id = hv.vehiculo_id
        WHERE 
            hv.habilitacion_id = :id
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($vehiculo_query);
    $stmt->execute(['id' => $id]);
    
    if ($stmt->rowCount() > 0) {
        $vehiculo = $stmt->fetch(PDO::FETCH_ASSOC);
        $licencia['vehiculo_id'] = $vehiculo['vehiculo_id'];
        $licencia['vehiculo_dominio'] = $vehiculo['dominio'];
        $licencia['vehiculo_marca'] = $vehiculo['marca'];
        $licencia['vehiculo_modelo'] = $vehiculo['modelo'];
        $licencia['vehiculo_anio'] = $vehiculo['ano'];
        $licencia['vehiculo_motor'] = $vehiculo['motor'];
        $licencia['vehiculo_chasis'] = $vehiculo['chasis'];
        $licencia['vehiculo_asientos'] = $vehiculo['asientos'];
        $licencia['vehiculo_vtv'] = $vehiculo['Vencimiento_VTV'];
        $licencia['vehiculo_seguro'] = $vehiculo['Aseguradora'];
        $licencia['vehiculo_poliza'] = $vehiculo['poliza'];
        $licencia['vehiculo_poliza_vencimiento'] = $vehiculo['Vencimiento_Poliza'];
    }
    
    // Obtener establecimiento/remisería asociado
    $destino_query = "
        SELECT 
            he.tipo, 
            CASE 
                WHEN he.tipo = 'establecimiento' THEN e.nombre 
                ELSE r.nombre 
            END AS nombre, 
            CASE 
                WHEN he.tipo = 'establecimiento' THEN e.domicilio 
                ELSE NULL 
            END AS domicilio 
        FROM 
            habilitaciones_establecimientos he 
        LEFT JOIN 
            establecimientos e ON (he.tipo = 'establecimiento' AND he.establecimiento_id = e.id) 
        LEFT JOIN 
            remiserias r ON (he.tipo = 'remiseria' AND he.establecimiento_id = r.id) 
        WHERE 
            he.habilitacion_id = :id 
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($destino_query);
    $stmt->execute(['id' => $id]);
    
    if ($stmt->rowCount() > 0) {
        $destino = $stmt->fetch(PDO::FETCH_ASSOC);
        $licencia['destino_tipo'] = $destino['tipo'];
        $licencia['destino_nombre'] = $destino['nombre'];
        $licencia['destino_domicilio'] = $destino['domicilio'];
    }
    
    // Buscar si tiene turno asociado
    $turno_query = "
        SELECT 
            id, fecha, hora, observaciones, estado 
        FROM 
            turnos
        WHERE 
            habilitacion_id = :id
        ORDER BY 
            fecha DESC, hora DESC
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($turno_query);
    $stmt->execute(['id' => $id]);
    
    if ($stmt->rowCount() > 0) {
        $turno = $stmt->fetch(PDO::FETCH_ASSOC);
        $licencia['turno_id'] = $turno['id'];
        $licencia['turno_fecha'] = $turno['fecha'];
        $licencia['turno_hora'] = $turno['hora'];
        $licencia['turno_estado'] = $turno['estado'];
        $licencia['turno_observaciones'] = $turno['observaciones'];
    }
    
    // Obtener token de acceso para credencial
    $token_query = "
        SELECT 
            token 
        FROM 
            tokens_acceso 
        WHERE 
            habilitacion_id = :id 
        ORDER BY 
            creado_en DESC 
        LIMIT 1
    ";
    
    $stmt = $pdo->prepare($token_query);
    $stmt->execute(['id' => $id]);
    
    if ($stmt->rowCount() > 0) {
        $licencia['token_acceso'] = $stmt->fetchColumn();
    }
    
    // Verificar si la tabla de inspecciones existe
    try {
        $tabla_inspecciones_existe = $pdo->query("SHOW TABLES LIKE 'inspecciones'")->rowCount() > 0;
        $licencia['debug_inspecciones_tabla_existe'] = $tabla_inspecciones_existe;
        
        if (!$tabla_inspecciones_existe) {
            $licencia['inspecciones'] = [];
            $licencia['debug_inspecciones_error'] = "La tabla 'inspecciones' no existe en la base de datos";
        } else {
            // Verificar si hay registros para esta habilitación específicamente
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM inspecciones WHERE habilitacion_id = :id");
            $stmt_check->execute(['id' => $id]);
            $count_inspecciones = $stmt_check->fetchColumn();
            $licencia['debug_inspecciones_count'] = $count_inspecciones;
            
            // Obtener historial de inspecciones
            $inspecciones_query = "
                SELECT 
                    i.id,
                    i.habilitacion_id,
                    i.nro_licencia, 
                    i.nombre_inspector, 
                    i.fecha_inspeccion, 
                    i.tipo_transporte,
                    i.resultado,
                    i.email_contribuyente,
                    i.firma_inspector,
                    i.firma_contribuyente
                FROM 
                    inspecciones i
                WHERE 
                    i.habilitacion_id = :id
                ORDER BY 
                    i.fecha_inspeccion DESC
            ";
            
            $stmt = $pdo->prepare($inspecciones_query);
            $stmt->execute(['id' => $id]);
            
            // Verificar si se está pasando un número incorrecto
            $licencia['debug_id_usado'] = $id;
            $licencia['debug_id_tipo'] = gettype($id);
        }
        $inspecciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $licencia['inspecciones'] = $inspecciones;
        
        // Para cada inspección, obtener sus detalles
        foreach ($inspecciones as $key => $inspeccion) {
            // Consulta para obtener los detalles de la inspección
            $detalles_query = "
                SELECT 
                    d.id,
                    d.inspeccion_id,
                    d.item_id,
                    d.nombre_item,
                    d.estado,
                    d.observacion,
                    d.foto_url,
                    d.creado_en,
                    d.latitud,
                    d.longitud
                FROM 
                    inspeccion_detalles d
                WHERE 
                    d.inspeccion_id = :inspeccion_id
                ORDER BY 
                    d.id ASC
            ";
            
            try {
                $stmt_detalles = $pdo->prepare($detalles_query);
                $stmt_detalles->execute(['inspeccion_id' => $inspeccion['id']]);
                $licencia['inspecciones'][$key]['detalles'] = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);
                
                // Obtener fotos adicionales de la inspección
                $fotos_query = "
                    SELECT 
                        f.id,
                        f.inspeccion_id,
                        f.tipo_foto,
                        f.item_id_original,
                        f.foto_path,
                        f.latitud,
                        f.longitud,
                        f.fecha_creacion
                    FROM 
                        inspeccion_fotos f
                    WHERE 
                        f.inspeccion_id = :inspeccion_id
                    ORDER BY 
                        f.id ASC
                ";
                
                $stmt_fotos = $pdo->prepare($fotos_query);
                $stmt_fotos->execute(['inspeccion_id' => $inspeccion['id']]);
                $licencia['inspecciones'][$key]['fotos_adicionales'] = $stmt_fotos->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $licencia['inspecciones'][$key]['detalles'] = [];
                $licencia['inspecciones'][$key]['fotos_adicionales'] = [];
                error_log("Error al obtener detalles de inspección ID {$inspeccion['id']}: " . $e->getMessage());
            }      }
        
    } catch (Exception $e) {
        // Si la tabla no existe o hay otro error, inicializar como array vacío
        $licencia['inspecciones'] = [];
        error_log("Error al obtener inspecciones: " . $e->getMessage());
    }
    
    // Verificar si la tabla de obleas existe
    try {
        $tabla_obleas_existe = $pdo->query("SHOW TABLES LIKE 'obleas'")->rowCount() > 0;
        $licencia['debug_obleas_tabla_existe'] = $tabla_obleas_existe;
        
        if (!$tabla_obleas_existe) {
            $licencia['obleas'] = [];
            $licencia['debug_obleas_error'] = "La tabla 'obleas' no existe en la base de datos";
        } else {
            // Verificar si hay registros para esta habilitación específicamente
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM obleas WHERE habilitacion_id = :id");
            $stmt_check->execute(['id' => $id]);
            $count_obleas = $stmt_check->fetchColumn();
            $licencia['debug_obleas_count'] = $count_obleas;
            
            // Obtener historial de colocación de obleas
            $obleas_query = "
                SELECT 
                    o.id,
                    o.habilitacion_id,
                    o.nro_licencia,
                    o.titular,
                    o.fecha_colocacion,
                    o.path_foto,
                    o.path_firma_receptor,
                    o.path_firma_inspector
                FROM 
                    obleas o
                WHERE 
                    o.habilitacion_id = :id
                ORDER BY 
                    o.fecha_colocacion DESC
            ";
            
            $stmt = $pdo->prepare($obleas_query);
            $stmt->execute(['id' => $id]);
            $licencia['obleas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        // Si la tabla no existe o hay otro error, inicializar como array vacío
        $licencia['obleas'] = [];
        error_log("Error al obtener obleas: " . $e->getMessage());
    }
    
    // Devolver los datos
    echo json_encode([
        'success' => true,
        'licencia' => $licencia
    ]);
    
} catch (Exception $e) {
    // Registrar el error
    error_log("Error en get_licencia_detalle.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
