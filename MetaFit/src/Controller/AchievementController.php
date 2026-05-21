<?php

namespace App\Controller;

use App\Repository\UserAchievementRepository;
use App\Repository\AchievementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/achievements', name: 'achievement_')]
#[IsGranted('ROLE_USER')]
class AchievementController extends AbstractController
{
    /**
     * Ver todos los logros del usuario
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(
        UserAchievementRepository $userAchievementRepository,
        AchievementRepository $achievementRepository
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Logros desbloqueados por el usuario
        $unlockedAchievements = $userAchievementRepository->findBy(
            ['appUser' => $user],
            ['date_obtained' => 'DESC']
        );

        // Todos los logros disponibles
        $allAchievements = $achievementRepository->findBy([], ['sort_order' => 'ASC']);

        // IDs de logros desbloqueados
        $unlockedIds = array_map(fn($ua) => $ua->getAchievement()->getId(), $unlockedAchievements);

        // Logros bloqueados
        $lockedAchievements = array_filter($allAchievements, fn($a) => !in_array($a->getId(), $unlockedIds));

        // Estadísticas
        $stats = [
            'total' => count($allAchievements),
            'unlocked' => count($unlockedAchievements),
            'locked' => count($lockedAchievements),
            'percentage' => count($allAchievements) > 0 ? (count($unlockedAchievements) / count($allAchievements)) * 100 : 0,
            'totalXp' => array_sum(array_map(fn($ua) => $ua->getAchievement()->getOtorgatedXp(), $unlockedAchievements)),
        ];

        return $this->render('achievement/index.html.twig', [
            'unlockedAchievements' => $unlockedAchievements,
            'lockedAchievements' => $lockedAchievements,
            'stats' => $stats,
        ]);
    }

    /**
     * Ver detalle de un logro
     */
    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, AchievementRepository $achievementRepository): Response
    {
        $achievement = $achievementRepository->find($id);

        if (!$achievement) {
            throw $this->createNotFoundException('Logro no encontrado');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Verificar si el usuario ya lo desbloqueó
        $userAchievement = null;
        foreach ($achievement->getUserAchievements() as $ua) {
            if ($ua->getAppUser() === $user) {
                $userAchievement = $ua;
                break;
            }
        }

        return $this->render('achievement/show.html.twig', [
            'achievement' => $achievement,
            'userAchievement' => $userAchievement,
        ]);
    }
}
