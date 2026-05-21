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
        
        // Usar PHP para aleatorizar en lugar de RAND() de base de datos
        $results = $qb->getQuery()->getResult();
        shuffle($results);
        return $results;
    }
}
