<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\TrainingRepository;
use App\Repository\AchievementRepository;
use Doctrine\ORM\EntityManagerInterface;

class DashboardStatsService
{
    public function __construct(
        private TrainingRepository $trainingRepository,
        private AchievementRepository $achievementRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Obtener estadísticas del dashboard
     */
    public function getUserDashboardStats(User $user): array
    {
        $allTrainings = $this->trainingRepository->findAllByUserWithRelations($user);
        $completedTrainings = array_filter($allTrainings, fn($t) => $t->isCompleted());
        
        // Entrenamientos por semana (últimos 7 días)
        $weekTrainings = $this->getTrainingsLastDays($allTrainings, 7);
        
        // Entrenamientos por mes (últimos 30 días)
        $monthTrainings = $this->getTrainingsLastDays($allTrainings, 30);

        // Calorías estimadas (aproximado: 5 calorías por minuto de entrenamiento)
        $totalCalories = array_reduce($allTrainings, fn($sum, $t) => $sum + (($t->getDurationMinutes() ?? 0) * 5), 0);
        $weekCalories = array_reduce($weekTrainings, fn($sum, $t) => $sum + (($t->getDurationMinutes() ?? 0) * 5), 0);
        $monthCalories = array_reduce($monthTrainings, fn($sum, $t) => $sum + (($t->getDurationMinutes() ?? 0) * 5), 0);

        // Racha actual (streak)
        $currentStreak = $this->calculateCurrentStreak($allTrainings);
        $longestStreak = $this->calculateLongestStreak($allTrainings);

        // Tiempo total de entrenamiento
        $totalMinutes = array_reduce($allTrainings, fn($sum, $t) => $sum + ($t->getDurationMinutes() ?? 0), 0);
        $weekMinutes = array_reduce($weekTrainings, fn($sum, $t) => $sum + ($t->getDurationMinutes() ?? 0), 0);
        $monthMinutes = array_reduce($monthTrainings, fn($sum, $t) => $sum + ($t->getDurationMinutes() ?? 0), 0);

        // Peso promedio levantado
        $totalWeight = array_reduce($allTrainings, fn($sum, $t) => $sum + ($t->getWeight() ?? 0), 0);
        $avgWeight = count($allTrainings) > 0 ? $totalWeight / count($allTrainings) : 0;

        // Grupo muscular más entrenado
        $muscleGroups = [];
        foreach ($allTrainings as $training) {
            $group = $training->getExercise()->getMuscularGroup();
            $muscleGroups[$group] = ($muscleGroups[$group] ?? 0) + 1;
        }
        arsort($muscleGroups);
        $topMuscleGroup = array_key_first($muscleGroups) ?? 'N/A';

        // Ejercicio favorito (más realizado)
        $exercises = [];
        foreach ($allTrainings as $training) {
            $exercise = $training->getExercise()->getName();
            $exercises[$exercise] = ($exercises[$exercise] ?? 0) + 1;
        }
        arsort($exercises);
        $favoriteExercise = array_key_first($exercises) ?? 'N/A';

        // Progreso últimas 2 semanas
        $last2WeeksTrainings = $this->getTrainingsLastDays($allTrainings, 14);
        $last2WeeksData = $this->groupTrainingsByDay($last2WeeksTrainings);

        return [
            'totalTrainings' => count($allTrainings),
            'completedTrainings' => count($completedTrainings),
            'weekTrainings' => count($weekTrainings),
            'monthTrainings' => count($monthTrainings),

            'totalCalories' => (int)$totalCalories,
            'weekCalories' => (int)$weekCalories,
            'monthCalories' => (int)$monthCalories,
            
            'totalMinutes' => $totalMinutes,
            'weekMinutes' => $weekMinutes,
            'monthMinutes' => $monthMinutes,
            
            'currentStreak' => $currentStreak,
            'longestStreak' => $longestStreak,
                
            'avgWeight' => round($avgWeight, 2),
            'topMuscleGroup' => $topMuscleGroup,
            'favoriteExercise' => $favoriteExercise,
            
            'chartData' => $last2WeeksData,
            'weekData' => $this->groupTrainingsByDay($weekTrainings),
        ];
    }

    /**
     * Obtener entrenamientos de los últimos N días
     */
    private function getTrainingsLastDays(array $allTrainings, int $days): array
    {
        $cutoffDate = new \DateTime("-{$days} days");
        
        return array_filter($allTrainings, function($training) use ($cutoffDate) {
            return $training->getDate() && $training->getDate() >= $cutoffDate;
        });
    }

    /**
     * Calcular racha actual (entrenamientos consecutivos sin faltar)
     */
    private function calculateCurrentStreak(array $trainings): int
    {
        if (empty($trainings)) return 0;

        usort($trainings, fn($a, $b) => $b->getDate() <=> $a->getDate());

        $streak = 0;
        $today = new \DateTime();
        $expectedDate = new \DateTime();

        foreach ($trainings as $training) {
            $trainingDate = (new \DateTime())->setTimestamp($training->getDate()->getTimestamp());
            
            if ($trainingDate->format('Y-m-d') === $expectedDate->format('Y-m-d') || 
                $trainingDate->format('Y-m-d') === $expectedDate->modify('-1 day')->format('Y-m-d')) {
                $streak++;
                $expectedDate->modify('-1 day');
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Calcular racha más larga
     */
    private function calculateLongestStreak(array $trainings): int
    {
        if (empty($trainings)) return 0;

        usort($trainings, fn($a, $b) => $a->getDate() <=> $b->getDate());

        $maxStreak = 1;
        $currentStreak = 1;
        $lastDate = null;

        foreach ($trainings as $training) {
            if ($lastDate) {
                // Seteamos a las 00:00:00 para evitar el bug de las 24 horas estrictas de PHP
                $lastDateObj = (new \DateTime())->setTimestamp($lastDate->getTimestamp())->setTime(0, 0, 0);
                $trainingDateObj = (new \DateTime())->setTimestamp($training->getDate()->getTimestamp())->setTime(0, 0, 0);
                $interval = $lastDateObj->diff($trainingDateObj)->days;

                if ($interval === 1) {
                    $currentStreak++;
                } elseif ($interval > 1) {
                    $maxStreak = max($maxStreak, $currentStreak);
                    $currentStreak = 1;
                }
            }
            $lastDate = $training->getDate();
        }

        return max($maxStreak, $currentStreak);
    }

    /**
     * Agrupar entrenamientos por día
     */
    private function groupTrainingsByDay(array $trainings): array
    {
        $grouped = [];
        
        // Pre-rellenar los últimos 14 días para que el gráfico no tenga "agujeros temporales"
        for ($i = 13; $i >= 0; $i--) {
            $date = new \DateTime("-{$i} days");
            $grouped[$date->format('Y-m-d')] = [
                'date' => $date->format('d/m'),
                'count' => 0,
                'minutes' => 0,
                'calories' => 0,
            ];
        }

        foreach ($trainings as $training) {
            $day = $training->getDate()->format('Y-m-d');
            if (isset($grouped[$day])) {
                $grouped[$day]['count']++;
                $grouped[$day]['minutes'] += $training->getDurationMinutes() ?? 0;
                $grouped[$day]['calories'] += ($training->getDurationMinutes() ?? 0) * 5;
            }
        }

        // Ya están ordenados cronológicamente desde hace 14 días hasta hoy
        return array_values($grouped);
    }

    /**
     * Obtener entrenamientos recientes
     */
    public function getRecentTrainings(User $user, int $limit = 5)
    {
        return $this->trainingRepository->findBy(
            ['appUser' => $user],
            ['date' => 'DESC'],
            $limit
        );
    }
}
