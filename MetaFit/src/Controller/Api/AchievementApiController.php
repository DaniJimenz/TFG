<?php

namespace App\Controller\Api;

use App\Repository\AchievementRepository;
use App\Repository\UserAchievementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/achievements', name: 'api_achievement_')]
#[IsGranted('ROLE_USER')]
class AchievementApiController extends AbstractController
{
    /**
     * Listar todos los logros disponibles
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(AchievementRepository $achievementRepository): JsonResponse
    {
        $achievements = $achievementRepository->findAll();

        return new JsonResponse([
            'success' => true,
            'data' => array_map(fn($achievement) => $this->serializeAchievement($achievement), $achievements),
        ]);
    }

    /**
     * Obtener logros del usuario autenticado
     */
    #[Route('/user', name: 'user', methods: ['GET'])]
    public function userAchievements(
        UserAchievementRepository $userAchievementRepository,
        AchievementRepository $achievementRepository
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Logros desbloqueados
        $unlockedAchievements = $userAchievementRepository->findBy(
            ['appUser' => $user],
            ['date_obtained' => 'DESC']
        );

        // Todos los logros
        $allAchievements = $achievementRepository->findAll();

        // IDs desbloqueados
        $unlockedIds = array_map(fn($ua) => $ua->getAchievement()->getId(), $unlockedAchievements);

        // Logros bloqueados
        $lockedAchievements = array_filter($allAchievements, fn($a) => !in_array($a->getId(), $unlockedIds));

        // Calcular XP total
        $totalXp = array_sum(array_map(fn($ua) => $ua->getAchievement()->getOtorgatedXp(), $unlockedAchievements));

        return new JsonResponse([
            'success' => true,
            'stats' => [
                'total' => count($allAchievements),
                'unlocked' => count($unlockedAchievements),
                'locked' => count($lockedAchievements),
                'percentage' => count($allAchievements) > 0 
                    ? round((count($unlockedAchievements) / count($allAchievements)) * 100, 2)
                    : 0,
                'total_xp' => $totalXp,
            ],
            'unlocked' => array_map(
                fn($ua) => array_merge(
                    $this->serializeAchievement($ua->getAchievement()),
                    ['date_obtained' => $ua->getDateObtained()->format('Y-m-d H:i:s')]
                ),
                $unlockedAchievements
            ),
            'locked' => array_map(fn($achievement) => $this->serializeAchievement($achievement), $lockedAchievements),
        ]);
    }

    /**
     * Helper para serializar logro
     */
    private function serializeAchievement($achievement): array
    {
        return [
            'id' => $achievement->getId(),
            'name' => $achievement->getName(),
            'description' => $achievement->getDescription(),
            'icon' => $achievement->getIcon(),
            'judgment' => $achievement->getJudgment(),
            'otorgated_xp' => $achievement->getOtorgatedXp(),
        ];
    }
}
