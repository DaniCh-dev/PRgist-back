# 📚 CRUD Completo de Rutinas - Resumen

## 📁 Archivos PHP Creados

1. **createRoutine.php** - Crear rutina
2. **getRoutines.php** - Listar rutinas del usuario
3. **getRoutineById.php** - Ver detalle completo de una rutina
4. **getActiveRoutine.php** - Obtener la rutina activa
5. **updateRoutine.php** - Actualizar nombre de rutina
6. **activateRoutine.php** - Activar una rutina (desactiva las demás)
7. **deleteRoutine.php** - Eliminar rutina

---

## 1️⃣ createRoutine.php

**Método:** POST  
**URL:** `/createRoutine.php`  
**Headers:** `Authorization: Bearer <jwt>`  
**Body:**
```
name: Mi Rutina Push Pull
active: 1  (opcional, default: 1)
```

**Respuesta (200):**
```json
{
    "ok": true,
    "msg": "Rutina creada correctamente",
    "routine": {
        "id": 1,
        "name": "Mi Rutina Push Pull",
        "active": 1,
        "id_owner": 1
    }
}
```

**Características:**
- ✅ Solo puedes tener una rutina activa a la vez
- ✅ Si creas una rutina con `active: 1`, desactiva automáticamente las demás
- ✅ No permite nombres duplicados (por usuario)

---

## 2️⃣ getRoutines.php

**Método:** GET  
**URL:** `/getRoutines.php`  
**Headers:** `Authorization: Bearer <jwt>`

**Respuesta (200):**
```json
{
    "ok": true,
    "msg": "Rutinas obtenidas correctamente",
    "count": 3,
    "routines": [
        {
            "id": 1,
            "name": "Push Pull Legs",
            "active": 1,
            "id_owner": 1,
            "total_days": 6
        },
        {
            "id": 2,
            "name": "Full Body",
            "active": 0,
            "id_owner": 1,
            "total_days": 3
        },
        {
            "id": 3,
            "name": "Upper Lower",
            "active": 0,
            "id_owner": 1,
            "total_days": 4
        }
    ]
}
```

**Características:**
- ✅ Solo muestra TUS rutinas
- ✅ Ordenadas por activa primero, luego alfabéticamente
- ✅ Incluye contador de días de cada rutina

---

## 3️⃣ getRoutineById.php

**Método:** GET  
**URL:** `/getRoutineById.php?id=1`  
**Headers:** `Authorization: Bearer <jwt>`

**Respuesta (200):**
```json
{
    "ok": true,
    "msg": "Rutina obtenida correctamente",
    "routine": {
        "id": 1,
        "name": "Push Pull Legs",
        "active": 1,
        "id_owner": 1,
        "days": [
            {
                "id": 1,
                "name": "Push Day",
                "day_of_week": 1,
                "exercises": [
                    {
                        "id_exercise": 1,
                        "n_sets": 4,
                        "n_reps": 8,
                        "time_break": 90,
                        "exercise_name": "Press Banca"
                    },
                    {
                        "id_exercise": 2,
                        "n_sets": 3,
                        "n_reps": 12,
                        "time_break": 60,
                        "exercise_name": "Press Militar"
                    }
                ]
            },
            {
                "id": 2,
                "name": "Pull Day",
                "day_of_week": 2,
                "exercises": [...]
            }
        ]
    }
}
```

**Características:**
- ✅ Retorna rutina completa con todos sus días
- ✅ Cada día incluye sus ejercicios con sets, reps y descanso
- ✅ Solo puedes ver TUS rutinas

---

## 4️⃣ getActiveRoutine.php

**Método:** GET  
**URL:** `/getActiveRoutine.php`  
**Headers:** `Authorization: Bearer <jwt>`

**Respuesta (200):**
```json
{
    "ok": true,
    "msg": "Rutina activa obtenida correctamente",
    "routine": {
        "id": 1,
        "name": "Push Pull Legs",
        "active": 1,
        "id_owner": 1,
        "days": [...]
    }
}
```

**Si no hay rutina activa:**
```json
{
    "ok": true,
    "msg": "No tienes ninguna rutina activa",
    "routine": null
}
```

**Características:**
- ✅ Retorna la rutina que está marcada como activa
- ✅ Incluye todos los días y ejercicios
- ✅ Útil para la pantalla principal de la app

---

## 5️⃣ updateRoutine.php

