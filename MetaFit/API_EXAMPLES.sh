#!/bin/bash

# Ejemplos de uso de la API de Comidas de MetaFit AI

BASE_URL="http://localhost:8000"
JWT_TOKEN="your-jwt-token-here"

# ============================================
# 1. OBTENER JWT TOKEN
# ============================================

echo "=== 1. LOGIN Y OBTENER JWT TOKEN ==="
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/api/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "password123"
  }')

JWT_TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.token')
echo "JWT Token obtenido: $JWT_TOKEN"
echo ""

# ============================================
# 2. LISTAR COMIDAS DEL USUARIO
# ============================================

echo "=== 2. LISTAR COMIDAS ==="
curl -s -X GET "$BASE_URL/api/meals" \
  -H "Authorization: Bearer $JWT_TOKEN" | jq .
echo ""

# ============================================
# 3. CREAR COMIDA MANUALMENTE (JSON)
# ============================================

echo "=== 3. CREAR COMIDA MANUAL ==="
MEAL_RESPONSE=$(curl -s -X POST "$BASE_URL/api/meals" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "food_type": "comida",
    "calories_total": 750,
    "proteines_g": 40,
    "carbohidrats_g": 70,
    "fats_g": 20,
    "notes": "Pollo al horno con arroz integral"
  }')

MEAL_ID=$(echo $MEAL_RESPONSE | jq -r '.data.id')
echo "Comida creada con ID: $MEAL_ID"
echo $MEAL_RESPONSE | jq .
echo ""

# ============================================
# 4. REGISTRAR COMIDA DESDE FOTO (IA)
# ============================================

echo "=== 4. REGISTRAR COMIDA DESDE FOTO ==="
# Suponiendo que tienes una imagen llamada "comida.jpg"
if [ -f "comida.jpg" ]; then
  curl -s -X POST "$BASE_URL/api/meals/photo" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -F "photo=@comida.jpg" | jq .
else
  echo "⚠️  No se encontró 'comida.jpg' para el ejemplo"
fi
echo ""

# ============================================
# 5. OBTENER RESUMEN DEL DÍA
# ============================================

echo "=== 5. RESUMEN DEL DÍA ==="
curl -s -X GET "$BASE_URL/api/meals/today/summary" \
  -H "Authorization: Bearer $JWT_TOKEN" | jq .
echo ""

# ============================================
# 6. ACTUALIZAR COMIDA
# ============================================

if [ ! -z "$MEAL_ID" ] && [ "$MEAL_ID" != "null" ]; then
  echo "=== 6. ACTUALIZAR COMIDA (ID: $MEAL_ID) ==="
  curl -s -X PUT "$BASE_URL/api/meals/$MEAL_ID" \
    -H "Authorization: Bearer $JWT_TOKEN" \
    -H "Content-Type: application/json" \
    -d '{
      "calories_total": 800,
      "proteines_g": 42,
      "carbohidrats_g": 75,
      "fats_g": 22
    }' | jq .
  echo ""

  # ============================================
  # 7. ELIMINAR COMIDA
  # ============================================

  echo "=== 7. ELIMINAR COMIDA (ID: $MEAL_ID) ==="
  curl -s -X DELETE "$BASE_URL/api/meals/$MEAL_ID" \
    -H "Authorization: Bearer $JWT_TOKEN" | jq .
fi

# ============================================
# ENDPOINTS DISPONIBLES
# ============================================

echo ""
echo "========== ENDPOINTS DISPONIBLES =========="
echo ""
echo "AUTENTICACIÓN:"
echo "  POST   /api/login                    - Login y obtener JWT"
echo "  GET    /api/profile                  - Obtener perfil del usuario"
echo ""
echo "COMIDAS (WEB):"
echo "  GET    /meals                        - Listar comidas"
echo "  GET    /meals/{id}                   - Ver detalle de comida"
echo "  GET    /meals/new                    - Formulario para nueva comida"
echo "  POST   /meals/new                    - Crear comida manual"
echo "  POST   /meals/photo                  - Registrar desde foto"
echo "  GET    /meals/{id}/edit              - Formulario editar comida"
echo "  POST   /meals/{id}/edit              - Actualizar comida"
echo "  POST   /meals/{id}/delete            - Eliminar comida"
echo "  GET    /meals/today/summary          - Resumen del día (JSON)"
echo ""
echo "COMIDAS (API):"
echo "  GET    /api/meals                    - Listar comidas (JSON)"
echo "  POST   /api/meals                    - Crear comida manual (JSON)"
echo "  POST   /api/meals/photo              - Registrar desde foto (JSON)"
echo "  GET    /api/meals/today/summary      - Resumen del día (JSON)"
echo "  PUT    /api/meals/{id}               - Actualizar comida (JSON)"
echo "  DELETE /api/meals/{id}               - Eliminar comida (JSON)"
echo ""
