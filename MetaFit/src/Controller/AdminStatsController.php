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
        $totalUsers = count($userRepository->findAll());
        $activeUsers = count($userRepository->createQueryBuilder('u')
            ->where('u.deleted_at IS NULL')
            ->getQuery()
            ->getResult());
        $adminUsers = count($userRepository->findBy(['rol' => 'ROLE_ADMIN']));

        // Usuarios creados en últimos 7 días
        $sevenDaysAgo = new \DateTimeImmutable('-7 days');
        $newUsersLastWeek = count($userRepository->createQueryBuilder('u')
            ->where('u.created_at >= :date')
            ->setParameter('date', $sevenDaysAgo)
            ->getQuery()
            ->getResult());

        // Estadísticas de entrenamientos
        $totalTrainings = count($trainingRepository->findAll());
        $completedTrainings = count($trainingRepository->createQueryBuilder('t')
            ->where('t.completed = true')
            ->getQuery()
            ->getResult());

        // Entrenamientos esta semana
        $trainingsThisWeek = count($trainingRepository->createQueryBuilder('t')
            ->where('t.date >= :date')
            ->setParameter('date', $sevenDaysAgo)
            ->getQuery()
            ->getResult());

        // Estadísticas de comidas
        $totalMeals = count($mealRepository->findAll());
        $mealsThisWeek = count($mealRepository->createQueryBuilder('m')
            ->where('m.register_date >= :date')
            ->setParameter('date', $sevenDaysAgo)
            ->getQuery()
            ->getResult());

        // Estadísticas de rutinas
        $totalRoutines = count($routineRepository->findAll());
        $activeRoutines = count($routineRepository->findBy(['active' => true]));

        // Logros
        $totalAchievements = count($achievementRepository->findAll());

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
        $trainings = $trainingRepository->createQueryBuilder('t')
            ->where('t.date >= :date')
            ->setParameter('date', $fromDate)
            ->getQuery()
            ->getResult();

        $userActivity = [];
        foreach ($trainings as $training) {
            $userId = $training->getAppUser()->getId();
            $userName = $training->getAppUser()->getName() . ' ' . $training->getAppUser()->getLastname();
            
            if (!isset($userActivity[$userId])) {
                $userActivity[$userId] = [
                    'name' => $userName,
                    'count' => 0,
                ];
            }
            $userActivity[$userId]['count']++;
        }

        uasort($userActivity, fn($a, $b) => $b['count'] <=> $a['count']);

        return array_slice($userActivity, 0, 10);
    }

    /**
     * Obtener ejercicios más realizados
     */
    private function getTopExercises($trainingRepository): array
    {
        $trainings = $trainingRepository->findAll();

        $exerciseCount = [];
        foreach ($trainings as $training) {
            $exerciseName = $training->getExercise()->getName();
            
            if (!isset($exerciseCount[$exerciseName])) {
                $exerciseCount[$exerciseName] = 0;
            }
            $exerciseCount[$exerciseName]++;
        }

        arsort($exerciseCount);

        return array_slice($exerciseCount, 0, 10);
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

            $trainings = $trainingRepository->createQueryBuilder('t')
                ->where('t.date >= :start AND t.date <= :end')
                ->setParameter('start', $startOfDay)
                ->setParameter('end', $endOfDay)
                ->getQuery()
                ->getResult();

            $data[$date->format('Y-m-d')] = count($trainings);
        }

        return $data;
    }
}
