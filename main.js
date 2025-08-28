const { app, BrowserWindow, ipcMain } = require('electron');
const path = require('path');
const axios = require('axios');

const API_BASE_URL = 'https://apis.transportelanus.com.ar/api';

let win;
let detailsWindow;
let printWindow;
let currentDetailsId = null; // Variable para guardar el ID para la ventana de detalles

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

app.whenReady().then(async () => {
  // Limpiar la caché para asegurar que los cambios se reflejen
  const session = require('electron').session;
  await session.defaultSession.clearCache();

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

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});
