<?php

namespace App\Repository;

use Doctrine\ORM\Query;
use App\Entity\Servicess;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Servicess>
 */
class ServicessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Servicess::class);
    }


    //Afficher la services correspond à une matricule et sont id
    public function findByMatriculeAndId(string $matricule, int $id): ?Servicess
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.matricule = :matricule')
            ->andWhere('s.id = :id')
            ->setParameter('matricule', $matricule)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //Liste des 4 premiers services publié
    public function findFirstPublishedServices(int $limit = 4): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status = 1') // On sélectionne uniquement les services publiés
            ->orderBy('s.date', 'DESC') // Trier par date décroissante
            ->setMaxResults($limit) // Limiter à 4 résultats
            ->getQuery()
            ->getResult();
    }

    //Liste des services active dans la page service
    public function findAllPublishedServices(): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('s')
            ->where('s.status = 1') // Filtrer uniquement les services publiés
            ->andWhere('s.service_supp = 0')  //Afficher uniquement les servcices qui n'est sont pas supprimer
            ->orderBy('s.id', 'DESC'); // Trier par ID décroissant
    }



    //Liste des services active dans la page service
    public function findAllServicesNonsupp(): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('s')
            ->Where('s.service_supp = :stat')  //Afficher uniquement les servcices qui n'est sont pas supprimer
            ->setParameter(':stat', 0)
            ->orderBy('s.id', 'DESC'); // Trier par ID décroissant
    }


    public function getServicesAll(): Query
    {
        return $this->createQueryBuilder('s')
            ->select('s') // Sélectionner seulement les service 
            ->getQuery();
    }



    public function findServicesWithRepairCount(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s AS service', 'COUNT(r.id) AS repairCount')
            ->leftJoin('s.reparations', 'r') // Relation entre services et réparations
            ->where('s.service_supp = :status')
            ->setParameter(':status', false)
            ->groupBy('s.id')               // Grouper par service
            ->orderBy('repairCount', 'DESC') // Trier par nombre de réparations, décroissant
            ->getQuery()
            ->getResult();
    }



    //Dans admin liste des services qui sont un service_supp = 0
    public function findServiceListe(): array
    {
        return $this->createQueryBuilder('s')
            ->addSelect('s') // Inclure la catégorie dans les résultats
            ->where('c.ServiceSupp= 0') // Produits publiés
            ->orderBy('p.date', 'DESC') // T     
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Servicess[] Returns an array of Servicess objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Servicess
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
