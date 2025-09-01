/**
 * Archivo de configuración global para Habilitaciones2026
 * Define variables de configuración para toda la aplicación
 */

// Configuración de rutas
const CONFIG = {
    // Ruta base para las fotos de inspección
    FOTOS_BASE_URL: 'https://credenciales.transportelanus.com.ar/',
    
    // Otras configuraciones pueden agregarse aquí
    API_BASE_URL: 'api/',
};

/**
 * Obtiene la URL completa para una foto, combinando la ruta base con la ruta relativa
 * @param {string} fotoPath - Ruta relativa de la foto
 * @return {string} URL completa de la foto
 */
function getFullPhotoUrl(fotoPath) {
    if (!fotoPath) return null;
    
    // Si la foto ya tiene una URL completa (comienza con http o data:), devolverla como está
    if (fotoPath.startsWith('http') || fotoPath.startsWith('data:')) {
        return fotoPath;
    }
    
    // Eliminar barras diagonales iniciales si existen
    const cleanPath = fotoPath.startsWith('/') ? fotoPath.substring(1) : fotoPath;
    
    // Combinar la ruta base con la ruta relativa
    return CONFIG.FOTOS_BASE_URL + cleanPath;
}
