<?php

namespace App\Repository;

use App\Entity\Finance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Finance>
 */
class FinanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Finance::class);
    }


    public function countByType(string $type): int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id) as count')
            ->where('f.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }



    public function findByFilters(?string $type = null, ?string $numero = null)
    {
        $queryBuilder = $this->createQueryBuilder('f');

        if ($type) {
            $queryBuilder->andWhere('f.type = :type')
                ->setParameter('type', $type);
        }

        if ($numero) {
            $queryBuilder->andWhere('f.numero LIKE :numero')
                ->setParameter('numero', '%' . $numero . '%');
        }

        return $queryBuilder->getQuery()->getResult();
    }


    public function getPrixTotal(): float
    {
        return $this->createQueryBuilder('f')
            ->select('SUM(f.prix) as total')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }

    public function getPrixByType(string $type): float
    {
        return $this->createQueryBuilder('f')
            ->select('SUM(f.prix) as total')
            ->where('f.type = :type')
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }


    //    /**
    //     * @return Finance[] Returns an array of Finance objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Finance
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
