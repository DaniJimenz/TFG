# Configuración de Google Cloud Vision API

## ¿Por qué Google Vision?
- Reconocimiento preciso de objetos en imágenes
- API de etiquetado automático de alimentos
- Primer 1000 requests/mes son gratis
- Fácil integración en Symfony

## Pasos de Configuración

### 1. Crear un proyecto en Google Cloud

1. Ir a [Google Cloud Console](https://console.cloud.google.com)
2. Crear un nuevo proyecto (ej: "MetaFit AI")
3. Copiar el **Project ID** (lo necesitarás)

### 2. Habilitar Vision API

1. En la consola, buscar "Vision API"
2. Click en "Vision API"
3. Click en "Habilitar"

### 3. Crear credenciales de servicio

1. Ir a **APIs & Servicios** > **Credenciales**
2. Click en **Crear Credenciales** > **Cuenta de Servicio**
3. Rellenar:
   - Nombre de cuenta: `metafit-ai-service`
   - ID: Se genera automático
   - Descripción: "Servicio para análisis de comida"
4. Click en **Crear y continuar**
5. Asignar rol: **Basic** > **Editor** (o más específico: **AI Platform** > **AI Platform User**)
6. Continuar sin agregar usuarios
7. Click en **Crear Clave**
   - Formato: **JSON**
   - Se descargará un archivo `metafit-ai-service-xxxxxx.json`

### 4. Guardar las credenciales

1. Renombrar el archivo descargado a: `google-credentials.json`
2. Copiar a: `{raíz-proyecto}/config/google-credentials.json`

```bash
cp ~/Downloads/google-credentials-xxxxxx.json /path/to/MetaFit/config/google-credentials.json
```

### 5. Configurar variables de entorno

En `.env.local` (o `.env`):

```env
GOOGLE_CLOUD_PROJECT_ID=your-project-id
GOOGLE_APPLICATION_CREDENTIALS=%kernel.project_dir%/config/google-credentials.json
```

### 6. Agregar al .gitignore

Nunca subir credenciales a Git:

```bash
echo "config/google-credentials.json" >> .gitignore
```

## Uso

### Vía Web Form

```bash
curl -X POST http://localhost:8000/meals/photo \
  -F "photo=@image.jpg" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Vía API REST

```bash
POST /api/meals/photo
Content-Type: multipart/form-data

photo: <binary-image-data>
```

**Respuesta exitosa:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "food_type": "comida",
    "calories_total": 650,
    "proteines_g": 35,
    "carbohidrats_g": 60,
    "fats_g": 18,
    "register_method": "photo_ai",
    "register_date": "2026-04-27 14:30:00",
    "url_image": "/uploads/meals/meal_xxx.jpg",
    "notes": null
  },
  "analysis": {
    "detected_items": ["chicken", "rice", "broccoli"],
    "confidence": 85.5
  }
}
```

## Fallback a Mock Data

Si no está configurado Google Vision (credenciales no encontradas), el sistema automáticamente usa **datos mock** para desarrollo y testing. Esto permite trabajar sin necesidad de claves activas.

## Límites de uso

**Plan Gratuito:**
- 1,000 solicitudes/mes = **33 comidas diarias**
- Suficiente para desarrollo y MVP

**Plan Pago:**
- $1.50 por cada 1,000 solicitudes
- Recomendado en producción

## Troubleshooting

### Error: "Credentials file not found"
- Verificar que `config/google-credentials.json` existe
- Verificar ruta en `.env`

### Error: "Project ID not configured"
- Asegurarse que `GOOGLE_CLOUD_PROJECT_ID` está en `.env`
- Reiniciar el servidor

### Error: "Permission denied"
- Verificar que la cuenta de servicio tiene rol "Editor"
- Verificar que Vision API está habilitada en el proyecto

### Vision API devuelve resultados incorrectos
- Probar con imágenes de mejor calidad (>100x100px)
- El sistema tiene fallback a valores por defecto
