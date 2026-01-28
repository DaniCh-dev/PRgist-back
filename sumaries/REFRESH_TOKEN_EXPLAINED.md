# 🔐 Refresh Token: Explicación Clara y Sencilla

## 🎭 La Analogía del Cine

Imagina que vas al cine:

### 🎫 JWT (Access Token) = Ticket de Entrada
- **Duración:** 1-2 horas (la película)
- **Uso:** Lo muestras cada vez que entras/sales de la sala
- **Problema:** Cuando expira, ya no puedes entrar
- **Solución:** Necesitas volver a la taquilla

### 🎟️ Refresh Token = Pase VIP
- **Duración:** 1-2 semanas (múltiples películas)
- **Uso:** Lo usas para obtener nuevos tickets sin hacer cola
- **Ventaja:** No necesitas volver a identificarte cada vez

---

## 🔄 Flujo Completo: Paso a Paso

### 1️⃣ LOGIN INICIAL (Primera vez)

```
Usuario → Backend: "Hola, soy daniel123@gmail.com, password: xxx"
                   
Backend → Base de Datos: ¿Existe este usuario? ¿Password correcto?

Base de Datos → Backend: ✅ Sí, es el usuario con ID=1

Backend crea DOS tokens:
┌─────────────────────────────────────────────────────────────┐
│ JWT (Access Token)                                          │
│ ─────────────────                                           │
│ • Duración: 1 hora                                          │
│ • Contiene: {id: 1, email: "daniel123", name: "Daniel"}    │
│ • Se envía en CADA petición                                 │
│ • NO se guarda en la base de datos                          │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│ Refresh Token                                               │
│ ─────────────                                               │
│ • Duración: 14 días                                         │
│ • Token aleatorio: "8d34a76f262efa6019f059ca..."          │
│ • SÍ se guarda en la base de datos                          │
│ • Se usa SOLO para renovar el JWT                           │
└─────────────────────────────────────────────────────────────┘

Backend → Usuario: {
    jwt: "eyJ0eXAiOiJKV1Qi...",
    refresh_token: "8d34a76f262efa6019f059ca...",
    user: {...}
}
```

**Tu aplicación Android guarda ambos:**
```kotlin
SharedPreferences.edit()
    .putString("jwt", "eyJ0eXAi...")
    .putString("refresh_token", "8d34a76f...")
    .apply()
```

---

### 2️⃣ USO NORMAL (Durante 1 hora)

```
┌─────────────┐
│   Android   │
└──────┬──────┘
       │
       │ GET /getExercises.php
       │ Header: Authorization: Bearer eyJ0eXAi...
       │
       ▼
┌─────────────┐
│   Backend   │ ✅ JWT válido (aún no expiró)
└──────┬──────┘
       │
       │ Respuesta con ejercicios
       │
       ▼
┌─────────────┐
│   Android   │ Muestra los datos
└─────────────┘
```

**Todo funciona normal durante 1 hora.**

---

### 3️⃣ JWT EXPIRA (Después de 1 hora)

```
┌─────────────┐
│   Android   │
└──────┬──────┘
       │
       │ GET /getExercises.php
       │ Header: Authorization: Bearer eyJ0eXAi... (EXPIRADO)
       │
       ▼
┌─────────────┐
│   Backend   │ ❌ JWT expirado (ya pasó 1 hora)
└──────┬──────┘
       │
       │ HTTP 401: "Token inválido o expirado"
       │
       ▼
┌─────────────┐
│   Android   │ Detecta el error 401
└─────────────┘
```

**¿Qué hace ahora Android?**

---

### 4️⃣ RENOVAR TOKEN (Sin hacer login de nuevo)

