# Changelog

## [1.1.0] - 2025-08-31

### Fixed
- Corregido problema crítico de autenticación que causaba la desconexión de usuarios
- Mejorado el sistema de persistencia de sesiones para mayor robustez
- Optimizado el manejo de credenciales en toda la aplicación
- Solucionado error que mostraba "Sesión no iniciada" incorrectamente
- Implementada redirección automática a la página de login

### Added
- Nuevo logging detallado para facilitar la depuración de problemas de autenticación
- Pantalla de redirección mejorada con animación de carga

## [1.0.0] - 2025-08-31

### Added
- **Panel de Habilitaciones (Dashboard):**
  - Interfaz principal para visualizar y gestionar habilitaciones de transporte.
  - KPIs (Indicadores Clave de Rendimiento) para un resumen rápido del estado de las habilitaciones.
  - Pestañas para filtrar habilitaciones por tipo (Escolar, Remis, Todos).
  - Funcionalidad de búsqueda en tiempo real por titular o número de licencia.

- **Asignación de Turnos:**
  - Se añadió el botón "Asignar Turno" en el menú de acciones de cada habilitación.
  - Modal para seleccionar fecha y hora, con validación de disponibilidad para evitar duplicados.
  - Integración con el backend para guardar los turnos en la base de datos.
  - Notificación por correo electrónico al titular al asignar un turno.

- **Visualización de Turnos:**
  - La fecha y hora del turno asignado ahora se muestra directamente en la tabla del dashboard.

### Fixed
- Se corrigieron múltiples errores de JavaScript que impedían la apertura del modal de asignación de turnos.
- Se solucionó un error 404 (Not Found) y 500 (Internal Server Error) al cambiar las rutas de la API a relativas, asegurando la compatibilidad entre el entorno de desarrollo y producción.

### Changed
- Se actualizó el diseño del dashboard a una versión más moderna y limpia.
- Se mejoró la estructura del código JavaScript para una mejor organización y mantenibilidad.
