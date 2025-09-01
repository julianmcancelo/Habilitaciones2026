const { contextBridge, ipcRenderer } = require('electron');

// Exponer de forma segura las funciones al proceso de renderizado
contextBridge.exposeInMainWorld('electronAPI', {
  // Función para enviar credenciales desde login.html a main.js
  login: (credentials) => ipcRenderer.invoke('login', credentials),

  // Función para que welcome.html escuche el evento 'login-success'
  onLoginSuccess: (callback) => ipcRenderer.on('login-success', (event, ...args) => callback(...args)),
  onDashboardDataUpdated: (callback) => ipcRenderer.on('dashboard-data-updated', (event, ...args) => callback(...args)),
  requestDashboardRefresh: () => ipcRenderer.send('request-dashboard-refresh'),

  logout: () => ipcRenderer.send('logout'),

  // --- API para la ventana de detalles ---
  openDetailsWindow: (id) => ipcRenderer.invoke('open-details-window', id),
  getDetailsId: () => ipcRenderer.invoke('get-details-id'),
  printOblea: (id) => ipcRenderer.invoke('print-oblea', id),
  readyToPrint: () => ipcRenderer.send('ready-to-print'),
  renewHabilitation: (id) => ipcRenderer.invoke('renew-habilitation', id),
  createHabilitation: (payload) => ipcRenderer.invoke('create-habilitation', payload),
  getHabilitationDetails: (id) => ipcRenderer.invoke('get-habilitation-details', id),
  updateHabilitation: (payload) => ipcRenderer.invoke('update-habilitation', payload),
  openEditWindow: (id) => ipcRenderer.invoke('open-edit-window', id),
  getEditId: () => ipcRenderer.invoke('get-edit-id'),
  updateVehicle: (payload) => ipcRenderer.invoke('update-vehicle', payload),
  unlinkVehicle: (id) => ipcRenderer.invoke('unlink-vehicle', id),
  unlinkPerson: (id) => ipcRenderer.invoke('unlink-person', id),
  deleteDocument: (id) => ipcRenderer.invoke('delete-document', id),
  uploadDocument: (formData) => ipcRenderer.invoke('upload-document', formData),
  resetCredentials: (id) => ipcRenderer.invoke('reset-credentials', id),
  deleteHabilitation: (id) => ipcRenderer.invoke('delete-habilitation', id),
  openWindow: (url) => ipcRenderer.invoke('open-window', url),
  onCredentialData: (callback) => ipcRenderer.on('credential-data', (event, ...args) => callback(...args))
});
