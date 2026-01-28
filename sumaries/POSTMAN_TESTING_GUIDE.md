# 🧪 Guía de Pruebas con Postman - PRgist API

## 📋 Prerequisitos

1. **Servidor local corriendo:**
   - XAMPP/WAMP/MAMP activo
   - Apache y MySQL iniciados
   - Archivos PHP en: `C:\xampp\htdocs\prgist\` (o tu carpeta del servidor)

2. **Base de datos importada:**
   - Importar `prgist.sql` en phpMyAdmin
   - Verificar que exista la BD `prgist`

3. **Archivo .env configurado:**
   ```env
   JWT_SECRET=tu_clave_secreta_super_segura_aqui
   JWT_EXPIRATION=3600
   REFRESH_EXPIRATION=1209600
   ```

---

## 🔄 PASO 1: Registrar un Usuario

### Request
- **Método:** `POST`
- **URL:** `http://localhost/prgist/userRegist.php`
- **Headers:** (ninguno especial)
- **Body:** `x-www-form-urlencoded`

| Key | Value |
|-----|-------|
| name | Juan Pérez |
| email | juan@example.com |
| password | password123 |
| confirm_password | password123 |

### Response Esperada (200 OK)
```json
{
    "ok": true,
    "msg": "Usuario registrado correctamente",
    "name": "Juan Pérez",
    "email": "juan@example.com"
}
```

### Screenshots Postman:
1. Selecciona **POST**
2. Pega la URL
3. Ve a pestaña **Body**
4. Selecciona **x-www-form-urlencoded**
5. Añade los 4 campos (name, email, password, confirm_password)
6. Click en **Send**

---

## 🔑 PASO 2: Hacer Login

### Request
- **Método:** `POST`
- **URL:** `http://localhost/prgist/userLogin.php`
- **Headers:** (ninguno especial)
- **Body:** `x-www-form-urlencoded`

| Key | Value |
|-----|-------|
| email | juan@example.com |
| password | password123 |

### Response Esperada (200 OK)
```json
{
    "ok": true,
    "msg": "Inicio de sesión exitoso",
    "jwt": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "8d34a76f262efa6019f059ca...",
    "user": {
        "id": 1,
        "name": "Juan Pérez",
        "email": "juan@example.com"
    }
}
```

### ⚠️ IMPORTANTE: Copia el JWT
**Guarda el valor de `jwt`** que recibes. Lo necesitarás para los siguientes pasos.

Ejemplo de JWT:
```
eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE3MDY0NTY3ODksImV4cCI6MTcwNjQ2MDM4OSwic3ViIjoxLCJlbWFpbCI6Imp1YW5AZXhhbXBsZS5jb20iLCJuYW1lIjoiSnVhbiBQw6lyZXoifQ.xyz123...
```

---

## 🏋️ PASO 3: Crear un Ejercicio (CON AUTENTICACIÓN)

### Request
- **Método:** `POST`
- **URL:** `http://localhost/prgist/createExercise.php`
- **Headers:**

| Key | Value |
|-----|-------|
| Authorization | Bearer eyJ0eXAiOiJKV1QiLCJhbGc... |

⚠️ **IMPORTANTE:** Incluye la palabra `Bearer` seguida de un espacio y luego el JWT

- **Body:** `x-www-form-urlencoded`

| Key | Value |
|-----|-------|
| name | Press Banca |

### Response Esperada (200 OK)
```json
{
    "ok": true,
    "msg": "Ejercicio creado correctamente",
    "exercise": {
        "id": 1,
        "name": "Press Banca"
    }
}
```

### Configuración en Postman paso a paso:

1. **Configurar URL y Método:**
   - Método: `POST`
   - URL: `http://localhost/prgist/createExercise.php`

2. **Configurar Headers:**
   - Ve a la pestaña **Headers**
   - Click en "Add manually" o escribe directamente:
     - Key: `Authorization`
     - Value: `Bearer eyJ0eXAi...` (tu JWT completo)
   
3. **Configurar Body:**
   - Ve a la pestaña **Body**
   - Selecciona **x-www-form-urlencoded**
   - Añade: Key: `name`, Value: `Press Banca`

4. **Enviar:**
   - Click en **Send**

---

## 🔄 PASO 4: Renovar Token (Opcional)

Si tu JWT expira después de 1 hora, puedes renovarlo:

### Request
- **Método:** `POST`
- **URL:** `http://localhost/prgist/refresh.php`
- **Headers:** (ninguno especial)
- **Body:** `x-www-form-urlencoded`

