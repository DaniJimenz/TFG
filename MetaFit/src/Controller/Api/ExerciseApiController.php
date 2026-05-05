<?php

namespace App\Controller\Api;

use App\Repository\ExerciseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/exercises', name: 'api_exercise_')]
#[IsGranted('ROLE_USER')]
class ExerciseApiController extends AbstractController
{
    /**
     * Listar todos los ejercicios disponibles (con paginación)
     */
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(ExerciseRepository $exerciseRepository, Request $request): JsonResponse
    {
        $page = max(1, (int)$request->query->get('page', 1));
        $perPage = (int)$request->query->get('per_page', 20);
        $muscularGroup = $request->query->get('muscular_group');
        $difficulty = $request->query->get('difficulty');

        $query = $exerciseRepository->createQueryBuilder('e');

        if ($muscularGroup) {
            $query->where('e.muscular_group = :group')
                ->setParameter('group', $muscularGroup);
        }

        if ($difficulty) {
            $query->andWhere('e.difficulty = :difficulty')
                ->setParameter('difficulty', $difficulty);
        }

        $total = count($query->getQuery()->getResult());
        $exercises = $query
            ->orderBy('e.name', 'ASC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return new JsonResponse([
            'success' => true,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
            ],
            'data' => array_map(fn($exercise) => $this->serializeExercise($exercise), $exercises),
        ]);
    }

    /**
     * Obtener detalle de un ejercicio
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id, ExerciseRepository $exerciseRepository): JsonResponse
    {
        $exercise = $exerciseRepository->find($id);

        if (!$exercise) {
            return new JsonResponse(
                ['error' => 'Exercise not found'],
                Response::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse([
            'success' => true,
            'data' => $this->serializeExerciseDetail($exercise),
        ]);
    }

    /**
     * Listar ejercicios por grupo muscular
     */
    #[Route('/by-group/{group}', name: 'by_group', methods: ['GET'])]
    public function byGroup(string $group, ExerciseRepository $exerciseRepository): JsonResponse
    {
        $exercises = $exerciseRepository->findBy(['muscular_group' => $group]);

        return new JsonResponse([
            'success' => true,
            'muscular_group' => $group,
            'data' => array_map(fn($exercise) => $this->serializeExercise($exercise), $exercises),
        ]);
    }

    /**
     * Helper para serializar ejercicio (básico)
     */
    private function serializeExercise($exercise): array
    {
        return [
            'id' => $exercise->getId(),
            'name' => $exercise->getName(),
            'muscular_group' => $exercise->getMuscularGroup(),
            'difficulty' => $exercise->getDifficulty(),
            'compound' => $exercise->isCompound(),
            'url_image' => $exercise->getUrlImage(),
        ];
    }

    /**
     * Helper para serializar ejercicio con detalles
     */
    private function serializeExerciseDetail($exercise): array
    {
        return [
            'id' => $exercise->getId(),
            'name' => $exercise->getName(),
            'muscular_group' => $exercise->getMuscularGroup(),
            'description' => $exercise->getDescription(),
            'technique' => $exercise->getTechnique(),
            'difficulty' => $exercise->getDifficulty(),
            'compound' => $exercise->isCompound(),
            'necessary_material' => $exercise->getNecessaryMaterial(),
            'url_image' => $exercise->getUrlImage(),
            'url_video' => $exercise->getUrlVideo(),
        ];
    }
}
