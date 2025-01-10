<?php

namespace App\Repository;

use App\Entity\Actualiter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Actualiter>
 */
class ActualiterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Actualiter::class);
    }

    public function findBySearch(?string $search)
    {
        $qb = $this->createQueryBuilder('a');

        if (!empty($search)) {
            $qb->where('a.titre LIKE :search')
                ->setParameter('search', "%{$search}%");
        }

        $qb->orderBy('a.createdAt', 'DESC');

        return $qb->getQuery();
    }


    /**
     * Récupère les 3 dernières actualités avec le statut = 1
     */
    public function findLatestPublished(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.status = :status')
            ->setParameter('status', true)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Actualiter[] Returns an array of Actualiter objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Actualiter
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
