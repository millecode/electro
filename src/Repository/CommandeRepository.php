<?php

namespace App\Repository;

use Doctrine\ORM\Query;
use App\Entity\Commande;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    //Afficher la commande correspond à une matricule et sont id
    public function findByMatriculeAndId(string $matricule, int $id): ?Commande
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.Matricule = :matricule')
            ->andWhere('c.id = :id')
            ->setParameter('matricule', $matricule)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }


    // Compter le nombre total de commandes par utilisateur
    public function countByUser($user)
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    // Trouver les commandes de l'utilisateur par statut
    public function findByUserAndStatus($user, $status)
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->setParameter('user', $user);

        if ($status) {
            $qb->andWhere('c.status = :status')
                ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    //Statistique pour afficher les nombres de users avec leur nombre de commande qu'il ont effectuer par ordre descroissante
    public function getUserOrderStatistics(): array
    {
        return $this->createQueryBuilder('c') // Alias pour la table des commandes
            ->select('u.id AS user_id, u.matricule as user_matricule, u.nom AS user_nom, COUNT(c.id) AS order_count') // Champs sélectionnés
            ->join('c.user', 'u') // Assurer que 'user' est bien la propriété dans Commande
            ->groupBy('u.id') // Grouper par utilisateur
            ->orderBy('order_count', 'DESC') // Trier par nombre de commandes décroissant
            ->setMaxResults(3) // Limite les résultats aux 3 premiers
            ->getQuery()
            ->getResult(); // Retourner le résultat en tableau
    }


    public function searchCommandes(array $criteria)
    {
        $qb = $this->createQueryBuilder('c');

        // Filtrer par terme de recherche (produit, utilisateur, etc.)
        if (isset($criteria['searchTerm']) && $criteria['searchTerm']) {
            $qb->andWhere('c.produit LIKE :searchTerm OR c.user.name LIKE :searchTerm OR c.user.phone LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . $criteria['searchTerm'] . '%');
        }

        // Filtrer par statut si un statut est sélectionné
        if (isset($criteria['status']) && $criteria['status'] !== '') {
            $qb->andWhere('c.status = :status')
                ->setParameter('status', $criteria['status']);
        }

        return $qb->getQuery()->getResult();
    }

    //Statistique Admin

    public function getOrderStatistics(): array
    {
        return [
            'total' => $this->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->getQuery()
                ->getSingleScalarResult(),

            'in_progress' => $this->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.status = :in_progress')
                ->setParameter('in_progress', false)
                ->getQuery()
                ->getSingleScalarResult(),

            'completed' => $this->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.status = :completed')
                ->setParameter('completed', true)
                ->getQuery()
                ->getSingleScalarResult(),
        ];
    }

    //    /**
    //     * @return Commande[] Returns an array of Commande objects
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

    //    public function findOneBySomeField($value): ?Commande
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