```
┌─────────────┐
│   Android   │ Detectó 401, necesita renovar JWT
└──────┬──────┘
       │
       │ POST /refresh.php
       │ Body: refresh_token: "8d34a76f262efa6019f059ca..."
       │
       ▼
┌─────────────────┐
│     Backend     │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────┐
│  1. ¿Este refresh_token existe en la BD?                    │
│     SELECT * FROM refreshtoken WHERE token = "8d34a76..."   │
│                                                              │
│  2. ¿Está expirado?                                          │
│     Compara expires_at con fecha actual                      │
│                                                              │
│  3. ✅ Todo OK: Crear nuevo JWT                              │
│     jwt_nuevo = JWT.encode({id: 1, email: "daniel123"...})  │
│                                                              │
│  4. 🔄 ROTAR el refresh token (seguridad extra)             │
│     refresh_token_nuevo = random_bytes(64)                   │
│     UPDATE refreshtoken SET token = nuevo, expires_at = ...  │
└─────────────────────────────────────────────────────────────┘
         │
         │ Respuesta: {
         │   jwt: "eyJ0eXAi... (NUEVO)",
         │   refresh_token: "nuevo_refresh_token",
         │   user: {...}
         │ }
         │
         ▼
┌─────────────┐
│   Android   │ Guarda el nuevo JWT y refresh token
│             │ SharedPreferences.edit()
│             │   .putString("jwt", nuevo_jwt)
│             │   .putString("refresh_token", nuevo)
│             │   .apply()
└──────┬──────┘
       │
       │ REINTENTAR petición original
       │ GET /getExercises.php
       │ Header: Authorization: Bearer nuevo_jwt ✅
       │
       ▼
┌─────────────┐
│   Backend   │ ✅ JWT válido (recién renovado)
└──────┬──────┘
       │
       │ Respuesta con ejercicios
       │
       ▼
┌─────────────┐
│   Android   │ ¡Funcionó! El usuario ni se enteró
└─────────────┘
```

---

## 🎯 Comparación Visual

### SIN Refresh Token (Mala experiencia):

```
Hora 0:00 → Login ✅
Hora 0:30 → Usando la app ✅
Hora 1:00 → JWT expira
Hora 1:01 → Intenta ver ejercicios ❌ 
            "Sesión expirada, vuelve a iniciar sesión"
            Usuario se ENOJA 😡
```

### CON Refresh Token (Buena experiencia):

```
Hora 0:00 → Login ✅
Hora 0:30 → Usando la app ✅
Hora 1:00 → JWT expira
Hora 1:01 → Intenta ver ejercicios
            → Android automáticamente renueva JWT con refresh token
            → Usuario sigue usando la app ✅
            → Usuario NI SE ENTERA 😊

Día 14 → Refresh token expira
         Ahora SÍ pide login (pero pasaron 2 semanas)
```

---

## 📊 Tabla Comparativa

| Característica | JWT (Access Token) | Refresh Token |
|----------------|-------------------|---------------|
| **Duración** | 1 hora | 14 días |
| **Se envía en cada petición** | ✅ Sí | ❌ No |
| **Se guarda en BD** | ❌ No | ✅ Sí |
| **Para qué sirve** | Acceder a recursos | Renovar JWT |
| **Dónde se usa** | Header Authorization | Body de /refresh.php |
| **Expira y...** | Se renueva con refresh token | Usuario debe hacer login |

---

## 🔒 Seguridad: ¿Por Qué Dos Tokens?

### ❓ ¿Por qué no hacer el JWT de 14 días directamente?

**Problema:** Si alguien roba tu JWT, tiene acceso a tu cuenta por 14 días completos.

**Solución con Refresh Token:**

1. **JWT corto (1 hora):** Si lo roban, solo funciona 1 hora
2. **Refresh token largo (14 días):** 
   - Se usa SOLO una vez (cuando el JWT expira)
   - Se ROTA (cambia) cada vez que se usa
   - Está en la BD, se puede invalidar manualmente

### 🛡️ Ventajas de Seguridad

```
Escenario: Hacker roba tu JWT

CON JWT largo (14 días):
┌────────────────────────────────────────┐
│ Hacker tiene acceso por 14 días       │
│ No puedes hacer nada                   │
│ 😱 DESASTRE                            │
└────────────────────────────────────────┘

CON JWT corto + Refresh Token:
┌────────────────────────────────────────┐
│ Hacker tiene acceso por 1 hora        │
│ Después necesita el refresh token      │
│ Refresh token está guardado solo en    │
│ el dispositivo del usuario             │
│ Admin puede invalidar refresh tokens   │
│ en la BD si detecta algo raro          │
│ ✅ MUCHO MÁS SEGURO                    │
└────────────────────────────────────────┘
```

---

## 💻 Código en Android (Simplificado)

### Interceptor para Auto-Renovación

