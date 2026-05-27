<?php

namespace App\Controller;

use App\Entity\Meal;
use App\Repository\MealRepository;
use App\Service\FoodAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

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

        // Limitamos las comidas a las últimas 50 para la vista
        $meals = $mealRepository->findBy(
            ['appUser' => $user],
            ['register_date' => 'DESC'],
            50
        );

        // Calcular macros de hoy
        $today = clone new \DateTimeImmutable('today');
        $tomorrow = clone new \DateTimeImmutable('tomorrow');

        // Pedimos a la BD solo las de hoy, no iteramos sobre todo el historial
        $todayMeals = $mealRepository->findMealsByDateRange($user, $today, $tomorrow);

        $todayMacros = [
            'calories' => 0,
            'proteins' => 0,
            'carbs' => 0,
            'fats' => 0
        ];

        foreach ($todayMeals as $meal) {
            $todayMacros['calories'] += $meal->getCaloriesTotal() ?? 0;
            $todayMacros['proteins'] += $meal->getProteinesG() ?? 0;
            $todayMacros['carbs'] += $meal->getCarbohidratesG() ?? 0;
            $todayMacros['fats'] += $meal->getFatsG() ?? 0;
        }

        // Agrupar comidas por día
        $mealsByDay = [];
        foreach ($meals as $meal) {
            $mealDate = $meal->getRegisterDate();
            $dateKey = $mealDate->format('Y-m-d');
            
            if (!isset($mealsByDay[$dateKey])) {
                $mealsByDay[$dateKey] = [];
            }
            $mealsByDay[$dateKey][] = $meal;
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
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $constraints = new Assert\Collection([
                'food_type' => new Assert\Required([new Assert\NotBlank(), new Assert\Choice(choices: ['desayuno', 'comida', 'merienda', 'cena', 'snack'])]),
                'calories_total' => new Assert\Required([new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\PositiveOrZero()]),
                'proteines_g' => new Assert\Required([new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\PositiveOrZero()]),
                'carbohidrats_g' => new Assert\Required([new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\PositiveOrZero()]),
                'fats_g' => new Assert\Required([new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\PositiveOrZero()]),
                'notes' => new Assert\Optional([new Assert\Type('string')]),
            ]);
            $constraints->allowExtraFields = true;
            $constraints->allowMissingFields = true;
            
            $violations = $validator->validate($data, $constraints);
            if (count($violations) > 0) {
                $this->addFlash('error', 'Revisa los macros introducidos. Deben ser números positivos o cero.');
                return $this->redirectToRoute('meal_new');
            }

            $meal = new Meal();
            $meal->setAppUser($user);
            $meal->setFoodType($data['food_type']);
            $meal->setCaloriesTotal((float)$data['calories_total']);
            $meal->setProteinesG((float)$data['proteines_g']);
            $meal->setCarbohidratesG((float)$data['carbohidrats_g']);
            $meal->setFatsG((float)$data['fats_g']);
            $meal->setBarScanner(false);
            $meal->setRegisterMethod('manual');
            $meal->setRegisterDate(new \DateTimeImmutable());
            $meal->setNotes($request->request->get('notes', null));

            $entityManager->persist($meal);
            $entityManager->flush();

            $this->addFlash('success', '¡Comida registrada exitosamente!');

            return $this->redirectToRoute('meal_index');
        }

        return $this->render('meal/new.html.twig');
    }

    /**
     * Ver detalle de una comida específica
     */
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
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

        $uploadedFile = $request->files->get('photo');

        if (!$uploadedFile) {
            return new JsonResponse(
                ['error' => 'No se recibió ninguna imagen. Asegúrate de que el archivo no supere 2MB.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!$uploadedFile->isValid()) {
            $errorMessages = [
                \UPLOAD_ERR_INI_SIZE   => 'La imagen supera el límite permitido por el servidor (2MB).',
                \UPLOAD_ERR_FORM_SIZE  => 'La imagen supera el límite del formulario.',
                \UPLOAD_ERR_PARTIAL    => 'La imagen se subió de forma incompleta. Inténtalo de nuevo.',
                \UPLOAD_ERR_NO_TMP_DIR => 'Error de configuración del servidor.',
                \UPLOAD_ERR_CANT_WRITE => 'No se pudo guardar la imagen en el servidor.',
            ];
            $msg = $errorMessages[$uploadedFile->getError()] ?? 'Error al subir la imagen.';
            return new JsonResponse(['error' => $msg], Response::HTTP_BAD_REQUEST);
        }

        $mimeType = $uploadedFile->getMimeType();
        if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'])) {
            return new JsonResponse(
                ['error' => 'El archivo debe ser una imagen (JPEG, PNG o WebP).'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/meals';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $ext = $uploadedFile->guessExtension() ?? 'jpg';
            $fileName = uniqid('meal_') . '.' . $ext;
            $tempPath = $uploadedFile->move($uploadDir, $fileName);

            $analysisData = $analysisService->analyzeFoodImage($tempPath->getRealPath());

            $userFoodType = $request->request->get('food_type');
            $validTypes = ['desayuno', 'comida', 'merienda', 'cena', 'snack'];
            $foodType = in_array($userFoodType, $validTypes) ? $userFoodType : $analysisData['food_type'];

            $meal = new Meal();
            $meal->setAppUser($user);
            $meal->setFoodType($foodType);
            $meal->setCaloriesTotal((float)$analysisData['calories_total']);
            $meal->setProteinesG((float)$analysisData['proteines_g']);
            $meal->setCarbohidratesG((float)$analysisData['carbohidrats_g']);
            $meal->setFatsG((float)$analysisData['fats_g']);
            $meal->setBarScanner(false);
            $meal->setRegisterMethod('photo_ai');
            $meal->setRegisterDate(new \DateTimeImmutable());
            $meal->setUrlImage('/uploads/meals/' . $fileName);
            $meal->setNotes($request->request->get('notes'));

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
        } catch (\Throwable $e) {
            return new JsonResponse(
                ['error' => 'Error al procesar la imagen: ' . $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Editar comida existente
     */
    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Meal $meal, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator): Response
    {
        // Verificar permisos
        if ($meal->getAppUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot edit this meal.');
        }

        if ($request->isMethod('POST')) {
            $data = $request->request->all();

            $constraints = new Assert\Collection([
                'food_type' => new Assert\Required([new Assert\NotBlank(), new Assert\Choice(choices: ['desayuno', 'comida', 'merienda', 'cena', 'snack'])]),
                'calories_total' => new Assert\Required([new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\PositiveOrZero()]),
                'proteines_g' => new Assert\Required([new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\PositiveOrZero()]),
                'carbohidrats_g' => new Assert\Required([new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\PositiveOrZero()]),
                'fats_g' => new Assert\Required([new Assert\NotBlank(), new Assert\Type('numeric'), new Assert\PositiveOrZero()]),
            ]);
            $constraints->allowExtraFields = true;
            $constraints->allowMissingFields = true;
            
            $violations = $validator->validate($data, $constraints);
            if (count($violations) > 0) {
                $this->addFlash('error', 'Revisa los macros introducidos. Deben ser números positivos o cero.');
                return $this->redirectToRoute('meal_edit', ['id' => $meal->getId()]);
            }

            $meal->setFoodType($data['food_type']);
            $meal->setCaloriesTotal((float)$data['calories_total']);
            $meal->setProteinesG((float)$data['proteines_g']);
            $meal->setCarbohidratesG((float)$data['carbohidrats_g']);
            $meal->setFatsG((float)$data['fats_g']);
            $meal->setNotes($data['notes'] ?? null);

            $entityManager->flush();

            $this->addFlash('success', '¡Comida actualizada exitosamente!');

            return $this->redirectToRoute('meal_index');
        }

        return $this->render('meal/edit.html.twig', [
            'meal' => $meal,
        ]);
    }

    /**
     * Eliminar comida
     */
    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Meal $meal, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Verificar permisos y token CSRF
        if ($meal->getAppUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot delete this meal.');
        }

        if ($this->isCsrfTokenValid('delete_meal_' . $meal->getId(), $request->request->get('_token'))) {
            // Eliminar la imagen física del servidor para no saturar el disco
            if ($meal->getUrlImage()) {
                $imagePath = $this->getParameter('kernel.project_dir') . '/public' . $meal->getUrlImage();
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            $entityManager->remove($meal);
            $entityManager->flush();

            $this->addFlash('success', '¡Comida eliminada exitosamente!');
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
