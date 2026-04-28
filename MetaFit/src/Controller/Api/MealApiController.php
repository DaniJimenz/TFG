<?php

namespace App\Controller\Api;

use App\Entity\Meal;
use App\Repository\MealRepository;
use App\Service\FoodAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/meals', name: 'api_meal_')]
#[IsGranted('ROLE_USER')]
class MealApiController extends AbstractController
{
    /**
     * Listar comidas del usuario autenticado
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(MealRepository $mealRepository): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $meals = $mealRepository->findBy(
            ['appUser' => $user],
            ['register_date' => 'DESC']
        );

        return new JsonResponse([
            'success' => true,
            'data' => array_map(function (Meal $meal) {
                return $this->serializeMeal($meal);
            }, $meals),
        ]);
    }

    /**
     * Obtener resumen de macros del día
     */
    #[Route('/today/summary', name: 'today_summary', methods: ['GET'])]
    public function todaySummary(MealRepository $mealRepository): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $today = new \DateTimeImmutable();
        $startOfDay = $today->setTime(0, 0, 0);
        $endOfDay = $today->setTime(23, 59, 59);

        $meals = $mealRepository->findMealsByDateRange($user, $startOfDay, $endOfDay);

        $totals = [
            'calories' => 0,
            'proteines' => 0,
            'carbohidrats' => 0,
            'fats' => 0,
        ];

        foreach ($meals as $meal) {
            $totals['calories'] += $meal->getCaloriesTotal() ?? 0;
            $totals['proteines'] += $meal->getProteinesG() ?? 0;
            $totals['carbohidrats'] += $meal->getCarbohidratesG() ?? 0;
            $totals['fats'] += $meal->getFatsG() ?? 0;
        }

        return new JsonResponse([
            'success' => true,
            'date' => $today->format('Y-m-d'),
            'meals' => array_map(function (Meal $meal) {
                return $this->serializeMeal($meal);
            }, $meals),
            'totals' => $totals,
        ]);
    }

    /**
     * Crear comida manualmente (API)
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);

        // Validar campos requeridos
        $required = ['food_type', 'calories_total', 'proteines_g', 'carbohidrats_g', 'fats_g'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return new JsonResponse(
                    ['error' => "Field '{$field}' is required"],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        $meal = new Meal();
        $meal->setAppUser($user);
        $meal->setFoodType($data['food_type']);
        $meal->setCaloriesTotal((float)$data['calories_total']);
        $meal->setProteinesG((float)$data['proteines_g']);
        $meal->setCarbohidratesG((float)$data['carbohidrats_g']);
        $meal->setFatsG((float)$data['fats_g']);
        $meal->setBarScanner(false);
        $meal->setRegisterMethod('api_manual');
        $meal->setRegisterDate(new \DateTimeImmutable());
        $meal->setNotes($data['notes'] ?? null);

        $entityManager->persist($meal);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'data' => $this->serializeMeal($meal),
        ], Response::HTTP_CREATED);
    }

    /**
     * Registrar comida desde foto (IA)
     */
    #[Route('/photo', name: 'photo', methods: ['POST'])]
    public function photoAnalysis(Request $request, EntityManagerInterface $entityManager, FoodAnalysisService $analysisService): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$request->files->has('photo')) {
            return new JsonResponse(
                ['error' => 'Photo file is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $uploadedFile = $request->files->get('photo');

        if (!in_array($uploadedFile->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'])) {
            return new JsonResponse(
                ['error' => 'File must be an image (JPEG, PNG, WebP)'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            // Guardar imagen
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/meals';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = uniqid('meal_') . '.' . $uploadedFile->guessExtension();
            $tempPath = $uploadedFile->move($uploadDir, $fileName);

            // Analizar imagen con Google Vision
            $analysisData = $analysisService->analyzeFoodImage($tempPath->getRealPath());

            // Guardar comida
            $meal = new Meal();
            $meal->setAppUser($user);
            $meal->setFoodType($analysisData['food_type']);
            $meal->setCaloriesTotal((float)$analysisData['calories_total']);
            $meal->setProteinesG((float)$analysisData['proteines_g']);
            $meal->setCarbohidratesG((float)$analysisData['carbohidrats_g']);
            $meal->setFatsG((float)$analysisData['fats_g']);
            $meal->setBarScanner(false);
            $meal->setRegisterMethod('photo_ai');
            $meal->setRegisterDate(new \DateTimeImmutable());
            $meal->setUrlImage('/uploads/meals/' . $fileName);

            $entityManager->persist($meal);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'data' => $this->serializeMeal($meal),
                'analysis' => [
                    'detected_items' => $analysisData['detected_items'],
                    'confidence' => $analysisData['confidence'],
                ],
            ], Response::HTTP_CREATED);

        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Error analyzing image: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Actualizar comida
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Meal $meal, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($meal->getAppUser() !== $this->getUser()) {
            return new JsonResponse(
                ['error' => 'You cannot access this meal'],
                Response::HTTP_FORBIDDEN
            );
        }

        $data = json_decode($request->getContent(), true);

        $meal->setFoodType($data['food_type'] ?? $meal->getFoodType());
        $meal->setCaloriesTotal((float)($data['calories_total'] ?? $meal->getCaloriesTotal()));
        $meal->setProteinesG((float)($data['proteines_g'] ?? $meal->getProteinesG()));
        $meal->setCarbohidratesG((float)($data['carbohidrats_g'] ?? $meal->getCarbohidratesG()));
        $meal->setFatsG((float)($data['fats_g'] ?? $meal->getFatsG()));
        $meal->setNotes($data['notes'] ?? $meal->getNotes());

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'data' => $this->serializeMeal($meal),
        ]);
    }

    /**
     * Eliminar comida
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Meal $meal, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($meal->getAppUser() !== $this->getUser()) {
            return new JsonResponse(
                ['error' => 'You cannot access this meal'],
                Response::HTTP_FORBIDDEN
            );
        }

        $entityManager->remove($meal);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Helper para serializar una comida
     */
    private function serializeMeal(Meal $meal): array
    {
        return [
            'id' => $meal->getId(),
            'food_type' => $meal->getFoodType(),
            'calories_total' => $meal->getCaloriesTotal(),
            'proteines_g' => $meal->getProteinesG(),
            'carbohidrats_g' => $meal->getCarbohidratesG(),
            'fats_g' => $meal->getFatsG(),
            'register_method' => $meal->getRegisterMethod(),
            'register_date' => $meal->getRegisterDate()->format('Y-m-d H:i:s'),
            'url_image' => $meal->getUrlImage(),
            'notes' => $meal->getNotes(),
        ];
    }
}
