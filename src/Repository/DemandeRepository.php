<?php

namespace App\Repository;

use App\Entity\Demande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Demande>
 */
class DemandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Demande::class);
    }

    //Afficher la demande correspond à une matricule et sont id
    public function findByMatriculeAndId(string $matricule, int $id): ?Demande
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.matricule = :matricule')
            ->andWhere('d.id = :id')
            ->setParameter('matricule', $matricule)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }


    public function findDemandeWithFilters(?string $search = null, ?int $serviceId = null)
    {
        $qb = $this->createQueryBuilder('d')
            ->join('d.service', 's') // Relation avec le service
            ->addSelect('s')
            ->orderBy('d.date', 'DESC'); // Tri par date décroissante

        if ($search) {
            $qb->andWhere('d.nom LIKE :search OR d.phone LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($serviceId) {
            $qb->andWhere('s.id = :serviceId')
                ->setParameter('serviceId', $serviceId);
        }

        return $qb->getQuery()->getResult();
    }



    //    /**
    //     * @return Demande[] Returns an array of Demande objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Demande
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
