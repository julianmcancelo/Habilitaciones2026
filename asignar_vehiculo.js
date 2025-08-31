document.addEventListener('alpine:init', () => {
    Alpine.data('formAsignarVehiculo', () => ({
        // Datos iniciales
        habilitacionId: null,
        nro_licencia: '', // Se podría cargar con una llamada inicial si es necesario

        // Búsqueda
        filtro: '',
        resultados: [],
        sugerenciasVisible: false,
        cargando: false,

        // Formulario
        vehiculo: {},
        vehiculoSeleccionado: false,
        enviando: false,

        init() {
            const urlParams = new URLSearchParams(window.location.search);
            this.habilitacionId = urlParams.get('id');
            this.nro_licencia = urlParams.get('nro_licencia') || 'Desconocido'; // Tomar de la URL o mostrar texto alternativo

            if (!this.habilitacionId) {
                Swal.fire('Error', 'Falta el ID de la habilitación en la URL.', 'error');
                return;
            }

            this.resetVehiculo();
        },

        resetVehiculo() {
            this.vehiculo = {
                id: null,
                dominio: '',
                marca: '',
                modelo: '',
                tipo: '',
                chasis: '',
                ano: null,
                motor: '',
                asientos: null,
                inscripcion_inicial: '',
                Aseguradora: '',
                poliza: '',
                Vencimiento_Poliza: '',
                Vencimiento_VTV: ''
            };
            this.vehiculoSeleccionado = false;
        },

        buscar() {
            if (this.filtro.length < 2) {
                this.resultados = [];
                return;
            }
            this.cargando = true;
            // Apuntar al nuevo endpoint para buscar vehículos
            fetch(`https://apis.transportelanus.com.ar/api/buscar_vehiculos.php?q=${this.filtro}`)
                .then(res => res.json())
                .then(data => {
                    if (data.exito) {
                        this.resultados = data.vehiculos;
                    }
                })
                .finally(() => this.cargando = false);
        },

        seleccionar(v) {
            this.vehiculo = Object.assign({}, this.vehiculo, v);
            this.vehiculoSeleccionado = true;
            this.sugerenciasVisible = false;
            this.filtro = '';
        },

        prepararNuevo() {
            this.resetVehiculo();
            this.vehiculo.dominio = this.filtro.toUpperCase();
            this.vehiculoSeleccionado = true;
            this.sugerenciasVisible = false;
            this.filtro = '';
        },

        confirmarAsignacion() {
            this.enviando = true;

            // Construir FormData manualmente para asegurar que todos los datos se envíen
            const formData = new FormData();
            formData.append('habilitacion_id', this.habilitacionId);
            for (const key in this.vehiculo) {
                if (this.vehiculo[key] !== null && this.vehiculo[key] !== undefined) {
                    formData.append(key, this.vehiculo[key]);
                }
            }

            // Apuntar al nuevo endpoint para asignar vehículos
            fetch('https://apis.transportelanus.com.ar/api/asignar_vehiculo.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.exito) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: 'Vehículo asignado correctamente.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        if (window.opener) {
                            window.opener.location.reload();
                            window.close();
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
