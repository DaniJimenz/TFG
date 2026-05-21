<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\TrainingRepository;
use App\Repository\MealRepository;
use App\Repository\ExerciseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminDashboardController extends AbstractController
{
    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function dashboard(
        UserRepository $userRepository,
        TrainingRepository $trainingRepository,
        ExerciseRepository $exerciseRepository,
        MealRepository $mealRepository
    ): Response {
        // Estadísticas de usuarios
        $totalUsers = $userRepository->count([]);
        $activeUsers = (int) $userRepository->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('u.deleted_at IS NULL')
            ->getQuery()
            ->getSingleScalarResult();

        // Usuarios nuevos en últimos 7 días
        $sevenDaysAgo = new \DateTimeImmutable('-7 days');
        $newUsersLastWeek = (int) $userRepository->createQueryBuilder('u')
            ->select('count(u.id)')
            ->where('u.created_at >= :date')
            ->setParameter('date', $sevenDaysAgo)
            ->getQuery()
            ->getSingleScalarResult();

        // Estadísticas de entrenamientos
        $totalTrainings = $trainingRepository->count([]);
        $trainingsLastWeek = (int) $trainingRepository->createQueryBuilder('t')
            ->select('count(t.id)')
            ->where('t.date >= :date')
            ->setParameter('date', $sevenDaysAgo)
            ->getQuery()
            ->getSingleScalarResult();

        // Estadísticas de ejercicios
        $totalExercises = $exerciseRepository->count([]);

        // Usuarios con más entrenamientos
        $topUsers = $userRepository->createQueryBuilder('u')
            ->select('u.id, u.name, u.email, COUNT(t.id) as training_count')
            ->leftJoin('u.trainings', 't')
            ->groupBy('u.id')
            ->orderBy('training_count', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('admin/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'newUsersLastWeek' => $newUsersLastWeek,
            'totalTrainings' => $totalTrainings,
            'trainingsLastWeek' => $trainingsLastWeek,
            'totalExercises' => $totalExercises,
            'topUsers' => $topUsers,
        ]);
    }
}
