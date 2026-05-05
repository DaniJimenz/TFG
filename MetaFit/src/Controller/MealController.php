<?php

namespace App\Controller;

use App\Entity\Meal;
use App\Entity\MealFood;
use App\Entity\Food;
use App\Repository\MealRepository;
use App\Repository\FoodRepository;
use App\Service\FoodAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/meals', name: 'meal_')]
#[IsGranted('ROLE_USER')]
class MealController extends AbstractController
{
    /**
     * Listar todas las comidas del usuario autenticado
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(MealRepository $mealRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Traemos todas las comidas del usuario
        $meals = $mealRepository->findBy(
            ['appUser' => $user],
            ['register_date' => 'DESC']
        );

        // Calcular macros de hoy
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $tomorrow = (new \DateTime())->setTime(23, 59, 59);

        $todayMacros = [
            'calories' => 0,
            'proteins' => 0,
            'carbs' => 0,
            'fats' => 0
        ];

        // Agrupar comidas por día
        $mealsByDay = [];
        foreach ($meals as $meal) {
            $mealDate = $meal->getRegisterDate();
            $dateKey = $mealDate->format('Y-m-d');
            
            if (!isset($mealsByDay[$dateKey])) {
                $mealsByDay[$dateKey] = [];
            }
            $mealsByDay[$dateKey][] = $meal;

            // Sumar macros de hoy
            if ($mealDate >= $today && $mealDate <= $tomorrow) {
                $todayMacros['calories'] += $meal->getCaloriesTotal() ?? 0;
                $todayMacros['proteins'] += $meal->getProteinesG() ?? 0;
                $todayMacros['carbs'] += $meal->getCarbohidratesG() ?? 0;
                $todayMacros['fats'] += $meal->getFatsG() ?? 0;
            }
        }

        return $this->render('meal/index.html.twig', [
            'meals' => $meals,
            'mealsByDay' => $mealsByDay,
            'todayMacros' => $todayMacros,
        ]);
    }

    /**
     * Crear una nueva comida (manual)
     */
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $meal = new Meal();
            $meal->setAppUser($user);
            $meal->setFoodType($request->request->get('food_type')); // desayuno, comida, merienda, cena
            $meal->setCaloriesTotal((float)$request->request->get('calories_total'));
            $meal->setProteinesG((float)$request->request->get('proteines_g'));
            $meal->setCarbohidratesG((float)$request->request->get('carbohidrats_g'));
            $meal->setFatsG((float)$request->request->get('fats_g'));
            $meal->setBarScanner(false);
            $meal->setRegisterMethod('manual');
            $meal->setRegisterDate(new \DateTimeImmutable());
            $meal->setNotes($request->request->get('notes', null));

            $entityManager->persist($meal);
            $entityManager->flush();

            $this->addFlash('success', 'Meal registered successfully!');

            return $this->redirectToRoute('meal_index');
        }

        return $this->render('meal/new.html.twig');
    }

    /**
     * Ver detalle de una comida específica
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Meal $meal): Response
    {
        // Verificar que la comida pertenece al usuario autenticado
        if ($meal->getAppUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot access this meal.');
        }

        return $this->render('meal/show.html.twig', [
            'meal' => $meal,
        ]);
    }

    /**
     * Crear comida desde foto (IA)
     * Endpoint para procesar imagen y extraer macros
     */
    #[Route('/photo', name: 'photo', methods: ['POST'])]
    public function photoAnalysis(Request $request, EntityManagerInterface $entityManager, FoodAnalysisService $analysisService): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Verificar que hay una imagen
        if (!$request->files->has('photo')) {
            return new JsonResponse(
                ['error' => 'Photo is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $uploadedFile = $request->files->get('photo');

        // Validar que es una imagen
        if (!in_array($uploadedFile->getMimeType(), ['image/jpeg', 'image/png', 'image/webp'])) {
            return new JsonResponse(
                ['error' => 'File must be an image (JPEG, PNG, WebP)'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            // Guardar la imagen temporalmente
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/meals';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = uniqid('meal_') . '.' . $uploadedFile->guessExtension();
            $tempPath = $uploadedFile->move($uploadDir, $fileName);

            // Analizar imagen con Google Vision
            $analysisData = $analysisService->analyzeFoodImage($tempPath->getRealPath());

            // Guardar la comida
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
                'meal' => [
                    'id' => $meal->getId(),
                    'food_type' => $meal->getFoodType(),
                    'calories_total' => $meal->getCaloriesTotal(),
                    'proteines_g' => $meal->getProteinesG(),
                    'carbohidrats_g' => $meal->getCarbohidratesG(),
                    'fats_g' => $meal->getFatsG(),
                    'detected_items' => $analysisData['detected_items'],
                    'confidence' => $analysisData['confidence'],
                ],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Error analyzing image: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Editar comida existente
     */
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Meal $meal, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Verificar permisos
        if ($meal->getAppUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot edit this meal.');
        }

        if ($request->isMethod('POST')) {
            $meal->setFoodType($request->request->get('food_type'));
            $meal->setCaloriesTotal((float)$request->request->get('calories_total'));
            $meal->setProteinesG((float)$request->request->get('proteines_g'));
            $meal->setCarbohidratesG((float)$request->request->get('carbohidrats_g'));
            $meal->setFatsG((float)$request->request->get('fats_g'));
            $meal->setNotes($request->request->get('notes', null));

            $entityManager->flush();

            $this->addFlash('success', 'Meal updated successfully!');

            return $this->redirectToRoute('meal_index');
        }

        return $this->render('meal/edit.html.twig', [
            'meal' => $meal,
        ]);
    }

    /**
     * Eliminar comida
     */
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Meal $meal, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Verificar permisos y token CSRF
        if ($meal->getAppUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot delete this meal.');
        }

        if ($this->isCsrfTokenValid('delete_meal_' . $meal->getId(), $request->request->get('_token'))) {
            $entityManager->remove($meal);
            $entityManager->flush();

            $this->addFlash('success', 'Meal deleted successfully!');
        }

        return $this->redirectToRoute('meal_index');
    }

    /**
     * Resumen de macros del día
     */
    #[Route('/today/summary', name: 'today_summary', methods: ['GET'])]
    public function todaySummary(MealRepository $mealRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $today = new \DateTimeImmutable();
        $startOfDay = $today->setTime(0, 0, 0);
        $endOfDay = $today->setTime(23, 59, 59);

        // Traer comidas de hoy
        $meals = $mealRepository->findMealsByDateRange($user, $startOfDay, $endOfDay);

        // Calcular totales
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
            'date' => $today->format('Y-m-d'),
            'meals' => array_map(function (Meal $meal) {
                return [
                    'id' => $meal->getId(),
                    'food_type' => $meal->getFoodType(),
                    'calories' => $meal->getCaloriesTotal(),
                    'proteines' => $meal->getProteinesG(),
                    'carbohidrats' => $meal->getCarbohidratesG(),
                    'fats' => $meal->getFatsG(),
                ];
            }, $meals),
            'totals' => $totals,
        ]);
    }
}
