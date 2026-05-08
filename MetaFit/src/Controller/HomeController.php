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
    #[Route('/home', name: 'app_home')]
    #[IsGranted('ROLE_USER')]
    public function index(
        ExerciseRepository $exerciseRepository, 
        DashboardStatsService $dashboardStatsService, 
        MealRepository $mealRepository, 
        RoutineRepository $routineRepository,
        RecommendationService $recommendationService,
        RoutineService $routineService
    ): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Obtener objetivos diarios calculados en base a su perfil actual
        $dailyGoals = $recommendationService->calculateDailyGoals($user);
        
        // Obtener la rutina persistida del usuario
        $activeRoutines = $routineService->getUserActiveRoutines($user);
        $rutina = !empty($activeRoutines) ? $activeRoutines[0]->getExercises()->toArray() : [];

        // Obtener estadísticas del dashboard
        $stats = $dashboardStatsService->getUserDashboardStats($user);
        $recentTrainings = $dashboardStatsService->getRecentTrainings($user, 5);

        // Obtener datos del último mes para el calendario
        $startDate = new DateTimeImmutable('-30 days');
        $endDate = new DateTimeImmutable();
        $meals = $mealRepository->findBy(
            ['appUser' => $user],
            ['register_date' => 'DESC']
        );
        
        return $this->render('home/index.html.twig', [
            'user' => $user,
            'dailyGoals' => $dailyGoals,
            'rutina' => $rutina,
            'stats' => $stats,
            'recentTrainings' => $recentTrainings,
            'meals' => $meals,
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
                    'name' => $meal->getName(),
                    'type' => $meal->getFoodType(),
                    'calories' => $meal->getCalories(),
                    'proteins' => $meal->getProteines(),
                    'carbs' => $meal->getCarbohidrats(),
                    'fats' => $meal->getFats(),
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
