<?php

namespace App\Controller;

use App\Repository\ExerciseRepository;
use App\Service\DashboardStatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    #[IsGranted('ROLE_USER')]
    public function index(ExerciseRepository $exerciseRepository, DashboardStatsService $dashboardStatsService): Response
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

        return $this->render('home/index.html.twig', [
            'user' => $user,
            'rutina' => $rutina,
            'stats' => $stats,
            'recentTrainings' => $recentTrainings,
        ]);
    }
}