**Método:** POST / PUT  
**URL:** `/updateRoutine.php`  
**Headers:** `Authorization: Bearer <jwt>`  
**Body:**
```
id: 1
name: Push Pull Legs Modificado
```

**Respuesta (200):**
```json
{
    "ok": true,
    "msg": "Rutina actualizada correctamente",
    "routine": {
        "id": 1,
        "name": "Push Pull Legs Modificado",
        "active": 1,
        "id_owner": 1
    }
}
```

**Errores:**
- 403: No es tu rutina
- 409: Ya existe otra rutina con ese nombre
- 422: ID o nombre vacío

---

## 6️⃣ activateRoutine.php

**Método:** POST  
**URL:** `/activateRoutine.php`  
**Headers:** `Authorization: Bearer <jwt>`  
**Body:**
```
id: 2
```

**Respuesta (200):**
```json
{
    "ok": true,
    "msg": "Rutina activada correctamente",
    "routine": {
        "id": 2,
        "name": "Full Body",
        "active": 1
    }
}
```

**Características:**
- ✅ Desactiva automáticamente todas tus demás rutinas
- ✅ Solo puedes tener UNA rutina activa
- ✅ Solo puedes activar TUS rutinas

---

## 7️⃣ deleteRoutine.php

**Método:** POST / DELETE  
**URL:** `/deleteRoutine.php`  
**Headers:** `Authorization: Bearer <jwt>`  
**Body:**
```
id: 3
```

**Respuesta (200):**
```json
{
    "ok": true,
    "msg": "Rutina eliminada correctamente",
    "routine": {
        "id": 3,
        "name": "Upper Lower"
    }
}
```

**Error si está en uso (409):**
```json
{
    "ok": false,
    "msg": "No puedes eliminar esta rutina porque está asignada a usuarios",
    "users_count": 5
}
```

**Características:**
- ✅ Elimina en cascada todos los días asociados
- ✅ No permite eliminar si está en `user_routine`
- ✅ Solo puedes eliminar TUS rutinas

---

## 🔄 Flujo Típico en Postman

### 1. Crear Rutina
```
POST /createRoutine.php
Body: name: "Push Pull Legs", active: 1
→ Rutina creada con id: 1
```

### 2. Listar Rutinas
```
GET /getRoutines.php
→ Ver todas tus rutinas con contador de días
```

### 3. Ver Detalle Completo
```
GET /getRoutineById.php?id=1
→ Ver rutina con todos sus días y ejercicios
```

### 4. Ver Rutina Activa
```
GET /getActiveRoutine.php
→ Ver la rutina que está marcada como activa
```

### 5. Actualizar Nombre
```
POST /updateRoutine.php
Body: id: 1, name: "Mi Nueva Rutina"
```

### 6. Cambiar Rutina Activa
```
POST /activateRoutine.php
Body: id: 2
→ Desactiva la 1, activa la 2
```

### 7. Eliminar Rutina
```
POST /deleteRoutine.php
Body: id: 3
```

---

## 🔐 Seguridad

✅ **Autenticación:** Todos requieren JWT válido  
✅ **Autorización:** Solo puedes ver/modificar TUS rutinas  
✅ **Validación:** Campos obligatorios verificados  
✅ **Integridad:** No permite eliminar si está en uso  
✅ **Unicidad:** Solo UNA rutina activa por usuario  
✅ **Cascada:** Eliminar rutina elimina sus días automáticamente  

---

## 📊 Códigos HTTP

| Código | Significado |
|--------|-------------|
| 200 | Operación exitosa |
| 401 | JWT inválido/expirado |
| 403 | No es tu rutina |
| 405 | Método HTTP incorrecto |
| 409 | Duplicado o en uso |
| 422 | Validación fallida |
| 500 | Error del servidor |

---

## 🎯 Próximos Pasos

Con el CRUD de rutinas completo, el siguiente paso es:

**CRUD de Days (Días)**
- `createDay.php` - Añadir día a una rutina
- `updateDay.php` - Modificar día
- `deleteDay.php` - Eliminar día
- `getDays.php` - Listar días de una rutina

Después:

**Gestión de Ejercicios en Días**
- `addExerciseToDay.php` - Añadir ejercicio a un día
- `updateDayExercise.php` - Modificar sets/reps/descanso
- `removExerciseFromDay.php` - Quitar ejercicio de un día

---

¿Continuamos con el CRUD de Days? 🏋️
