document.addEventListener('alpine:init', () => {
    Alpine.data('formAsignar', () => ({
        // Datos iniciales
        habilitacionId: null,
        rol: '',
        label: '',
        nro_licencia: '',
        csrfToken: '',

        // Búsqueda
        filtro: '',
        resultados: [],
        sugerenciasVisible: false,
        cargando: false,

        // Formulario
        persona: {},
        personaSeleccionada: false,
        enviando: false,

        init() {
            const urlParams = new URLSearchParams(window.location.search);
            this.habilitacionId = urlParams.get('id');
            this.rol = urlParams.get('rol')?.toUpperCase();

            if (!this.habilitacionId || !this.rol) {
                Swal.fire('Error', 'Faltan parámetros en la URL (id y rol).', 'error');
                return;
            }

            // Cargar datos iniciales desde un nuevo endpoint
            fetch(`https://apis.transportelanus.com.ar/api/init_asignar_data.php?id=${this.habilitacionId}&rol=${this.rol.toLowerCase()}`)
                .then(res => res.json())
                .then(data => {
                    if (data.exito) {
                        this.nro_licencia = data.nro_licencia;
                        this.label = data.label;
                        this.csrfToken = data.csrf_token;
                    } else {
                        Swal.fire('Error', data.error, 'error');
                    }
                });

            this.resetPersona();
        },

        resetPersona() {
            this.persona = {
                id: null,
                nombre: '',
                dni: '',
                genero: 'Masculino',
                cuit: '',
                telefono: '',
                email: '',
                domicilio: '',
                foto_path: '',
                licencia_categoria: ''
            };
            this.personaSeleccionada = false;
        },

        buscar() {
            if (this.filtro.length < 2) {
                this.resultados = [];
                return;
            }
            this.cargando = true;
            fetch(`https://apis.transportelanus.com.ar/api/buscar_personas.php?q=${this.filtro}`)
                .then(res => res.json())
                .then(data => {
                    if (data.exito) {
                        this.resultados = data.personas;
                    }
                })
                .finally(() => this.cargando = false);
        },

        seleccionar(p) {
            this.persona = Object.assign({}, this.persona, p);
            this.personaSeleccionada = true;
            this.sugerenciasVisible = false;
            this.filtro = '';
        },

        prepararNuevo() {
            this.resetPersona();
            this.persona.nombre = this.filtro;
            this.personaSeleccionada = true;
            this.sugerenciasVisible = false;
            this.filtro = '';
        },

        confirmarAsignacion() {
            const formData = new FormData(document.getElementById('formularioAsignar'));
            // Agregar rol al FormData
            formData.append('rol', this.rol);

            this.enviando = true;

            fetch('https://apis.transportelanus.com.ar/api/asignar_persona.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.exito) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: data.mensaje,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        if (window.opener) {
                            window.opener.location.reload(); // Recargar la ventana anterior si existe
                            window.close(); // Cerrar esta ventana
                        }
                    });
                } else {
                    Swal.fire('Error', data.error, 'error');
                }
            })
            .catch(() => Swal.fire('Error', 'Ocurrió un problema de conexión.', 'error'))
            .finally(() => this.enviando = false);
        }
    }));
});
