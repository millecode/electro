<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    //Liste des utilisateur avec nombre de commande et nombre de reparation effectuer
    public function findUsersWithFilters(array $filters, PaginatorInterface $paginator, int $page = 1, int $limit = 10)
    {
        $qb = $this->createQueryBuilder('u')
            ->select('u.id, u.nom, u.email, u.phone, u.statuscompte, u.matricule, u.fonction')
            ->addSelect('COUNT(DISTINCT c.id) as commandesCount')
            ->addSelect('COUNT(DISTINCT r.id) as reparationsCount')
            ->leftJoin('u.commandes', 'c')
            ->leftJoin('u.reparations', 'r')
            ->groupBy('u.id');

        // Filtre par nom
        if (!empty($filters['nom'])) {
            $qb->andWhere('u.nom LIKE :nom')
                ->setParameter('nom', '%' . $filters['nom'] . '%');
        }

        // Filtre par téléphone
        if (!empty($filters['phone'])) {
            $qb->andWhere('u.phone LIKE :phone')
                ->setParameter('phone', '%' . $filters['phone'] . '%');
        }

        // Filtre par rôle
        if (!empty($filters['fonction'])) {
            $qb->andWhere('u.fonction = :fonction')
                ->setParameter('fonction', $filters['fonction']);
        }

        // Retourne la pagination
        return $paginator->paginate(
            $qb->getQuery(),
            $page,
            $limit
        );
    }



    //Statistique pour afficher les nombres de users avec leur nombre de reparation qu'il ont effectuer par ordre descroissante
    public function getUserReparationStats(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.id, u.nom,u.matricule, COUNT(r.id) AS reparationCount')
            ->leftJoin('u.reparations', 'r')
            ->groupBy('u.id')
            ->orderBy('reparationCount', 'DESC')
            ->setMaxResults(3) // Limite les résultats aux 3 premiers
            ->getQuery()
            ->getResult();
    }


    public function findemail($value): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getResult()
        ;
    }
    public function findAllWhitePaginationAdmin(): Query
    {
        return $this->createQueryBuilder('u')
            ->getQuery()
        ;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    //Afficher la commande correspond à une matricule et sont id
    public function findByMatriculeAndId(string $matricule, int $id): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.matricule = :matricule')
            ->andWhere('u.id = :id')
            ->setParameter('matricule', $matricule)
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
