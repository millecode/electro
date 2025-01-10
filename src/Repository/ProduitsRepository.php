<?php

namespace App\Repository;

use Doctrine\ORM\Query;
use App\Entity\Produits;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Produits>
 */
class ProduitsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produits::class);
    }

    //Afficher la commande correspond à une matricule et sont id
    public function findByMatriculeAndId(string $matricule, int $id): ?Produits
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.matricule = :matricule')
            ->andWhere('p.id = :id')
            ->setParameter('matricule', $matricule)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //Liste de derniers produits publier
    public function findLastProducts(int $limit): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c') // Inclure la catégorie dans les résultats
            ->where('p.status = 1') // Produits publiés
            ->andwhere('p.produit_supp = 1') // Produits publiés
            ->orderBy('p.date', 'DESC') // Trier par date décroissante
            ->setMaxResults($limit) // Limiter à 8 résultats
            ->getQuery()
            ->getResult();
    }

    //Liste des produits qui à le meme categories d'un produits
    public function findRelatedProducts(int $categorieId, int $currentProductId, int $limit = 4): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.categorie = :categorieId')
            ->andWhere('p.id != :currentProductId')
            ->setParameter('categorieId', $categorieId)
            ->setParameter('currentProductId', $currentProductId)
            ->setMaxResults($limit)
            ->orderBy('p.date', 'DESC') // Trier par date décroissante
            ->getQuery()
            ->getResult();
    }

    public function findWithSearch(?string $titre, ?int $categorieId)
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c')
            ->andWhere('p.produit_supp = :produit_supp') // Condition pour afficher uniquement les produits non supprimés
            ->setParameter('produit_supp', true);
        if (!empty($titre)) {
            $qb->andWhere('p.titre LIKE :titre')
                ->setParameter('titre', '%' . $titre . '%');
        }

        if (!empty($categorieId)) {
            $qb->andWhere('c.id = :categorieId')
                ->setParameter('categorieId', $categorieId);
        }

        return $qb->getQuery();
    }

    //statistique Admin 

    public function getProductStatistics(): array
    {
        return [
            'total' => $this->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->getQuery()
                ->getSingleScalarResult(),

            'active' => $this->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->where('p.status = :active')
                ->setParameter('active', true)
                ->getQuery()
                ->getSingleScalarResult(),

            'disabled' => $this->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->where('p.status = :disabled')
                ->setParameter('disabled', false)
                ->getQuery()
                ->getSingleScalarResult(),
        ];
    }

    public function findWithSearchlisteproduits(?string $titre, ?string $categorieId)
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.categorie', 'c')
            ->addSelect('c')
            ->andWhere('p.status = :status')
            ->setParameter('status', true);

        if (!empty($titre)) {
            $qb->andWhere('p.titre LIKE :titre')
                ->setParameter('titre', '%' . $titre . '%');
        }

        if (!empty($categorieId)) {
            $qb->andWhere('c.id = :categorieId')
                ->setParameter('categorieId', $categorieId);
        }

        return $qb->getQuery();
    }


    // public function findAllWhitePaginationAdmin(): Query
    // {
    //     return $this->createQueryBuilder('p')
    //         ->getQuery()
    //     ;
    // }

    //    /**
    //     * @return Produits[] Returns an array of Produits objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Produits
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
