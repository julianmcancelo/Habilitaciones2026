const express = require('express');
const mysql = require('mysql2');
const bodyParser = require('body-parser');
const cors = require('cors');

const app = express();
const port = process.env.PORT || 3000; // El puerto que te asigne tu hosting

// --- Middlewares ---
app.use(cors()); // Permite solicitudes desde otros dominios (tu app de Electron)
app.use(bodyParser.json());

// --- Configuración de la Base de Datos ---
// IMPORTANTE: Reemplaza estos valores con tus credenciales reales.
const db = mysql.createPool({
    host: 'TU_HOST',
    user: 'TU_USUARIO',
    password: 'TU_CONTRASEÑA',
    database: 'TU_BASE_DE_DATOS'
}).promise();

// --- Rutas de la API ---
app.post('/api/login', async (req, res) => {
    const { email, password } = req.body;

    if (!email || !password) {
        return res.status(400).json({ success: false, message: 'Correo y contraseña son requeridos.' });
    }

    try {
        // ADVERTENCIA DE SEGURIDAD: ¡Nunca compares contraseñas en texto plano en producción!
        const [rows] = await db.query('SELECT nombre, password FROM admin WHERE email = ?', [email]);

        if (rows.length > 0) {
            const user = rows[0];
            if (user.password === password) { // ¡INSEGURO!
                res.json({ success: true, name: user.nombre });
            } else {
                res.status(401).json({ success: false, message: 'Contraseña incorrecta.' });
            }
        } else {
            res.status(404).json({ success: false, message: 'Usuario no encontrado.' });
        }
    } catch (error) {
        console.error('Error de base de datos:', error);
        res.status(500).json({ success: false, message: 'Error en el servidor.' });
    }
});

app.listen(port, () => {
    console.log(`Servidor de la API corriendo en el puerto ${port}`);
});
