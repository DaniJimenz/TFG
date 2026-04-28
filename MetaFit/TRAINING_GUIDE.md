# 🏋️ MetaFit AI - Guía Completa de Entrenamientos

## Estado Actual del Sistema (v1.0)

✅ **Implementado y Funcional:**
- Sistema completo de creación y gestión de rutinas
- Seguimiento detallado de entrenamientos con cálculo de 1RM
- Interfaz visual con gráficos de progresión
- Sistema de recomendaciones de carga inteligente
- Integración con sistema de logros y XP

---

## 🎮 Demostración Interactiva

### Para probar la funcionalidad completa:

1. **Login como Admin**
   - Email: `admin@metafit.com`
   - Contraseña: `admin123`

2. **Ir a Entrenamientos**
   - URL: `http://localhost:8000/routines`
   - Verás "Rutina Full Body" con 5 ejercicios

3. **Entrenar**
   - Click en "Entrenar Ahora"
   - Ingresa series, reps y peso
   - Observa cálculos en tiempo real
   - Completa la sesión

4. **Ver Progreso**
   - Haz click en cualquier ejercicio
   - Visualiza gráfico de 10 sesiones de prueba
   - La progresión: 20kg → 42.5kg (+2.5kg por sesión)
   - Recomendación: 44.5kg para próxima sesión

---

## 📊 Datos de Prueba Precargados

### Rutina: "Full Body"
- **Duración:** 3 días/semana
- **Material:** Gym completo
- **Objetivo:** Ganar Masa
- **Ejercicios:** 5 (Bench Press + 4 más)

### Entrenamientos Históricos
- **10 sesiones** del ejercicio "Bench Press"
- **Progresión de peso:** 20kg → 42.5kg
- **Fechas:** Últimos 10 días
- **1RM progresivo:** 26.67kg → 56.77kg

---

## 🔧 Configuración de Base de Datos

### Tablas Relacionadas

```sql
-- Rutinas
CREATE TABLE routine (
  id SERIAL PRIMARY KEY,
  owner_id INT NOT NULL,
  name VARCHAR(255),
  objective VARCHAR(255),
  days_week INT,
  dispo_material VARCHAR(255),
  created_at TIMESTAMP,
  date_start TIMESTAMP,
  active BOOLEAN
);

-- Entrenamientos
CREATE TABLE training (
  id SERIAL PRIMARY KEY,
  app_user_id INT NOT NULL,
  exercise_id INT NOT NULL,
  routine_id INT NOT NULL,
  date TIMESTAMP,
  completed_series INT,
  repetitions INT,
  weight FLOAT,
  duration_minutes INT,
  notes TEXT,
  completed BOOLEAN,
  one_rm_estimated FLOAT
);

-- Relación Rutina-Ejercicio
CREATE TABLE routine_exercise (
  routine_id INT,
  exercise_id INT,
  PRIMARY KEY (routine_id, exercise_id)
);
```

---

## 🚀 Cómo Funciona Internamente

### 1. Crear Rutina
```
Usuario → POST /routines/new
  ↓
RoutineController.new()
  ↓
RoutineService.createRoutine($user, $data)
  ↓
BD: INSERT INTO routine
  ↓
Redirect → /routines/{id}/edit
```

### 2. Entrenar
```
Usuario → GET /routines/{id}/start
  ↓
RoutineController.start()
  ↓
Template: routine/start.html.twig
  ↓
JavaScript calcula en tiempo real:
  - Volumen = Series × Reps × Peso
  - 1RM = Brzycki Formula
  ↓
Usuario completa → POST /routines/{id}/complete
  ↓
RoutineService.completeRoutineSession()
  ↓
Para cada ejercicio:
  - RecordTraining()
  - Calcula 1RM
  - INSERT INTO training
  ↓
AchievementService.checkWorkoutAchievements()
  ↓
+XP & Logro desbloqueado
```

