<?php

namespace App\Controller;

use App\Entity\Produits;
use App\Service\CartService;
use App\Repository\LogosRepository;
use App\Repository\ProduitsRepository;
use App\Repository\CategorieRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProduitsController extends AbstractController
{
    private $categorieRepo;
    private $products = [];
    private $logoRepo;


    public function __construct(CategorieRepository $categorieRepo, LogosRepository $logoRepo)
    {
        $this->categorieRepo = $categorieRepo;
        $this->logoRepo = $logoRepo;
    }


    #[Route('/produits', name: 'app_produits')]
    public function index(): Response
    {
        //Menu Categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();



        return $this->render('produits/index.html.twig', [
            'categoriess' => $categoriess,
            "logo" => $lastActiveLogos
        ]);
    }



    //voir produit
    #[Route('/produits/voir/{id}/{slug}', name: 'voir_produit')]
    public function show(Produits $produit, ProduitsRepository $produitRepo): Response
    {
        // Récupérer les produits de la même catégorie
        $relatedProducts = [];

        if ($produit->getCategorie()) {
            $relatedProducts = $produitRepo->findRelatedProducts(
                $produit->getCategorie()->getId(),
                $produit->getId()
            );
        }

        if ($produit->isProduitSupp() == 0) {
            $this->addFlash('danger', "Cette produits n'est plus disponible dans notre magasin.");
        }
        //Menu Categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('produits/Voirproduits.html.twig', [
            'produit' => $produit,
            'relatedProducts' => $relatedProducts,
            'categoriess' => $categoriess,
            "logo" => $lastActiveLogos


        ]);
    }



    #[Route('/liste/produits', name: 'liste_categorie')]
    public function listeCategories(
        ProduitsRepository $produitsRepository,
        CategorieRepository $categorieRepository,
        PaginatorInterface $paginator,
        Request $request
    ) {
        // Récupérer les filtres de la requête
        $titre = $request->query->get('titre', '');
        $categorieId = $request->query->get('categorie', '');

        // Récupérer toutes les catégories pour alimenter le menu déroulant
        $allCategories = $categorieRepository->findAll();

        // Récupérer les produits filtrés par titre et catégorie
        $query = $produitsRepository->findWithSearchlisteproduits($titre, $categorieId);

        // Paginer les résultats
        $produits = $paginator->paginate(
            $query, // Requête Doctrine ou QueryBuilder
            $request->query->getInt('page', 1), // Numéro de la page
            16 // Nombre d'éléments par page
        );

        //Menu Categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();
        return $this->render('produits/listeproduits.html.twig', [
            'produits' => $produits,
            'allCategories' => $allCategories,
            'titre' => $titre,
            'selectedCategorieId' => $categorieId,
            'categoriess' => $categoriess,
            "logo" => $lastActiveLogos


        ]);
    }
}
