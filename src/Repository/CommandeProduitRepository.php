<?php
// src/Repository/CommandeProduitRepository.php

namespace App\Repository;

use App\Entity\CommandeProduit;
use App\Entity\CommandeProduits;
use App\Entity\Produits;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommandeProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommandeProduits::class);
    }

    public function countTotalCommandeByProduit(Produits $produit): int
    {
        $result = $this->createQueryBuilder('cp')
            ->select('SUM(cp.quantity) as total')
            ->where('cp.produit = :produit')
            ->setParameter('produit', $produit)
            ->getQuery()
            ->getSingleScalarResult();

        // Retourne 0 si le résultat est null
        return $result !== null ? (int) $result : 0;
    }


    // Ajoutez ici des méthodes personnalisées si nécessaire
}
