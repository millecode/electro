<?php

namespace App\Repository;

use App\Entity\Categorie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Categorie>
 */
class CategorieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Categorie::class);
    }


    /**
     * Récupère les catégories avec le nombre de produits associés, sauf la catégorie "Aucune".
     */
    public function getCategoriesWithProductCountQuery(): Query
    {
        return $this->createQueryBuilder('c')  // Alias de catégorie
            ->select('c') // Sélectionner seulement la catégorie
            ->where('c.categorie != :aucune') // Exclure la catégorie "Aucune"
            ->setParameter('aucune', 'Aucune') // Remplacer 'Aucune' par le nom exact de votre catégorie
            ->getQuery();
    }

    public function getCategorieAucune(): ?Categorie
    {
        return $this->createQueryBuilder('c') // Alias de catégorie
            ->where('c.categorie = :aucune') // Chercher la catégorie avec le nom "Aucune"
            ->setParameter('aucune', 'Aucune')
            ->getQuery()
            ->getOneOrNullResult(); // Retourner une seule catégorie ou null si elle n'existe pas
    }



    //    /**
    //     * @return Categorie[] Returns an array of Categorie objects
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

    //    public function findOneBySomeField($value): ?Categorie
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
