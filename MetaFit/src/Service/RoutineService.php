<?php

namespace App\Service;

use App\Entity\ExerciseTraining;
use App\Entity\Routine;
use App\Entity\Exercise;
use App\Entity\Training;
use App\Entity\User;
use App\Repository\RoutineRepository;
use App\Repository\ExerciseRepository;
use Doctrine\ORM\EntityManagerInterface;

class RoutineService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoutineRepository $routineRepository,
        private ExerciseRepository $exerciseRepository,
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
    public function addExerciseToRoutine(Routine $routine, Exercise $exercise, int $series = 3, int $reps = 10): void
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
    public function recordTraining(User $user, Exercise $exercise, array $data, bool $flush = true): Training
    {
        $training = new Training();
        $training->setAppUser($user);
        $training->setExercise($exercise);
        $training->setDate(new \DateTimeImmutable());
        $training->setCompletedSeries($data['completed_series'] ?? 3);
        $training->setRepetitions($data['repetitions'] ?? 10);
        $training->setWeight($data['weight'] ?? 0);
        $training->setDurationMinutes($data['duration_minutes'] ?? 0);
        $training->setNotes($data['notes'] ?? null);
        $training->setCompleted($data['completed'] ?? true);

        // Calcular 1RM estimado (Brzycki Formula)
        if (isset($data['weight']) && isset($data['repetitions']) && $data['weight'] > 0 && $data['repetitions'] > 0) {
            $oneRm = $this->calculateOneRM($data['weight'], $data['repetitions']);
            $training->setOneRmEstimated($oneRm);
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
                $training = $this->recordTraining($user, $exercise, $exerciseData[$exercise->getId()], false);
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
    public function getNextLoadRecommendation(User $user, Exercise $exercise): float
    {
        $trainings = $this->entityManager->createQuery(
            'SELECT t FROM App\Entity\Training t 
             WHERE t.appUser = :user AND t.exercise = :exercise 
             AND t.completed = true
             ORDER BY t.date DESC LIMIT 5'
        )
        ->setParameter('user', $user)
        ->setParameter('exercise', $exercise)
        ->getResult();

        if (empty($trainings)) {
            return $exercise->getDifficulty() === 'Alta' ? 50 : ($exercise->getDifficulty() === 'Media' ? 30 : 20);
        }

        // Promediar último peso y sugerir +2.5kg o +5%
        $avgWeight = array_reduce($trainings, fn($sum, $t) => $sum + $t->getWeight(), 0) / count($trainings);
        $increase = max(2.5, $avgWeight * 0.05); // El mayor entre 2.5kg o 5%

        return round($avgWeight + $increase, 1);
    }
}
