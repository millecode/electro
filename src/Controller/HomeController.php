<?php

namespace App\Controller;

use App\Repository\ActualiterRepository;
use App\Repository\ProduitsRepository;
use App\Repository\CategorieRepository;
use App\Repository\LogosRepository;
use App\Repository\ServicessRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    private $categorieRepo;
    private $logoRepo;

    public function __construct(CategorieRepository $categorieRepo, LogosRepository $logoRepo)
    {
        $this->categorieRepo = $categorieRepo;
        $this->logoRepo = $logoRepo;
    }


    #[Route('/', name: 'home')]
    public function index(ProduitsRepository $produitRepository, ServicessRepository $serviceRepo, ActualiterRepository $actualiterRepository): Response
    {




        // Récupérer les 8 derniers produits ajoutés
        $dernierProduits = $produitRepository->findLastProducts(6);

        // Récupérer les 4 premiers services publiés
        $premiersServices = $serviceRepo->findFirstPublishedServices(4);

        // Récupérer les 3 dernières actualités publiées
        $latestActualiters = $actualiterRepository->findLatestPublished();

        //Menu categorie
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('home/index.html.twig', [
            'categoriess' => $categoriess,
            'premiersServices' => $premiersServices,
            'dernierProduits' => $dernierProduits,
            'latestActualiters' => $latestActualiters,
            "logo" => $lastActiveLogos

        ]);
    }
}
