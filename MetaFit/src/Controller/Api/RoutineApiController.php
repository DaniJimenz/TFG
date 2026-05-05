<?php

namespace App\Controller\Api;

use App\Entity\Routine;
use App\Entity\Exercise;
use App\Repository\RoutineRepository;
use App\Repository\ExerciseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/routines', name: 'api_routine_')]
#[IsGranted('ROLE_USER')]
class RoutineApiController extends AbstractController
{
    /**
     * Listar rutinas del usuario
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(RoutineRepository $routineRepository): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $routines = $routineRepository->findBy(
            ['owner' => $user],
            ['created_at' => 'DESC']
        );

        return new JsonResponse([
            'success' => true,
            'data' => array_map(fn($routine) => $this->serializeRoutine($routine), $routines),
        ]);
    }

    /**
     * Obtener detalle de una rutina
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Routine $routine): JsonResponse
    {
        if ($routine->getOwner() !== $this->getUser()) {
            return new JsonResponse(
                ['error' => 'You cannot access this routine'],
                Response::HTTP_FORBIDDEN
            );
        }

        return new JsonResponse([
            'success' => true,
            'data' => $this->serializeRoutineDetail($routine),
        ]);
    }

    /**
     * Crear nueva rutina
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true);

        // Validar campos requeridos
        $required = ['name', 'objective', 'days_week', 'dispo_material'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return new JsonResponse(
                    ['error' => "Field '{$field}' is required"],
                    Response::HTTP_BAD_REQUEST
                );
            }
        }

        $routine = new Routine();
        $routine->setName($data['name']);
        $routine->setObjective($data['objective']);
        $routine->setDaysWeek((int)$data['days_week']);
        $routine->setDispoMaterial($data['dispo_material']);
        $routine->setOwner($user);
        $routine->setCreatedAt(new \DateTimeImmutable());
        $routine->setDateStart(new \DateTimeImmutable());
        $routine->setActive(true);

        $entityManager->persist($routine);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'data' => $this->serializeRoutine($routine),
        ], Response::HTTP_CREATED);
    }

    /**
     * Actualizar rutina
     */
    #[Route('/{id}', name: 'update', methods: ['PUT'])]
    public function update(Routine $routine, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($routine->getOwner() !== $this->getUser()) {
            return new JsonResponse(
                ['error' => 'You cannot access this routine'],
                Response::HTTP_FORBIDDEN
            );
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $routine->setName($data['name']);
        }
        if (isset($data['objective'])) {
            $routine->setObjective($data['objective']);
        }
        if (isset($data['days_week'])) {
            $routine->setDaysWeek((int)$data['days_week']);
        }
        if (isset($data['dispo_material'])) {
            $routine->setDispoMaterial($data['dispo_material']);
        }
        if (isset($data['active'])) {
            $routine->setActive($data['active']);
        }

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'data' => $this->serializeRoutine($routine),
        ]);
    }

    /**
     * Eliminar rutina
     */
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Routine $routine, EntityManagerInterface $entityManager): JsonResponse
    {
        if ($routine->getOwner() !== $this->getUser()) {
            return new JsonResponse(
                ['error' => 'You cannot access this routine'],
                Response::HTTP_FORBIDDEN
            );
        }

        $entityManager->remove($routine);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Agregar ejercicio a rutina
     */
    #[Route('/{id}/exercises', name: 'add_exercise', methods: ['POST'])]
    public function addExercise(
        Routine $routine,
        Request $request,
        EntityManagerInterface $entityManager,
        ExerciseRepository $exerciseRepository
    ): JsonResponse {
        if ($routine->getOwner() !== $this->getUser()) {
            return new JsonResponse(
                ['error' => 'You cannot access this routine'],
                Response::HTTP_FORBIDDEN
            );
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['exercise_id'])) {
            return new JsonResponse(
                ['error' => 'exercise_id is required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $exercise = $exerciseRepository->find($data['exercise_id']);

        if (!$exercise) {
            return new JsonResponse(
                ['error' => 'Exercise not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        if (!$routine->getExercises()->contains($exercise)) {
            $routine->addExercise($exercise);
            $entityManager->flush();
        }

        return new JsonResponse([
            'success' => true,
            'data' => $this->serializeRoutineDetail($routine),
        ]);
    }

    /**
     * Remover ejercicio de rutina
     */
    #[Route('/{id}/exercises/{exerciseId}', name: 'remove_exercise', methods: ['DELETE'])]
    public function removeExercise(
        Routine $routine,
        int $exerciseId,
        EntityManagerInterface $entityManager,
        ExerciseRepository $exerciseRepository
    ): JsonResponse {
        if ($routine->getOwner() !== $this->getUser()) {
            return new JsonResponse(
                ['error' => 'You cannot access this routine'],
                Response::HTTP_FORBIDDEN
            );
        }

        $exercise = $exerciseRepository->find($exerciseId);

        if (!$exercise) {
            return new JsonResponse(
                ['error' => 'Exercise not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        if ($routine->getExercises()->contains($exercise)) {
            $routine->removeExercise($exercise);
            $entityManager->flush();
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Helper para serializar rutina (básico)
     */
    private function serializeRoutine(Routine $routine): array
    {
        return [
            'id' => $routine->getId(),
            'name' => $routine->getName(),
            'objective' => $routine->getObjective(),
            'days_week' => $routine->getDaysWeek(),
            'dispo_material' => $routine->getDispoMaterial(),
            'active' => $routine->isActive(),
            'created_at' => $routine->getCreatedAt()->format('Y-m-d H:i:s'),
            'date_start' => $routine->getDateStart()->format('Y-m-d'),
            'date_end' => $routine->getDateEnd()?->format('Y-m-d'),
            'exercises_count' => $routine->getExercises()->count(),
        ];
    }

    /**
     * Helper para serializar rutina con detalles
     */
    private function serializeRoutineDetail(Routine $routine): array
    {
        return array_merge(
            $this->serializeRoutine($routine),
            [
                'exercises' => array_map(function (Exercise $exercise) {
                    return [
                        'id' => $exercise->getId(),
                        'name' => $exercise->getName(),
                        'muscular_group' => $exercise->getMuscularGroup(),
                        'difficulty' => $exercise->getDifficulty(),
                        'compound' => $exercise->isCompound(),
                    ];
                }, $routine->getExercises()->toArray()),
            ]
        );
    }
}
