<?php

namespace App\Repository;

use Doctrine\ORM\Query;
use App\Entity\Contacts;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Contacts>
 */
class ContactsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contacts::class);
    }

    public function findContactsByName(string $searchTerm): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->where('c.nom LIKE :searchTerm')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->orderBy('c.nom', 'ASC');
    }

    //Statistique 

    public function getContactStatistics(): array
    {
        return [
            'total' => $this->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->getQuery()
                ->getSingleScalarResult(),

            'confirmed' => $this->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.statusemail = :confirmed')
                ->setParameter('confirmed', true)
                ->getQuery()
                ->getSingleScalarResult(),

            'not_confirmed' => $this->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.statusemail = :not_confirmed')
                ->setParameter('not_confirmed', false)
                ->getQuery()
                ->getSingleScalarResult(),
        ];
    }

    //    /**
    //     * @return Contacts[] Returns an array of Contacts objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Contacts
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
