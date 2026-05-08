<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ApiController extends AbstractController
{
    #[Route('/api/food-macros', name: 'api_food_macros', methods: ['POST'])]
    public function calculateFoodMacros(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            
            if (!is_array($data)) {
                return $this->json([
                    'success' => false,
                    'error' => 'Formato JSON inválido o cuerpo de petición vacío.'
                ], 400);
            }

            $foodName = $data['food_name'] ?? '';
            $weight = (float)($data['weight_g'] ?? 0);

            if (!$foodName || $weight <= 0) {
                return $this->json([
                    'success' => false,
                    'error' => 'Datos inválidos'
                ], 400);
            }

            // Cargar base de datos local de alimentos
            $foodDatabase = $this->getFoodDatabase();

            // Buscar el alimento (búsqueda fuzzy simple)
            $foodLower = strtolower(trim($foodName));
            $foundFood = null;
            $foundKey = null;

            // Búsqueda exacta
            if (isset($foodDatabase[$foodLower])) {
                $foundFood = $foodDatabase[$foodLower];
                $foundKey = $foodLower;
            } else {
                // Búsqueda por palabra contenida
                foreach ($foodDatabase as $key => $macros) {
                    if (strpos($foodLower, $key) !== false || strpos($key, $foodLower) !== false) {
                        $foundFood = $macros;
                        $foundKey = $key;
                        break;
                    }
                }
            }

            if (!$foundFood) {
                return $this->json([
                    'success' => false,
                    'error' => "No se encontró información para '$foodName'. Intenta con otros alimentos."
                ], 400);
            }

            // Calcular macros para el peso indicado
            $factor = $weight / 100;
            $macros = [
                'calories' => round($foundFood['calories'] * $factor, 1),
                'proteins' => round($foundFood['proteins'] * $factor, 1),
                'carbs' => round($foundFood['carbs'] * $factor, 1),
                'fats' => round($foundFood['fats'] * $factor, 1),
            ];

            return $this->json([
                'success' => true,
                'macros' => $macros,
                'food_found' => ucfirst($foundKey)
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => 'Error del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Retorna la base de datos de macros por cada 100g.
     */
    private function getFoodDatabase(): array
    {
        return [
            'pollo' => ['calories' => 165, 'proteins' => 31, 'carbs' => 0, 'fats' => 3.6],
            'arroz' => ['calories' => 130, 'proteins' => 2.7, 'carbs' => 28, 'fats' => 0.3],
            'huevo' => ['calories' => 155, 'proteins' => 13, 'carbs' => 1.1, 'fats' => 11],
            'pan' => ['calories' => 265, 'proteins' => 9, 'carbs' => 49, 'fats' => 3.3],
            'leche' => ['calories' => 42, 'proteins' => 3.4, 'carbs' => 5, 'fats' => 0.1],
            'yogur' => ['calories' => 59, 'proteins' => 10, 'carbs' => 3.6, 'fats' => 0.4],
            'queso' => ['calories' => 402, 'proteins' => 25, 'carbs' => 1.3, 'fats' => 33],
            'pescado' => ['calories' => 82, 'proteins' => 17.4, 'carbs' => 0, 'fats' => 0.7],
            'salmón' => ['calories' => 208, 'proteins' => 20, 'carbs' => 0, 'fats' => 13],
            'ternera' => ['calories' => 250, 'proteins' => 26, 'carbs' => 0, 'fats' => 15],
            'pavo' => ['calories' => 135, 'proteins' => 29, 'carbs' => 0, 'fats' => 1],
            'cerdo' => ['calories' => 242, 'proteins' => 27, 'carbs' => 0, 'fats' => 14],
            'atún' => ['calories' => 144, 'proteins' => 29.9, 'carbs' => 0, 'fats' => 1.3],
            'bacalao' => ['calories' => 82, 'proteins' => 17.8, 'carbs' => 0, 'fats' => 0.7],
            'camarones' => ['calories' => 99, 'proteins' => 24, 'carbs' => 0, 'fats' => 0.3],
            'carne molida' => ['calories' => 217, 'proteins' => 23, 'carbs' => 0, 'fats' => 13],
            'jamón' => ['calories' => 145, 'proteins' => 21, 'carbs' => 0, 'fats' => 6],
            'pechuga' => ['calories' => 165, 'proteins' => 31, 'carbs' => 0, 'fats' => 3.6],
            'manzana' => ['calories' => 52, 'proteins' => 0.3, 'carbs' => 14, 'fats' => 0.2],
            'plátano' => ['calories' => 89, 'proteins' => 1.1, 'carbs' => 23, 'fats' => 0.3],
            'naranja' => ['calories' => 47, 'proteins' => 0.9, 'carbs' => 12, 'fats' => 0.1],
            'aguacate' => ['calories' => 160, 'proteins' => 2, 'carbs' => 9, 'fats' => 15],
            'fresa' => ['calories' => 32, 'proteins' => 0.8, 'carbs' => 8, 'fats' => 0.3],
            'sandía' => ['calories' => 30, 'proteins' => 0.6, 'carbs' => 8, 'fats' => 0.2],
            'melón' => ['calories' => 34, 'proteins' => 0.8, 'carbs' => 8, 'fats' => 0.2],
            'piña' => ['calories' => 50, 'proteins' => 0.5, 'carbs' => 13, 'fats' => 0.1],
            'kiwi' => ['calories' => 61, 'proteins' => 1.1, 'carbs' => 15, 'fats' => 0.5],
            'limón' => ['calories' => 29, 'proteins' => 1.1, 'carbs' => 9, 'fats' => 0.3],
            'tomate' => ['calories' => 18, 'proteins' => 0.9, 'carbs' => 3.9, 'fats' => 0.2],
            'lechuga' => ['calories' => 15, 'proteins' => 1.2, 'carbs' => 2.9, 'fats' => 0.2],
            'brócoli' => ['calories' => 34, 'proteins' => 2.8, 'carbs' => 7, 'fats' => 0.4],
            'zanahoria' => ['calories' => 41, 'proteins' => 0.9, 'carbs' => 10, 'fats' => 0.2],
            'cebolla' => ['calories' => 40, 'proteins' => 1.1, 'carbs' => 9, 'fats' => 0.1],
            'ajo' => ['calories' => 149, 'proteins' => 6.4, 'carbs' => 33, 'fats' => 0.5],
            'pepino' => ['calories' => 16, 'proteins' => 0.7, 'carbs' => 3.6, 'fats' => 0.1],
            'espinaca' => ['calories' => 23, 'proteins' => 2.7, 'carbs' => 3.6, 'fats' => 0.4],
            'calabaza' => ['calories' => 26, 'proteins' => 1, 'carbs' => 5, 'fats' => 0.1],
            'pimiento' => ['calories' => 31, 'proteins' => 1, 'carbs' => 6, 'fats' => 0.3],
            'berenjena' => ['calories' => 25, 'proteins' => 0.98, 'carbs' => 6, 'fats' => 0.2],
            'patata' => ['calories' => 77, 'proteins' => 2, 'carbs' => 17, 'fats' => 0.1],
            'aceite' => ['calories' => 884, 'proteins' => 0, 'carbs' => 0, 'fats' => 100],
            'mantequilla' => ['calories' => 717, 'proteins' => 0.9, 'carbs' => 0, 'fats' => 81],
            'frutos secos' => ['calories' => 607, 'proteins' => 21, 'carbs' => 27, 'fats' => 50],
            'almendras' => ['calories' => 579, 'proteins' => 21, 'carbs' => 22, 'fats' => 50],
            'cacahuete' => ['calories' => 567, 'proteins' => 26, 'carbs' => 16, 'fats' => 49],
            'avena' => ['calories' => 389, 'proteins' => 17, 'carbs' => 66, 'fats' => 7],
            'lentejas' => ['calories' => 116, 'proteins' => 9, 'carbs' => 20, 'fats' => 0.4],
            'garbanzos' => ['calories' => 164, 'proteins' => 9, 'carbs' => 27, 'fats' => 3],
            'frijoles' => ['calories' => 127, 'proteins' => 8.4, 'carbs' => 23, 'fats' => 0.5],
            'pasta' => ['calories' => 131, 'proteins' => 5, 'carbs' => 25, 'fats' => 1.1],
            'carne' => ['calories' => 250, 'proteins' => 26, 'carbs' => 0, 'fats' => 15],
            'verdura' => ['calories' => 30, 'proteins' => 2, 'carbs' => 6, 'fats' => 0.3],
            'fruta' => ['calories' => 60, 'proteins' => 0.8, 'carbs' => 15, 'fats' => 0.2],
        ];
    }
}
