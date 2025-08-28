const { contextBridge, ipcRenderer } = require('electron');

// Exponer de forma segura las funciones al proceso de renderizado
contextBridge.exposeInMainWorld('electronAPI', {
  // Función para enviar credenciales desde login.html a main.js
  login: (credentials) => ipcRenderer.invoke('login', credentials),

  // Función para que welcome.html escuche el evento 'login-success'
  onLoginSuccess: (callback) => ipcRenderer.on('login-success', (event, ...args) => callback(...args)),

  logout: () => ipcRenderer.send('logout'),

  // --- API para la ventana de detalles ---
  openDetailsWindow: (id) => ipcRenderer.invoke('open-details-window', id),
  getDetailsId: () => ipcRenderer.invoke('get-details-id'),
  printOblea: (id) => ipcRenderer.invoke('print-oblea', id),
  readyToPrint: () => ipcRenderer.send('ready-to-print'),
  renewHabilitation: (id) => ipcRenderer.invoke('renew-habilitation', id)
});
