<?php

namespace App\Controller;

use App\Repository\ExerciseRepository;
use App\Repository\MealRepository;
use App\Repository\RoutineRepository;
use App\Repository\TrainingRepository;
use App\Service\DashboardStatsService;
use App\Service\RecommendationService;
use App\Service\RoutineService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use DateTimeImmutable;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function root(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        return $this->redirectToRoute('app_login');
    }

    #[Route('/home', name: 'app_home')]
    #[IsGranted('ROLE_USER')]
    public function index(
        RoutineService $routineService
    ): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Calcular el IMC en el controlador (Lógica de negocio)
        $imc = 0;
        if ($user->getHeight() > 0 && $user->getActualWeight() > 0) {
            $imc = $user->getActualWeight() / (($user->getHeight() / 100) ** 2);
        }

        // Obtener la rutina persistida del usuario
        $activeRoutines = $routineService->getUserActiveRoutines($user);
        $activeRoutine = !empty($activeRoutines) ? $activeRoutines[0] : null;
        
        $rutinaEjercicios = $activeRoutine ? $activeRoutine->getExercises()->toArray() : [];
        
        // Dividir los ejercicios equitativamente según los días de la semana de la rutina
        $rutinaPorDias = [];
        if ($activeRoutine && count($rutinaEjercicios) > 0) {
            $daysWeek = $activeRoutine->getDaysWeek() > 0 ? $activeRoutine->getDaysWeek() : 3;
            $exercisesPerDay = ceil(count($rutinaEjercicios) / $daysWeek);
            
            $chunks = array_chunk($rutinaEjercicios, $exercisesPerDay);
            foreach ($chunks as $index => $chunk) {
                $rutinaPorDias['Día ' . ($index + 1)] = $chunk;
            }
        }

        return $this->render('home/index.html.twig', [
            'user' => $user,
            'imc' => $imc,
            'rutinaPorDias' => $rutinaPorDias,
        ]);
    }

    /**
     * API para obtener datos de un día específico
     */
    #[Route('/api/calendar/day/{date}', name: 'api_calendar_day', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getDayData(string $date, MealRepository $mealRepository, TrainingRepository $trainingRepository): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        try {
            $selectedDate = new DateTimeImmutable($date);
            $startOfDay = $selectedDate->setTime(0, 0, 0);
            $endOfDay = $selectedDate->setTime(23, 59, 59);

            // Obtener comidas del día
            $meals = $mealRepository->createQueryBuilder('m')
                ->where('m.appUser = :user')
                ->andWhere('m.register_date >= :start')
                ->andWhere('m.register_date <= :end')
                ->setParameter('user', $user)
                ->setParameter('start', $startOfDay)
                ->setParameter('end', $endOfDay)
                ->orderBy('m.register_date', 'ASC')
                ->getQuery()
                ->getResult();

            // Obtener entrenamientos del día
            $trainings = $trainingRepository->createQueryBuilder('t')
                ->where('t.appUser = :user')
                ->andWhere('t.date >= :start')
                ->andWhere('t.date <= :end')
                ->setParameter('user', $user)
                ->setParameter('start', $startOfDay)
                ->setParameter('end', $endOfDay)
                ->orderBy('t.date', 'ASC')
                ->getQuery()
                ->getResult();

            $mealsData = [];
            foreach ($meals as $meal) {
                $mealsData[] = [
                    'name' => ucfirst($meal->getFoodType()), // Usamos el tipo de comida como nombre
                    'type' => ucfirst($meal->getFoodType()),
                    'calories' => $meal->getCaloriesTotal(),
                    'proteins' => $meal->getProteinesG(),
                    'carbs' => $meal->getCarbohidratesG(),
                    'fats' => $meal->getFatsG(),
                    'date' => $meal->getRegisterDate()->format('H:i'),
                ];
            }

            $trainingsData = [];
            foreach ($trainings as $training) {
                $trainingsData[] = [
                    'name' => $training->getExercise()->getName(),
                    'exercises' => 1,
                    'weight' => $training->getWeight(),
                    'duration' => $training->getDurationMinutes() . ' min',
                    'completed' => $training->isCompleted(),
                ];
            }

            return $this->json([
                'success' => true,
                'date' => $selectedDate->format('Y-m-d'),
                'meals' => $mealsData,
                'trainings' => $trainingsData,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
