# Configuración de Gmail para el Sistema de Habilitaciones

Este documento describe cómo configurar correctamente una cuenta de Gmail para ser utilizada con el sistema de envío de correos electrónicos del Sistema de Habilitaciones.

## 1. Requisitos previos

- Una cuenta de Gmail (se recomienda crear una específica para el sistema)
- Acceso administrativo al Sistema de Habilitaciones

## 2. Configuración de la cuenta de Gmail

### 2.1 Habilitar acceso a aplicaciones menos seguras (si no tiene 2FA)

Si no tiene la verificación en dos pasos activada:

1. Inicie sesión en su cuenta de Gmail
2. Vaya a [Configuración de seguridad](https://myaccount.google.com/security)
3. En la sección "Acceso a Google", busque "Acceso de aplicaciones menos seguras" 
4. Active la opción "Permitir el acceso de aplicaciones menos seguras"

**Nota**: Google no recomienda este método por razones de seguridad. Es preferible usar contraseñas de aplicación con 2FA.

### 2.2 Configurar una contraseña de aplicación (recomendado, requiere 2FA)

Si tiene la verificación en dos pasos activada (recomendado):

1. Inicie sesión en su cuenta de Gmail
2. Vaya a [Configuración de seguridad](https://myaccount.google.com/security)
3. Verifique que la "Verificación en dos pasos" esté activada
4. Busque "Contraseñas de aplicación" y haga clic en ella
5. Seleccione "Otra (nombre personalizado)" del menú desplegable
6. Escriba "Sistema de Habilitaciones" y haga clic en "Generar"
7. Google le proporcionará una contraseña de 16 caracteres. **Cópiela y guárdela de forma segura**
8. Esta contraseña es la que debe usar en la configuración del sistema, no su contraseña regular de Gmail

## 3. Configuración en el Sistema de Habilitaciones

1. Acceda a la página de configuración de correo electrónico: `configuracion_email.html`
2. Complete los siguientes campos:
   - **Servidor SMTP**: smtp.gmail.com
   - **Puerto SMTP**: 587
   - **Seguridad**: TLS
   - **Autenticación SMTP**: Activado
   - **Nombre de usuario**: su_cuenta@gmail.com (la cuenta de Gmail completa)
   - **Contraseña**: Contraseña de aplicación generada (si usa 2FA) o su contraseña de Gmail
   - **Email remitente**: su_cuenta@gmail.com (debe ser la misma cuenta)
   - **Nombre remitente**: Sistema de Habilitaciones
   - **Email respuesta**: su_cuenta@gmail.com

3. Haga clic en "Enviar correo de prueba" para verificar la configuración
4. Si la prueba es exitosa, haga clic en "Guardar configuración"

## 4. Solución de problemas comunes

### Error de autenticación

Si recibe un error de autenticación:

1. Verifique que el nombre de usuario y contraseña sean correctos
2. Si usa su contraseña normal, pruebe habilitando "Acceso a aplicaciones menos seguras"
3. Si usa 2FA, asegúrese de estar usando una contraseña de aplicación válida

### Error de conexión

Si no puede conectarse al servidor:

1. Verifique que los datos del servidor SMTP sean correctos
2. Compruebe que el puerto 587 no esté bloqueado en su firewall
3. Verifique su conexión a Internet

### Correos no recibidos

Si los correos se envían pero no llegan:

1. Verifique la carpeta de spam/correo no deseado
2. Asegúrese de que la cuenta no haya superado los límites de envío de Gmail
3. Compruebe que el destinatario sea válido

## 5. Límites de Gmail

- Cuentas gratuitas: Hasta 500 correos por día
- Google Workspace: Hasta 2000 correos por día

Para volúmenes mayores, considere usar un servicio especializado como SendGrid, Mailgun o Amazon SES.

---

Para obtener más información o asistencia, contacte al administrador del sistema.
