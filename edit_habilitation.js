document.addEventListener('DOMContentLoaded', async () => {
    const id = await window.electronAPI.getEditId();
    
    if (!id) {
        document.body.innerHTML = '<h1 class="text-red-500 text-center p-8">Error: No se proporcionó un ID de habilitación.</h1>';
        return;
    }

    // Cargar todos los datos de la habilitación
    const data = await window.electronAPI.getHabilitationDetails(id);

    if (!data || !data.success) {
        document.body.innerHTML = `<h1 class="text-red-500 text-center p-8">Error: No se pudieron cargar los datos de la habilitación con ID ${id}.</h1>`;
        return;
    }

    // Poblar todas las secciones con los datos recibidos
    populateGeneralData(data.habilitacion);
    populatePersonas(data.personas);
    populateVehiculo(data.vehiculo);
    populateDocumentos(data.documentos);

    window.confirmarEliminacionDocumento = async (documentoId, nombreDocumento) => {
        const result = await Swal.fire({
            title: '¿Estás seguro?',
            html: `Se eliminará el documento <strong>${nombreDocumento}</strong> de forma permanente.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            const deleteResult = await window.electronAPI.deleteDocument(documentoId);
            if (deleteResult.success) {
                await Swal.fire('¡Eliminado!', deleteResult.message, 'success');
                // Recargar los datos para refrescar la lista
                const freshData = await window.electronAPI.getHabilitationDetails(id);
                populateDocumentos(freshData.documentos);
            } else {
                Swal.fire('Error', deleteResult.message || 'No se pudo eliminar el documento.', 'error');
            }
        }
    };

    window.confirmarReseteoCredenciales = async (personaId, nombrePersona) => {
        const result = await Swal.fire({
            title: '¿Estás seguro?',
            html: `Se generará una nueva contraseña para <strong>${nombrePersona}</strong>. Deberás compartirla con el usuario.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, resetear',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            const resetResult = await window.electronAPI.resetCredentials(personaId);
            if (resetResult.success) {
                await Swal.fire({
                    title: '¡Contraseña Reseteada!',
                    html: `La nueva contraseña para <strong>${nombrePersona}</strong> es:<br><pre class="mt-2 p-2 bg-slate-100 rounded text-center font-mono text-lg">${resetResult.new_password}</pre><br>Por favor, cópiala y compártela de forma segura.`,
                    icon: 'success'
                });
            } else {
                Swal.fire('Error', resetResult.message || 'No se pudo resetear la contraseña.', 'error');
            }
        }
    };

    window.confirmarDesvinculacion = async (personaHabilitacionId, nombrePersona) => {
        const result = await Swal.fire({
            title: '¿Estás seguro?',
            html: `Se desvinculará a <strong>${nombrePersona}</strong> de esta habilitación.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, desvincular',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            const unlinkResult = await window.electronAPI.unlinkPerson(personaHabilitacionId);
            if (unlinkResult.success) {
                await Swal.fire('¡Desvinculado!', unlinkResult.message, 'success');
                // Recargar los datos para refrescar la lista
                const freshData = await window.electronAPI.getHabilitationDetails(id);
                populatePersonas(freshData.personas);
            } else {
                Swal.fire('Error', unlinkResult.message || 'No se pudo desvincular a la persona.', 'error');
            }
        }
    };

    // Manejar la subida de un nuevo documento
    document.getElementById('form-upload-document').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const fileInput = form.querySelector('input[type="file"]');
        const file = fileInput.files[0];
        const tipo = form.querySelector('select[name="tipo"]').value;

        if (!file || !tipo) {
            Swal.fire('Error', 'Debe seleccionar un archivo y especificar un tipo.', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('documento', file);
        formData.append('tipo', tipo);
        formData.append('habilitacion_id', id); // 'id' está disponible en el scope del DOMContentLoaded

        const result = await window.electronAPI.uploadDocument(formData);

        if (result.success) {
            await Swal.fire('¡Subido!', result.message, 'success');
            form.reset();
            // Recargar la lista de documentos
            const freshData = await window.electronAPI.getHabilitationDetails(id);
            populateDocumentos(freshData.documentos);
        } else {
            Swal.fire('Error', result.message || 'No se pudo subir el documento.', 'error');
        }
    });

    // Manejar la eliminación completa de la habilitación
    document.getElementById('btn-delete-habilitation').addEventListener('click', async () => {
        const nroLicencia = document.getElementById('nro_licencia').value;

        const { value: confirmLicencia } = await Swal.fire({
            title: 'Confirmación Requerida',
            html: `Esta acción no se puede deshacer. Para confirmar la eliminación permanente, por favor escriba el número de licencia: <strong>${nroLicencia}</strong>`,
            input: 'text',
            inputPlaceholder: 'Escriba el número de licencia aquí',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Eliminar Permanentemente',
            confirmButtonColor: '#d33',
            cancelButtonText: 'Cancelar',
            preConfirm: (value) => {
                if (value !== nroLicencia) {
                    Swal.showValidationMessage('El número de licencia no coincide.');
                }
                return value;
            }
        });

        if (confirmLicencia) {
            const deleteResult = await window.electronAPI.deleteHabilitation(id);
            if (deleteResult.success) {
                await Swal.fire(
                    '¡Eliminada!',
                    'La habilitación ha sido eliminada con éxito.',
                    'success'
                );
                // Cerrar la ventana de edición ya que el registro ya no existe
                window.close();
            } else {
                Swal.fire('Error', deleteResult.message || 'No se pudo eliminar la habilitación.', 'error');
            }
        }
    });

    // --- MANEJADORES DE FORMULARIOS ---

    // Manejar el guardado de datos generales
    document.getElementById('form-generales').addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = {
            id: id,
            nro_licencia: document.getElementById('nro_licencia').value,
            expte: document.getElementById('expte').value,
            tipo_transporte: document.getElementById('tipo_transporte').value,
            resolucion: document.getElementById('resolucion').value,
            vigencia_inicio: document.getElementById('vigencia_inicio').value,
            vigencia_fin: document.getElementById('vigencia_fin').value,
            tipo: document.getElementById('tipo').value,
            estado: document.getElementById('estado').value,
            observaciones: document.getElementById('observaciones').value
        };

        const result = await window.electronAPI.updateHabilitation(payload);
        if (result.success) {
            await Swal.fire('¡Guardado!', 'Los datos generales han sido actualizados.', 'success');
            const freshData = await window.electronAPI.getHabilitationDetails(id);
            populateGeneralData(freshData.habilitacion);
        } else {
            Swal.fire('Error', result.message || 'No se pudo actualizar la habilitación.', 'error');
        }
    });

    // --- MANEJADORES DE BOTONES DE ASIGNACIÓN ---

    const habilitacionId = await window.electronAPI.getEditId();

    document.getElementById('btn-asignar-titular').addEventListener('click', () => {
        window.electronAPI.openWindow(`asignar_persona.html?id=${id}&rol=titular`);
    });

    document.getElementById('btn-asignar-conductor').addEventListener('click', () => {
        window.electronAPI.openWindow(`asignar_persona.html?id=${id}&rol=conductor`);
    });

        document.getElementById('btn-asignar-celador').addEventListener('click', () => {
        window.electronAPI.openWindow(`asignar_persona.html?id=${id}&rol=celador`);
    });

    document.getElementById('btn-asignar-vehiculo').addEventListener('click', () => {
        const nroLicencia = document.getElementById('nro_licencia').value;
        window.electronAPI.openWindow(`asignar_vehiculo.html?id=${id}&nro_licencia=${nroLicencia}`);
    });

    document.getElementById('btn-asignar-establecimiento').addEventListener('click', () => {
        Swal.fire('Próximamente', 'Aquí se abrirá el modal para buscar y asignar un establecimiento.', 'info');
    });

    document.getElementById('btn-desvincular-vehiculo').addEventListener('click', async () => {
        console.log('Botón Desvincular Vehículo clickeado.');
        const result = await Swal.fire({
            title: '¿Estás seguro?',
            text: "El vehículo será desvinculado de esta habilitación.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, desvincular',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            console.log('Intentando desvincular vehículo para la habilitación con ID:', id);
            const unlinkResult = await window.electronAPI.unlinkVehicle(id);
            if (unlinkResult.success) {
                await Swal.fire('¡Desvinculado!', unlinkResult.message, 'success');
                const freshData = await window.electronAPI.getHabilitationDetails(id);
                populateVehiculo(freshData.vehiculo);
            } else {
                Swal.fire('Error', unlinkResult.message || 'No se pudo desvincular el vehículo.', 'error');
            }
        }
    });

    // Manejar el guardado de datos del vehículo
    document.getElementById('form-vehiculo').addEventListener('submit', async (e) => {
        e.preventDefault();
        const form = e.target;
        const payload = {
            id: form.vehiculo_id.value,
            dominio: form.dominio.value,
            marca: form.marca.value,
            modelo: form.modelo.value,
            chasis: form.chasis.value,
            ano: form.ano.value,
            motor: form.motor.value,
            asientos: form.asientos.value,
            inscripcion_inicial: form.inscripcion_inicial.value,
            aseguradora: form.aseguradora.value,
            poliza: form.poliza.value,
            vencimiento_poliza: form.vencimiento_poliza.value,
            vencimiento_vtv: form.vencimiento_vtv.value
        };

        if (!payload.id || !payload.dominio) {
            Swal.fire('Error', 'Faltan datos esenciales del vehículo (ID o Dominio).', 'error');
            return;
        }

        const result = await window.electronAPI.updateVehicle(payload);
        if (result.success) {
            await Swal.fire('¡Guardado!', result.message, 'success');
            const freshData = await window.electronAPI.getHabilitationDetails(id);
            populateVehiculo(freshData.vehiculo);
        } else {
            Swal.fire('Error', result.message || 'No se pudo actualizar el vehículo.', 'error');
        }
    });
});

// --- FUNCIONES PARA POBLAR DATOS ---

function populateGeneralData(h) {
    if (!h) return;
    document.getElementById('nro_licencia').value = h.nro_licencia || '';
    document.getElementById('expte').value = h.expte || '';
    document.getElementById('resolucion').value = h.resolucion || '';
    document.getElementById('tipo_transporte').value = h.tipo_transporte || 'Escolar';
    document.getElementById('estado').value = h.estado || 'EN TRAMITE';
    document.getElementById('tipo').value = h.tipo || 'originaria';
    document.getElementById('vigencia_inicio').value = h.vigencia_inicio || '';
    document.getElementById('vigencia_fin').value = h.vigencia_fin || '';
    document.getElementById('observaciones').value = h.observaciones || '';
}

function populatePersonas(personas) {
    const container = document.getElementById('personas-container');
    if (!personas || personas.length === 0) {
        container.innerHTML = '<p class="text-center text-slate-500 py-4">No hay personas asignadas.</p>';
        return;
    }
    container.innerHTML = personas.map(p => {
        let rol_clase_color = 'bg-slate-100 text-slate-800';
        if (p.rol === 'Titular') rol_clase_color = 'bg-blue-100 text-blue-800';
        if (p.rol === 'Chofer') rol_clase_color = 'bg-green-100 text-green-800';
        if (p.rol === 'Celador') rol_clase_color = 'bg-yellow-100 text-yellow-800';
        return `
        <div class="p-4 rounded-lg border bg-slate-50/70">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <p class="text-lg font-bold text-slate-800">${p.nombre}</p>
                    <span class="text-xs font-semibold py-1 px-2.5 rounded-full ${rol_clase_color}">${p.rol}</span>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="confirmarDesvinculacion(${p.persona_habilitacion_id}, '${p.nombre}')" class="text-sm text-red-600 hover:text-red-800 font-semibold">Desvincular</button>
                    ${['Titular', 'Chofer'].includes(p.rol) ? `<button onclick="confirmarReseteoCredenciales(${p.id}, '${p.nombre}')" class="text-sm text-blue-600 hover:text-blue-800 font-semibold">Resetear Contraseña</button>` : ''}
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-3 pt-3 border-t border-slate-200">
                <div><span class="text-xs font-semibold text-slate-500">DNI</span><p class="text-slate-900">${p.dni || 'No informado'}</p></div>
                <div class="sm:col-span-2"><span class="text-xs font-semibold text-slate-500">Domicilio</span><p class="text-slate-900">${p.domicilio || 'No informado'}</p></div>
                <div><span class="text-xs font-semibold text-slate-500">Teléfono</span><p class="text-slate-900">${p.telefono || 'No informado'}</p></div>
                <div class="sm:col-span-2"><span class="text-xs font-semibold text-slate-500">Email</span><p class="text-slate-900">${p.email || 'No informado'}</p></div>
            </div>
        </div>`;
    }).join('');
}

function populateVehiculo(vehiculo) {
    const container = document.getElementById('vehiculo-container');
    const saveButtonContainer = document.querySelector('#form-vehiculo .border-t');
    const btnAsignar = document.getElementById('btn-asignar-vehiculo');
    const btnDesvincular = document.getElementById('btn-desvincular-vehiculo');

    if (!vehiculo) {
        container.innerHTML = '<p class="text-center text-slate-500 py-4 col-span-full">No hay un vehículo asignado a esta habilitación.</p>';
        if(saveButtonContainer) saveButtonContainer.style.display = 'none';
        btnAsignar.innerHTML = '➕ Asignar Vehículo';
        btnAsignar.style.display = 'inline-block';
        btnDesvincular.style.display = 'none';
        return;
    }

    // Si hay un vehículo, se permite cambiarlo o desvincularlo
    btnAsignar.innerHTML = '🔄 Cambiar Vehículo';
    btnAsignar.style.display = 'inline-block';
    btnDesvincular.style.display = 'inline-block';

    if(saveButtonContainer) saveButtonContainer.style.display = 'flex'; // Asegurarse que el botón sea visible

    container.innerHTML = `
        <input type="hidden" name="vehiculo_id" value="${vehiculo.id || ''}">
        <div><label for="dominio" class="block text-sm font-medium text-slate-600 mb-1">Dominio</label><input id="dominio" type="text" name="dominio" value="${vehiculo.dominio || ''}" class="block w-full rounded-md border-slate-300 shadow-sm"></div>
        <div><label for="marca" class="block text-sm font-medium text-slate-600 mb-1">Marca</label><input id="marca" type="text" name="marca" value="${vehiculo.marca || ''}" class="block w-full rounded-md border-slate-300 shadow-sm"></div>
        <div><label for="modelo" class="block text-sm font-medium text-slate-600 mb-1">Modelo</label><input id="modelo" type="text" name="modelo" value="${vehiculo.modelo || ''}" class="block w-full rounded-md border-slate-300 shadow-sm"></div>
        <div><label for="ano" class="block text-sm font-medium text-slate-600 mb-1">Año</label><input id="ano" type="number" name="ano" value="${vehiculo.ano || ''}" class="block w-full rounded-md border-slate-300 shadow-sm"></div>
        <div><label for="chasis" class="block text-sm font-medium text-slate-600 mb-1">Chasis</label><input id="chasis" type="text" name="chasis" value="${vehiculo.chasis || ''}" class="block w-full rounded-md border-slate-300 shadow-sm"></div>
        <div><label for="motor" class="block text-sm font-medium text-slate-600 mb-1">Motor</label><input id="motor" type="text" name="motor" value="${vehiculo.motor || ''}" class="block w-full rounded-md border-slate-300 shadow-sm"></div>
        <div><label for="asientos" class="block text-sm font-medium text-slate-600 mb-1">Asientos</label><input id="asientos" type="number" name="asientos" value="${vehiculo.asientos || ''}" class="block w-full rounded-md border-slate-300 shadow-sm"></div>
        <div><label for="inscripcion_inicial" class="block text-sm font-medium text-slate-600 mb-1">Fecha Inscripción</label><input id="inscripcion_inicial" type="date" name="inscripcion_inicial" value="${vehiculo.inscripcion_inicial || ''}" class="block w-full rounded-md border-slate-300 shadow-sm"></div>
        <div><label for="aseguradora" class="block text-sm font-medium text-slate-600 mb-1">Aseguradora</label><input id="aseguradora" type="text" name="aseguradora" value="${vehiculo.aseguradora || ''}" class="block w-full rounded-md border-slate-300 shadow-sm"></div>
        <div><label for="poliza" class="block text-sm font-medium text-slate-600 mb-1">Póliza</label><input id="poliza" type="text" name="poliza" value="${vehiculo.poliza || ''}" class="block w-full rounded-md border-slate-300 shadow-sm"></div>
        <div><label for="vencimiento_poliza" class="block text-sm font-medium text-slate-600 mb-1">Vencimiento Póliza</label><input id="vencimiento_poliza" type="date" name="vencimiento_poliza" value="${vehiculo.vencimiento_poliza || ''}" class="block w-full rounded-md border-slate-300 shadow-sm"></div>
        <div><label for="vencimiento_vtv" class="block text-sm font-medium text-slate-600 mb-1">Vencimiento VTV</label><input id="vencimiento_vtv" type="date" name="vencimiento_vtv" value="${vehiculo.vencimiento_vtv || ''}" class="block w-full rounded-md border-slate-300 shadow-sm"></div>
    `;
}

function populateDocumentos(documentos) {
    const container = document.getElementById('documentos-container');
    if (!documentos || documentos.length === 0) {
        container.innerHTML = '<p class="text-center text-slate-500 py-4">No hay documentos adjuntos.</p>';
        return;
    }
    container.innerHTML = documentos.map(d => `
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center p-3 rounded-lg border bg-slate-50/70">
            <div class="md:col-span-2">
                <p class="font-semibold text-slate-800">${d.nombre_archivo_original}</p>
                <span class="text-xs text-slate-500">${d.tipo_documento} - Subido el ${d.fecha_formateada}</span>
            </div>
            <div class="md:col-span-2 flex justify-end items-center gap-4">
                <a href="${d.url}" target="_blank" class="text-sm text-blue-600 hover:text-blue-800 font-semibold">Ver</a>
                <button onclick="confirmarEliminacionDocumento(${d.id}, '${d.nombre_archivo_original}')" class="text-sm text-red-600 hover:text-red-800 font-semibold">Eliminar</button>
            </div>
        </div>`
    ).join('');
}
