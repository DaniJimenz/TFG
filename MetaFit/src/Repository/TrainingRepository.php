<?php

namespace App\Repository;

use App\Entity\Training;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Training>
 */
class TrainingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Training::class);
    }

    //    /**
    //     * @return Training[] Returns an array of Training objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    /**
     * Find trainings after a specific date for a user
     * @return Training[]
     */
    public function findTrainingsAfterDate($user, \DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.appUser = :user')
            ->andWhere('t.date >= :date')
            ->setParameter('user', $user)
            ->setParameter('date', $date)
            ->orderBy('t.date', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Find trainings by user and exercise
     * @return Training[]
     */
    public function findByUserAndExercise($user, $exercise): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.appUser = :user')
            ->andWhere('t.exercise = :exercise')
            ->setParameter('user', $user)
            ->setParameter('exercise', $exercise)
            ->orderBy('t.date', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Get PR (Personal Record) for a user and exercise
     */
    public function getPRByUserAndExercise($user, $exercise): ?float
    {
        $result = $this->createQueryBuilder('t')
            ->select('MAX(t.weight) as max_weight')
            ->andWhere('t.appUser = :user')
            ->andWhere('t.exercise = :exercise')
            ->setParameter('user', $user)
            ->setParameter('exercise', $exercise)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $result['max_weight'] ?? null;
    }
}
