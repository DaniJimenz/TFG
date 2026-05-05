<?php

namespace App\Controller;

use App\Repository\ExerciseRepository;
use App\Repository\MealRepository;
use App\Repository\RoutineRepository;
use App\Repository\TrainingRepository;
use App\Service\DashboardStatsService;
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
    public function index(ExerciseRepository $exerciseRepository, DashboardStatsService $dashboardStatsService, MealRepository $mealRepository, RoutineRepository $routineRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Aseguramos que los valores coincidan con los de tu DB de ejercicios
        // Si en tu DB de ejercicios los niveles son "Baja", "Media", "Alta", esto funcionará
        $dificultad = $user->getActivityLevel() ?? 'Baja';
        $sexo = $user->getGender() ?? 'H';

        $rutina = [];

        try {
            if ($sexo === 'M' || $sexo === 'Mujer') {
                $rutina = array_merge($rutina, $exerciseRepository->findRandomByGroupAndDifficulty('Piernas', $dificultad, 3));
                $rutina = array_merge($rutina, $exerciseRepository->findRandomByGroupAndDifficulty('Espalda', $dificultad, 1));
            } else {
                $rutina = array_merge($rutina, $exerciseRepository->findRandomByGroupAndDifficulty('Pecho', $dificultad, 2));
                $rutina = array_merge($rutina, $exerciseRepository->findRandomByGroupAndDifficulty('Espalda', $dificultad, 2));
            }
            $rutina = array_merge($rutina, $exerciseRepository->findRandomByGroupAndDifficulty('Core', $dificultad, 1));
        } catch (\Exception $e) {
            // Si la consulta falla (ej. por el RAND()), la rutina estará vacía pero la página cargará
            $rutina = [];
        }

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
