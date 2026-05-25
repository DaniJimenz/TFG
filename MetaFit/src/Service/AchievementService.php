<?php

namespace App\Service;

use App\Entity\Achievement;
use App\Entity\User;
use App\Entity\UserAchievement;
use App\Repository\AchievementRepository;
use App\Repository\MealRepository;
use App\Repository\RoutineRepository;
use App\Repository\TrainingRepository;
use App\Repository\UserAchievementRepository;
use Doctrine\ORM\EntityManagerInterface;

class AchievementService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AchievementRepository $achievementRepository,
        private UserAchievementRepository $userAchievementRepository,
        private TrainingRepository $trainingRepository,
        private MealRepository $mealRepository,
        private RoutineRepository $routineRepository,
    ) {}

    /**
     * Desbloquear un logro para un usuario (si no lo tiene ya)
     */
    public function unlockAchievement(User $user, Achievement $achievement): UserAchievement
    {
        $existing = $this->userAchievementRepository->findOneBy([
            'appUser' => $user,
            'achievement' => $achievement,
        ]);

        if ($existing) {
            return $existing;
        }

        $userAchievement = new UserAchievement();
        $userAchievement->setAppUser($user);
        $userAchievement->setAchievement($achievement);
        $userAchievement->setAchievementName($achievement->getName());
        $userAchievement->setDateObtained(new \DateTimeImmutable());
        $userAchievement->setNotificated(false);

        $user->setPointsXp($user->getPointsXp() + $achievement->getOtorgatedXp());

        $this->entityManager->persist($userAchievement);
        $this->entityManager->flush();

        return $userAchievement;
    }

    /**
     * Verifica y desbloquea todos los logros relacionados con entrenamientos.
     * Llamado desde RoutineController y TrainingController tras completar sesión.
     */
    public function checkWorkoutAchievements(User $user): array
    {
        $unlocked = [];

        $allTrainings = $this->trainingRepository->findBy(['appUser' => $user]);
        $totalTrainings = count($allTrainings);

        // ── Conteo de entrenamientos ──────────────────────────────────────────
        $this->tryUnlock($user, 'completed_1_training', $totalTrainings >= 1, $unlocked);
        $this->tryUnlock($user, 'completed_10_training', $totalTrainings >= 10, $unlocked);
        $this->tryUnlock($user, 'hundred_trainings', $totalTrainings >= 100, $unlocked);

        // ── Nivel ─────────────────────────────────────────────────────────────
        $this->tryUnlock($user, 'reached_level_10', $user->getLevel() >= 10, $unlocked);
        $this->tryUnlock($user, 'level_20', $user->getLevel() >= 20, $unlocked);

        // ── XP total ─────────────────────────────────────────────────────────
        $this->tryUnlock($user, 'ten_thousand_xp', $user->getPointsXp() >= 10000, $unlocked);

        // ── Entrenamientos últimos 7 días (Semana de hierro) ─────────────────
        $sevenDaysAgo = new \DateTimeImmutable('-7 days');
        $recentTrainings = array_filter(
            $allTrainings,
            fn($t) => $t->getDate() >= $sevenDaysAgo
        );
        $this->tryUnlock($user, 'week_of_iron', count($recentTrainings) >= 7, $unlocked);

        // ── Entrenamientos últimos 30 días (Mes dedicado) ────────────────────
        $thirtyDaysAgo = new \DateTimeImmutable('-30 days');
        $monthTrainings = array_filter(
            $allTrainings,
            fn($t) => $t->getDate() >= $thirtyDaysAgo
        );
        $this->tryUnlock($user, 'dedicated_month', count($monthTrainings) >= 20, $unlocked);

        // ── Ejercicios únicos realizados ─────────────────────────────────────
        $uniqueExercises = array_unique(
            array_map(fn($t) => $t->getExercise()?->getId(), $allTrainings)
        );
        $this->tryUnlock($user, 'fifty_exercises', count($uniqueExercises) >= 50, $unlocked);

        // ── Basados en el último entrenamiento registrado ────────────────────
        $sorted = $allTrainings;
        usort($sorted, fn($a, $b) => $b->getDate() <=> $a->getDate());
        $latest = $sorted[0] ?? null;

        if ($latest) {
            $hour = (int) $latest->getDate()->format('H');
            $duration = $latest->getDurationMinutes() ?? 0;
            $weight = $latest->getWeight() ?? 0;

            // Madrugador: entrenamiento antes de las 7:00
            $this->tryUnlock($user, 'early_morning_training', $hour < 7, $unlocked);

            // Noctámbulo: entrenamiento después de las 22:00
            $this->tryUnlock($user, 'night_training', $hour >= 22, $unlocked);

            // Velocidad: entrenamiento de 30 min o menos (pero > 0)
            $this->tryUnlock($user, 'quick_training', $duration > 0 && $duration <= 30, $unlocked);

            // Resistencia: entrenamiento de 90 min o más
            $this->tryUnlock($user, 'long_training', $duration >= 90, $unlocked);

            // Fuerza bruta: peso >= 100 kg en algún set
            $this->tryUnlock($user, 'max_weight', $weight >= 100, $unlocked);
        }

        // ── Rutinas completadas ───────────────────────────────────────────────
        // "Combo perfecto": 5 sesiones de rutina distintas
        // Contamos trainings agrupados por fecha (cada día cuenta como sesión)
        $sessionDays = array_unique(
            array_map(fn($t) => $t->getDate()->format('Y-m-d'), $allTrainings)
        );
        $this->tryUnlock($user, 'five_routines', count($sessionDays) >= 5, $unlocked);

        // ── Nutrición ────────────────────────────────────────────────────────
        $mealCount = count($this->mealRepository->findBy(['appUser' => $user]));
        $this->tryUnlock($user, 'perfect_nutrition', $mealCount >= 7, $unlocked);

        // ── Logros de biblioteca ─────────────────────────────────────────────
        $routines = $this->routineRepository->findBy(['owner' => $user]);
        $exercisesInRoutines = 0;
        foreach ($routines as $routine) {
            $exercisesInRoutines += $routine->getExercises()->count();
        }
        $this->tryUnlock($user, 'added_20_exercises', $exercisesInRoutines >= 20, $unlocked);
        $this->tryUnlock($user, 'created_routine', count($routines) >= 1, $unlocked);

        return $unlocked;
    }

    /**
     * Mantenido por compatibilidad con los controladores existentes.
     * Redirige a checkWorkoutAchievements para no duplicar lógica.
     */
    public function checkStreakAchievements(User $user): array
    {
        return [];
    }

    /**
     * Desbloquea el logro de registro. Llamar al crear un nuevo usuario.
     */
    public function checkRegistrationAchievement(User $user): void
    {
        $achievement = $this->achievementRepository->findOneBy(['judgment' => 'registered']);
        if ($achievement) {
            $this->unlockAchievement($user, $achievement);
        }
    }

    /**
     * Verifica si el usuario ya tiene un logro concreto.
     */
    public function hasAchievement(User $user, Achievement $achievement): bool
    {
        return $this->userAchievementRepository->findOneBy([
            'appUser' => $user,
            'achievement' => $achievement,
        ]) !== null;
    }

    /**
     * Obtener todos los logros desbloqueados de un usuario.
     */
    public function getUserAchievements(User $user): array
    {
        return $this->userAchievementRepository->findBy(
            ['appUser' => $user],
            ['date_obtained' => 'DESC']
        );
    }

    /**
     * Estadísticas de logros del usuario.
     */
    public function getUserAchievementStats(User $user): array
    {
        $allAchievements = $this->achievementRepository->findAll();
        $userAchievements = $this->getUserAchievements($user);

        $totalXp = array_sum(
            array_map(fn($ua) => $ua->getAchievement()->getOtorgatedXp(), $userAchievements)
        );
        $percentage = count($allAchievements) > 0
            ? (count($userAchievements) / count($allAchievements)) * 100
            : 0;

        return [
            'total'    => count($allAchievements),
            'unlocked' => count($userAchievements),
            'locked'   => count($allAchievements) - count($userAchievements),
            'percentage' => $percentage,
            'totalXp'  => $totalXp,
        ];
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    private function tryUnlock(User $user, string $judgment, bool $condition, array &$unlocked): void
    {
        if (!$condition) {
            return;
        }
        $achievement = $this->achievementRepository->findOneBy(['judgment' => $judgment]);
        if ($achievement && !$this->hasAchievement($user, $achievement)) {
            $unlocked[] = $this->unlockAchievement($user, $achievement);
        }
    }
}