| Key | Value |
|-----|-------|
| refresh_token | 8d34a76f262efa6019f059ca... |

### Response Esperada (200 OK)
```json
{
    "ok": true,
    "msg": "Token renovado correctamente",
    "jwt": "eyJ0eXAiOiJKV1QiLCJhbGc...",
    "refresh_token": "nuevo_refresh_token...",
    "user": {
        "id": 1,
        "name": "Juan Pérez",
        "email": "juan@example.com"
    }
}
```

---

## ❌ Posibles Errores y Soluciones

### Error 401: "Token no proporcionado"
**Causa:** No enviaste el header Authorization
**Solución:** 
- Ve a la pestaña Headers en Postman
- Añade: `Authorization: Bearer tu_jwt_aqui`
- Asegúrate de incluir "Bearer " antes del token

### Error 401: "Token inválido o expirado"
**Causa:** El JWT expiró (después de 1 hora) o es incorrecto
**Solución:** 
- Haz login de nuevo (PASO 2)
- Copia el nuevo JWT
- Úsalo en tus requests

### Error 409: "El ejercicio ya existe"
**Causa:** Ya existe un ejercicio con ese nombre
**Solución:** 
- Usa otro nombre de ejercicio
- O borra el ejercicio de la BD primero

### Error 500: "Error al crear el ejercicio"
**Causa:** Problema con la BD o servidor
**Solución:** 
- Verifica que MySQL esté corriendo
- Verifica que la tabla Exercise exista
- Revisa los logs de PHP

### Error 422: "El nombre del ejercicio es obligatorio"
**Causa:** No enviaste el campo "name" en el body
**Solución:** 
- Ve a la pestaña Body
- Asegúrate de tener: Key: `name`, Value: `Press Banca`

---

## 🎯 Tips para Postman

### 1. Guardar el JWT como Variable de Entorno

En vez de copiar/pegar el JWT cada vez:

1. Click en el ⚙️ (Settings) arriba a la derecha
2. Click en **Environments**
3. Click en **+ Create Environment**
4. Nombre: `PRgist Local`
5. Añade variable:
   - Variable: `jwt_token`
   - Initial Value: (vacío)
   - Current Value: (vacío)

6. Después del login, ve a la pestaña **Tests** en Postman:
```javascript
pm.environment.set("jwt_token", pm.response.json().jwt);
```

7. Ahora en tus headers usa:
```
Authorization: Bearer {{jwt_token}}
```

### 2. Crear una Colección

1. Click en **Collections** en el sidebar
2. Click en **+ New Collection**
3. Nombre: `PRgist API`
4. Añade todos tus requests ahí
5. Organízalos en carpetas:
   - 📁 Auth (Login, Register, Refresh)
   - 📁 Exercises (Create, Get, Update, Delete)
   - 📁 Routines (...)

### 3. Pre-request Script para Auto-Login

Si el token expira mucho, puedes crear un script que haga login automático:

En la pestaña **Pre-request Script** de tu colección:
```javascript
// Si el token está vacío o expirado, hacer login automático
if (!pm.environment.get("jwt_token")) {
    // Aquí puedes hacer un request de login
    console.log("Token vacío, necesitas hacer login");
}
```

---

## 📝 Checklist de Prueba Completa

- [ ] Servidor local corriendo (XAMPP/WAMP)
- [ ] Base de datos importada
- [ ] Archivo .env configurado
- [ ] ✅ PASO 1: Registro exitoso
- [ ] ✅ PASO 2: Login exitoso
- [ ] ✅ PASO 3: JWT guardado
- [ ] ✅ PASO 4: Crear ejercicio exitoso
- [ ] ✅ Verificar en phpMyAdmin que el ejercicio se guardó

---

## 🔍 Verificar en la Base de Datos

Después de crear un ejercicio, verifica en phpMyAdmin:

```sql
SELECT * FROM Exercise;
```

Deberías ver tu ejercicio recién creado:

| id | name |
|----|------|
| 1  | Press Banca |

---

## 📞 Próximos Endpoints a Crear

Con este mismo sistema podrás probar:
- `getExercises.php` - Listar ejercicios
- `updateExercise.php` - Actualizar ejercicio
- `deleteExercise.php` - Eliminar ejercicio
- `createRoutine.php` - Crear rutina
- `getRoutines.php` - Listar rutinas
- ... y muchos más

---

¿Necesitas ayuda con algún paso específico? 🚀
