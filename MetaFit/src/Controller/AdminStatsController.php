<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\TrainingRepository;
use App\Repository\MealRepository;
use App\Repository\RoutineRepository;
use App\Repository\AchievementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/stats', name: 'admin_stats_')]
#[IsGranted('ROLE_ADMIN')]
class AdminStatsController extends AbstractController
{
    /**
     * Dashboard de estadísticas generales
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        UserRepository $userRepository,
        TrainingRepository $trainingRepository,
        MealRepository $mealRepository,
        RoutineRepository $routineRepository,
        AchievementRepository $achievementRepository
    ): Response {
        // Estadísticas de usuarios
        $totalUsers = $userRepository->count([]);
        $activeUsers = (int) $userRepository->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('u.deleted_at IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
        $adminUsers = $userRepository->count(['rol' => 'ROLE_ADMIN']);

        // Usuarios creados en últimos 7 días
        $sevenDaysAgo = new \DateTimeImmutable('-7 days');
        $newUsersLastWeek = (int) $userRepository->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('u.created_at >= :date')
            ->setParameter('date', $sevenDaysAgo)
            ->getQuery()
            ->getSingleScalarResult();

        // Estadísticas de entrenamientos
        $totalTrainings = $trainingRepository->count([]);
        $completedTrainings = (int) $trainingRepository->createQueryBuilder('t')
            ->select('count(t.id)')
            ->where('t.completed = true')
            ->getQuery()
            ->getSingleScalarResult();

        // Entrenamientos esta semana
        $trainingsThisWeek = (int) $trainingRepository->createQueryBuilder('t')
            ->select('count(t.id)')
            ->where('t.date >= :date')
            ->setParameter('date', $sevenDaysAgo)
            ->getQuery()
            ->getSingleScalarResult();

        // Estadísticas de comidas
        $totalMeals = $mealRepository->count([]);
        $mealsThisWeek = (int) $mealRepository->createQueryBuilder('m')
            ->select('count(m.id)')
            ->where('m.register_date >= :date')
            ->setParameter('date', $sevenDaysAgo)
            ->getQuery()
            ->getSingleScalarResult();

        // Estadísticas de rutinas
        $totalRoutines = $routineRepository->count([]);
        $activeRoutines = $routineRepository->count(['active' => true]);

        // Logros
        $totalAchievements = $achievementRepository->count([]);

        // Usuarios más activos (últimos 30 días)
        $thirtyDaysAgo = new \DateTimeImmutable('-30 days');
        $activeUsersData = $this->getMostActiveUsers($trainingRepository, $thirtyDaysAgo);

        // Ejercicios más realizados
        $topExercises = $this->getTopExercises($trainingRepository);

        // Estadísticas por día (últimos 7 días)
        $trainingsByDay = $this->getTrainingsByDay($trainingRepository);

        return $this->render('admin/stats/index.html.twig', [
            'stats' => [
                'users' => [
                    'total' => $totalUsers,
                    'active' => $activeUsers,
                    'admin' => $adminUsers,
                    'new_last_week' => $newUsersLastWeek,
                ],
                'trainings' => [
                    'total' => $totalTrainings,
                    'completed' => $completedTrainings,
                    'this_week' => $trainingsThisWeek,
                    'completion_rate' => $totalTrainings > 0 
                        ? round(($completedTrainings / $totalTrainings) * 100, 2)
                        : 0,
                ],
                'meals' => [
                    'total' => $totalMeals,
                    'this_week' => $mealsThisWeek,
                ],
                'routines' => [
                    'total' => $totalRoutines,
                    'active' => $activeRoutines,
                ],
                'achievements' => [
                    'total' => $totalAchievements,
                ],
            ],
            'activeUsers' => $activeUsersData,
            'topExercises' => $topExercises,
            'trainingsByDay' => $trainingsByDay,
        ]);
    }

    /**
     * Obtener usuarios más activos
     */
    private function getMostActiveUsers($trainingRepository, \DateTimeImmutable $fromDate): array
    {
        $results = $trainingRepository->createQueryBuilder('t')
            ->select('u.id, u.name, u.lastname, COUNT(t.id) as training_count')
            ->join('t.appUser', 'u')
            ->where('t.date >= :date')
            ->setParameter('date', $fromDate)
            ->groupBy('u.id')
            ->orderBy('training_count', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getArrayResult();

        $userActivity = [];
        foreach ($results as $row) {
            $userActivity[$row['id']] = [
                'name' => $row['name'] . ' ' . $row['lastname'],
                'count' => $row['training_count'],
            ];
        }

        return $userActivity;
    }

    /**
     * Obtener ejercicios más realizados
     */
    private function getTopExercises($trainingRepository): array
    {
        $results = $trainingRepository->createQueryBuilder('t')
            ->select('e.name, COUNT(t.id) as exercise_count')
            ->join('t.exercise', 'e')
            ->groupBy('e.id')
            ->orderBy('exercise_count', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getArrayResult();

        $exerciseCount = [];
        foreach ($results as $row) {
            $exerciseCount[$row['name']] = $row['exercise_count'];
        }

        return $exerciseCount;
    }

    /**
     * Entrenamientos por día (últimos 7 días)
     */
    private function getTrainingsByDay($trainingRepository): array
    {
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = new \DateTimeImmutable("-{$i} days");
            $startOfDay = $date->setTime(0, 0, 0);
            $endOfDay = $date->setTime(23, 59, 59);

            $count = (int) $trainingRepository->createQueryBuilder('t')
                ->select('COUNT(t.id)')
                ->where('t.date >= :start AND t.date <= :end')
                ->setParameter('start', $startOfDay)
                ->setParameter('end', $endOfDay)
                ->getQuery()
                ->getSingleScalarResult();

            $data[$date->format('Y-m-d')] = $count;
        }

        return $data;
    }
}
