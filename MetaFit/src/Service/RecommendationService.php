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
     * Genera la rutina inicial personalizada según objetivo y nivel de actividad del usuario.
     * Días por semana: Baja→3, Media→4, Alta→5
     */
    public function generateInitialRoutine(User $user): void
    {
        $purpose    = strtolower($user->getPurpose() ?? 'mantenimiento');
        $difficulty = $user->getActivityLevel() ?? 'Baja';

        $daysWeek = match(strtolower($difficulty)) {
            'alta'  => 5,
            'media' => 4,
            default => 3,
        };

        [$routineName, $split, $volume] = $this->buildRoutineConfig($purpose, $daysWeek);

        $routine = $this->routineService->createRoutine($user, [
            'name'          => $routineName,
            'objective'     => $user->getPurpose() ?? 'General',
            'days_week'     => $daysWeek,
            'dispo_material'=> 'Gimnasio',
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
     * Devuelve [nombre, split, volumen] según objetivo y días semanales.
     *
     * Grupos válidos: Pecho, Espalda, Piernas, Hombros, Brazos, Core
     * (Bíceps/Tríceps/Glúteos no existen como grupo independiente en la BD)
     *
     * Ganar masa    → PPL (3d) | PPL+Lower (4d) | PPL+Upper+Arms (5d) — 4×8-12 90s
     * Perder grasa  → Full Body rotacional — 3×12-15 60s
     * Mantenimiento → Upper/Lower — 3×10-12 75s
     */
    private function buildRoutineConfig(string $purpose, int $daysWeek): array
    {
        // ── GANAR MASA ─────────────────────────────────────────────────────────
        if (str_contains($purpose, 'ganar') || str_contains($purpose, 'masa')) {
            $volume = ['series' => 4, 'reps_min' => 8, 'reps_max' => 12, 'rest_seconds' => 90];

            if ($daysWeek >= 5) {
                return [
                    'Rutina de Hipertrofia — PPL 5 días',
                    [
                        ['day' => 1, 'muscle_groups' => [ // Push
                            ['group' => 'Pecho',   'count' => 3, 'prefer_compound' => true],
                            ['group' => 'Hombros', 'count' => 2],
                            ['group' => 'Brazos',  'count' => 2],
                        ]],
                        ['day' => 2, 'muscle_groups' => [ // Pull
                            ['group' => 'Espalda', 'count' => 4, 'prefer_compound' => true],
                            ['group' => 'Brazos',  'count' => 2],
                            ['group' => 'Core',    'count' => 1],
                        ]],
                        ['day' => 3, 'muscle_groups' => [ // Legs
                            ['group' => 'Piernas', 'count' => 5, 'prefer_compound' => true],
                            ['group' => 'Core',    'count' => 1],
                        ]],
                        ['day' => 5, 'muscle_groups' => [ // Upper B
                            ['group' => 'Pecho',   'count' => 2, 'prefer_compound' => true],
                            ['group' => 'Espalda', 'count' => 2, 'prefer_compound' => true],
                            ['group' => 'Hombros', 'count' => 2],
                        ]],
                        ['day' => 6, 'muscle_groups' => [ // Arms + Core
                            ['group' => 'Brazos',  'count' => 4],
                            ['group' => 'Core',    'count' => 3],
                        ]],
                    ],
                    $volume,
                ];
            }

            if ($daysWeek === 4) {
                return [
                    'Rutina de Hipertrofia — Upper/Lower 4 días',
                    [
                        ['day' => 1, 'muscle_groups' => [ // Upper A (push heavy)
                            ['group' => 'Pecho',   'count' => 3, 'prefer_compound' => true],
                            ['group' => 'Hombros', 'count' => 2],
                            ['group' => 'Brazos',  'count' => 2],
                        ]],
                        ['day' => 2, 'muscle_groups' => [ // Lower A
                            ['group' => 'Piernas', 'count' => 5, 'prefer_compound' => true],
                            ['group' => 'Core',    'count' => 2],
                        ]],
                        ['day' => 4, 'muscle_groups' => [ // Upper B (pull heavy)
                            ['group' => 'Espalda', 'count' => 3, 'prefer_compound' => true],
                            ['group' => 'Brazos',  'count' => 2],
                            ['group' => 'Hombros', 'count' => 2],
                        ]],
                        ['day' => 5, 'muscle_groups' => [ // Lower B
                            ['group' => 'Piernas', 'count' => 4, 'prefer_compound' => true],
                            ['group' => 'Core',    'count' => 2],
                        ]],
                    ],
                    $volume,
                ];
            }

            // 3 días: Push / Pull / Legs
            return [
                'Rutina de Hipertrofia — Push/Pull/Legs',
                [
                    ['day' => 1, 'muscle_groups' => [
                        ['group' => 'Pecho',   'count' => 3, 'prefer_compound' => true],
                        ['group' => 'Hombros', 'count' => 2],
                        ['group' => 'Brazos',  'count' => 2],
                    ]],
                    ['day' => 3, 'muscle_groups' => [
                        ['group' => 'Espalda', 'count' => 3, 'prefer_compound' => true],
                        ['group' => 'Brazos',  'count' => 2],
                        ['group' => 'Core',    'count' => 2],
                    ]],
                    ['day' => 5, 'muscle_groups' => [
                        ['group' => 'Piernas', 'count' => 5, 'prefer_compound' => true],
                        ['group' => 'Core',    'count' => 2],
                    ]],
                ],
                $volume,
            ];
        }

        // ── PERDER GRASA ───────────────────────────────────────────────────────
        if (str_contains($purpose, 'perder')) {
            $volume = ['series' => 3, 'reps_min' => 12, 'reps_max' => 15, 'rest_seconds' => 60];

            if ($daysWeek >= 5) {
                return [
                    'Rutina de Definición — Full Body 5 días',
                    [
                        ['day' => 1, 'muscle_groups' => [
                            ['group' => 'Pecho',   'count' => 2, 'prefer_compound' => true],
                            ['group' => 'Piernas', 'count' => 2, 'prefer_compound' => true],
                            ['group' => 'Core',    'count' => 2],
                        ]],
                        ['day' => 2, 'muscle_groups' => [
                            ['group' => 'Espalda', 'count' => 2, 'prefer_compound' => true],
                            ['group' => 'Hombros', 'count' => 2],
                            ['group' => 'Core',    'count' => 2],
                        ]],
                        ['day' => 3, 'muscle_groups' => [
                            ['group' => 'Piernas', 'count' => 3, 'prefer_compound' => true],
                            ['group' => 'Brazos',  'count' => 2],
                            ['group' => 'Core',    'count' => 2],
                        ]],
                        ['day' => 5, 'muscle_groups' => [
                            ['group' => 'Pecho',   'count' => 2, 'prefer_compound' => true],
                            ['group' => 'Espalda', 'count' => 2, 'prefer_compound' => true],
                            ['group' => 'Hombros', 'count' => 2],
                        ]],
                        ['day' => 6, 'muscle_groups' => [
                            ['group' => 'Piernas', 'count' => 2, 'prefer_compound' => true],
                            ['group' => 'Brazos',  'count' => 2],
                            ['group' => 'Core',    'count' => 3],
                        ]],
                    ],
                    $volume,
                ];
            }

            if ($daysWeek === 4) {
                return [
                    'Rutina de Definición — Full Body 4 días',
                    [
                        ['day' => 1, 'muscle_groups' => [
                            ['group' => 'Pecho',   'count' => 2, 'prefer_compound' => true],
                            ['group' => 'Piernas', 'count' => 2, 'prefer_compound' => true],
                            ['group' => 'Core',    'count' => 2],
                        ]],
                        ['day' => 2, 'muscle_groups' => [
                            ['group' => 'Espalda', 'count' => 2, 'prefer_compound' => true],
                            ['group' => 'Brazos',  'count' => 2],
                            ['group' => 'Core',    'count' => 2],
                        ]],
                        ['day' => 4, 'muscle_groups' => [
                            ['group' => 'Pecho',   'count' => 2, 'prefer_compound' => true],
                            ['group' => 'Piernas', 'count' => 2, 'prefer_compound' => true],
                            ['group' => 'Hombros', 'count' => 2],
                        ]],
                        ['day' => 5, 'muscle_groups' => [
                            ['group' => 'Espalda', 'count' => 2, 'prefer_compound' => true],
                            ['group' => 'Hombros', 'count' => 2],
                            ['group' => 'Core',    'count' => 2],
                        ]],
                    ],
                    $volume,
                ];
            }

            // 3 días: Full Body rotacional A/B/C
            return [
                'Rutina de Definición — Full Body',
                [
                    ['day' => 1, 'muscle_groups' => [
                        ['group' => 'Pecho',   'count' => 2, 'prefer_compound' => true],
                        ['group' => 'Espalda', 'count' => 2, 'prefer_compound' => true],
                        ['group' => 'Piernas', 'count' => 2, 'prefer_compound' => true],
                        ['group' => 'Core',    'count' => 1],
                    ]],
                    ['day' => 3, 'muscle_groups' => [
                        ['group' => 'Espalda', 'count' => 2, 'prefer_compound' => true],
                        ['group' => 'Piernas', 'count' => 2, 'prefer_compound' => true],
                        ['group' => 'Hombros', 'count' => 2],
                        ['group' => 'Core',    'count' => 1],
                    ]],
                    ['day' => 5, 'muscle_groups' => [
                        ['group' => 'Pecho',   'count' => 2, 'prefer_compound' => true],
                        ['group' => 'Piernas', 'count' => 2, 'prefer_compound' => true],
                        ['group' => 'Brazos',  'count' => 2],
                        ['group' => 'Core',    'count' => 1],
                    ]],
                ],
                $volume,
            ];
        }

        // ── MANTENIMIENTO (default) ────────────────────────────────────────────
        $volume = ['series' => 3, 'reps_min' => 10, 'reps_max' => 12, 'rest_seconds' => 75];

        if ($daysWeek >= 5) {
            return [
                'Rutina de Mantenimiento — 5 días',
                [
                    ['day' => 1, 'muscle_groups' => [ // Upper A
                        ['group' => 'Pecho',   'count' => 3, 'prefer_compound' => true],
                        ['group' => 'Espalda', 'count' => 2, 'prefer_compound' => true],
                        ['group' => 'Hombros', 'count' => 2],
                    ]],
                    ['day' => 2, 'muscle_groups' => [ // Lower A
                        ['group' => 'Piernas', 'count' => 4, 'prefer_compound' => true],
                        ['group' => 'Core',    'count' => 2],
                    ]],
                    ['day' => 3, 'muscle_groups' => [ // Push
                        ['group' => 'Pecho',   'count' => 2, 'prefer_compound' => true],
                        ['group' => 'Hombros', 'count' => 2],
                        ['group' => 'Brazos',  'count' => 2],
                    ]],
                    ['day' => 5, 'muscle_groups' => [ // Pull
                        ['group' => 'Espalda', 'count' => 3, 'prefer_compound' => true],
                        ['group' => 'Brazos',  'count' => 2],
                        ['group' => 'Core',    'count' => 1],
                    ]],
                    ['day' => 6, 'muscle_groups' => [ // Lower B + Core
                        ['group' => 'Piernas', 'count' => 3, 'prefer_compound' => true],
                        ['group' => 'Core',    'count' => 3],
                    ]],
                ],
                $volume,
            ];
        }

        if ($daysWeek === 4) {
            return [
                'Rutina de Mantenimiento — Upper/Lower 4 días',
                [
                    ['day' => 1, 'muscle_groups' => [ // Upper A
                        ['group' => 'Pecho',   'count' => 3, 'prefer_compound' => true],
                        ['group' => 'Espalda', 'count' => 2, 'prefer_compound' => true],
                        ['group' => 'Hombros', 'count' => 2],
                    ]],
                    ['day' => 2, 'muscle_groups' => [ // Lower A
                        ['group' => 'Piernas', 'count' => 4, 'prefer_compound' => true],
                        ['group' => 'Core',    'count' => 2],
                    ]],
                    ['day' => 4, 'muscle_groups' => [ // Upper B
                        ['group' => 'Espalda', 'count' => 3, 'prefer_compound' => true],
                        ['group' => 'Brazos',  'count' => 2],
                        ['group' => 'Hombros', 'count' => 2],
                    ]],
                    ['day' => 5, 'muscle_groups' => [ // Lower B
                        ['group' => 'Piernas', 'count' => 4, 'prefer_compound' => true],
                        ['group' => 'Core',    'count' => 2],
                    ]],
                ],
                $volume,
            ];
        }

        // 3 días: Upper A / Lower / Upper B
        return [
            'Rutina de Mantenimiento — Upper/Lower',
            [
                ['day' => 1, 'muscle_groups' => [
                    ['group' => 'Pecho',   'count' => 2, 'prefer_compound' => true],
                    ['group' => 'Espalda', 'count' => 2, 'prefer_compound' => true],
                    ['group' => 'Hombros', 'count' => 2],
                    ['group' => 'Brazos',  'count' => 1],
                ]],
                ['day' => 3, 'muscle_groups' => [
                    ['group' => 'Piernas', 'count' => 4, 'prefer_compound' => true],
                    ['group' => 'Core',    'count' => 3],
                ]],
                ['day' => 5, 'muscle_groups' => [
                    ['group' => 'Pecho',   'count' => 2, 'prefer_compound' => true],
                    ['group' => 'Espalda', 'count' => 2, 'prefer_compound' => true],
                    ['group' => 'Hombros', 'count' => 1],
                    ['group' => 'Brazos',  'count' => 2],
                ]],
            ],
            $volume,
        ];
    }
}