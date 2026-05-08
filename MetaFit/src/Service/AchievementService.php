<?php

namespace App\Service;

use App\Entity\Achievement;
use App\Entity\User;
use App\Entity\UserAchievement;
use App\Repository\AchievementRepository;
use App\Repository\UserAchievementRepository;
use App\Repository\TrainingRepository;
use Doctrine\ORM\EntityManagerInterface;

class AchievementService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AchievementRepository $achievementRepository,
        private UserAchievementRepository $userAchievementRepository,
        private TrainingRepository $trainingRepository,
    ) {}

    /**
     * Desbloquear un logro para un usuario
     */
    public function unlockAchievement(User $user, Achievement $achievement): UserAchievement
    {
        // Verificar si ya lo tiene
        $existing = $this->userAchievementRepository->findOneBy([
            'appUser' => $user,
            'achievement' => $achievement,
        ]);

        if ($existing) {
            return $existing; // Ya lo tiene
        }

        $userAchievement = new UserAchievement();
        $userAchievement->setAppUser($user);
        $userAchievement->setAchievement($achievement);
        $userAchievement->setAchievementName($achievement->getName());
        $userAchievement->setDateObtained(new \DateTimeImmutable());
        $userAchievement->setNotificated(false);

        // Sumar XP al usuario
        $user->setPointsXp($user->getPointsXp() + $achievement->getOtorgatedXp());

        $this->entityManager->persist($userAchievement);
        $this->entityManager->flush();

        return $userAchievement;
    }

    /**
     * Verificar y desbloquear logros basados en entrenamientos completados
     */
    public function checkWorkoutAchievements(User $user): array
    {
        $unlockedAchievements = [];

        // Logro: Primer Entrenamiento
        $firstWorkoutAchievement = $this->achievementRepository->findOneBy(['judgment' => 'first_workout']);
        if ($firstWorkoutAchievement && !$this->hasAchievement($user, $firstWorkoutAchievement)) {
            $unlockedAchievements[] = $this->unlockAchievement($user, $firstWorkoutAchievement);
        }

        return $unlockedAchievements;
    }

    /**
     * Verificar y desbloquear logros por racha (streak)
     */
    public function checkStreakAchievements(User $user): array
    {
        $unlockedAchievements = [];

        // Logro: 7 días de racha
        $streak7Achievement = $this->achievementRepository->findOneBy(['judgment' => 'streak_7_days']);
        if ($streak7Achievement && !$this->hasAchievement($user, $streak7Achievement)) {
            $trainings = $this->trainingRepository->findBy(['appUser' => $user], ['date' => 'DESC']);
            
            // Comprobar días únicos consecutivos
            $consecutiveDays = 0;
            $expectedDate = new \DateTime();
            
            foreach ($trainings as $training) {
                $tDate = $training->getDate()->format('Y-m-d');
                if ($tDate === $expectedDate->format('Y-m-d') || $tDate === clone($expectedDate)->modify('-1 day')->format('Y-m-d')) {
                    if ($tDate !== $expectedDate->format('Y-m-d')) {
                        $expectedDate->modify('-1 day');
                    }
                    $consecutiveDays++;
                    if ($consecutiveDays >= 7) {
                        $unlockedAchievements[] = $this->unlockAchievement($user, $streak7Achievement);
                        break;
                    }
                }
            }
        }

        return $unlockedAchievements;
    }

    /**
     * Verificar si el usuario ya tiene un logro
     */
    public function hasAchievement(User $user, Achievement $achievement): bool
    {
        return $this->userAchievementRepository->findOneBy([
            'appUser' => $user,
            'achievement' => $achievement,
        ]) !== null;
    }

    /**
     * Obtener todos los logros desbloqueados de un usuario
     */
    public function getUserAchievements(User $user): array
    {
        return $this->userAchievementRepository->findBy(
            ['appUser' => $user],
            ['date_obtained' => 'DESC']
        );
    }

    /**
     * Obtener estadísticas de logros del usuario
     */
    public function getUserAchievementStats(User $user): array
    {
        $allAchievements = $this->achievementRepository->findAll();
        $userAchievements = $this->getUserAchievements($user);
        $userAchievementIds = array_map(fn($ua) => $ua->getAchievement()->getId(), $userAchievements);

        $totalXp = array_sum(array_map(fn($ua) => $ua->getAchievement()->getOtorgatedXp(), $userAchievements));
        $percentage = count($allAchievements) > 0 ? (count($userAchievements) / count($allAchievements)) * 100 : 0;

        return [
            'total' => count($allAchievements),
            'unlocked' => count($userAchievements),
            'locked' => count($allAchievements) - count($userAchievements),
            'percentage' => $percentage,
            'totalXp' => $totalXp,
        ];
    }
}
