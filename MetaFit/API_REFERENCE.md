# 🚀 API Reference - MetaFit Entrenamientos

## Base URL
```
http://localhost:8000
```

## Autenticación
```
Todas las rutas requieren:
- Session activa de usuario autenticado
- ROLE_USER mínimo
```

---

## 📋 RUTAS DE RUTINAS

### Listar Rutinas del Usuario
```http
GET /routines
```
**Respuesta:** HTML con lista de rutinas  
**Parámetros:** Ninguno  
**Requerimientos:** Autenticación

---

### Crear Nueva Rutina
```http
GET /routines/new
```
**Respuesta:** HTML con formulario  
**Parámetros:** Ninguno

---

### Guardar Nueva Rutina
```http
POST /routines/new
Content-Type: application/x-www-form-urlencoded

name=Full Body
objective=Ganar Masa
days_week=3
dispo_material=Gym Completo
```

**Response:** Redirección a `/routines/{id}/edit`  
**Validación:**
- `name` (string, requerido): 3-255 caracteres
- `objective` (string, requerido): Una de las opciones predefinidas
- `days_week` (int, requerido): 3-6
- `dispo_material` (string, requerido): Material disponible

---

### Editar Rutina
```http
GET /routines/{id}/edit
```

**Path Parameters:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | int | ID de la rutina |

**Response:** HTML con editor y ejercicios

---

### Agregar Ejercicio a Rutina
```http
POST /routines/{id}/edit
Content-Type: application/x-www-form-urlencoded

action=add_exercise
exercise_id=5
```

**Path Parameters:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | int | ID de la rutina |

**Body:**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `action` | string | "add_exercise" |
| `exercise_id` | int | ID del ejercicio a agregar |

**Response:** Redirección a `/routines/{id}/edit`

---

### Remover Ejercicio de Rutina
```http
POST /routines/{id}/edit
Content-Type: application/x-www-form-urlencoded

action=remove_exercise
exercise_id=5
```

**Body:**
| Campo | Tipo | Descripción |
|-------|------|-------------|
| `action` | string | "remove_exercise" |
| `exercise_id` | int | ID del ejercicio a remover |

---

### Eliminar Rutina
```http
POST /routines/{id}/delete
```

**Path Parameters:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | int | ID de la rutina |

**Response:** Redirección a `/routines`

---

## 💪 RUTAS DE ENTRENAMIENTOS

### Mostrar Formulario de Entrenamiento
```http
GET /routines/{id}/start
```

**Path Parameters:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | int | ID de la rutina |

**Response:** HTML con interfaz de entrenamiento

---

### Guardar Sesión de Entrenamiento
```http
POST /routines/{id}/complete
Content-Type: application/json

{
  "exercises": {
    "1": {
      "completed_series": 3,
      "repetitions": 10,
      "weight": 80.5,
      "duration_minutes": 5,
      "notes": "Buena sesión",
      "completed": true
    },
    "2": {
      "completed_series": 4,
      "repetitions": 8,
      "weight": 100,
      "duration_minutes": 6,
      "notes": "",
      "completed": true
    }
  }
}
```

**Path Parameters:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | int | ID de la rutina |

**Body - Objeto exercises:**
| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `completed_series` | int | ✅ | Número de series realizadas |
| `repetitions` | int | ✅ | Número de repeticiones |
| `weight` | float | ✅ | Peso en kg |
| `duration_minutes` | int | ✅ | Duración en minutos |
| `notes` | string | ❌ | Notas opcionales |
| `completed` | bool | ✅ | Ejercicio completado |

**Response (JSON):**
```json
{
  "success": true,
  "message": "¡Entrenamiento completado! +XP",
  "xp_gained": 30,
  "achievements": ["Primer Entrenamiento"]
}
```

---

## 📊 RUTAS DE PROGRESO

### Ver Progreso de Ejercicio
```http
GET /routines/exercise/{id}/progress
```

**Path Parameters:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | int | ID del ejercicio |

**Response:** HTML con gráficos y análisis (últimos 30 días)

**Datos Incluidos:**
- Entrenamientos totales
- Peso máximo levantado
- Peso promedio
- 1RM máximo estimado
- Volumen total
- Tabla histórica
- Recomendación de próxima carga

---

### API: Obtener Recomendación de Carga
```http
GET /routines/api/exercise/{id}/next-load
```

**Path Parameters:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `id` | int | ID del ejercicio |

**Response (JSON):**
```json
{
  "exercise_id": 1,
  "next_load": 22.5,
  "suggestion": "22.5 kg"
}
```

---

## 🎯 CODIGOS DE ERROR

