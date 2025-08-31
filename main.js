const { app, BrowserWindow, ipcMain } = require('electron');
const { autoUpdater } = require('electron-updater');
const path = require('node:path');
const axios = require('axios');

const API_BASE_URL = 'https://apis.transportelanus.com.ar/api';

let win;
let detailsWindow;
let printWindow;
let currentDetailsId = null; // Variable para guardar el ID para la ventana de detalles
let currentEditId = null; // Variable para guardar el ID para la ventana de edición // Variable para guardar el ID para la ventana de detalles

function createWindow () {
  win = new BrowserWindow({
    width: 1024,
    height: 768,
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      nodeIntegration: false,
      contextIsolation: true,
      webSecurity: false // Deshabilita CORS para permitir comunicación con el hosting
    }
  });

  win.loadFile('login.html');
}

// --- Lógica de Autenticación con API Externa ---
ipcMain.on('logout', () => {
    if (win) {
        win.loadFile('login.html');
    }
});

ipcMain.handle('login', async (event, credentials) => {
    try {
        const formData = new URLSearchParams();
        formData.append('email', credentials.email);
        formData.append('password', credentials.password);

        const response = await fetch(`${API_BASE_URL}/login.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            const user = data;

            // Una vez logueado, obtenemos los datos del dashboard
            try {
                const dashboardResponse = await axios.get(`${API_BASE_URL}/dashboard_data.php`, { withCredentials: true });
                if (dashboardResponse.data.success) {
                    win.loadFile('dashboard.html');
                    win.webContents.once('did-finish-load', () => {
                        win.webContents.send('login-success', {
                            user: { name: user.name },
                            dashboard: dashboardResponse.data
                        });
                    });
                    return { success: true };
                } else {
                    return { success: false, message: 'Error al cargar datos del dashboard.' };
                }
            } catch (error) {
                console.error('Error al obtener datos del dashboard:', error);
                return { success: false, message: 'No se pudo conectar al servicio del dashboard.' };
            }
        
        } else {
            return { success: false, message: data.message || 'Credenciales inválidas.' };
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        return { success: false, message: 'No se pudo conectar con el servidor.' };
    }
});

app.whenReady().then(() => {
  autoUpdater.checkForUpdatesAndNotify();
  createWindow();

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
      createWindow();
    }
  });
});

// --- Lógica para la Ventana de Detalles ---
ipcMain.handle('open-details-window', (event, id) => {
    currentDetailsId = id;

    detailsWindow = new BrowserWindow({
        width: 800,
        height: 600,
        parent: win, // La ventana principal es la padre
        modal: true, // Bloquea la interacción con la ventana padre
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            nodeIntegration: false,
            contextIsolation: true,
        }
    });

    detailsWindow.loadFile('details.html');
    detailsWindow.on('closed', () => {
        detailsWindow = null;
    });
});

ipcMain.handle('get-details-id', (event) => {
    return currentDetailsId;
});

// Nueva función para obtener detalles para la página de edición
ipcMain.handle('get-habilitation-details', async (event, id) => {
    try {
        const response = await axios.get(`${API_BASE_URL}/details.php?id=${id}`);
        return response.data;
    } catch (error) {
        console.error('Error al obtener detalles de la habilitación:', error?.response?.data || error.message);
        return { success: false, message: 'No se pudieron obtener los detalles.' };
    }
});


// Maneja la actualización de una habilitación
ipcMain.handle('update-habilitation', async (event, payload) => {
    const url = `${API_BASE_URL}/update_habilitation.php`;
    console.log('Intentando actualizar habilitación en URL:', url);
    try {
        const response = await axios.post(`${API_BASE_URL}/update_habilitation.php`, payload, {
            headers: { 'Content-Type': 'application/json' }
        });
        return response.data;
    } catch (error) {
        console.error('Error al actualizar la habilitación:', error?.response?.data || error.message);
        return { success: false, message: 'No se pudo actualizar la habilitación.' };
    }
});

function createEditWindow(id) {
    const editWindow = new BrowserWindow({
        width: 1280,
        height: 900,
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            contextIsolation: true
        }
    });

    currentEditId = id; // Guardamos el ID para que la ventana de edición lo pida
    editWindow.loadFile('edit_habilitation.html');
    
    editWindow.on('closed', () => {
        currentEditId = null; // Limpiamos el ID cuando la ventana se cierra
    });
}

ipcMain.handle('open-edit-window', (event, id) => {
    createEditWindow(id);
});

// Manejador para que la ventana de edición pida su ID
ipcMain.handle('get-edit-id', () => {
    return currentEditId;
});

// --- Lógica para abrir ventanas genéricas (como modales de asignación) ---
ipcMain.handle('open-window', (event, url) => {
    const parentWindow = BrowserWindow.getFocusedWindow();
    const childWindow = new BrowserWindow({
        width: 900,
        height: 700,
        parent: parentWindow,
        modal: true,
        show: false,
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            contextIsolation: true,
            nodeIntegration: false
        }
    });

    const [filePath, queryString] = url.split('?');
    const query = new URLSearchParams(queryString);

    childWindow.loadFile(path.join(__dirname, filePath), { query: Object.fromEntries(query) });
    
    childWindow.once('ready-to-show', async () => {
        childWindow.show();

        if (filePath.includes('credencial.html')) {
            const habilitacionId = query.get('id');
            if (habilitacionId) {
                try {
                    const response = await axios.get(`${API_BASE_URL}/get_credential_data.php?id=${habilitacionId}`);
                    if (response.data.success) {
                        childWindow.webContents.send('credential-data', response.data.data);
                    } else {
                        console.error('API did not return success for credential data.');
                    }
                } catch (error) {
                    console.error('Error fetching credential data for window:', error);
                }
            }
        }
    });

    childWindow.on('closed', () => {
        if (parentWindow && !parentWindow.isDestroyed()) {
            parentWindow.reload();
        }
    });
});

// Maneja la actualización de un vehículo
// Maneja la desvinculación de una persona
// Maneja la eliminación de un documento
// Maneja la subida de un nuevo documento
// Maneja el reseteo de credenciales de una persona
// Maneja la eliminación completa de una habilitación
ipcMain.handle('delete-habilitation', async (event, id) => {
    try {
        const response = await axios.post(`${API_BASE_URL}/delete_habilitation.php`, { id }, {
            headers: { 'Content-Type': 'application/json' }
        });
        return response.data;
    } catch (error) {
        console.error('Error al eliminar habilitación:', error?.response?.data || error.message);
        return { success: false, message: 'No se pudo eliminar la habilitación.' };
    }
});

// Maneja el reseteo de credenciales de una persona
ipcMain.handle('reset-credentials', async (event, id) => {
    try {
        const response = await axios.post(`${API_BASE_URL}/reset_credentials.php`, { id }, {
            headers: { 'Content-Type': 'application/json' }
        });
        return response.data;
    } catch (error) {
        console.error('Error al resetear credenciales:', error?.response?.data || error.message);
        return { success: false, message: 'No se pudieron resetear las credenciales.' };
    }
});

// Maneja la subida de un nuevo documento
ipcMain.handle('upload-document', async (event, formData) => {
    try {
        // Cuando se usa FormData, axios establece automáticamente el Content-Type a 'multipart/form-data'
        const response = await axios.post(`${API_BASE_URL}/upload_document.php`, formData);
        return response.data;
    } catch (error) {
        console.error('Error al subir documento:', error?.response?.data || error.message);
        return { success: false, message: 'No se pudo subir el documento.' };
    }
});

// Maneja la eliminación de un documento
ipcMain.handle('delete-document', async (event, id) => {
    try {
        const response = await axios.post(`${API_BASE_URL}/delete_document.php`, { id }, {
            headers: { 'Content-Type': 'application/json' }
        });
        return response.data;
    } catch (error) {
        console.error('Error al eliminar documento:', error?.response?.data || error.message);
        return { success: false, message: 'No se pudo eliminar el documento.' };
    }
});

// Maneja la desvinculación de una persona
ipcMain.handle('unlink-person', async (event, id) => {
    try {
        const response = await axios.post(`${API_BASE_URL}/unlink_person.php`, { id }, {
            headers: { 'Content-Type': 'application/json' }
        });
        return response.data;
    } catch (error) {
        console.error('Error al desvincular persona:', error?.response?.data || error.message);
        return { success: false, message: 'No se pudo desvincular la persona.' };
    }
});

// Maneja la desvinculación de un vehículo
ipcMain.handle('unlink-vehicle', async (event, id) => {
    try {
        const response = await axios.post(`${API_BASE_URL}/desvincular_vehiculo.php`, { id }, {
            headers: { 'Content-Type': 'application/json' }
        });
        return response.data;
    } catch (error) {
        console.error('Error al desvincular vehículo:', error?.response?.data || error.message);
        return { success: false, message: 'No se pudo desvincular el vehículo.' };
    }
});

// Maneja la actualización de un vehículo
ipcMain.handle('update-vehicle', async (event, payload) => {
    try {
        const response = await axios.post(`${API_BASE_URL}/update_vehicle.php`, payload, {
            headers: { 'Content-Type': 'application/json' }
        });
        return response.data;
    } catch (error) {
        console.error('Error al actualizar el vehículo:', error?.response?.data || error.message);
        return { success: false, message: 'No se pudo actualizar el vehículo.' };
    }
});

ipcMain.handle('print-oblea', (event, id) => {
    currentDetailsId = id;

    printWindow = new BrowserWindow({
        show: false, // La ventana estará oculta
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            nodeIntegration: false,
            contextIsolation: true,
        }
    });

    printWindow.loadFile('oblea.html');

    printWindow.on('closed', () => {
        printWindow = null;
    });
});

// Cuando la ventana de la oblea esté lista, imprimirá
ipcMain.on('ready-to-print', (event) => {
    const webContents = event.sender;
    webContents.print({}, (success, errorType) => {
        if (!success) console.log(`Error de impresión: ${errorType}`);
        // Cierra la ventana de impresión después de imprimir o cancelar
        if(printWindow) printWindow.close();
    });
});

ipcMain.handle('renew-habilitation', async (event, id) => {
    try {
        const response = await axios.post(`${API_BASE_URL}/renew.php`, { id });
        return response.data;
    } catch (error) {
        console.error('Error al renovar la habilitación:', error);
        return { success: false, message: 'No se pudo conectar con el servicio de renovación.' };
    }
});

// --- Crear nueva habilitación ---
ipcMain.handle('create-habilitation', async (event, payload) => {
    try {
        const response = await axios.post(`${API_BASE_URL}/create.php`, payload, {
            headers: { 'Content-Type': 'application/json' }
        });
        return response.data;
    } catch (error) {
        const errorMessage = error?.response?.data?.message || error.message || 'No se pudo crear la habilitación.';
        console.error('Error al crear la habilitación:', error?.response?.data || error.message);
        return { success: false, message: errorMessage };
    }
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});
