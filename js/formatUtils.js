/**
 * Utilidades de formato para la aplicación
 */

/**
 * Formatea una fecha en formato YYYY-MM-DD a formato localizado DD/MM/YYYY
 * @param {string} dateStr - Fecha en formato YYYY-MM-DD
 * @returns {string} Fecha formateada en formato DD/MM/YYYY
 */
function formatDate(dateStr) {
    if (!dateStr) return '';
    
    // Convertir YYYY-MM-DD a un objeto Date
    const date = new Date(dateStr + 'T00:00:00');
    
    // Verificar que la fecha sea válida
    if (isNaN(date.getTime())) {
        return dateStr; // Devolver el string original si la fecha no es válida
    }
    
    // Formatear como DD/MM/YYYY para mostrar al usuario
    return date.toLocaleDateString('es-AR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
}

/**
 * Formatea una hora en formato HH:MM:SS a formato HH:MM
 * @param {string} timeStr - Hora en formato HH:MM:SS
 * @returns {string} Hora formateada en formato HH:MM
 */
function formatTime(timeStr) {
    if (!timeStr) return '';
    
    // Si ya tiene el formato correcto, devolverlo
    if (/^\d{2}:\d{2}$/.test(timeStr)) {
        return timeStr;
    }
    
    // Si tiene formato HH:MM:SS, extraer solo HH:MM
    const match = timeStr.match(/^(\d{2}:\d{2}):/);
    if (match) {
        return match[1];
    }
    
    return timeStr;
}

/**
 * Formatea una fecha y hora completa
 * @param {string} dateStr - Fecha en formato YYYY-MM-DD
 * @param {string} timeStr - Hora en formato HH:MM:SS
 * @returns {string} Fecha y hora formateada
 */
function formatDateTime(dateStr, timeStr) {
    return `${formatDate(dateStr)} a las ${formatTime(timeStr)}`;
}
