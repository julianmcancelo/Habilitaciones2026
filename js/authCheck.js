/**
 * Script para verificar autenticación
 * Este script verifica si el usuario está autenticado y redirige al login si no lo está
 */

// Función para verificar la autenticación
function checkAuthStatus() {
    return new Promise((resolve, reject) => {
        try {
            console.log('Verificando estado de autenticación...');
            // Usamos la API de Electron para verificar si hay un usuario autenticado
            window.electronAPI.checkAuthStatus()
                .then(response => {
                    console.log('Respuesta de checkAuthStatus:', response);
                    // Verificamos que response sea un objeto y tenga la propiedad authenticated=true
                    if (response && response.authenticated === true) {
                        console.log('Usuario autenticado correctamente');
                        // El usuario está autenticado
                        resolve(response);
                        return; // Aseguramos que no se ejecuta el código siguiente
                    } 
                    
                    console.log('No se detectó autenticación, redirigiendo a login');
                    // El usuario no está autenticado, redirigimos al login
                    redirectToLogin();
                    reject(new Error("No autenticado"));
                })
                .catch(error => {
                    console.error("Error verificando autenticación:", error);
                    redirectToLogin();
                    reject(error);
                });
        } catch (error) {
            console.error("Error en verificación de autenticación:", error);
            redirectToLogin();
            reject(error);
        }
    });
}

// Función para redireccionar al login
function redirectToLogin() {
    // Mostramos un mensaje de error antes de redireccionar
    Swal.fire({
        title: 'Sesión no iniciada',
        text: 'Debe iniciar sesión para continuar',
        icon: 'warning',
        confirmButtonText: 'Iniciar sesión',
        allowOutsideClick: false,
        customClass: {
            confirmButton: 'btn bg-gradient-to-r from-indigo-600 to-purple-600 text-white'
        }
    }).then(() => {
        // Redirigimos al login usando la API de Electron
        window.electronAPI.navigate('index.html');
    });
}

// Exponer funciones para su uso en otras páginas
window.authUtils = {
    checkAuthStatus,
    redirectToLogin
};
