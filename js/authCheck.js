/**
 * Script para verificar autenticación
 * Este script verifica si el usuario está autenticado y redirige al login si no lo está
 */

// Función para verificar la autenticación
function checkAuthStatus() {
    return new Promise((resolve, reject) => {
        try {
            // Usamos la API de Electron para verificar si hay un usuario autenticado
            window.electronAPI.checkAuthStatus()
                .then(response => {
                    if (response && response.authenticated) {
                        // El usuario está autenticado
                        resolve(response);
                    } else {
                        // El usuario no está autenticado, redirigimos al login
                        redirectToLogin();
                        reject(new Error("No autenticado"));
                    }
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
