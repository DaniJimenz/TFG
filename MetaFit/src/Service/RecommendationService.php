<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\ExerciseRepository;

class RecommendationService
{
    public function __construct(
        private RoutineService $routineService,
        private ExerciseRepository $exerciseRepository
    ) {}

    /**
     * Calcula calorías, macros y cardio diarios en base al perfil del usuario
     */
    public function calculateDailyGoals(User $user): array
    {
        $weight = $user->getActualWeight() ?? 70.0;
        $height = $user->getHeight() ?? 170.0;
        $age = $user->getAge() ?? 25;
        $gender = strtolower($user->getGender() ?? 'h');
        
        // Ecuación de Mifflin-St Jeor para Tasa Metabólica Basal (BMR)
        if ($gender === 'h' || $gender === 'hombre') {
            $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;
        } else {
            $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;
        }

        // Multiplicador de actividad
        $activityLevel = strtolower($user->getActivityLevel() ?? 'baja');
        $activityMultiplier = match($activityLevel) {
            'media' => 1.55,
            'alta' => 1.725,
            default => 1.2, // baja
        };

        $tdee = $bmr * $activityMultiplier;

        // Ajuste por objetivo (purpose) y asignación de cardio
        $purpose = strtolower($user->getPurpose() ?? 'mantenimiento');
        
        if (str_contains($purpose, 'perder')) {
            $targetCalories = $tdee - 500; // Déficit
            $cardioMinutes = 30;
        } elseif (str_contains($purpose, 'ganar') || str_contains($purpose, 'masa')) {
            $targetCalories = $tdee + 300; // Superávit
            $cardioMinutes = 10; // Solo calentamiento
        } else {
            $targetCalories = $tdee; // Mantenimiento
            $cardioMinutes = 20;
        }

        // Macros recomendados
        $protein = $weight * 2.0; // 2g por kg de peso
        $fat = $weight * 0.8;     // 0.8g por kg de peso
        // El resto de las calorías van a los carbohidratos (1g prote = 4kcal, 1g grasa = 9kcal, 1g carbo = 4kcal)
        $carbs = ($targetCalories - ($protein * 4) - ($fat * 9)) / 4;

        return [
            'calories' => max(1200, round($targetCalories)), // Mínimo 1200kcal por salud
            'proteins' => round($protein),
            'fats' => round($fat),
            'carbs' => round(max(0, $carbs)),
            'cardio_minutes' => $cardioMinutes
        ];
    }

    /**
     * Genera la primera rutina y la persiste en base de datos
     */
    public function generateInitialRoutine(User $user): void
    {
        // Crear la entidad Rutina para el usuario
        $routine = $this->routineService->createRoutine($user, [
            'name' => 'Rutina Inicial',
            'objective' => $user->getPurpose() ?? 'General',
            'days_week' => 3,
            'dispo_material' => 'Gimnasio'
        ]);

        $dificultad = $user->getActivityLevel() ?? 'Baja';
        
        // Conseguir ejercicios aleatorios de la DB según nivel
        $exercises = $this->exerciseRepository->findRandomByGroupAndDifficulty('Core', $dificultad, 4);

        foreach ($exercises as $exercise) {
            $this->routineService->addExerciseToRoutine($routine, $exercise, 3, 10);
        }
    }
}