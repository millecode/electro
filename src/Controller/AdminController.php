<?php

namespace App\Controller;

use TCPDF;
use DateTime;
use App\Entity\User;
use App\Form\UserType;
use DateTimeImmutable;
use App\Entity\Demande;
use App\Entity\Finance;
use App\Entity\Commande;
use App\Entity\Contacts;
use App\Entity\Produits;
use App\Entity\Categorie;
use App\Entity\Servicess;
use App\Entity\Actualiter;
use App\Entity\Coordonner;
use App\Entity\Logos;
use App\Entity\Reparation;
use App\Form\ProduitsType;
use App\Form\CategorieType;
use App\Form\ServicessType;
use App\Form\ActualiterType;
use App\Form\CoordonnerType;
use App\Form\ReparationType;
use App\Entity\MethodePaiement;
use App\Form\LogosType;
use App\Service\MailjetService;
use App\Form\MethodePaimentType;
use App\Repository\UserRepository;
use App\Repository\DemandeRepository;
use App\Repository\FinanceRepository;
use App\Repository\CommandeRepository;
use App\Repository\ContactsRepository;
use App\Repository\ProduitsRepository;
use App\Repository\CategorieRepository;
use App\Repository\ServicessRepository;
use App\Repository\ActualiterRepository;
use App\Repository\CoordonnerRepository;
use App\Repository\ReparationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Repository\CommandeProduitRepository;
use App\Repository\LogosRepository;
use App\Repository\MethodePaiementRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminController extends AbstractController
{
    private $categorieRepo;
    private $logoRepo;

    public function __construct(CategorieRepository $categorieRepo, LogosRepository $logoRepo)
    {
        $this->categorieRepo = $categorieRepo;
        $this->logoRepo = $logoRepo;
    }



    #[Route('/admin', name: 'admin')]
    public function admin(ProduitsRepository $repoP, CommandeRepository $repocommande, ReparationRepository $repoR, ContactsRepository $repoContact, Request $request, CommandeRepository $repoC, PaginatorInterface $paginator): Response
    {

        //Statistiques

        $productStats = $repoP->getProductStatistics();
        $orderStats = $repocommande->getOrderStatistics();
        $repairStats = $repoR->getRepairStatistics();
        $contactStats = $repoContact->getContactStatistics();


        // Récupération des filtres de recherche
        $searchTerm = $request->query->get('search', ''); // Recherche produit, utilisateur, ou téléphone
        $status = $request->query->get('status');         // Statut de la commande
        $userName = $request->query->get('userName');     // Nom de l'utilisateur

        // Créer un tableau pour les critères de filtrage
        $criteria = [];

        // Si un terme de recherche est fourni, ajouter au critère
        if ($searchTerm) {
            $criteria['searchTerm'] = $searchTerm;
        }

        // Si un statut est fourni (différent de ""), ajouter au critère
        if ($status !== '') {
            $criteria['status'] = $status;
        }

        // Récupérer les commandes avec les filtres via le repository
        $queryBuilder = $repoC->searchCommandes($criteria);

        // Paginer les résultats avec KnpPaginator
        $commandes = $paginator->paginate(
            $queryBuilder, // QueryBuilder
            $request->query->getInt('page', 1), // Page actuelle
            20 // Nombre de résultats par page
        );

        //Menu Categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();


        return $this->render('admin/admin.html.twig', [
            //statistique
            'productStats' => $productStats,
            'orderStats' => $orderStats,
            'repairStats' => $repairStats,
            'contactStats' => $contactStats,
            'categoriess' => $categoriess,
            'commandes' => $commandes,
            'searchTerm' => $searchTerm,
            'statusFilter' => $status,
            "logo" => $lastActiveLogos

        ]);
    }




    //Suppression d'une commande
    #[Route('/commande/delete/{id}', name: 'commande_delete', methods: ['POST', 'DELETE'])]
    public function deleteCommande(int $id, CommandeRepository $repoC, EntityManagerInterface $manager): Response
    {
        $user = $this->getUser();


        $commande = $repoC->find($id);
        if ($commande->isStatus()) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN'); // Vérifie que seul un administrateur peut supprimer une commande.
        }

        if (!$commande) {
            $this->addFlash('danger', 'Commande introuvable.');
            return $this->redirectToRoute('admin'); // Redirection vers la liste des commandes.
        }

        // Récupération des produits associés à la commande
        $commandeProduits = $commande->getCommandeProduits(); // Assurez-vous que cette méthode existe dans l'entité Commande.

        // Suppression explicite des CommandeProduits associés
        foreach ($commandeProduits as $commandeProduit) {
            $manager->remove($commandeProduit);
        }

        // Suppression de la commande
        $manager->remove($commande);



        if ($user->getRoles() == "ROLE_ADMIN" || $user->getRoles() == "ROLE_EMPLOYER") {
            $manager->flush();
            $this->addFlash('success', 'La commande a été supprimée avec succès.');
            return $this->redirectToRoute('admin'); // Redirection vers la liste des commandes après suppression.
        } else {
            $manager->flush();
            $this->addFlash('success', 'La commande a été annuler avec succès.');
            return $this->redirectToRoute('mes_commandes'); // Redirection vers la liste des commandes après suppression.
        }
    }





    #[Route('/commande/update-status/{id}', name: 'update_commande_status', methods: ['POST'])]
    public function updateCommandeStatus(Request $request, Commande $commande, EntityManagerInterface $manager): Response
    {


        // Récupérer le nouveau statut depuis le formulaire
        $status = $request->request->get('status');

        if ($status !== null && in_array($status, [0, 1])) {
            $commande->setStatus($status);

            // Si le statut passe à Terminé (1)
            if ($status == 1) {
                // Rechercher dans Finance si une ligne existe pour ce numéro de commande
                $financeRepo = $manager->getRepository(Finance::class);
                $financeEntry = $financeRepo->findOneBy(['numero' => $commande->getMatricule(), 'type' => 'Produit']);

                if (!$financeEntry) {
                    // Si aucune entrée n'existe, en créer une nouvelle Finance
                    $financeEntry = new Finance();
                    $financeEntry->setType('Produit');
                    $financeEntry->setIdCommande($commande->getId());
                    $financeEntry->setNumero($commande->getMatricule());
                    $financeEntry->setPhone($commande->getUser()->getPhone());
                    $financeEntry->setNom($commande->getUser()->getNom());
                    $financeEntry->setPrix($commande->getPrixtotal());
                    $financeEntry->setCreatedAt(new \DateTimeImmutable());
                    $manager->persist($financeEntry);
                } else {
                    // Mettre à jour l'entrée existante
                    $financeEntry->setPhone($commande->getUser()->getPhone());
                    $financeEntry->setNom($commande->getUser()->getNom());
                }
            }

            $manager->flush();

            // Retourner une réponse avec un message flash
            return $this->json([
                'message' => 'Le statut de la commande a été mis à jour.',
                'status' => $status
            ]);
        }

        return $this->json([
            'message' => 'Erreur dans la mise à jour du statut.',
        ], 400);
    }




































    //Gestions des categories
    #[Route('/admin/categorie', name: 'admin_categorie')]
    #[Route('/admin/categorie/modification/{id}', name: 'admin_categorie_modif')]
    public function adminCategorie(PaginatorInterface $paginator, Categorie $categori = null, CategorieRepository $repoC, Request $request, EntityManagerInterface $manager): Response
    {

        // Vérification du rôle de l'utilisateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        //Ajout et Modification d'une categorie
        if (!$categori) {
            $categori = new Categorie;
        }
        //Lister des catégories avec le nombre de produits avec paginations
        $categories = $paginator->paginate(
            $repoC->getCategoriesWithProductCountQuery(), // La query à paginer
            $request->query->getInt('page', 1), // Le numéro de la page (1 par défaut)
            10 // Nombre d'éléments par page
        );

        foreach ($categories as $categorie) {
            $productCount = count($categorie->getProduits());  // Compter les produits associés
            $categorie->productCount = $productCount;  // Ajouter le comptage des produits à la catégorie
        }




        $form = $this->createForm(CategorieType::class, $categori);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $slugCategorie = preg_replace('/[^a-z0-9]+/', '-', trim(strtolower($categori->getCategorie())));
            $categori->setSlug($slugCategorie);
            $manager->persist($categori);

            // Vérifier si c'est un ajout ou une modification
            if ($categori->getId()) {
                $manager->flush();
                $this->addFlash("warning", "La catégorie a bien été modifiée.");
            } else {
                $manager->flush();
                $this->addFlash("success", "La catégorie a bien été ajoutée.");
            }

            return $this->redirectToRoute('admin_categorie');
        }


        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('admin/gestionsCategorie.html.twig', [
            'categoriess' => $categoriess,
            'form' => $form->createView(),
            "categories" => $categories,
            "logo" => $lastActiveLogos

        ]);
    }


    //Supprimer un categorie
    #[Route('/admin/categorie/supp/{id}', name: 'admin_categorie_supp')]
    public function SuppressionCategorie(Categorie $categorie, Request $request, EntityManagerInterface $manager, CategorieRepository $repoC): Response
    {
        $user = $this->getUser();
        // Vérification du rôle de l'utilisateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid("SUP" . $categorie->getId(), $request->get('_token'))) {

            // Récupérer la catégorie "Aucune"
            $categorieAucune = $repoC->getCategorieAucune();
            // Vérifier si la catégorie "Aucune" existe
            if (!$categorieAucune) {
                $this->addFlash('danger', "La catégorie 'Aucune' n'existe pas. Veuillez la créer avant de supprimer une catégorie.");
                return $this->redirectToRoute('admin_categorie');
            }
            // Récupérer tous les produits liés à la catégorie
            $produits = $categorie->getProduits();
            // remplacer les produits associés avec une cat par defaut 
            foreach ($produits as $produit) {
                $produit->setStatus(false);
                // Remplacer la catégorie par "Aucune"
                $produit->setCategorie($categorieAucune);
            }
            $manager->remove($categorie);
            $manager->flush();
            $this->addFlash('danger', "Vous avez bien supprimer une catégorie.");
            return $this->redirectToRoute('admin_categorie');
        } else {
            // Si le token CSRF est invalide
            $this->addFlash('danger', "Action non autorisée.");
            return $this->redirectToRoute('admin_categorie');
        }
    }









































    //Paiment

    //Gestions des methode paiments
    #[Route('/admin/paiement-type', name: 'admin_paiement_type')]
    #[Route('/admin/paiement-type/modification/{id}', name: 'admin_paiement_modif')]
    public function paiementtype(PaginatorInterface $paginator, MethodePaiement $type = null, MethodePaiementRepository $repoM, Request $request, EntityManagerInterface $manager): Response
    {
        $user = $this->getUser();
        // Vérification du rôle de l'utilisateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();




        //Ajout et Modification d'une type paiments
        if (!$type) {
            $type = new MethodePaiement;
        }
        //Lister des types 
        $types = $repoM->findByTypesSupp(true);

        $form = $this->createForm(MethodePaimentType::class, $type);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $type->setTypeSupp(true);
            $type->setCreatedAt(new DateTimeImmutable());
            $manager->persist($type);

            // Vérifier si c'est un ajout ou une modificati\on
            if ($type->getId()) {
                $manager->flush();
                $this->addFlash("warning", "Le type de paiement a bien été modifié.");
            } else {
                $manager->flush();
                $this->addFlash("success", "Le type de paiement a bien été ajouter..");
            }

            return $this->redirectToRoute('admin_paiement_type');
        }




        return $this->render('admin/gestionsPaiment.html.twig', [
            'categoriess' => $categoriess,
            'form' => $form->createView(),
            "types" => $types,
            "logo" => $lastActiveLogos
        ]);
    }


    //Supprimer un paiement
    #[Route('/admin/paiement-type/supp/{id}', name: 'admin_paiement_supp')]
    public function SuppressionTypePeiament(MethodePaiement $type, Request $request, EntityManagerInterface $manager): Response
    {
        $user = $this->getUser();
        // Vérification du rôle de l'utilisateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid("SUP" . $type->getId(), $request->get('_token'))) {

            $type->setTypeSupp(false); // Marquer comme supprimé
            $manager->flush();

            $this->addFlash('success', "Vous avez bien supprimé un type paiement.");
            return $this->redirectToRoute('admin_paiement_type');
        } else {
            // Si le token CSRF est invalide
            $this->addFlash('danger', "Action non autorisée.");
            return $this->redirectToRoute('admin_paiement_type');
        }
    }





















































    // Gestions des Produits
    #[Route('/admin/produits', name: 'admin_produits')]
    public function adminProduits(
        PaginatorInterface $paginate,
        ProduitsRepository $repoP,
        Request $request
    ): Response {
        // Récupérer les critères de recherche depuis la requête GET
        $titre = $request->query->get('titre', '');
        $categorieId = $request->query->get('categorie', null);
        // Convertir le paramètre catégorie en entier si nécessaire
        $categorieId = is_numeric($categorieId) ? (int) $categorieId : null;
        // Rechercher les produits avec pagination
        $query = $repoP->findWithSearch($titre, $categorieId);

        $produits = $paginate->paginate(
            $query,
            $request->query->getInt('page', 1), // Numéro de page
            20 // Nombre de résultats par page
        );

        // Liste des catégories pour le filtre
        $categories = $this->categorieRepo->findAll();

        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('admin/gestionsProduits.html.twig', [
            'produitss' => $produits,
            'categories' => $categories,
            'titre' => $titre,
            'categorieId' => $categorieId,
            'categoriess' => $categoriess,
            "logo" => $lastActiveLogos
        ]);
    }



    //Ajouter et Modifier un produit
    #[Route('/admin/produits/ajouter', name: 'admin_ajouter_produit')]
    #[Route('/admin/produits/modification/{id}', name: 'admin_modif_produit')]
    public function adminAjouterModifierProduits(Produits $produits = null, ProduitsRepository $repoP, Request $request, EntityManagerInterface $manager): Response
    {

        //Ajout et Modification d'un produit
        if (!$produits) {
            $produits = new Produits;
        }


        $form = $this->createForm(ProduitsType::class, $produits);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Les caractères autorisés pour le matricule
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

            // Mélange et sélection aléatoire de caractères
            $matricule = '';
            for ($i = 0; $i < 10; $i++) {
                $matricule .= $characters[random_int(0, strlen($characters) - 1)];
            }
            $produits->setMatricule($matricule);
            $slugProduit = preg_replace('/[^a-z0-9]+/', '-', trim(strtolower($produits->getTitre())));
            $produits->setSlug($slugProduit);
            $produits->setProduitSupp(true);
            $produits->setStatus(false);
            $produits->setDate(new DateTime());

            $manager->persist($produits);

            // Vérifier si c'est un ajout ou une modification
            if ($produits->getId()) {
                $manager->flush();
                $this->addFlash("warning", "Le produit a bien été modifiée.");
                return $this->redirectToRoute('admin_produits');
            } else {
                $manager->flush();
                $this->addFlash("success", "Le produit a bien été ajoutée.");
                return $this->redirectToRoute('admin_ajouter_produit');
            }
        }


        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('admin/AjouterProduits.html.twig', [
            'categoriess' => $categoriess,
            'form' => $form->createView(),
            "logo" => $lastActiveLogos

        ]);
    }


    // Supprimer un produit
    #[Route('/admin/produit/supp/{id}', name: 'admin_supp_produit')]
    public function SuppressionProduits(Produits $produit, Request $request, EntityManagerInterface $manager): Response
    {

        $produit->setProduitSupp(false); // Marquer comme supprimé
        $manager->flush();

        $this->addFlash('success', "Vous avez bien supprimé le produit.");
        return $this->redirectToRoute('admin_produits');
    }



    //Activer/desactiver un produit
    #[Route('/admin/produit/activer/{id}', name: 'admin_activer_produit')]
    public function ActiverProduit(Produits $produits, EntityManagerInterface $manager)
    {
        $user = $this->getUser();
        // Vérification du rôle de l'utilisateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $produits->setStatus(($produits->isStatus()) ? false : true);
        $manager->persist($produits);
        $manager->flush();

        return new Response("true");
    }



    //Voir un produits
    #[Route('/admin/voir/produits/{matricule}/{id}', name: 'admin_voir_produit')]
    public function voirProduit($matricule, ProduitsRepository $produitRepository, CommandeProduitRepository $commandeProduitRepository, $id): Response
    {

        //En Selectionne la produits par matricule et sont id
        $produit = $produitRepository->findByMatriculeAndId($matricule, $id);

        if (empty($produit)) {
            return $this->redirectToRoute('app_logout');
        }
        if (!$produit->isProduitSupp()) {
            $this->addFlash('danger', "Cette produits n'est plus disponible dans notre magasin.");
        }

        if (!$produit) {
            return $this->redirectToRoute('home');
        }

        // Calculer le nombre total de fois où le produit a été acheté
        $nombreFoisAchete = $commandeProduitRepository->countTotalCommandeByProduit($produit);

        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('admin/voirProduit.html.twig', [
            'produit' => $produit,
            'nombreFoisAchete' => $nombreFoisAchete,
            'categoriess' => $categoriess,
            'stock' => $produit->getQuantiter(), // Stock disponible
            "logo" => $lastActiveLogos
        ]);
    }








































    //Gestions des services.
    #[Route('/admin/gestions-services', name: 'admin_services')]
    public function adminServices(PaginatorInterface $paginator, ReparationRepository $repoR, Request $request, ServicessRepository $serviceRepo): Response
    {
        //Compter les nombres des services et celles de reparations
        $servicesT = $serviceRepo->findAll();
        $reparationT = $repoR->findAll();
        $servicesTotale = count($servicesT);
        $reparationTotale = count($reparationT);

        // Récupérer les critères de recherche
        $searchPhone = $request->query->get('search_phone');
        $searchServiceId = $request->query->get('search_service');

        // Convertir `search_service` en entier si ce n'est pas vide
        $searchServiceId = $searchServiceId !== null ? (int) $searchServiceId : null;

        // Récupérer les services actifs (statut = 1)
        $servicesActifs = $serviceRepo->findBy(['status' => 1, 'service_supp' => 0]);

        //Lister des reparation avec paginations
        $reparations = $paginator->paginate(
            $repoR->findReparationsWithUserAndService($searchPhone, $searchServiceId), // La query à paginer
            $request->query->getInt('page', 1), // Le numéro de la page (1 par défaut)
            15 // Nombre d'éléments par page
        );

        //Statistique User avec nombre de reparation
        $userReparationStatistics = $repoR->getUserReparationStatistics();

        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('admin/gestionsServices.html.twig', [
            'categoriess' => $categoriess,
            'reparations' => $reparations,
            'search_phone' => $searchPhone,
            'search_service' => $searchServiceId,
            'servicesActifs' => $servicesActifs,
            'serviceTotale' => $servicesTotale,
            'reparationTotale' => $reparationTotale,
            'userStatisticsReparation' => $userReparationStatistics,
            "logo" => $lastActiveLogos

        ]);
    }


    //Listes des services publiés + Ajout + Modif
    #[Route('/admin/gestions-services/services/listes', name: 'admin_services_listes')]
    #[Route('/admin/gestions-services/services/listes/modification/{id}', name: 'admin_service_modif')]
    public function adminListeServices(ServicessRepository $serviceRepo, PaginatorInterface $paginator, Servicess $servicess = null, ServicessRepository $repoS, Request $request, EntityManagerInterface $manager): Response
    {

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Récupérer la liste des services avec le nombre de réparations
        $servicesWithRepairCount = $serviceRepo->findServicesWithRepairCount();

        //Ajout et Modification d'un service
        if (!$servicess) {
            $servicess = new Servicess;
        }

        //Lister des services avec paginations
        $queryy = $serviceRepo->findAllServicesNonsupp();
        $services = $paginator->paginate(
            $queryy, // La query à paginer
            $request->query->getInt('page', 1), // Le numéro de la page (1 par défaut)
            15 // Nombre d'éléments par page
        );


        $form = $this->createForm(ServicessType::class, $servicess);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $slugServices = preg_replace('/[^a-z0-9]+/', '-', trim(strtolower($servicess->getTitre())));
            $servicess->setSlug($slugServices);
            $servicess->setStatus(true);
            $servicess->setServiceSupp(false);
            $servicess->setDate(new DateTime());

            // Matricule: Les caractères autorisés pour le matricule
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

            // Mélange et sélection aléatoire de caractères
            $matricule = '';
            for ($i = 0; $i < 5; $i++) {
                $matricule .= $characters[random_int(0, strlen($characters) - 1)];
            }
            $servicess->setMatricule($matricule);


            $manager->persist($servicess);

            // Vérifier si c'est un ajout ou une modification
            if ($servicess->getId()) {
                $manager->flush();
                $this->addFlash("warning", "Le service a bien été modifiée.");
            } else {
                $manager->flush();
                $this->addFlash("success", "Le service a bien été ajoutée.");
            }

            return $this->redirectToRoute('admin_services_listes');
        }


        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('admin/ListesServices.html.twig', [
            'categoriess' => $categoriess,
            'form' => $form->createView(),
            'serviceslistereparation' => $servicesWithRepairCount,
            "services" => $services,
            "logo" => $lastActiveLogos

        ]);
    }


    //Supprimer un Services
    #[Route('/admin/services/listes/supp/{id}', name: 'admin_service_supp')]
    public function SuppressionServices(Servicess $servicess, Request $request, EntityManagerInterface $manager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid("SUP" . $servicess->getId(), $request->get('_token'))) {
            $servicess->setServiceSupp(true);
            $manager->persist($servicess);
            $manager->flush();
            $this->addFlash('danger', "Vous avez bien supprimer un service.");
            return $this->redirectToRoute('admin_services_listes');
        }
    }


    //Activer/desactiver un Services
    #[Route('/admin/services/listes/activer/{id}', name: 'admin_service_activer')]
    public function ActiverServices(Servicess $servicess, EntityManagerInterface $manager)
    {

        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        $servicess->setStatus(($servicess->isStatus()) ? false : true);
        $manager->persist($servicess);
        $manager->flush();

        return new Response("true");
    }



    //Voir un services
    #[Route('/admin/voir/service/{matricule}/{id}', name: 'admin_voir_service')]
    public function voirServices(Request $request, PaginatorInterface $paginator, $matricule, ServicessRepository $serviceRepository, ReparationRepository $reparationRepository, $id): Response
    {

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        //En Selectionne la service par matricule et sont id
        $service = $serviceRepository->findByMatriculeAndId($matricule, $id);
        if (!$service) {
            return $this->redirectToRoute('app_logout');
        }

        $reparationsta = $reparationRepository->findBy(['service' => $service]);

        $reparations = $paginator->paginate(
            $reparationRepository->findBy(['service' => $service]), // La query à paginer
            $request->query->getInt('page', 1), // Le numéro de la page (1 par défaut)
            15 // Nombre d'éléments par page
        );
        $statistiques = [
            'en_cours' => count(array_filter($reparationsta, fn($rep) => !$rep->isStatus())),
            'termines' => count(array_filter($reparationsta, fn($rep) => $rep->isStatus())),
            'total' => count($reparationsta)
        ];

        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('admin/voirService.html.twig', [
            'categoriess' => $categoriess,
            'service' => $service,
            'reparations' => $reparations,
            'statistiques' => $statistiques,
            "logo" => $lastActiveLogos

        ]);
    }


















    //Ajouter et Modifier Une réparation
    #[Route('/admin/services/reparations/ajouter', name: 'admin_reparations_ajouter')]
    #[Route('/admin/services/reparations/modifier/{id}', name: 'admin_reparation_modif')]
    public function adminReparation(MailjetService $mailjetService, UserRepository $userRepo, Reparation $reparation = null, EntityManagerInterface $manager, Request $request): Response
    {

        //Ajout et Modification d'un service
        if (!$reparation) {
            $reparation = new Reparation;
        }

        $form = $this->createForm(ReparationType::class, $reparation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //Trouver l'user du reparation
            $email = $form->get('email')->getData();

            if (empty($email)) {
                $email = "electro.multifix@gmail.com";
            }

            // Vérifier si l'email existe déjà dans la table User
            $user = $userRepo->findOneBy(['email' => $email]);

            if (!$user) {
                // Si l'utilisateur n'existe pas
                $this->addFlash('danger', "Le client n'est pas encore inscrit dans la plateforme. Veuillez les inscrires. ");
                return $this->redirectToRoute('admin_reparations_ajouter');
            }

            if (!$reparation->getPrix()) {
                $reparation->setPrix($reparation->getService()->getPrix());
            }

            if (!$reparation->getPhone()) {
                $reparation->setPhone($user->getPhone());
            }

            if (!$reparation->getService()) {
                // Si le champs services (le select) n'existe pas ou est vides
                $this->addFlash('danger', "Aucun services trouver");
                return $this->redirectToRoute('admin_reparations_ajouter');
            }

            // Les caractères autorisés pour le matricule
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

            // Mélange et sélection aléatoire de caractères
            $matricule = '';
            for ($i = 0; $i < 10; $i++) {
                $matricule .= $characters[random_int(0, strlen($characters) - 1)];
            }

            $reparation->setUser($user);
            $reparation->setMatricule($matricule);
            $reparation->setStatus(false);
            $reparation->setCreatedAt(new DateTime());
            $manager->persist($reparation);


            // Vérifier si c'est un ajout ou une modification
            if ($reparation->getId()) {
                $manager->flush();
                $this->addFlash("warning", "La réparation a bien été modifiée.");
                return $this->redirectToRoute('admin_reparations_ajouter');
            } else {
                $manager->flush();
                $this->addFlash("success", "La réparation a bien été energistrée.");
                return $this->redirectToRoute('admin_reparations_ajouter');
            }
        }

        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('admin/AjouterReparations.html.twig', [
            'form' => $form->createView(),
            'categoriess' => $categoriess,
            "logo" => $lastActiveLogos

        ]);
    }




    //Voir reparation
    #[Route('/services/reparations/{matricule}/{id}', name: 'admin_voir_reparation')]
    public function VoirReparation($id, $matricule, ReparationRepository $repoR): Response
    {
        $user = $this->getUser();
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');


        //En selectionne la reparation
        $reparation = $repoR->findByMatriculeAndId($matricule, $id);
        if (!$reparation) {
            return $this->redirectToRoute('app_logout');
        }

        // Vérifier si l'utilisateur est un client et qu'il n'est pas le propriétaire de la reparation
        if (
            in_array('ROLE_CLIENT', $user->getRoles()) && $reparation->getUser() !== $user
        ) {
            return $this->redirectToRoute('app_logout');
        }

        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('admin/voirReparation.html.twig', [
            'categoriess' => $categoriess,
            'reparation' => $reparation,
            "logo" => $lastActiveLogos

        ]);
    }



    //Changer En cour par Trerminer les reparations
    #[Route('/admin/reparation/{id}/update-status', name: 'admin_reparation_update_status', methods: ['POST'])]
    public function updateStatusAjax(
        Reparation $reparation,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $content = json_decode($request->getContent(), true);
        $newStatus = $content['status_reparation'] ?? null;

        if (in_array($newStatus, ['En cours', 'Terminé'])) {
            $reparation->setStatusReparation($newStatus);

            // Si le statut passe à Terminé
            if ($newStatus === 'Terminé') {
                // Rechercher dans Finance si une ligne existe pour ce numéro de réparation
                $financeRepo = $em->getRepository(Finance::class);
                $financeEntry = $financeRepo->findOneBy(['numero' => $reparation->getMatricule(), 'type' => 'Reparation']);

                if (!$financeEntry) {
                    // Si aucune entrée n'existe, en créer une nouvelle
                    $financeEntry = new Finance();
                    $financeEntry->setType('Reparation');
                    $financeEntry->setIdCommande($reparation->getId());
                    $financeEntry->setNumero($reparation->getMatricule());
                    $financeEntry->setPhone($reparation->getUser()->getPhone());
                    $financeEntry->setNom($reparation->getUser()->getNom());
                    $financeEntry->setPrix($reparation->getPrix());
                    $financeEntry->setCreatedAt(new \DateTimeImmutable());
                    $em->persist($financeEntry);
                } else {
                    // Mettre à jour l'entrée existante
                    $financeEntry->setPhone($reparation->getUser()->getPhone());
                    $financeEntry->setNom($reparation->getUser()->getNom());
                }
            }

            $em->flush();

            return new JsonResponse(['success' => true, 'message' => 'Statut mis à jour avec succès.']);
        }

        return new JsonResponse(['success' => false, 'message' => 'Statut invalide.'], 400);
    }





    //Supprimer une reparation
    #[Route('/admin/reparation/supp/{id}', name: 'admin_reparation_supp', methods: ['POST', 'DELETE'])]
    public function SuppressionReparation(Reparation $reparation, Request $request, EntityManagerInterface $manager): Response
    {
        $user = $this->getUser();

        $manager->remove($reparation);

        if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_EMPLOYER', $user->getRoles())) {
            $manager->flush();
            $this->addFlash('success', 'La réparation a été supprimée avec succès.');
            return $this->redirectToRoute('admin_services'); // Redirection vers la liste des commandes après suppression.
        } else {
            $manager->flush();
            $this->addFlash('success', 'La réparation a été annuler avec succès.');
            return $this->redirectToRoute('mes_reparations'); // Redirection vers la liste des commandes après suppression.
        }
    }





    //Generation du Facture du reparation
    #[Route('/services/reparation/generate-pdf/{matricule}/{id}', name: 'generate_pdf_reparation')]
    public function generatePDFReparation(int $id, string $matricule, ReparationRepository $repoR, ServicessRepository $repoS): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();
        //En Selectionne la reparation
        $reparation = $repoR->findByMatriculeAndId($matricule, $id);

        // Vérifier si l'utilisateur est un client et qu'il n'est pas le propriétaire de la reparation
        if (
            in_array('ROLE_CLIENT', $user->getRoles()) && $reparation->getUser() !== $user
        ) {
            return $this->redirectToRoute('app_logout');
        }




        if (empty($reparation)) {
            return $this->redirectToRoute('app_logout');
        }

        if (!$reparation || $reparation->getStatusReparation() !== "Terminé") {
            return $this->redirectToRoute('home');
        }

        // Création du PDF avec TCPDF
        $pdf = new TCPDF();
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->SetFont('times', 'B', 25);


        $pdf->Ln(8);

        // Titre "Facture de la commande"
        $pdf->Cell(0, 22, "Facture Réparation", 0, 1, 'C');
        $pdf->Line(10, 80, 200, 80);
        $pdf->SetFont('times', 'B', 12);
        $message = "Imprimer le ";

        $pdf->Cell(190, 15, $message . date('d/m/Y') . "", 0, 1, 'R', 0, '', false, 'M', 'M');

        $pdf->Cell(0, 15, "Numeros de la Commande : " . $reparation->getMatricule(), 0, 1, 'L', 0, '', false, 'M', 'M');
        $pdf->SetFont('times', 12);

        $message1 = "Téléphone";
        $pdf->Cell(0, 15, "$message1 : (+253) 77128840  /  (+253) 77369958", 0, 1, 'L', 0, '', false, 'M', 'M');
        $pdf->Cell(0, 15, "Email : support@electro-multifix.com", 0, 1, 'L', 0, '', false, 'M', 'M');
        $message2 = "Site web";
        $pdf->Cell(0, 15, "$message2 : https://www.electro-multifix.com", 0, 1, 'L', 0, '', false, 'M', 'M');



        $pdf->Ln(8);

        if ($reparation->getStatusReparation() == "Terminé") {

            $pdf->SetFont('times', 'B', 12);
            $message3 = "Information de la réparation";
            $pdf->Cell(150, -3, $message3, 0, 1, 'L');

            $pdf->SetFont('times', 12);
            $pdf->Cell(150, -3, $reparation->getUser()->getNom(), 0, 1, 'L');



            $pdf->Cell(150, -3, "Téléphone : " . $reparation->getPhone(), 0, 1, 'L');



            $pdf->SetFont('times', 12);
            $pdf->Cell(150, -3, "Email : " . $reparation->getUser()->getEmail(), 0, 1, 'L');


            // Espacement
            $pdf->Ln(10);

            // Titre "Facture de la commande"
            $pdf->Cell(0, 22, "La réparation", 0, 1, 'C');

            // Construction de la table en HTML
            $tableHTML = '
        <table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse; text-align:center;">
            <thead style="background-color:#D6C7C4; color:black;">
             <tr>
                <th>Service</th>
                <th>Date déposer</th>
                <th>Date répris</th>
                <th>Prix Total</th>
            </tr>
            </thead>
            <tbody>

            
                            <tr>
                                <td>' . $reparation->getService()->getTitre() . '</td>
                                <td>' . $reparation->getCreatedAt()->format('d/m/Y')  . '</td>
                                <td>' . $reparation->getDateRepri()->format('d/m/Y') . '</td>
                                <td>' . $reparation->getPrix() . ' DJF </td>
                            </tr>

                             <tr>
                                <td></td>
                                <td></td>
                                <td style="float:left">Prix total à payé : </td>
                                <td>' . $reparation->getPrix() . ' DJF </td>
                            </tr>
            </tbody>
        </table>';

            // Affichage ou insertion dans TCPDF
            $pdf->writeHTML($tableHTML, true, false, false, false, '');
        }









        // Espacement
        $pdf->Ln(10);

        // Footer (coordonnées de l'entreprise)
        $pdf->SetFont('Helvetica', 'I', 8);
        $pdf->SetY(266);
        $pdf->Cell(0, 10, 'Merci pour votre confiance. Contactez-nous pour toute question.', 0, 0, 'C');

        // Générer le PDF et l'envoyer en réponse
        $pdfContent = $pdf->Output('Facture_commande_' . $reparation->getMatricule(), 'I');  // S pour renvoyer le contenu au lieu de l'enregistrer dans un fichier
        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="facture_commande_' . $reparation->getId() . '.pdf"',
        ]);
    }
















































    //Gestions des Contacts
    #[Route('/admin/contacts', name: 'admin_contacts')]
    public function adminContacts(PaginatorInterface $paginate, ContactsRepository $repoContact, Request $request): Response
    {
        // Récupérer le terme de recherche depuis la requête GET
        $searchTerm = $request->query->get('search', '');

        // Appel à la méthode de repository pour récupérer les contacts filtrés
        $queryBuilder = $repoContact->findContactsByName($searchTerm);

        // Pagination des résultats
        $contacts = $paginate->paginate(
            $queryBuilder, // La requête filtrée
            $request->query->getInt('page', 1), // La page actuelle (par défaut la première)
            10 // Nombre de contacts par page
        );

        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('admin/gestionsContacts.html.twig', [
            "contacts" => $contacts,
            'categoriess' => $categoriess,
            'searchTerm' => $searchTerm,
            "logo" => $lastActiveLogos

        ]);
    }



    //Supprimer un contacts
    #[Route('/admin/contacts/supp/{id}', name: 'admin_contact_supp')]
    public function SuppressionContacts(Contacts $contact, Request $request, EntityManagerInterface $manager): Response
    {
        if ($this->isCsrfTokenValid("SUP" . $contact->getId(), $request->get('_token'))) {
            $manager->remove($contact);
            $manager->flush();
            $this->addFlash('danger', "Vous avez bien supprimer un contact.");
            return $this->redirectToRoute('admin_contacts');
        }
    }


    //Voir un contacts
    #[Route('/admin/contacts/{id}', name: 'admin_contacts_voir')]
    public function VoirContacts(Contacts $contact): Response
    {
        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('admin/voirContacts.html.twig', [
            'contact' => $contact,
            'categoriess' => $categoriess,
            "logo" => $lastActiveLogos


        ]);
    }

































    //Gestions des demandes
    #[Route('/admin/demandes', name: 'admin_demande')]
    public function adminDemande(PaginatorInterface $paginate, DemandeRepository $repoDemande, Request $request, ServicessRepository $reposervices): Response
    {
        // Récupération des paramètres de recherche
        $search = $request->query->get('search', '');
        $serviceId = $request->query->get('service', null);

        // Convertir $serviceId en entier ou null
        $serviceId = $serviceId !== null ? (int)$serviceId : null;

        // Appel au repository pour obtenir les données filtrées
        $query = $repoDemande->findDemandeWithFilters($search, $serviceId);

        // Pagination
        $demandes = $paginate->paginate(
            $query,
            $request->query->getInt('page', 1), // Numéro de la page
            10 // Nombre d'éléments par page
        );

        // Liste des services pour la barre de recherche (select)
        $services = $reposervices->findBy(['status' => 1, 'service_supp' => 0]);



        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('admin/gestionsDemande.html.twig', [
            'categoriess' => $categoriess,
            'demandes' => $demandes,
            'services' => $services,
            'search' => $search,
            'serviceId' => $serviceId,
            "logo" => $lastActiveLogos

        ]);
    }



    //Supprimer une demande de service
    #[Route('/admin/demandes/supp/{id}', name: 'admin_demande_supp')]
    public function SuppressionDemande(Demande $demande, Request $request, EntityManagerInterface $manager): Response
    {
        if ($this->isCsrfTokenValid("SUP" . $demande->getId(), $request->get('_token'))) {
            $manager->remove($demande);
            $manager->flush();
            $this->addFlash('danger', "Vous avez bien supprimer une demande de service.");
            return $this->redirectToRoute('admin_demande');
        }
    }


    //Voir une demande de service
    #[Route('/admin/demandes/voir/{matricule}/{id}', name: 'admin_demande_voir')]
    public function VoirDemande($matricule, $id, DemandeRepository $repoD): Response
    {
        $demande = $repoD->findByMatriculeAndId($matricule, $id);
        if (!$demande) {
            return $this->redirectToRoute('app_logout');
        }
        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('admin/voirDemande.html.twig', [
            'demande' => $demande,
            'categoriess' => $categoriess,
            "logo" => $lastActiveLogos

        ]);
    }


    //Ajouter une demande dans la reparation
    #[Route('/admin/demande/ajouter/{id}', name: 'admin_ajouter_demande_reparation')]
    public function AjouterDemandeDansReparation(int $id, ServicessRepository $servicerepo, DemandeRepository $demandeRepository, UserRepository $userRepository, ReparationRepository $reparationRepository, EntityManagerInterface $em): Response
    {
        // 1. Récupérer la demande via son ID
        $demande = $demandeRepository->find($id);

        if (!$demande) {
            $this->addFlash('error', 'Demande non trouvée.');
            return $this->redirectToRoute('liste_demandes'); // Retour à la liste des demandes
        }

        // 2. Vérifier si l'email de la demande correspond à un utilisateur enregistré
        $user = $userRepository->findOneBy(['email' => $demande->getEmail()]);

        if (!$user) {
            $this->addFlash('error', 'Aucun utilisateur trouvé avec cet email.');
            return $this->redirectToRoute('liste_demandes');
        }

        // 4. Trouver le service choisi dans la demande
        $service = $demande->getService();

        // 3. Créer une nouvelle réparation
        $reparation = new Reparation();
        $reparation->setImage($demande->getImage());
        $reparation->setDescription($demande->getDescription());
        $reparation->setPrix($service->getPrix()); // Prix peut être défini plus tard
        $reparation->setCreatedAt(new \DateTimeImmutable());
        $reparation->setDateRepri((new \DateTimeImmutable())->modify('+3 days'));
        $reparation->setStatus(false); // En cours par défaut
        $reparation->setService($demande->getService());
        $reparation->setUser($user);
        // Les caractères autorisés pour le matricule
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        // Mélange et sélection aléatoire de caractères
        $matricule = '';
        for ($i = 0; $i < 10; $i++) {
            $matricule .= $characters[random_int(0, strlen($characters) - 1)];
        }
        $reparation->setMatricule($matricule);


        // 4. Sauvegarder la réparation dans la base de données
        $em->persist($reparation);
        $em->flush();

        // 6. Ajouter un message flash et rediriger
        $this->addFlash('success', 'Demande convertie en réparation avec succès.');
        return $this->redirectToRoute('admin_demande');

        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('admin/voirDemande.html.twig', [
            'demande' => $demande,
            'categoriess' => $categoriess,
            "logo" => $lastActiveLogos
        ]);
    }

































    //Gestions des Utilisateurs
    #[Route('/admin/user', name: 'admin_users')]
    #[Route('/admin/user/modifier/{id}', name: 'admin_user_modifi')]
    public function adminUsers(CommandeRepository $repocommande, PaginatorInterface $paginator, MailjetService $mailjetService, PaginatorInterface $paginate, User $user = null, UserRepository $repoU, Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $userssss = $this->getUser();
        // Vérification du rôle de l'utilisateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        //Récupérer les statistiques des users avec leur nombre de commande
        $paginationUsersCommande = $repocommande->getUserOrderStatistics();

        // Récupérer les statistiques des utilisateurs avec leur nombre de reparations
        $paginationRepa = $repoU->getUserReparationStats();


        // Récupère les filtres depuis la requête
        $filters = [
            'nom' => $request->query->get('nom', ''),
            'phone' => $request->query->get('phone', ''),
            'fonction' => $request->query->get('fonction', ''),
        ];

        // Récupère les utilisateurs avec les filtres
        $users = $repoU->findUsersWithFilters($filters, $paginator, $request->query->getInt('page', 1), 20);



        //ajouter et Modifier user
        if (!$user) {
            $user = new User;
        }

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupérer et nettoyer le numéro de téléphone
            $telephone = $user->getPhone();
            if ($telephone) {
                $user->setPhone(ltrim($telephone, '+')); // Supprimer le signe '+'
            }
            // Vérifier si c'est un ajout ou une modification
            if (!$user->getId()) {
                // Vérifier si l'email existe déjà dans la base de données
                $existingUser = $repoU->findOneBy(['email' => $user->getEmail()]);

                if ($existingUser) {
                    // Si l'utilisateur existe déjà, afficher un message flash
                    $this->addFlash('danger', 'L\'utilisateur existe déjà avec cette adresse email.');
                    return $this->redirectToRoute('admin_users'); // Rediriger vers la page d'ajout
                }
            }


            // Les caractères autorisés pour le matricule
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

            // Mélange et sélection aléatoire de caractères
            $matricule = '';
            for ($i = 0; $i < 30; $i++) {
                $matricule .= $characters[random_int(0, strlen($characters) - 1)];
            }


            $fonction = $request->request->get('fonction');

            if ($fonction == "ROLE_ADMIN") {
                $user->setRoles(['ROLE_ADMIN']);
            } elseif ($fonction == "ROLE_EMPLOYER") {
                $user->setRoles(['ROLE_EMPLOYER']);
            } elseif ($fonction == "ROLE_CLIENT") {
                $user->setRoles(['ROLE_CLIENT']);
            }
            if (empty($user->getEmail())) {
                $user->setEmail('electro-multifix@gmail.com');
            }
            $hashedPassword = $passwordHasher->hashPassword($user, $form->get('password')->getData());
            $user->setPassword($hashedPassword);
            $token = bin2hex(random_bytes(32));
            $user->setToken($token);
            $user->setMatricule($matricule);
            $user->setStatuscompte(true);
            $user->setFonction($fonction);
            $user->setStatus(false);
            $user->setDate(new DateTime());
            $manager->persist($user);


            //Envoie d'email de confirmation
            $recipientEmail = $user->getEmail();
            $recipientName = $user->getNom();
            $subject = 'Confirmation de votre email.';
            $textPart = '';
            $htmlPart = '<p>Merci pour votre inscription. Veuillez confirmer votre adresse e-mail afin de continuer à utiliser nos services :</p>' .
                '<a href="' . $this->generateUrl('confirmation_inscription', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL) . '">Confirmer votre email.</a>';

            $success = $mailjetService->sendEmail($recipientEmail, $recipientName, $subject, $textPart, $htmlPart);

            if ($success) {
                // Vérifier si c'est un ajout ou une modification
                if ($user->getId()) {
                    $manager->flush();
                    $this->addFlash("warning", "L'utilisateur a bien été modifiée. Mais il doit confirmée son email.");
                } else {
                    $manager->flush();
                    $this->addFlash("success", "L'utilisateur a bien été ajoutée. Mais il doit confirmée son email.");
                }
            } else {
                $manager->flush();
                $this->addFlash('danger', 'Probléme d\'envois d\'email. Veuillez contactez les administrateurs.');
                return $this->redirectToRoute('admin_users');
            }

            return $this->redirectToRoute('admin_users');
        }

        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('admin/gestionsUser.html.twig', [
            'users' => $users,
            'filters' => $filters, // Transmet les filtres à la vue
            'categoriess' => $categoriess,
            "form" => $form->createView(),
            'userStats' => $paginationUsersCommande,
            'statistiqueRepa' => $paginationRepa,
            "logo" => $lastActiveLogos

        ]);
    }


    //Supprimer un User
    #[Route('/admin/user/supp/{id}', name: 'admin_user_supp')]
    #[IsGranted('ROLE_ADMIN')]  // Seules les personnes avec le rôle ROLE_ADMIN peuvent accéder à cette page
    public function SuppressionUsers(User $user, Request $request, EntityManagerInterface $manager): Response
    {

        // Vérification du rôle de l'utilisateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid("SUP" . $user->getId(), $request->get('_token'))) {
            $manager->remove($user);
            $manager->flush();
            $this->addFlash('danger', "Vous avez bien supprimer un utilisateur.");
            return $this->redirectToRoute('admin_users');
        }
    }



    //Activer/desactiver un User
    #[Route('/admin/user/activer/{id}', name: 'admin_user_activer')]
    public function ActiverUser(User $user, EntityManagerInterface $manager)
    {

        // Vérification du rôle de l'utilisateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user->setStatuscompte(($user->isStatuscompte()) ? false : true);
        $manager->persist($user);
        $manager->flush();

        return new Response("true");
    }












    //Voir User
    #[Route('/voir-user/{id}/{matricule}', name: 'admin_voir_user')]
    public function voirUser(
        User $user,
        string $matricule,
        Request $request,
        CommandeRepository $commandeRepository,
        ReparationRepository $reparationRepository,
        PaginatorInterface $paginator
    ): Response {


        // Vérification du rôle de l'utilisateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Vérification que le matricule correspond bien à celui de l'utilisateur
        if ($user->getMatricule() !== $matricule) {
            throw $this->createNotFoundException('Utilisateur non trouvé ou matricule incorrect.');
        }

        // Vérification du rôle (accès réservé aux administrateurs)
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Récupération des commandes de l'utilisateur
        $queryCommandes = $commandeRepository->findBy(['user' => $user]);

        // Ajout de la pagination (via KnpPaginatorBundle)
        $commandes = $paginator->paginate(
            $queryCommandes,
            $request->query->getInt('page_commande', 1), // Numéro de la page actuelle pour les commandes
            5 // Nombre d'éléments par page
        );


        // Récupération des réparations de l'utilisateur
        $queryReparations = $reparationRepository->findBy(['user' => $user]);

        // Ajout de la pagination pour les réparations
        $reparations = $paginator->paginate(
            $queryReparations,
            $request->query->getInt('page_reparation', 1), // Numéro de la page actuelle pour les réparations
            5
        );


        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('admin/voirUser.html.twig', [
            'user' => $user,
            'commandes' => $commandes,
            'reparations' => $reparations,
            'categoriess' => $categoriess,
            "logo" => $lastActiveLogos


        ]);
    }




























































































    //////////////////////////////////////Parametre//////////////////////////////////


    //Gestioins des actualiter
    #[Route('/admin/actualiter', name: 'admin_actualiter')]
    #[Route('/admin/actualiter/edit/{id}', name: 'admin_actualiter_modif')]
    public function gestionActualiter(
        Actualiter $actualiter = null,
        ActualiterRepository $actualiterRepository,
        Request $request,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator
    ): Response {

        // Vérification du rôle de l'utilisateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Création d'un nouvel article si aucun n'est fourni
        if (!$actualiter) {
            $actualiter = new Actualiter();
        }

        // Création et gestion du formulaire
        $form = $this->createForm(ActualiterType::class, $actualiter);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Génération automatique du slug
            $slug = strtolower(str_replace(' ', '-', $actualiter->getTitre()));
            $actualiter->setSlug($slug);
            $actualiter->setStatus(true);
            $actualiter->setCreatedAt(new DateTimeImmutable());

            // Enregistrement
            $entityManager->persist($actualiter);

            if ($actualiter->getId()) {
                $entityManager->flush();
                $this->addFlash('warning', 'Actualité Modifier avec succès.');
                return $this->redirectToRoute('admin_actualiter');
            } else {
                $entityManager->flush();
                $this->addFlash('success', 'Actualité enregistrée avec succès.');
                return $this->redirectToRoute('admin_actualiter');
            }
        }

        // Gestion de la recherche via le repository
        $search = $request->query->get('search', '');
        $query = $actualiterRepository->findBySearch($search);

        //Liste des actualités Pagination avec KNP Paginator
        $actualiters = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // Page actuelle
            20 // Articles par page
        );


        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();


        return $this->render('admin/gestionsActualiter.html.twig', [
            'form' => $form->createView(),
            'actualiters' => $actualiters,
            'search' => $search,
            'categoriess' => $categoriess,
            "logo" => $lastActiveLogos

        ]);
    }

    #[Route('/admin/actualiter/delete/{id}', name: 'admin_actualiter_supp')]
    public function delete(
        Actualiter $actualiter,
        EntityManagerInterface $entityManager
    ): Response {

        // Vérification du rôle de l'utilisateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $entityManager->remove($actualiter);
        $entityManager->flush();

        $this->addFlash('danger', 'Actualité supprimée avec succès.');
        return $this->redirectToRoute('admin_actualiter');
    }




    //Activer/desactiver un Actualiter
    #[Route('/admin/actualiter/activer/{id}', name: 'admin_actualiter_activer')]
    public function ActiverActualiter(Actualiter $actualiter, EntityManagerInterface $manager)
    {
        // Vérification du rôle de l'utilisateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $actualiter->setStatus(($actualiter->isStatus()) ? false : true);
        $manager->persist($actualiter);
        $manager->flush();

        return new Response("true");
    }













    //Gestions des fincances
    #[Route('/admin/finance', name: 'admin_finance')]
    public function gestionFinance(
        Request $request,
        FinanceRepository $financeRepo,
        PaginatorInterface $paginator
    ): Response {

        // Vérification du rôle de l'utilisateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        // Récupérer les filtres de recherche
        $searchType = $request->query->get('type', null);
        $searchNumero = $request->query->get('numero', null);

        // Requête pour les finances avec filtres
        $query = $financeRepo->findByFilters($searchType, $searchNumero);

        // Pagination des résultats
        $finances = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        // Statistiques
        $prixTotal = $financeRepo->getPrixTotal();
        $prixProduit = $financeRepo->getPrixByType('Produit');
        $prixReparation = $financeRepo->getPrixByType('Reparation');
        $nombreProduits = $financeRepo->countByType('Produit');
        $nombreReparations = $financeRepo->countByType('Reparation');


        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('admin/gestionsFinance.html.twig', [
            'finances' => $finances,
            'prix_total' => $prixTotal,
            'prix_produit' => $prixProduit,
            'prix_reparation' => $prixReparation,
            'categoriess' => $categoriess,
            'nombre_produits' => $nombreProduits,
            'nombre_reparations' => $nombreReparations,
            "logo" => $lastActiveLogos


        ]);
    }
















    //Gestions des coordonner
    #[Route('/admin/gestion-coordonner', name: 'admin_coordonner')]
    #[Route('/admin/gestion-coordonner/{id}', name: 'admin_coordonner_modif')]
    public function gestionCoordonner(
        Request $request,
        CoordonnerRepository $coordonnerRepo,
        PaginatorInterface $paginator,
        EntityManagerInterface $em
    ): Response {

        // Vérification du rôle de l'utilisateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        // Récupération des données paginées depuis le Repository
        $query = $coordonnerRepo->findAllCoordonnees();
        $coordonnees = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // Page actuelle
            10 // Limite par page
        );

        // Formulaire d'ajout/modification
        $coordonner = new Coordonner();
        $form = $this->createForm(CoordonnerType::class, $coordonner);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $coordonner->setStatus(false);
            $em->persist($coordonner);

            if ($coordonner->getId()) {
                $em->flush();
                $this->addFlash('warning', 'Coordonnée Modifier avec succès.');
                return $this->redirectToRoute('admin_coordonner');
            } else {
                $em->flush();
                $this->addFlash('success', 'Coordonnée enregistrée avec succès.');
                return $this->redirectToRoute('admin_coordonner');
            }
        }

        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('admin/gestionsCoordonner.html.twig', [
            'form' => $form->createView(),
            'coordonnees' => $coordonnees,
            'categoriess' => $categoriess,
            "logo" => $lastActiveLogos


        ]);
    }




    //Supprimer un Coordonner
    #[Route('/admin/coordonner/supp/{id}', name: 'admin_coordonner_supp')]
    public function SuppressionCoordonner(Coordonner $coordonner, Request $request, EntityManagerInterface $manager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid("SUP" . $coordonner->getId(), $request->get('_token'))) {
            $manager->remove($coordonner);
            $manager->flush();
            $this->addFlash('danger', "Vous avez bien supprimer un coordonnée.");
            return $this->redirectToRoute('admin_coordonner');
        }
    }



    //Activer/desactiver un produit
    #[Route('/admin/coordonner/activer/{id}', name: 'admin_activer_coordonner')]
    public function ActiverCoordonner(Coordonner $coordonner, EntityManagerInterface $manager)
    {
        $user = $this->getUser();
        // Vérification du rôle de l'utilisateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $coordonner->setStatus(($coordonner->isStatus()) ? false : true);
        $manager->persist($coordonner);
        $manager->flush();

        return new Response("true");
    }












    //Gestions des Logos
    #[Route('/admin/gestion-logos', name: 'admin_logos')]
    public function gestionLogos(
        Request $request,
        LogosRepository $logosRepository,
        PaginatorInterface $paginator,
        EntityManagerInterface $em
    ): Response {

        // Vérification du rôle de l'utilisateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');


        // Récupération des données paginées depuis le Repository
        $query = $logosRepository->findAllLogos();
        $logos = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // Page actuelle
            10 // Limite par page
        );

        // Formulaire d'ajout/modification
        $logo = new Logos();
        $form = $this->createForm(LogosType::class, $logo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $logo->setStatus(false);
            $em->persist($logo);
            $em->flush();
            $this->addFlash('success', 'Logo enregistrée avec succès.');
            return $this->redirectToRoute('admin_logos');
        }

        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('admin/gestionsLogos.html.twig', [
            'form' => $form->createView(),
            'logos' => $logos,
            'categoriess' => $categoriess,
            "logo" => $lastActiveLogos


        ]);
    }




    //Supprimer un Coordonner
    #[Route('/admin/logos/supp/{id}', name: 'admin_logos_supp')]
    public function SuppressionLogos(Logos $logos, Request $request, EntityManagerInterface $manager): Response
    {
        // Vérification du rôle de l'utilisateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid("SUP" . $logos->getId(), $request->get('_token'))) {
            $manager->remove($logos);
            $manager->flush();
            $this->addFlash('danger', "Vous avez bien supprimer un logo.");
            return $this->redirectToRoute('admin_logos');
        }
    }



    //Activer/desactiver un produit
    #[Route('/admin/logos/activer/{id}', name: 'admin_activer_logos')]
    public function ActiverLogos(Logos $logo, EntityManagerInterface $manager)
    {

        $user = $this->getUser();
        // Vérification du rôle de l'utilisateur
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $logo->setStatus(($logo->isStatus()) ? false : true);
        $manager->persist($logo);
        $manager->flush();

        return new Response("true");
    }
}
