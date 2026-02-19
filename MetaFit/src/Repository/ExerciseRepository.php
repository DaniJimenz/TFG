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
        return $this->createQueryBuilder('e')
            ->andWhere('e.muscular_group = :group')
            ->andWhere('e.difficulty = :difficulty')
            ->setParameter('group', $group)
            ->setParameter('difficulty', $difficulty)
            ->addSelect('RAND() as HIDDEN rand')
            ->orderBy('rand')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
