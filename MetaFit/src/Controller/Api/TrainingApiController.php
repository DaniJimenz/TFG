<?php

namespace App\Controller\Api;

use App\Entity\Training;
use App\Repository\TrainingRepository;
use App\Repository\ExerciseRepository;
use App\Repository\RoutineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/trainings', name: 'api_training_')]
#[IsGranted('ROLE_USER')]
class TrainingApiController extends AbstractController
{
    /**
     * Listar entrenamientos del usuario
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(TrainingRepository $trainingRepository, Request $request): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Filtrar por ejercicio si se proporciona
        $exerciseId = $request->query->get('exercise_id');
        
        if ($exerciseId) {
            $trainings = $trainingRepository->findBy(
                ['appUser' => $user, 'exercise' => $exerciseId],
                ['date' => 'DESC']
            );
        } else {
            $trainings = $trainingRepository->findBy(
                ['appUser' => $user],
                ['date' => 'DESC']
            );
        }

        return new JsonResponse([
            'success' => true,
            'data' => array_map(function (Training $training) {
                return $this->serializeTraining($training);
            }, $trainings),
        ]);
    }

    /**
     * Crear entrenamiento
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        ExerciseRepository $exerciseRepository,
        RoutineRepository $routineRepository
    ): JsonResponse {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);

        // Validar campos requeridos
        $required = ['exercise_id', 'routine_id', 'completed_series', 'repetitions', 'weight', 'duration_minutes'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return new JsonResponse(
                    ['error' => "Field '{$field}' is required"],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        $exercise = $exerciseRepository->find($data['exercise_id']);
        $routine = $routineRepository->find($data['routine_id']);

        if (!$exercise || !$routine) {
            return new JsonResponse(
                ['error' => 'Exercise or Routine not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        $training = new Training();
        $training->setAppUser($user);
        $training->setExercise($exercise);
        $training->setRoutine($routine);
        $training->setDate(new \DateTimeImmutable());
        $training->setCompletedSeries((int)$data['completed_series']);
        $training->setRepetitions((int)$data['repetitions']);
        $training->setWeight((float)$data['weight']);
        $training->setDurationMinutes((int)$data['duration_minutes']);
        $training->setCompleted($data['completed'] ?? false);
        $training->setNotes($data['notes'] ?? null);

        // Calcular 1RM
        $oneRm = $training->getWeight() * (1 + ($training->getRepetitions() / 30));
        $training->setOneRmEstimated($oneRm);

        $entityManager->persist($training);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'data' => $this->serializeTraining($training),
        ], Response::HTTP_CREATED);
    }

    /**
     * Actualizar entrenamiento
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Training $training, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($training->getAppUser() !== $this->getUser()) {
            return new JsonResponse(
                ['error' => 'You cannot access this training'],
                Response::HTTP_FORBIDDEN
            );
        }

        $data = json_decode($request->getContent(), true);

        $training->setCompletedSeries((int)($data['completed_series'] ?? $training->getCompletedSeries()));
        $training->setRepetitions((int)($data['repetitions'] ?? $training->getRepetitions()));
        $training->setWeight((float)($data['weight'] ?? $training->getWeight()));
        $training->setDurationMinutes((int)($data['duration_minutes'] ?? $training->getDurationMinutes()));
        $training->setCompleted($data['completed'] ?? $training->isCompleted());
        $training->setNotes($data['notes'] ?? $training->getNotes());

        // Recalcular 1RM
        $oneRm = $training->getWeight() * (1 + ($training->getRepetitions() / 30));
        $training->setOneRmEstimated($oneRm);

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'data' => $this->serializeTraining($training),
        ]);
    }

    /**
     * Eliminar entrenamiento
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Training $training, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($training->getAppUser() !== $this->getUser()) {
            return new JsonResponse(
                ['error' => 'You cannot access this training'],
                Response::HTTP_FORBIDDEN
            );
        }

        $entityManager->remove($training);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Obtener estadísticas de entrenamientos
     */
    #[Route('/stats/summary', name: 'stats', methods: ['GET'])]
    public function stats(TrainingRepository $trainingRepository): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $thirtyDaysAgo = new \DateTimeImmutable('-30 days');
        $recentTrainings = $trainingRepository->findTrainingsAfterDate($user, $thirtyDaysAgo);

        $stats = [
            'total_trainings' => count($recentTrainings),
            'completed_trainings' => 0,
            'total_weight_lifted' => 0,
            'total_duration' => 0,
            'avg_weight_per_training' => 0,
        ];

        foreach ($recentTrainings as $training) {
            if ($training->isCompleted()) {
                $stats['completed_trainings']++;
            }
            $stats['total_weight_lifted'] += ($training->getWeight() * $training->getRepetitions() * $training->getCompletedSeries());
            $stats['total_duration'] += $training->getDurationMinutes();
        }

        if ($stats['total_trainings'] > 0) {
            $stats['avg_weight_per_training'] = round($stats['total_weight_lifted'] / $stats['total_trainings'], 2);
        }

        return new JsonResponse([
            'success' => true,
            'period' => 'last_30_days',
            'stats' => $stats,
        ]);
    }

    /**
     * Helper para serializar entrenamiento
     */
    private function serializeTraining(Training $training): array
    {
        return [
            'id' => $training->getId(),
            'exercise' => [
                'id' => $training->getExercise()->getId(),
                'name' => $training->getExercise()->getName(),
                'muscular_group' => $training->getExercise()->getMuscularGroup(),
            ],
            'routine' => [
                'id' => $training->getRoutine()->getId(),
                'name' => $training->getRoutine()->getName(),
            ],
            'date' => $training->getDate()->format('Y-m-d H:i:s'),
            'completed_series' => $training->getCompletedSeries(),
            'repetitions' => $training->getRepetitions(),
            'weight' => $training->getWeight(),
            'duration_minutes' => $training->getDurationMinutes(),
            'completed' => $training->isCompleted(),
            'one_rm_estimated' => $training->getOneRmEstimated(),
            'notes' => $training->getNotes(),
        ];
    }
}