### 3. Ver Progreso
```
Usuario → GET /routines/exercise/{id}/progress
  ↓
RoutineController.exerciseProgress()
  ↓
Query últimos 30 días de entrenamientos
  ↓
Calcular estadísticas:
  - Max weight
  - Avg weight
  - Max 1RM
  - Total volume
  ↓
Preparar datos para Chart.js
  ↓
Render template con datos
```

---

## 🧮 Ejemplos de Cálculos

### Ejemplo 1: Estimación de 1RM
```
Ejercicio: Bench Press
Datos: 80kg × 5 repeticiones

Fórmula: 1RM = weight × (36 / (37 - reps))
1RM = 80 × (36 / (37 - 5))
1RM = 80 × (36 / 32)
1RM = 80 × 1.125
1RM = 90kg ✓

Interpretación: Si levantaste 80kg × 5 reps,
tu máximo estimado es de 90kg
```

### Ejemplo 2: Volumen Total
```
Sesión de Sentadillas:
- Series: 4
- Repeticiones: 8
- Peso: 100kg

Volumen = 4 × 8 × 100 = 3200kg

Interpretación: Moviste 3200kg en total
durante esta sesión
```

### Ejemplo 3: Recomendación de Carga
```
Histórico de Sentadillas (últimas 5 sesiones):
- Sesión 1: 100kg
- Sesión 2: 100kg
- Sesión 3: 102.5kg
- Sesión 4: 102.5kg
- Sesión 5: 105kg
Promedio: 102kg

Recomendación: 102 + MAX(2.5, 102 × 0.05)
             = 102 + MAX(2.5, 5.1)
             = 102 + 5.1
             = 107.1kg

✓ Incremento sugerido: +5.1kg
```

---

## 📋 API Endpoints Completo

### 1. Rutinas
```http
GET    /routines              → Listar rutinas
POST   /routines/new          → Crear rutina
GET    /routines/{id}/edit    → Editar rutina
POST   /routines/{id}/edit    → Guardar cambios
POST   /routines/{id}/delete  → Eliminar rutina
```

### 2. Entrenamientos
```http
GET    /routines/{id}/start       → Mostrar formulario
POST   /routines/{id}/complete    → Guardar sesión
```

### 3. Progreso
```http
GET    /routines/exercise/{id}/progress          → Ver progreso
GET    /routines/api/exercise/{id}/next-load     → JSON: recomendación
```

---

## 💾 Formato de Datos

### Crear Rutina
```json
POST /routines/new
{
  "name": "Full Body",
  "objective": "Ganar Masa",
  "days_week": 3,
  "dispo_material": "Gym Completo"
}
```

### Completar Entrenamiento
```json
POST /routines/{id}/complete
{
  "exercises": {
    "1": {
      "completed_series": 3,
      "repetitions": 10,
      "weight": 80,
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

### Respuesta API Next Load
```json
GET /routines/api/exercise/{id}/next-load
{
  "exercise_id": 1,
  "next_load": 22.5,
  "suggestion": "22.5 kg"
}
```

---

## 🎯 Validaciones Implementadas

### En Formulario
- ✅ Nombre rutina: requerido, min 3 caracteres
- ✅ Objetivo: seleccionar de lista predefinida
- ✅ Días/semana: 3-6 días
- ✅ Material: seleccionar de lista

### En Entrenamientos
- ✅ Series: 1 o más
- ✅ Repeticiones: 1-36 (para cálculos precisos)
- ✅ Peso: 0 o superior
- ✅ Duración: 0 o superior
- ✅ Notas: texto libre, opcional

### En Servidor
- ✅ Usuario debe ser propietario de rutina
- ✅ Ejercicio debe existir en rutina
- ✅ 1RM solo para reps 1-36

---

## 🎨 Componentes UI

### Tarjetas de Ejercicio (start.html.twig)
```html
<div class="bg-[#0d1117] border border-slate-800 rounded-2xl p-6">
  <h3>Nombre Ejercicio</h3>
  <input type="number" placeholder="Series">
  <input type="number" placeholder="Reps">
  <input type="number" placeholder="Peso (kg)">
  <div class="stats">
    <span>Volumen: 600kg</span>
    <span>1RM: 26.67kg</span>
  </div>
