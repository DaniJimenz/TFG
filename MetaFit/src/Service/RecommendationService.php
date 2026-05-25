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
     * Genera la rutina inicial personalizada según el objetivo y nivel del usuario.
     * Crea un split semanal de 3 días con grupos musculares específicos,
     * volumen adaptado al objetivo y programación en ExerciseTraining.
     */
    public function generateInitialRoutine(User $user): void
    {
        $purpose = strtolower($user->getPurpose() ?? 'mantenimiento');
        $difficulty = $user->getActivityLevel() ?? 'Baja';

        [$routineName, $split, $volume] = $this->buildRoutineConfig($purpose);

        $routine = $this->routineService->createRoutine($user, [
            'name' => $routineName,
            'objective' => $user->getPurpose() ?? 'General',
            'days_week' => count($split),
            'dispo_material' => 'Gimnasio',
        ]);

        foreach ($split as $dayConfig) {
            $order = 1;
            foreach ($dayConfig['muscle_groups'] as $groupConfig) {
                $exercises = $this->exerciseRepository->findForRoutineDay(
                    $groupConfig['group'],
                    $difficulty,
                    $groupConfig['count'],
                    $groupConfig['prefer_compound'] ?? false
                );
                foreach ($exercises as $exercise) {
                    $this->routineService->addExerciseWithSchedule(
                        $routine,
                        $exercise,
                        $dayConfig['day'],
                        $order++,
                        $volume['series'],
                        $volume['reps_min'],
                        $volume['reps_max'],
                        $volume['rest_seconds']
                    );
                }
            }
        }

        $this->routineService->flush();
    }

    /**
     * Devuelve [nombre, split, volumen] según el objetivo del usuario.
     *
     * Ganar masa   → Push/Pull/Legs, 4×8-12, 90s descanso
     * Perder grasa → Full Body x3,   3×12-15, 60s descanso
     * Mantenimiento→ Upper/Lower,    3×10-12, 75s descanso
     */
    private function buildRoutineConfig(string $purpose): array
    {
        if (str_contains($purpose, 'ganar') || str_contains($purpose, 'masa')) {
            return [
                'Rutina de Hipertrofia — Push/Pull/Legs',
                [
                    [
                        'day' => 1,
                        'muscle_groups' => [
                            ['group' => 'Pecho',    'count' => 2, 'prefer_compound' => true],
                            ['group' => 'Hombros',  'count' => 2],
                            ['group' => 'Tríceps',  'count' => 2],
                        ],
                    ],
                    [
                        'day' => 3,
                        'muscle_groups' => [
                            ['group' => 'Espalda',  'count' => 2, 'prefer_compound' => true],
                            ['group' => 'Bíceps',   'count' => 2],
                            ['group' => 'Core',     'count' => 1],
                        ],
                    ],
                    [
                        'day' => 5,
                        'muscle_groups' => [
                            ['group' => 'Piernas',  'count' => 2, 'prefer_compound' => true],
                            ['group' => 'Glúteos',  'count' => 2],
                            ['group' => 'Core',     'count' => 1],
                        ],
                    ],
                ],
                ['series' => 4, 'reps_min' => 8,  'reps_max' => 12, 'rest_seconds' => 90],
            ];
        }

        if (str_contains($purpose, 'perder')) {
            return [
                'Rutina de Definición — Full Body',
                [
                    [
                        'day' => 1,
                        'muscle_groups' => [
                            ['group' => 'Pecho',    'count' => 1, 'prefer_compound' => true],
                            ['group' => 'Espalda',  'count' => 1, 'prefer_compound' => true],
                            ['group' => 'Piernas',  'count' => 1, 'prefer_compound' => true],
                            ['group' => 'Hombros',  'count' => 1],
                            ['group' => 'Core',     'count' => 1],
                        ],
                    ],
                    [
                        'day' => 3,
                        'muscle_groups' => [
                            ['group' => 'Espalda',  'count' => 1, 'prefer_compound' => true],
                            ['group' => 'Piernas',  'count' => 1, 'prefer_compound' => true],
                            ['group' => 'Glúteos',  'count' => 1],
                            ['group' => 'Bíceps',   'count' => 1],
                            ['group' => 'Core',     'count' => 1],
                        ],
                    ],
                    [
                        'day' => 5,
                        'muscle_groups' => [
                            ['group' => 'Pecho',    'count' => 1, 'prefer_compound' => true],
                            ['group' => 'Piernas',  'count' => 1, 'prefer_compound' => true],
                            ['group' => 'Hombros',  'count' => 1],
                            ['group' => 'Tríceps',  'count' => 1],
                            ['group' => 'Core',     'count' => 1],
                        ],
                    ],
                ],
                ['series' => 3, 'reps_min' => 12, 'reps_max' => 15, 'rest_seconds' => 60],
            ];
        }

        // Mantenimiento (default)
        return [
            'Rutina de Mantenimiento — Upper/Lower',
            [
                [
                    'day' => 1,
                    'muscle_groups' => [
                        ['group' => 'Pecho',    'count' => 2, 'prefer_compound' => true],
                        ['group' => 'Hombros',  'count' => 1],
                        ['group' => 'Tríceps',  'count' => 2],
                    ],
                ],
                [
                    'day' => 3,
                    'muscle_groups' => [
                        ['group' => 'Piernas',  'count' => 2, 'prefer_compound' => true],
                        ['group' => 'Glúteos',  'count' => 2],
                        ['group' => 'Core',     'count' => 1],
                    ],
                ],
                [
                    'day' => 5,
                    'muscle_groups' => [
                        ['group' => 'Espalda',  'count' => 2, 'prefer_compound' => true],
                        ['group' => 'Bíceps',   'count' => 2],
                        ['group' => 'Core',     'count' => 1],
                    ],
                ],
            ],
            ['series' => 3, 'reps_min' => 10, 'reps_max' => 12, 'rest_seconds' => 75],
        ];
    }
}