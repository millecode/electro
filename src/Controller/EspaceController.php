<?php

namespace App\Controller;

use App\Repository\LogosRepository;
use App\Repository\CommandeRepository;
use App\Repository\CategorieRepository;
use App\Repository\ReparationRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EspaceController extends AbstractController
{
    private $categorieRepo;
    private $logoRepo;

    public function __construct(CategorieRepository $categorieRepo, LogosRepository $logoRepo)
    {
        $this->categorieRepo = $categorieRepo;
        $this->logoRepo = $logoRepo;
    }

    #[Route('/mes-commandes', name: 'mes_commandes')]
    #[IsGranted('ROLE_USER')] // Sécurise la page pour les utilisateurs connectés
    public function EspaceClient(Request $request, CommandeRepository $commandeRepo, ReparationRepository $reparationRepo, PaginatorInterface $paginator): Response
    {
        $user = $this->getUser();

        // Statistiques
        $commandeCount = $commandeRepo->countByUser($user);
        $reparationCount = $reparationRepo->countByUser($user);

        // Recherche et pagination des réparations
        $statusFilter = $request->query->get('status');
        $queryReparations = $reparationRepo->findByUserAndStatus($user, $statusFilter);

        $paginationReparations = $paginator->paginate(
            $queryReparations,
            $request->query->getInt('page', 1),
            10
        );

        // Recherche et pagination des commandes
        $queryCommandes = $commandeRepo->findByUserAndStatus($user, $statusFilter);

        $paginationCommandes = $paginator->paginate(
            $queryCommandes,
            $request->query->getInt('page', 1),
            10
        );

        //Menu Categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('espace/espace_client.html.twig', [
            'categoriess' => $categoriess,
            'commandeCount' => $commandeCount,
            'reparationCount' => $reparationCount,
            'paginationReparations' => $paginationReparations,
            'paginationCommandes' => $paginationCommandes,
            "logo" => $lastActiveLogos
        ]);
    }


    #[Route('/mes-reparations', name: 'mes_reparations')]
    #[IsGranted('ROLE_USER')]
    public function index(ReparationRepository $reparationRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $user = $this->getUser();

        // Récupération des critères de recherche
        $searchStatus = $request->query->get('status'); // "en_cours" ou "terminee"
        $searchService = $request->query->get('service'); // Nom du service

        // Obtenir la requête filtrée depuis le repository
        $query = $reparationRepository->findReparationsByUserWithFilters($user, $searchStatus, $searchService);

        // Pagination
        $reparations = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        //Menu Categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('espace/mesReparations.html.twig', [
            'categoriess' => $categoriess,
            'reparations' => $reparations,
            "logo" => $lastActiveLogos

        ]);
    }
}