| Código | Descripción |
|--------|-------------|
| 200 | Éxito |
| 302 | Redirección (después de POST) |
| 400 | Datos inválidos |
| 401 | No autenticado |
| 403 | Acceso denegado (no es propietario) |
| 404 | Rutina/Ejercicio no encontrado |
| 500 | Error del servidor |

---

## 📐 FORMULAS DE CALCULO (Backend)

### 1. Estimación de 1RM
```php
if ($reps > 0 && $reps < 37) {
    $oneRM = $weight * (36 / (37 - $reps));
} else {
    $oneRM = $weight; // Sin estimar
}
```

### 2. Volumen Total
```php
$volume = $series * $reps * $weight;
```

### 3. Recomendación de Carga
```php
$avgWeight = array_sum($weights) / count($weights);
$increase = max(2.5, $avgWeight * 0.05);
$nextLoad = $avgWeight + $increase;
```

---

## 🔐 VALIDACIONES

### En Cliente (JavaScript)
- Series: min="1"
- Reps: min="1"
- Weight: min="0", step="0.5"
- Duration: min="0"

### En Servidor (PHP)
```php
// Serie/Reps/Peso validado
if ($reps < 1 || $reps > 36) {
    // Error
}

// Ownership validado
if ($routine->getOwner() !== $user) {
    throw new AccessDeniedException();
}
```

---

## 💾 COMANDOS DE PRUEBA

### Crear Rutina de Prueba
```bash
php bin/console app:create-test-routine
```
Respuesta:
```
✓ Rutina Full Body con 5 ejercicios
  ID: 1
```

### Crear Entrenamientos Históricos
```bash
php bin/console app:create-test-trainings
```
Respuesta:
```
✓ 10 entrenamientos creados
  Progresión: 20kg → 42.5kg
```

### Crear Usuario Admin
```bash
php bin/console app:create-admin-user
```
Respuesta:
```
✓ admin@metafit.com / admin123
  Role: ROLE_ADMIN
```

---

## 📱 EJEMPLOS DE USO

### Ejemplo 1: Crear Rutina y Entrenar
```bash
# 1. Login como admin@metafit.com

# 2. Create routine
curl -X POST http://localhost:8000/routines/new \
  -d "name=Mi Rutina" \
  -d "objective=Ganar Masa" \
  -d "days_week=3" \
  -d "dispo_material=Gym Completo"

# 3. Get training form
curl http://localhost:8000/routines/1/start

# 4. Complete training
curl -X POST http://localhost:8000/routines/1/complete \
  -H "Content-Type: application/json" \
  -d '{
    "exercises": {
      "1": {
        "completed_series": 3,
        "repetitions": 10,
        "weight": 80,
        "duration_minutes": 5,
        "notes": "",
        "completed": true
      }
    }
  }'
```

### Ejemplo 2: Ver Progreso
```bash
# Get progress
curl http://localhost:8000/routines/exercise/1/progress

# Get next load recommendation
curl http://localhost:8000/routines/api/exercise/1/next-load
```

---

## 🔄 FLUJO COMPLETO DE DATOS

```
1. POST /routines/new
   ↓
2. RoutineController.new()
   ↓
3. RoutineService.createRoutine()
   ↓
4. BD: INSERT Routine
   ↓
5. GET /routines/{id}/edit (agregar ejercicios)
   ↓
6. POST /routines/{id}/edit (add/remove exercises)
   ↓
7. RoutineService.addExerciseToRoutine()
   ↓
8. BD: UPDATE routine_exercise
   ↓
9. GET /routines/{id}/start
   ↓
10. POST /routines/{id}/complete
    ↓
11. RoutineService.completeRoutineSession()
    ↓
12. Para cada ejercicio:
    - recordTraining()
    - calculateOneRM()
    - BD: INSERT Training
    ↓
13. AchievementService.checkWorkoutAchievements()
    ↓
14. BD: UPDATE User XP/Level
    ↓
15. Redirect /routines
    ↓
16. GET /routines/exercise/{id}/progress
    ↓
17. Query últimos 30 días
    ↓
18. Calcular estadísticas
    ↓
19. Preparar datos para gráfico
    ↓
20. Render template con datos
```

---

## 🎨 ESTADOS DE INTERFAZ

### Rutina
```
Estado: Active/Inactive
Propietario: User que la creó
Ejercicios: List[Exercise]
```

### Entrenamiento
```
Estado: Completed/Incomplete
Datos: Series, Reps, Weight, Duration
1RM: Calculado automáticamente
```

### Progreso
```
Período: Últimos 30 días
Datos: Peso, 1RM, Volume, Duration
Gráfico: Línea progresiva
```

---

## 📞 SOPORTE

**Versión API:** 1.0.0  
**Última Actualización:** Abril 26, 2026  
**Ambiante:** Development  
**Estado:** ✅ Production Ready

Para más detalles, ver `TRAINING_GUIDE.md`