```kotlin
class AuthInterceptor : Interceptor {
    override fun intercept(chain: Interceptor.Chain): Response {
        val request = chain.request()
        
        // 1. Añadir JWT a la petición
        val authenticatedRequest = request.newBuilder()
            .header("Authorization", "Bearer ${getJWT()}")
            .build()
        
        // 2. Hacer la petición
        var response = chain.proceed(authenticatedRequest)
        
        // 3. Si responde 401 (token expirado)
        if (response.code == 401) {
            response.close()
            
            // 4. Renovar el JWT con el refresh token
            val newJWT = refreshToken()
            
            // 5. Reintentar la petición con el nuevo JWT
            val newRequest = request.newBuilder()
                .header("Authorization", "Bearer $newJWT")
                .build()
            
            response = chain.proceed(newRequest)
        }
        
        return response
    }
    
    private fun refreshToken(): String {
        val refreshToken = getRefreshToken()
        
        // POST a refresh.php
        val response = api.refresh(refreshToken)
        
        // Guardar nuevos tokens
        saveJWT(response.jwt)
        saveRefreshToken(response.refresh_token)
        
        return response.jwt
    }
}
```

**El usuario NUNCA ve que esto está pasando. Todo es automático.**

---

## 🔄 Rotación de Refresh Token (Seguridad Extra)

### Sin Rotación (Menos Seguro):
```
Login → refresh_token: "ABC123"

Día 1: Renovar JWT → refresh_token sigue siendo: "ABC123"
Día 2: Renovar JWT → refresh_token sigue siendo: "ABC123"
Día 3: Renovar JWT → refresh_token sigue siendo: "ABC123"
...
Día 14: refresh_token sigue siendo: "ABC123"

Problema: Si roban "ABC123", funciona por 14 días
```

### Con Rotación (Más Seguro):
```
Login → refresh_token: "ABC123"

Día 1: Renovar JWT → refresh_token cambia a: "DEF456"
Día 2: Renovar JWT → refresh_token cambia a: "GHI789"
Día 3: Renovar JWT → refresh_token cambia a: "JKL012"
...

Ventaja: El refresh token cambia constantemente
         Si roban uno viejo, ya no funciona
```

**Tu código YA implementa rotación:**
```php
$newRefreshToken = bin2hex(random_bytes(64));
$stmt = $pdo->prepare("UPDATE RefreshToken SET token = :newToken WHERE id = :id");
```

---

## 📝 Base de Datos: ¿Qué se Guarda?

### Tabla `refreshtoken`:

```sql
| id | user_id | token                    | expires_at          | created_at          |
|----|---------|--------------------------|---------------------|---------------------|
| 1  | 1       | 8d34a76f262efa60...     | 2026-02-11 16:59:28 | 2026-01-28 16:59:28 |
| 2  | 2       | 79d09f93a0b15b4a...     | 2026-02-09 21:09:55 | 2026-01-26 21:09:55 |
```

**¿Por qué guardar refresh tokens?**

1. **Invalidación:** Si un usuario reporta robo, puedes borrar su refresh token
2. **Auditoría:** Ver cuántas sesiones activas tiene cada usuario
3. **Límites:** Puedes limitar a 5 sesiones por usuario (borrar los más viejos)
4. **Seguridad:** Verificar que el refresh token existe antes de renovar

---

## 🎬 Resumen Final

1. **Login** → Obtienes JWT (1h) + Refresh Token (14d)

2. **Uso normal** → Envías JWT en cada petición

3. **JWT expira** (1h después) → Android detecta error 401

4. **Auto-renovación** → Android usa Refresh Token para obtener nuevo JWT

5. **Usuario feliz** → Sigue usando la app sin interrupciones

6. **Refresh Token expira** (14d después) → Ahí SÍ pide login

---

## 🆚 Pregunta Frecuente

### ❓ "¿Por qué no simplemente guardar email y password para hacer login automático?"

**❌ MUY PELIGROSO:**
- Si hackean tu app, tienen la password en texto plano
- La password puede usarse en otros sitios (la gente reutiliza passwords)
- Violación de privacidad

**✅ Con Refresh Token:**
- Es un código aleatorio sin sentido fuera de tu app
- Si lo roban, solo funciona en tu app
- Puedes invalidarlo desde el servidor
- No compromete la password real del usuario

---

## 🔑 Analogía Final: Las Llaves de Tu Casa

### JWT = Llave de Papel (1 hora)
- Funciona solo hoy
- Si la pierdes, no es grave (expira pronto)
- Necesitas una nueva mañana

### Refresh Token = Llave Maestra (14 días)
- NO la usas para abrir la puerta directamente
- La usas para crear nuevas llaves de papel
- Si la pierdes, SÍ es grave (cambias la cerradura)

### Password = Código del Cerrajero
- NUNCA lo compartes
- Solo lo usas para hacer llaves maestras nuevas
- Es el nivel de seguridad más alto

---

¿Ahora se entiende mejor? 🎯
