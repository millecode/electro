<?php

namespace App\Repository;

use App\Entity\Reparation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reparation>
 */
class ReparationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reparation::class);
    }

    //Afficher la reparation correspond à une matricule et sont id
    public function findByMatriculeAndId(string $matricule, int $id): ?Reparation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.matricule = :matricule')
            ->andWhere('r.id = :id')
            ->setParameter('matricule', $matricule)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }


    // Compter le nombre de réparations effectuées par un utilisateur
    public function countByUser($user)
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    // Trouver les réparations d'un utilisateur selon le statut (par exemple, "en_cours" ou "termine")
    public function findByUserAndStatus($user, $status)
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->setParameter('user', $user);

        if ($status) {
            $qb->andWhere('r.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function findReparationsWithUserAndService(?string $phone = null, ?int $serviceId = null)
    {
        $qb = $this->createQueryBuilder('r')
            ->join('r.user', 'u')
            ->join('r.service', 's')
            ->addSelect('u', 's')
            ->orderBy('r.id', 'DESC');

        // Ajouter une condition pour le numéro de téléphone si fourni
        if ($phone) {
            $qb->andWhere('u.phone LIKE :phone')
                ->setParameter('phone', '%' . $phone . '%');
        }

        // Ajouter une condition pour le service si fourni
        if ($serviceId) {
            $qb->andWhere('s.id = :serviceId')
                ->setParameter('serviceId', $serviceId);
        }

        return $qb->getQuery();
    }


    //Statistique 

    public function getRepairStatistics(): array
    {
        return [
            'total' => $this->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->getQuery()
                ->getSingleScalarResult(),

            'in_progress' => $this->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.status_reparation = :in_progress')
                ->setParameter('in_progress', 'en cours')
                ->getQuery()
                ->getSingleScalarResult(),

            'completed' => $this->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->where('r.status_reparation  = :completed')
                ->setParameter('completed', 'Terminé')
                ->getQuery()
                ->getSingleScalarResult(),
        ];
    }

    public function getUserReparationStatistics(): array
    {
        return $this->createQueryBuilder('r')
            ->select('u.id AS user_id, u.nom AS user_nom,u.matricule as matricule, COUNT(r.id) AS repair_count')
            ->join('r.user', 'u') // Reliez la réparation à l'utilisateur
            ->groupBy('u.id') // Groupez par utilisateur
            ->orderBy('repair_count', 'DESC') // Trier par le nombre de réparations (ordre décroissant)
            ->getQuery()
            ->getResult();
    }

    //Liste de reparartion de l'user connecter
    public function findReparationsByUserWithFilters($user, ?string $status = null, ?string $service = null)
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user);

        if ($status !== null) {
            $statusBoolean = ($status) ? "Terminé" : "En cours";
            $qb->andWhere('r.status_reparation = :status')
                ->setParameter('status', $statusBoolean);
        }

        if (!empty($service)) {
            $qb->join('r.service', 's') // Jointure avec la table Service
                ->andWhere('s.titre LIKE :service')
                ->setParameter('service', '%' . $service . '%');
        }

        return $qb->getQuery();
    }


    //Dans Admin Liste des re


    //    /**
    //     * @return Reparation[] Returns an array of Reparation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Reparation
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
