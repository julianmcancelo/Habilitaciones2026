<?php
// =================================================================
// 1. LÓGICA Y SEGURIDAD (Sin cambios)
// =================================================================
$titulo_pagina = 'Asignar Persona';
require_once __DIR__ . '/nucleo/verificar_sesion.php';

$habilitacion_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$rol_param = isset($_GET['rol']) ? strtolower(trim($_GET['rol'] ?? '')) : 'titular';
$roles_permitidos = ['titular', 'chofer', 'celador'];

if ($habilitacion_id === 0 || !in_array($rol_param, $roles_permitidos)) { 
    header('Location: index.php?error=parametros_invalidos'); exit; 
}
if (empty($_SESSION['csrf_token'])) { 
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
}

require_once __DIR__ . '/nucleo/conexion.php';
$stmt = $pdo->prepare("SELECT nro_licencia FROM habilitaciones_generales WHERE id = ?");
$stmt->execute([$habilitacion_id]);
$nro_licencia = $stmt->fetchColumn();
$label = ucfirst($rol_param);
$titulo_pagina = "Asignar $label";

// =================================================================
// 2. INICIO DE LA PRESENTACIÓN (HTML)
// =================================================================
require_once __DIR__ . '/plantillas/header_panel.php';
?>

<div class="w-full max-w-3xl mx-auto" x-data="formAsignar(<?= htmlspecialchars($habilitacion_id, ENT_QUOTES, 'UTF-8') ?>, '<?= strtoupper(htmlspecialchars($rol_param, ENT_QUOTES, 'UTF-8')) ?>')">
    <div class="text-center mb-8">
        <h2 class="text-3xl font-bold text-[#891628]">Asignar <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></h2>
        <p class="text-slate-500">A la habilitación <strong class="text-slate-700">N° <?= htmlspecialchars($nro_licencia, ENT_QUOTES, 'UTF-8') ?></strong></p>
    </div>

    <div class="bg-white p-8 rounded-xl shadow-lg border border-slate-200">
        <form id="formularioAsignar" @submit.prevent="confirmarAsignacion">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="habilitacion_id" :value="habilitacionId">
            <input type="hidden" name="persona_id" x-model="persona.id">
            
            <div class="relative mb-6">
                 <label for="buscar" class="block text-sm font-medium text-slate-600 mb-1">1. Buscar persona existente (por Nombre o DNI)</label>
                 <div class="relative">
                     <input type="text" id="buscar" x-model="filtro" @input.debounce.300ms="buscar" @focus="sugerenciasVisible = true" autocomplete="off" placeholder="Escribir para buscar..." class="w-full border-slate-300 rounded-lg shadow-sm focus:ring-2 focus:ring-[#891628]">
                     <div x-show="cargando" class="absolute top-0 right-0 h-full flex items-center pr-3">
                         <svg class="animate-spin h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                     </div>
                 </div>
                 <ul x-show="sugerenciasVisible && filtro.length > 1" @click.away="sugerenciasVisible = false" class="absolute w-full bg-white border border-slate-300 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto z-50">
                     <template x-if="!cargando && resultados.length === 0"><li @click="prepararNuevo" class="px-4 py-3 hover:bg-green-50 cursor-pointer text-center"><span class="font-semibold text-green-700">+ Registrar a '<span x-text="filtro"></span>' como nueva persona</span></li></template>
                     <template x-for="p in resultados" :key="p.id"><li @click="seleccionar(p)" class="px-4 py-3 hover:bg-red-50 cursor-pointer border-b"><p class="font-semibold text-slate-800" x-text="p.nombre"></p><p class="text-xs text-slate-500" x-text="'DNI: ' + p.dni"></p></li></template>
                 </ul>
            </div>

            <div class="flex items-center text-center my-8"><hr class="flex-grow border-t border-slate-200"><span class="px-4 text-slate-500 text-sm font-semibold">2. Completar o Verificar Datos</span><hr class="flex-grow border-t border-slate-200"></div>

            <div x-show="formularioVisible" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div><label for="nueva_nombre" class="block text-sm font-medium text-slate-600 mb-1">Nombre Completo <span class="text-red-500">*</span></label><input type="text" name="nueva_persona" x-model="persona.nombre" id="nueva_nombre" :readonly="persona.id > 0" class="w-full border-slate-300 rounded-lg read-only:bg-slate-100 read-only:text-slate-500 focus:ring-2 focus:ring-[#891628]"></div>
                <div><label for="nuevo_dni" class="block text-sm font-medium text-slate-600 mb-1">DNI <span class="text-red-500">*</span></label><input type="text" name="nuevo_dni" x-model="persona.dni" id="nuevo_dni" :readonly="persona.id > 0" class="w-full border-slate-300 rounded-lg read-only:bg-slate-100 read-only:text-slate-500 focus:ring-2 focus:ring-[#891628]"></div>
                
                <div>
                    <label for="nuevo_genero" class="block text-sm font-medium text-slate-600 mb-1">Género</label>
                    <select name="nuevo_genero" x-model="persona.genero" id="nuevo_genero" class="w-full border-slate-300 rounded-lg focus:ring-2 focus:ring-[#891628]">
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                    </select>
                </div>
                
                <div><label for="nuevo_cuit" class="block text-sm font-medium text-slate-600 mb-1">CUIT</label><input type="text" name="nuevo_cuit" x-model="persona.cuit" id="nuevo_cuit" class="w-full border-slate-300 rounded-lg focus:ring-2 focus:ring-[#891628]"></div>
                <div><label for="nuevo_telefono" class="block text-sm font-medium text-slate-600 mb-1">Teléfono</label><input type="text" name="nuevo_telefono" x-model="persona.telefono" id="nuevo_telefono" class="w-full border-slate-300 rounded-lg focus:ring-2 focus:ring-[#891628]"></div>
                <div class="md:col-span-2"><label for="nuevo_email" class="block text-sm font-medium text-slate-600 mb-1">Email</label><input type="email" name="nuevo_email" x-model="persona.email" id="nuevo_email" class="w-full border-slate-300 rounded-lg focus:ring-2 focus:ring-[#891628]"></div>
                
                <div class="md:col-span-2"><label for="nuevo_domicilio_calle" class="block text-sm font-medium text-slate-600 mb-1">Domicilio (Calle)</label><input type="text" name="nuevo_domicilio_calle" x-model="persona.domicilio_calle" id="nuevo_domicilio_calle" class="w-full border-slate-300 rounded-lg focus:ring-2 focus:ring-[#891628]"></div>
                <div><label for="nuevo_domicilio_nro" class="block text-sm font-medium text-slate-600 mb-1">Número</label><input type="text" name="nuevo_domicilio_nro" x-model="persona.domicilio_nro" id="nuevo_domicilio_nro" class="w-full border-slate-300 rounded-lg focus:ring-2 focus:ring-[#891628]"></div>
                <div><label for="nuevo_domicilio_localidad" class="block text-sm font-medium text-slate-600 mb-1">Localidad</label><input type="text" name="nuevo_domicilio_localidad" x-model="persona.domicilio_localidad" id="nuevo_domicilio_localidad" class="w-full border-slate-300 rounded-lg focus:ring-2 focus:ring-[#891628]"></div>
                
                <div class="md:col-span-2"><label for="foto_url" class="block text-sm font-medium text-slate-600 mb-1">URL de Foto</label><input type="text" name="foto_url" x-model="persona.foto_url" id="foto_url" class="w-full border-slate-300 rounded-lg focus:ring-2 focus:ring-[#891628]"></div>
                
                <?php if ($rol_param === 'chofer'): ?>
                <div><label for="licencia_categoria" class="block text-sm font-medium text-slate-600 mb-1">Categoría de Licencia</label><input type="text" name="licencia_categoria" id="licencia_categoria" placeholder="Ej: D1, D2" class="w-full border-slate-300 rounded-lg focus:ring-2 focus:ring-[#891628]"></div>
                <?php endif; ?>
            </div>

            <div class="pt-8 mt-8 border-t border-slate-200 flex justify-end items-center gap-4">
                 <a href="edit_habilitation.php?id=<?= htmlspecialchars($habilitacion_id, ENT_QUOTES, 'UTF-8') ?>" class="text-sm font-medium text-slate-600 hover:text-slate-900">Cancelar</a>
                <button type="submit" :disabled="!formularioVisible || !persona.nombre || !persona.dni || enviando" class="bg-[#891628] hover:bg-red-800 text-white font-bold py-3 px-6 rounded-lg shadow-md transition disabled:bg-slate-300 disabled:cursor-not-allowed flex items-center justify-center min-w-[120px]">
                     <span x-show="!enviando">✅ Asignar <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                     <span x-show="enviando">Asignando...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function formAsignar(habilitacionId, rol) {
    return {
        habilitacionId: habilitacionId,
        rol: rol.toUpperCase(),
        filtro: '',
        resultados: [],
        sugerenciasVisible: false,
        cargando: false,
        enviando: false,
        formularioVisible: false,
        
        // MODIFICADO: Objeto persona con la nueva estructura de datos
        persona: { 
            id: 0, 
            nombre: '', 
            dni: '', 
            genero: 'Masculino', // Valor por defecto
            cuit: '', 
            email: '', 
            telefono: '', 
            domicilio_calle: '', 
            domicilio_nro: '', 
            domicilio_localidad: '', 
            foto_url: 'assets/sinfoto.png' 
        },

        buscar() {
            if (this.filtro.length < 2) {
                this.resultados = [];
                return;
            }
            this.cargando = true;
            this.persona.id = 0;

            // La API ya devuelve los nuevos campos, así que esta parte no cambia
            fetch(`api/buscar_personas.php?id=${this.habilitacionId}&q=${encodeURIComponent(this.filtro)}`)
                .then(response => response.json())
                .then(data => {
                    this.resultados = data.exito ? data.personas : [];
                    this.cargando = false;
                });
        },
        seleccionar(p) {
            // El operador 'spread' (...) se encarga de rellenar los nuevos campos automáticamente
            // siempre que la API los devuelva.
            this.persona = { ...this.persona, ...p }; 
            this.filtro = `${p.nombre} - DNI: ${p.dni}`;
            this.sugerenciasVisible = false;
            this.formularioVisible = true;
        },
        prepararNuevo() {
            // Se resetea el objeto persona a su estado inicial, incluyendo el nombre buscado
            let nombreBuscado = this.filtro;
            this.persona = { 
                id: 0, nombre: nombreBuscado, dni: '', genero: 'Masculino', cuit: '', email: '', 
                telefono: '', domicilio_calle: '', domicilio_nro: '', domicilio_localidad: '', 
                foto_url: 'assets/sinfoto.png' 
            };
            this.sugerenciasVisible = false;
            this.formularioVisible = true;
            this.$nextTick(() => document.getElementById('nuevo_dni').focus());
        },
        async confirmarAsignacion() {
            // Lógica de confirmación y envío (sin cambios)
            if(this.enviando || !this.persona.nombre || !this.persona.dni) {
                Swal.fire('Datos incompletos', 'El nombre y DNI son obligatorios.', 'warning');
                return;
            }

            const swalResult = await Swal.fire({
                title: '¿Confirmar asignación?',
                html: `<p>Se asignará a <strong>${this.persona.nombre}</strong> con el rol de <strong>${this.rol.toLowerCase()}</strong>.</p>`,
                icon: 'info', showCancelButton: true, confirmButtonColor: '#16a34a', cancelButtonText: 'Cancelar', confirmButtonText: 'Sí, asignar ahora'
            });
            
            if (swalResult.isConfirmed) {
                this.enviando = true;
                const formData = new FormData(document.getElementById('formularioAsignar'));
                formData.append('rol', this.rol);
                
                // AÑADIDO: Se envía el campo de género también
                formData.append('genero', this.persona.genero);

                try {
                    const response = await fetch('api/asignar_persona.php', { method: 'POST', body: formData });
                    const data = await response.json();

                    if (data.exito) {
                        await Swal.fire({title: '¡Éxito!', text: 'La persona fue asociada correctamente.', icon: 'success', timer: 2000, showConfirmButton: false});
                        window.location.href = `edit_habilitation.php?id=${this.habilitacionId}`;
                    } else {
                        throw new Error(data.error || 'Ocurrió un error desconocido en el servidor.');
                    }
                } catch (error) {
                    Swal.fire('Error', error.message, 'error');
                } finally {
                    this.enviando = false;
                }
            }
        }
    }
}
</script>

<?php
require_once __DIR__ . '/plantillas/footer_panel.php';
?>
