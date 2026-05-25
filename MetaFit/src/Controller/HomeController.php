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
        RoutineService $routineService,
        RecommendationService $recommendationService
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

        $dailyGoals = ($user->getHeight() > 0 && $user->getActualWeight() > 0)
            ? $recommendationService->calculateDailyGoals($user)
            : null;

        $activeRoutines = $routineService->getUserActiveRoutines($user);
        $activeRoutine = !empty($activeRoutines) ? $activeRoutines[0] : null;

        $rutinaPorDias = [];
        $dayNames = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

        if ($activeRoutine) {
            $exerciseTrainings = $activeRoutine->getExerciseTrainings();

            if ($exerciseTrainings->count() > 0) {
                $byDayNumber = [];
                foreach ($exerciseTrainings as $et) {
                    $byDayNumber[$et->getDayWeek()][] = [
                        'exercise' => $et->getExercise(),
                        'series'   => $et->getSeriesObjective(),
                        'reps_min' => $et->getRepsMin(),
                        'reps_max' => $et->getRepsMax(),
                        'rest'     => $et->getRestSeconds(),
                        'order'    => $et->getOrderRutine(),
                    ];
                }
                ksort($byDayNumber);
                foreach ($byDayNumber as $dayNum => &$dayExercises) {
                    usort($dayExercises, fn($a, $b) => $a['order'] - $b['order']);
                    $rutinaPorDias[$dayNames[$dayNum] ?? "Día $dayNum"] = $dayExercises;
                }
            } else {
                // Fallback para rutinas sin ExerciseTraining (formato antiguo)
                $rutinaEjercicios = $activeRoutine->getExercises()->toArray();
                if (!empty($rutinaEjercicios)) {
                    $daysWeek = max(1, $activeRoutine->getDaysWeek());
                    foreach (array_chunk($rutinaEjercicios, (int) ceil(count($rutinaEjercicios) / $daysWeek)) as $i => $chunk) {
                        $rutinaPorDias['Día ' . ($i + 1)] = array_map(
                            fn($ex) => ['exercise' => $ex, 'series' => 3, 'reps_min' => 10, 'reps_max' => 12, 'rest' => 60, 'order' => 0],
                            $chunk
                        );
                    }
                }
            }
        }

        return $this->render('home/index.html.twig', [
            'user'          => $user,
            'imc'           => $imc,
            'activeRoutine' => $activeRoutine,
            'rutinaPorDias' => $rutinaPorDias,
            'dailyGoals'    => $dailyGoals,
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
