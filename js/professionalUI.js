/**
 * professionalUI.js
 * Script para mejorar la profesionalidad y consistencia visual de la interfaz
 */

document.addEventListener('DOMContentLoaded', function() {
    enhanceUIElements();
    addAnimations();
});

/**
 * Mejora los elementos de la interfaz de usuario con clases y estilos profesionales
 */
function enhanceUIElements() {
    // Mejorar tarjetas y secciones
    document.querySelectorAll('.card, .panel, .section').forEach(el => {
        el.classList.add('shadow-sm', 'rounded-lg', 'overflow-hidden');
    });

    // Mejorar botones que no tienen estilos adecuados
    document.querySelectorAll('button:not(.btn):not([class*="bg-"])').forEach(btn => {
        if (!btn.getAttribute('type') === 'submit') {
            btn.classList.add('bg-white', 'border', 'border-gray-300', 'rounded-md', 'px-4', 'py-2', 'text-sm', 'font-medium', 'text-gray-700', 'hover:bg-gray-50', 'focus:outline-none', 'focus:ring-2', 'focus:ring-offset-2', 'focus:ring-indigo-500', 'transition-colors');
        } else {
            btn.classList.add('bg-indigo-600', 'text-white', 'rounded-md', 'px-4', 'py-2', 'text-sm', 'font-medium', 'hover:bg-indigo-700', 'focus:outline-none', 'focus:ring-2', 'focus:ring-offset-2', 'focus:ring-indigo-500', 'transition-colors');
        }
    });

    // Mejorar inputs que no tienen estilos adecuados
    document.querySelectorAll('input:not([class*="border"]), select:not([class*="border"]), textarea:not([class*="border"])').forEach(input => {
        input.classList.add('border', 'border-gray-300', 'rounded-md', 'shadow-sm', 'px-3', 'py-2', 'focus:outline-none', 'focus:ring-indigo-500', 'focus:border-indigo-500', 'transition-colors');
    });

    // Mejorar tablas
    document.querySelectorAll('table:not([class*="shadow"])').forEach(table => {
        table.classList.add('min-w-full', 'divide-y', 'divide-gray-200', 'shadow-sm', 'rounded-lg', 'overflow-hidden');
        
        // Encabezados de tabla
        table.querySelectorAll('thead th').forEach(th => {
            th.classList.add('px-6', 'py-3', 'bg-gray-50', 'text-left', 'text-xs', 'font-medium', 'text-gray-500', 'uppercase', 'tracking-wider');
        });
        
        // Celdas de tabla
        table.querySelectorAll('tbody td').forEach(td => {
            td.classList.add('px-6', 'py-4', 'whitespace-nowrap', 'text-sm', 'text-gray-500', 'border-t', 'border-gray-200');
        });
        
        // Filas de tabla alternadas
        table.querySelectorAll('tbody tr:nth-child(even)').forEach(tr => {
            tr.classList.add('bg-gray-50');
        });
    });
    
    // Mejorar el encabezado para que sea más profesional
    const headers = document.querySelectorAll('header');
    if (headers.length > 0) {
        headers.forEach(header => {
            if (!header.classList.contains('enhanced')) {
                header.classList.add('bg-white', 'shadow-sm', 'border-b', 'border-gray-200', 'enhanced');
            }
        });
    }
}

/**
 * Agrega animaciones sutiles para mejorar la experiencia de usuario
 */
function addAnimations() {
    // Agregar transiciones a elementos interactivos
    document.querySelectorAll('button, a, input, select, textarea').forEach(el => {
        if (!el.classList.contains('transition')) {
            el.classList.add('transition-all', 'duration-200');
        }
    });
    
    // Agregar clases para revelar elementos al cargar
    const revealElements = document.querySelectorAll('.card, .panel, .section');
    if (revealElements.length > 0) {
        revealElements.forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(10px)';
            el.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            setTimeout(() => {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, 100 + (index * 50));
        });
    }
}

// Exportar funciones para uso en otras páginas
window.professionalUI = {
    enhance: enhanceUIElements,
    animate: addAnimations
};