</div>
```

### Gráfico de Progresión (exercise_progress.html.twig)
```javascript
new Chart(ctx, {
  type: 'line',
  data: {
    labels: ['01/04', '02/04', ...],
    datasets: [
      {
        label: 'Peso (kg)',
        data: [20, 22.5, 25, ...],
        borderColor: '#fbbf24'
      },
      {
        label: '1RM (kg)',
        data: [26.67, 30.08, 33.33, ...],
        borderColor: '#a855f7',
        borderDash: [5, 5]
      }
    ]
  }
});
```

---

## 🔗 Flujo de Navegación

```
Inicio
  ↓
[Entrenamientos] ← Menú principal
  ↓
Listar Rutinas
  ├─→ [Crear Nueva]
  │     ├─→ Form Crear
  │     └─→ [Editar] (agregar ejercicios)
  │
  ├─→ [Entrenar Ahora]
  │     ├─→ Formulario de Entrenamiento
  │     └─→ [Completar]
  │           ├─→ +XP
  │           └─→ Logro desbloqueado
  │
  └─→ [Ver Progreso]
        ├─→ Gráficos
        ├─→ Tabla histórica
        └─→ Recomendación de carga
```

---

## 📱 Puntos de Integración

### Con Sistema de Logros
```php
// Primer entrenamiento
Achievement: "Primer Entrenamiento" (+50 XP)
Trigger: Completar primer training

// Rachas
Achievement: "Guerrero de 7 Días" (+150 XP)
Trigger: 7 entrenamientos en 7 días
```

### Con Dashboard
```php
// Se actualiza automáticamente
- Total entrenamientos
- Tiempo total entrenado
- Calorías quemadas
- Racha actual
- Último ejercicio
```

---

## 🐛 Debugging

### Ver todas las rutinas de un usuario
```bash
php bin/console doctrine:query:dql "SELECT r FROM App\Entity\Routine r WHERE r.owner = :user" -u admin@metafit.com
```

### Ver entrenamientos de un ejercicio
```bash
php bin/console doctrine:query:dql "SELECT t FROM App\Entity\Training t WHERE t.exercise = :exercise ORDER BY t.date DESC" -l 10
```

### Limpiar caché
```bash
php bin/console cache:clear
```

---

## 📊 Estadísticas Implementadas

| Métrica | Descripción | Fórmula |
|---------|-------------|---------|
| **Entrenamientos** | Total de sesiones | COUNT(*) |
| **Peso Máx** | Máximo peso levantado | MAX(weight) |
| **Peso Prom** | Promedio de pesos | AVG(weight) |
| **1RM Máx** | Máxima estimación 1RM | MAX(one_rm_estimated) |
| **Volumen Total** | Total movido en kg | SUM(series × reps × weight) |
| **Duración Total** | Tiempo total entrenado | SUM(duration_minutes) |
| **Próxima Carga** | Sugerencia siguente | AVG + MAX(2.5, AVG×5%) |

---

## ✨ Características Premium (Futuras)

- 🎵 Música de fondo y sonidos de motivación
- 📹 Video demostrativo durante entrenamiento
- 🔔 Notificaciones inteligentes para entrenar
- 📈 Análisis predictivo de performance
- 🏆 Competencias con amigos
- 📱 App nativa iOS/Android
- ⌚ Sincronización con smartwatch
- 🤖 Generador de rutinas con IA

---

## 📞 Soporte Técnico

**Última Actualización:** Abril 26, 2026  
**Versión:** 1.0.0  
**Estado:** ✅ Production Ready  
**Ambiente:** Development (localhost:8000)

Para más información, consulta `TRAINING_SYSTEM.md`
