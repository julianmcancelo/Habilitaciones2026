/**
 * Módulo para manejar la aplicación de temas en las ventanas de la aplicación
 */

// Función para aplicar el tema actual a la página
function applyThemeToPage(theme) {
  const body = document.body;
  
  // Limpiar clases previas de tema
  body.classList.remove('theme-light', 'theme-dark', 'theme-system');
  
  // Agregar la clase correspondiente al tema actual
  body.classList.add(`theme-${theme}`);
  
  // Guardar en localStorage para persistencia en la ventana actual
  localStorage.setItem('app-theme', theme);
  
  console.log(`Tema aplicado: ${theme}`);
}

// Escuchar cambios de tema desde el proceso principal
window.addEventListener('DOMContentLoaded', () => {
  // Verificar si hay un tema guardado en localStorage
  const savedTheme = localStorage.getItem('app-theme') || 'system';
  applyThemeToPage(savedTheme);
  
  // Escuchar por cambios de tema desde el proceso principal
  if (window.electronAPI && window.electronAPI.onThemeChanged) {
    window.electronAPI.onThemeChanged(({ theme }) => {
      console.log('Cambio de tema recibido:', theme);
      applyThemeToPage(theme);
    });
  }
});

// Exportar para uso en scripts que lo requieran
if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    applyThemeToPage
  };
}
