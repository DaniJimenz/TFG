<?php

namespace App\Service;

use App\Entity\ExerciseTraining;
use App\Entity\Routine;
use App\Entity\Exercise;
use App\Entity\Training;
use App\Entity\User;
use App\Repository\RoutineRepository;
use Doctrine\ORM\EntityManagerInterface;

class RoutineService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoutineRepository $routineRepository,
    ) {}

    /**
     * Crear nueva rutina
     */
    public function createRoutine(User $owner, array $data): Routine
    {
        $routine = new Routine();
        $routine->setName($data['name']);
        $routine->setObjective($data['objective']);
        $routine->setDaysWeek($data['days_week']);
        $routine->setDispoMaterial($data['dispo_material']);
        $routine->setOwner($owner);
        $routine->setCreatedAt(new \DateTimeImmutable());
        $routine->setDateStart(new \DateTimeImmutable());
        $routine->setActive(true);

        $this->entityManager->persist($routine);
        $this->entityManager->flush();

        return $routine;
    }

    /**
     * Agregar ejercicio a rutina
     */
    public function addExerciseToRoutine(Routine $routine, Exercise $exercise): void
    {
        if (!$routine->getExercises()->contains($exercise)) {
            $routine->addExercise($exercise);
            $this->entityManager->persist($routine);
            $this->entityManager->flush();
        }
    }

    /**
     * Agrega un ejercicio a la rutina con su programación semanal completa (ExerciseTraining)
     */
    public function addExerciseWithSchedule(
        Routine $routine,
        Exercise $exercise,
        int $dayWeek,
        int $order,
        int $seriesObjective,
        int $repsMin,
        int $repsMax,
        int $restSeconds
    ): void {
        if (!$routine->getExercises()->contains($exercise)) {
            $routine->addExercise($exercise);
            $this->entityManager->persist($routine);
        }

        $exerciseTraining = new ExerciseTraining();
        $exerciseTraining->setDayWeek($dayWeek);
        $exerciseTraining->setOrderRutine($order);
        $exerciseTraining->setSeriesObjective($seriesObjective);
        $exerciseTraining->setRepsMin($repsMin);
        $exerciseTraining->setRepsMax($repsMax);
        $exerciseTraining->setRestSeconds($restSeconds);
        $exerciseTraining->setRoutine($routine);
        $exerciseTraining->setExercise($exercise);

        $this->entityManager->persist($exerciseTraining);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    /**
     * Remover ejercicio de rutina
     */
    public function removeExerciseFromRoutine(Routine $routine, Exercise $exercise): void
    {
        if ($routine->getExercises()->contains($exercise)) {
            $routine->removeExercise($exercise);
            $this->entityManager->persist($routine);
            $this->entityManager->flush();
        }
    }

    /**
     * Registrar entrenamiento
     */
    public function recordTraining(User $user, Exercise $exercise, array $data, bool $flush = true, ?Routine $routine = null): Training
    {
        $training = new Training();
        $training->setAppUser($user);
        $training->setExercise($exercise);
        $training->setRoutine($routine);
        $training->setDate(new \DateTimeImmutable());
        $training->setDurationMinutes($data['duration_minutes'] ?? null);
        $training->setNotes($data['notes'] ?? null);
        $training->setCompleted($data['completed'] ?? true);

        $seriesData = $data['series'] ?? null;
        if (!empty($seriesData) && is_array($seriesData)) {
            $training->setSeriesData($seriesData);
            $training->setCompletedSeries(count($seriesData));

            $weights    = array_column($seriesData, 'weight');
            $bestWeight = !empty($weights) ? (float)max($weights) : 0.0;
            $lastReps   = (int)(end($seriesData)['reps'] ?? 10);

            $training->setWeight($bestWeight);
            $training->setRepetitions($lastReps);

            if ($bestWeight > 0 && $lastReps > 0) {
                $training->setOneRmEstimated($this->calculateOneRM($bestWeight, $lastReps));
            }
        } else {
            $training->setCompletedSeries($data['completed_series'] ?? 3);
            $training->setRepetitions($data['repetitions'] ?? 10);
            $training->setWeight($data['weight'] ?? 0);

            if (!empty($data['weight']) && !empty($data['repetitions'])) {
                $training->setOneRmEstimated($this->calculateOneRM($data['weight'], $data['repetitions']));
            }
        }

        $this->entityManager->persist($training);
        if ($flush) {
            $this->entityManager->flush();
        }

        return $training;
    }

    /**
     * Calcular 1RM estimado usando fórmula de Brzycki
     * 1RM = weight * (36 / (37 - reps))
     */
    public function calculateOneRM(float $weight, int $reps): float
    {
        if ($reps >= 37 || $reps <= 0) {
            return $weight; // Sin estimación confiable
        }
        return round($weight * (36 / (37 - $reps)), 2);
    }

    /**
     * Obtener progreso de un ejercicio
     */
    public function getExerciseProgress(User $user, Exercise $exercise, int $days = 30): array
    {
        $trainings = $this->entityManager->createQuery(
            'SELECT t FROM App\Entity\Training t 
             WHERE t.appUser = :user AND t.exercise = :exercise 
             AND t.date >= :date
             ORDER BY t.date ASC'
        )
        ->setParameter('user', $user)
        ->setParameter('exercise', $exercise)
        ->setParameter('date', new \DateTime("-{$days} days"))
        ->getResult();

        $progressData = [];
        foreach ($trainings as $training) {
            $progressData[] = [
                'date' => $training->getDate()->format('d/m/Y'),
                'weight' => $training->getWeight(),
                'reps' => $training->getRepetitions(),
                'series' => $training->getCompletedSeries(),
                'oneRm' => $training->getOneRmEstimated(),
                'duration' => $training->getDurationMinutes(),
            ];
        }

        return $progressData;
    }

    /**
     * Obtener rutinas activas del usuario
     */
    public function getUserActiveRoutines(User $user): array
    {
        return $this->routineRepository->findBy([
            'owner' => $user,
            'active' => true,
        ], ['created_at' => 'DESC']);
    }

    /**
     * Completar rutina (marcar todos sus ejercicios como completados)
     */
    public function completeRoutineSession(Routine $routine, User $user, array $exerciseData): array
    {
        $completedTrainings = [];

        foreach ($routine->getExercises() as $exercise) {
            if (isset($exerciseData[$exercise->getId()])) {
                // Pasamos false para no hacer flush por cada ejercicio
                $training = $this->recordTraining($user, $exercise, $exerciseData[$exercise->getId()], false, $routine);
                $completedTrainings[] = $training;
            }
        }

        // Hacemos un solo flush al final (Optimización y Atomicidad)
        $this->entityManager->flush();

        // Desbloquear logro si es el primer entrenamiento
        // (esto se maneja en AchievementService)

        return $completedTrainings;
    }

    /**
     * Calcular volumen total (series x reps x weight)
     */
    public function calculateVolume(int $series, int $reps, float $weight): float
    {
        return $series * $reps * $weight;
    }

    /**
     * Obtener recomendaciones de carga siguiente
     */
    public function getNextLoadRecommendation(Training $lastTraining): float
    {
        $lastWeight = $lastTraining->getWeight();

        if ($lastWeight <= 0) {
            return 0;
        }

        // +2.5 kg o +5% sobre el peso de la última sesión, lo que sea mayor
        $increase = max(2.5, $lastWeight * 0.05);

        return round($lastWeight + $increase, 1);
    }
}
