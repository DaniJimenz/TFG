<?php

namespace App\Repository;

use App\Entity\Exercise;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Exercise>
 */
class ExerciseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Exercise::class);
    }

    /**
     * Busca ejercicios aleatorios filtrados por grupo muscular y dificultad
     */
    public function findRandomByGroupAndDifficulty(string $group, string $difficulty, int $limit): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.muscular_group = :group')
            ->andWhere('e.difficulty = :difficulty')
            ->setParameter('group', $group)
            ->setParameter('difficulty', $difficulty)
            ->setMaxResults($limit);

        $results = $qb->getQuery()->getResult();
        shuffle($results);
        return $results;
    }

    /**
     * Busca ejercicios para un día de rutina, priorizando compuestos si se indica
     */
    public function findForRoutineDay(string $group, string $difficulty, int $limit, bool $prioritizeCompound = false): array
    {
        $results = $this->createQueryBuilder('e')
            ->andWhere('e.muscular_group = :group')
            ->andWhere('e.difficulty = :difficulty')
            ->setParameter('group', $group)
            ->setParameter('difficulty', $difficulty)
            ->setMaxResults($limit * 3)
            ->getQuery()
            ->getResult();

        if (empty($results)) {
            return [];
        }

        if ($prioritizeCompound) {
            usort($results, fn($a, $b) => ($b->isCompound() ? 1 : 0) - ($a->isCompound() ? 1 : 0));
            return array_slice($results, 0, $limit);
        }

        shuffle($results);
        return array_slice($results, 0, $limit);
    }
}
