<?php

namespace App\Service;

use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Image;
use Google\Cloud\Vision\V1\Feature;
use Google\Cloud\Vision\V1\Feature\Type;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class FoodAnalysisService
{
    private ?ImageAnnotatorClient $client = null;
    private string $projectId;
    private bool $enabled = false;

    public function __construct(
        private ParameterBagInterface $params,
        private LoggerInterface $logger
    ) {
        $this->projectId = $this->params->get('kernel.environment') === 'test' 
            ? 'test-project' 
            : $_ENV['GOOGLE_CLOUD_PROJECT_ID'] ?? 'your-project-id';
        
        $this->enabled = file_exists($this->getCredentialsPath());
    }

    /**
     * Analiza una imagen de comida y extrae información nutricional
     * 
     * @param string $imagePath Ruta local de la imagen o URL
     * @return array Información de la comida detectada
     * @throws \Exception Si hay error en la API
     */
    public function analyzeFoodImage(string $imagePath): array
    {
        if (!$this->enabled) {
            $this->logger->warning('Google Cloud Vision API not configured, using mock data');
            return $this->getMockAnalysisData();
        }

        try {
            $client = $this->getClient();
            $image = new Image();

            // Determinar si es URL o archivo local
            if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
                $image->setSource(['image_uri' => $imagePath]);
            } else {
                $imageContent = file_get_contents($imagePath);
                if (!$imageContent) {
                    throw new \Exception("Cannot read image file: {$imagePath}");
                }
                $image->setContent($imageContent);
            }

            // Configurar características a detectar
            $features = [
                new Feature(['type' => Type::LABEL_DETECTION]),
                new Feature(['type' => Type::OBJECT_LOCALIZATION]),
                new Feature(['type' => Type::TEXT_DETECTION]),
            ];

            // Realizar anotación
            $response = $client->annotateImage($image, $features);

            return $this->parseAnalysisResponse($response);

        } catch (\Exception $e) {
            $this->logger->error('Google Vision API error: ' . $e->getMessage());
            throw new \Exception('Failed to analyze food image: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene o inicializa el cliente de Google Vision
     */
    private function getClient(): ImageAnnotatorClient
    {
        if ($this->client === null) {
            $credentialsPath = $this->getCredentialsPath();
            
            if (!file_exists($credentialsPath)) {
                throw new \Exception("Google credentials file not found at: {$credentialsPath}");
            }

            $this->client = new ImageAnnotatorClient([
                'credentials' => $credentialsPath
            ]);
        }

        return $this->client;
    }

    /**
     * Ruta al archivo de credenciales de Google
     */
    private function getCredentialsPath(): string
    {
        return $this->params->get('kernel.project_dir') . '/config/google-credentials.json';
    }

    /**
     * Parsea la respuesta de Google Vision y extrae información nutricional
     * 
     * Utiliza una base de datos simple de alimentos conocidos para calcular macros
     */
    private function parseAnalysisResponse($response): array
    {
        $labels = [];
        $confidence = 0;

        // Obtener etiquetas detectadas
        if ($response->hasLabelAnnotations()) {
            foreach ($response->getLabelAnnotations() as $label) {
                $labels[] = strtolower($label->getDescription());
                $confidence = max($confidence, $label->getScore());
            }
        }

        // Detectar objetos de comida
        $detectedFoods = [];
        if ($response->hasLocalizedObjectAnnotations()) {
            foreach ($response->getLocalizedObjectAnnotations() as $object) {
                $detectedFoods[] = strtolower($object->getName());
            }
        }

        // Combinar etiquetas y objetos
        $allItems = array_unique(array_merge($labels, $detectedFoods));
        $foodItems = $this->filterFoodItems($allItems);

        // Calcular macros basado en alimentos detectados
        $nutrition = $this->calculateNutritionFromItems($foodItems);

        // Detectar tipo de comida por contexto
        $foodType = $this->detectMealType($foodItems);

        return [
            'food_type' => $foodType,
            'detected_items' => $foodItems,
            'confidence' => round($confidence * 100, 2),
            'calories_total' => $nutrition['calories'],
            'proteines_g' => $nutrition['proteines'],
            'carbohidrats_g' => $nutrition['carbohidrats'],
            'fats_g' => $nutrition['fats'],
        ];
    }

    /**
     * Filtra solo los elementos que son alimentos
     */
    private function filterFoodItems(array $items): array
    {
        $foodKeywords = [
            'food', 'dish', 'meal', 'fruit', 'vegetable', 'protein', 'meat', 'chicken',
            'beef', 'fish', 'rice', 'pasta', 'bread', 'salad', 'soup', 'sauce', 'cheese',
            'egg', 'dairy', 'grain', 'cereal', 'nuts', 'bean', 'legume', 'vegetable',
            'broccoli', 'carrot', 'potato', 'tomato', 'lettuce', 'spinach', 'pepper',
            'avocado', 'banana', 'apple', 'orange', 'berry', 'grape', 'melon', 'milk',
            'yogurt', 'butter', 'oil', 'honey', 'sugar', 'salt', 'spice', 'herb',
        ];

        return array_filter($items, function ($item) use ($foodKeywords) {
            foreach ($foodKeywords as $keyword) {
                if (stripos($item, $keyword) !== false) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * Detecta el tipo de comida basado en los alimentos encontrados
     */
    private function detectMealType(array $items): string
    {
        $itemsStr = implode(' ', $items);

        // Patrones para detectar tipo de comida
        $breakfastPatterns = ['cereal', 'eggs', 'toast', 'pancake', 'waffle', 'milk', 'coffee', 'breakfast'];
        $lunchPatterns = ['sandwich', 'burger', 'salad', 'pasta', 'rice bowl', 'lunch'];
        $dinnerPatterns = ['steak', 'salmon', 'chicken', 'beef', 'dinner', 'roasted'];
        $snackPatterns = ['nuts', 'chips', 'candy', 'bar', 'popcorn', 'snack', 'cookie', 'yogurt'];

        foreach ($breakfastPatterns as $pattern) {
            if (stripos($itemsStr, $pattern) !== false) {
                return 'desayuno';
            }
        }

        foreach ($lunchPatterns as $pattern) {
            if (stripos($itemsStr, $pattern) !== false) {
                return 'comida';
            }
        }

        foreach ($dinnerPatterns as $pattern) {
            if (stripos($itemsStr, $pattern) !== false) {
                return 'cena';
            }
        }

        foreach ($snackPatterns as $pattern) {
            if (stripos($itemsStr, $pattern) !== false) {
                return 'merienda';
            }
        }

        return 'comida'; // Por defecto
    }

    /**
     * Calcula macronutrientes basado en alimentos detectados
     * 
     * Utiliza una tabla de composición de alimentos
     */
    private function calculateNutritionFromItems(array $items): array
    {
        // Base de datos simple de alimentos (por 100g)
        $foodDatabase = [
            'chicken' => ['calories' => 165, 'proteines' => 31, 'carbohidrats' => 0, 'fats' => 3.6],
            'beef' => ['calories' => 250, 'proteines' => 26, 'carbohidrats' => 0, 'fats' => 15],
            'fish' => ['calories' => 100, 'proteines' => 20, 'carbohidrats' => 0, 'fats' => 1],
            'salmon' => ['calories' => 208, 'proteines' => 20, 'carbohidrats' => 0, 'fats' => 13],
            'rice' => ['calories' => 206, 'proteines' => 2.7, 'carbohidrats' => 45, 'fats' => 0.3],
            'pasta' => ['calories' => 371, 'proteines' => 13, 'carbohidrats' => 75, 'fats' => 1.1],
            'bread' => ['calories' => 265, 'proteines' => 9, 'carbohidrats' => 49, 'fats' => 3.3],
            'egg' => ['calories' => 155, 'proteines' => 13, 'carbohidrats' => 1.1, 'fats' => 11],
            'broccoli' => ['calories' => 34, 'proteines' => 2.8, 'carbohidrats' => 7, 'fats' => 0.4],
            'spinach' => ['calories' => 23, 'proteines' => 2.9, 'carbohidrats' => 3.6, 'fats' => 0.4],
            'potato' => ['calories' => 77, 'proteines' => 2, 'carbohidrats' => 17, 'fats' => 0.1],
            'banana' => ['calories' => 89, 'proteines' => 1.1, 'carbohidrats' => 23, 'fats' => 0.3],
            'apple' => ['calories' => 52, 'proteines' => 0.3, 'carbohidrats' => 14, 'fats' => 0.2],
            'milk' => ['calories' => 61, 'proteines' => 3.2, 'carbohidrats' => 4.8, 'fats' => 3.3],
            'cheese' => ['calories' => 402, 'proteines' => 25, 'carbohidrats' => 1.3, 'fats' => 33],
            'yogurt' => ['calories' => 59, 'proteines' => 10, 'carbohidrats' => 3.6, 'fats' => 0.4],
            'nuts' => ['calories' => 607, 'proteines' => 21, 'carbohidrats' => 22, 'fats' => 54],
            'olive oil' => ['calories' => 884, 'proteines' => 0, 'carbohidrats' => 0, 'fats' => 100],
            'salad' => ['calories' => 15, 'proteines' => 1.2, 'carbohidrats' => 3, 'fats' => 0.2],
            'soup' => ['calories' => 50, 'proteines' => 3, 'carbohidrats' => 8, 'fats' => 1],
        ];

        $totalCalories = 0;
        $totalProteines = 0;
        $totalCarbohidrats = 0;
        $totalFats = 0;
        $itemCount = 0;

        // Calcular basado en items detectados
        foreach ($items as $item) {
            foreach ($foodDatabase as $foodName => $nutrients) {
                if (stripos($item, $foodName) !== false) {
                    // Asumir ~150g por item detectado
                    $portion = 1.5;
                    $totalCalories += $nutrients['calories'] * $portion;
                    $totalProteines += $nutrients['proteines'] * $portion;
                    $totalCarbohidrats += $nutrients['carbohidrats'] * $portion;
                    $totalFats += $nutrients['fats'] * $portion;
                    $itemCount++;
                    break;
                }
            }
        }

        // Si no encontró items específicos, usar valores por defecto
        if ($itemCount === 0) {
            $totalCalories = 600;
            $totalProteines = 30;
            $totalCarbohidrats = 50;
            $totalFats = 20;
        }

        return [
            'calories' => round($totalCalories),
            'proteines' => round($totalProteines, 1),
            'carbohidrats' => round($totalCarbohidrats, 1),
            'fats' => round($totalFats, 1),
        ];
    }

    /**
     * Devuelve datos mock cuando Google Vision no está disponible
     */
    private function getMockAnalysisData(): array
    {
        return [
            'food_type' => 'comida',
            'detected_items' => ['chicken', 'rice', 'broccoli'],
            'confidence' => 82.5,
            'calories_total' => 650,
            'proteines_g' => 35,
            'carbohidrats_g' => 60,
            'fats_g' => 18,
        ];
    }

    /**
     * Verifica si Google Vision está configurado
     */
    public function isConfigured(): bool
    {
        return $this->enabled;
    }
}
