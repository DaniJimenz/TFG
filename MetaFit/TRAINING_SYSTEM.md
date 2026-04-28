# MetaFit AI - Sistema de Entrenamientos y Rutinas

## 🎯 Características Implementadas (Opción C: Refinar Entrenamientos)

### 1. **Gestión de Rutinas Personalizadas**
- Crear rutinas con nombre, objetivo, días por semana y material disponible
- Editar y eliminar rutinas existentes
- Agregar/remover ejercicios de las rutinas
- Vista de todas las rutinas del usuario

### 2. **Seguimiento Detallado de Series/Repeticiones/Peso**
- Registrar series, repeticiones, peso y duración por ejercicio
- Cálculo automático de volumen total (Series × Reps × Weight)
- Estimación de 1RM usando la fórmula de Brzycki: **weight × (36 / (37 - reps))**
- Historial completo de entrenamientos

### 3. **Cronómetro Integrado**
- Timer para cada sesión de entrenamiento
- Seguimiento de duración en minutos
- Guardado automático de tiempos

### 4. **Validación de Forma y Técnica**
- Validación de reps < 37 para cálculos precisos de 1RM
- Consejos de técnica en progreso de ejercicios
- Aumento gradual recomendado de carga

### 5. **Progresión de Carga Inteligente**
- Recomendación automática del próximo peso
- Aumenta en +2.5kg o +5% del promedio (el mayor)
- Visualización de progresión en gráficos
- Análisis de tendencias

---

## 📱 Rutas y Endpoints

### Rutas Principales

| Ruta | Método | Descripción |
|------|--------|-------------|
| `/routines` | GET | Listar todas las rutinas del usuario |
| `/routines/new` | GET/POST | Crear nueva rutina |
| `/routines/{id}/edit` | GET/POST | Editar rutina y gestionar ejercicios |
| `/routines/{id}/start` | GET | Iniciar sesión de entrenamiento |
| `/routines/{id}/complete` | POST | Guardar sesión de entrenamiento completada |
| `/routines/exercise/{id}/progress` | GET | Ver progreso de un ejercicio |
| `/routines/api/exercise/{id}/next-load` | GET | API: Obtener recomendación de carga |
| `/routines/{id}/delete` | POST | Eliminar rutina |

---

## 📊 Cálculos Implementados

### 1. Fórmula de Brzycki para 1RM
```
1RM = weight × (36 / (37 - reps))
```
**Ejemplo:**
- Levantaste 20kg por 8 repeticiones
- 1RM estimado = 20 × (36 / (37 - 8)) = 20 × (36 / 29) = **24.83 kg**

### 2. Volumen Total
```
Volumen = Series × Repeticiones × Peso
```
**Ejemplo:**
- 3 series × 10 repeticiones × 20kg = **600 kg** de volumen total

### 3. Recomendación de Carga Siguiente
```
Siguiente = Promedio_Peso + MAX(2.5kg, Promedio_Peso × 5%)
```
**Ejemplo:**
- Promedio últimas sesiones: 20kg
- Aumento recomendado: MAX(2.5, 20 × 0.05) = MAX(2.5, 1) = 2.5kg
- **Siguiente: 22.5kg**

---

## 🎮 Flujo de Usuario Completo

### 1. Crear Rutina
```
1. Ir a /routines/new
2. Llenar formulario:
   - Nombre: "Full Body"
   - Objetivo: "Ganar Masa"
   - Días/Semana: 3
   - Material: "Gym Completo"
3. Submit → Editar y agregar ejercicios
```

### 2. Agregar Ejercicios
```
1. En /routines/{id}/edit
2. Seleccionar ejercicios disponibles
3. Hacer click en "+" para agregar
4. Hacer click en "-" para remover
```

### 3. Entrenar
```
1. Click en "Entrenar Ahora"
2. Para cada ejercicio:
   - Ingresar series (ej: 3)
   - Ingresar repeticiones (ej: 10)
   - Ingresar peso usado (ej: 20kg)
   - Ingresar duración (ej: 5 min)
   - (Opcional) Agregar notas
3. Sistema calcula automáticamente:
   - Volumen: 3 × 10 × 20 = 600kg
   - 1RM: 20 × (36/27) = 26.67kg
4. Click en "Completar Entrenamiento"
5. +XP ganado y logros desbloqueados
```

### 4. Ver Progreso
```
1. Ir a progreso de ejercicio
2. Visualizar gráfico de progresión
3. Ver estadísticas:
   - Entrenamientos totales
   - Peso máximo
   - Peso promedio
   - 1RM estimado máximo
   - Volumen total
4. Consultar historial con tabla detallada
5. Ver recomendación del próximo peso
```

---

## 💾 Servicios Implementados

### RoutineService
```php
// Crear rutina
$routine = $routineService->createRoutine($user, [...]);

// Agregar/remover ejercicios
$routineService->addExerciseToRoutine($routine, $exercise);
$routineService->removeExerciseFromRoutine($routine, $exercise);

// Registrar entrenamiento
$training = $routineService->recordTraining($user, $exercise, $data);

// Calcular 1RM
$oneRM = $routineService->calculateOneRM(20, 8); // 24.83

// Completar sesión
$completedTrainings = $routineService->completeRoutineSession($routine, $user, $exerciseData);

// Obtener progreso
$progress = $routineService->getExerciseProgress($user, $exercise, 30);

// Recomendación de carga
$nextLoad = $routineService->getNextLoadRecommendation($user, $exercise);

// Calcular volumen
$volume = $routineService->calculateVolume(3, 10, 20); // 600
```

---

## 🎨 Interfaz de Usuario

### 1. Listado de Rutinas (`routine/index.html.twig`)
- Grid de rutinas con información resumida
- Botón "Entrenar Ahora" (color dorado primario)
- Botón "Editar" para gestionar
- Estado activo/inactivo

### 2. Crear Rutina (`routine/new.html.twig`)
- Formulario con 4 campos
- Validación en cliente y servidor
- Redirección automática a edición

### 3. Editar Rutina (`routine/edit.html.twig`)
- Layout de dos columnas
- Ejercicios actuales en rutina (izquierda)
- Ejercicios disponibles para agregar (derecha)
- Información resumida de la rutina

### 4. Entrenar (`routine/start.html.twig`)
- Listado de ejercicios con inputs para:
  - Series (número)
  - Repeticiones (número)
  - Peso en kg (decimal)
  - Duración en minutos (número)
  - Notas (texto)
- Cálculos en tiempo real:
  - Volumen = Series × Reps × Peso
  - 1RM = Brzycki formula
- Botón "Completar Entrenamiento"

### 5. Progreso de Ejercicio (`routine/exercise_progress.html.twig`)
- Tarjetas de estadísticas rápidas
- Gráfico de progresión (peso y 1RM)
- Tabla histórica completa
- Recomendación de próxima carga con detalles
- Consejos de técnica

---

## 🧪 Comandos de Prueba

### Crear Rutina de Prueba
```bash
php bin/console app:create-test-routine
```
Crea una rutina "Full Body" con 5 ejercicios para el usuario admin.

### Crear Entrenamientos de Prueba
```bash
php bin/console app:create-test-trainings
```
Crea 10 entrenamientos con progresión de peso de 20kg a 42.5kg para demostrar seguimiento.

### Crear Usuario Admin
```bash
php bin/console app:create-admin-user
```
Email: `admin@metafit.com` | Contraseña: `admin123`

### Crear Logros
```bash
php bin/console app:create-achievements
```
Crea 12 logros del sistema.

---

## 📈 Integración con Sistema de Logros y XP

- **+10 XP** por cada ejercicio completado en una rutina
- **Desbloquea "Primer Entrenamiento"** al completar primer sesión
- Sistema de niveles automático: +100 XP por nivel
- Se puede ver en dashboard y perfil

---

## 🔒 Seguridad

- ✅ Solo usuarios autenticados pueden crear/acceder rutinas
- ✅ Validación de propiedad en todas las operaciones
- ✅ Control de acceso por ROLE_USER
- ✅ Datos filtrados por usuario en consultas

---

## 📦 Dependencias

- **Symfony 8.0**
- **PHP 8.4**
- **PostgreSQL 16**
- **Doctrine ORM**
- **Chart.js** (para gráficos)
- **Tailwind CSS**
- **Material Symbols Icons**

---

## 🚀 Próximas Mejoras Sugeridas

- [ ] Cronómetro con sonidos/notificaciones
- [ ] Descansos entre series automáticos
- [ ] Video tutorial de ejercicio durante entrenamiento
- [ ] Comparativa con sesiones anteriores
- [ ] Exportar rutina en PDF
- [ ] Sincronizar con wearables (reloj, banda fitness)
- [ ] Comparativa de progreso con otros usuarios (anónimo)
- [ ] Plan de entrenamientos automático basado en IA
- [ ] Notificaciones de nuevos logros
- [ ] Integración con Apple Health / Google Fit

---

## 📝 Notas Técnicas

### Base de Datos
- Tabla `routine`: Almacena rutinas del usuario
- Tabla `training`: Almacena historiales de entrenamientos
- Tabla `routine_exercise`: Relación many-to-many entre rutinas y ejercicios
- Campos calculados: `one_rm_estimated` en Training

### Fórmulas Validadas
- Brzycki formula válida para reps 1-36
- Para reps ≥ 37: retorna peso directo sin estimar
- Volumen siempre = Series × Reps × Peso

### Performance
- Queries optimizadas con límites de 30 días
- Índices en user + exercise + date
- Cachés de Symfony implementadas
- Cálculos en backend, no en frontend

---

## 📞 Soporte

Para reportar bugs o sugerir mejoras, contacta al equipo de desarrollo.

**Versión:** 1.0.0  
**Última Actualización:** Abril 2026  
**Estado:** ✅ Producción Ready
