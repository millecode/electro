<?php

namespace App\Controller;

use TCPDF;
use DateTime;
use App\Entity\Commande;
use App\Entity\Produits;
use App\Service\CartService;
use App\Entity\CommandeProduits;
use App\Repository\LogosRepository;
use App\Repository\CommandeRepository;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\MethodePaiementRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommandeController extends AbstractController
{
    private $cartService;
    private $entityManager;
    private $categorieRepo;
    private $logoRepo;


    public function __construct(LogosRepository $logoRepo, CartService $cartService, EntityManagerInterface $entityManager, CategorieRepository $categorieRepo)
    {
        $this->cartService = $cartService;
        $this->categorieRepo = $categorieRepo;
        $this->entityManager = $entityManager;
        $this->logoRepo = $logoRepo;
    }


    //Recaputulatifs de la commande
    #[Route('/commande/recapitulatif-commande', name: 'commande_recap', methods: ['GET', 'POST'])]
    public function summary(CartService $cartService, MethodePaiementRepository $repoM, EntityManagerInterface $entityManager): Response
    {

        //Listes de type paiements
        $types = $repoM->findByTypesSupp(true);
        // Récupérez les produits du panier
        $cartItems = $cartService->getFullCart();
        $total = $cartService->getTotal();

        // Vérifiez si le panier est vide
        if (empty($cartItems)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('cart'); // Redirige vers la page panier
        }

        //Menu Categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('Commande/recap.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total,
            'categoriess' => $categoriess,
            'types' => $types,
            "logo" => $lastActiveLogos
        ]);
    }



    #[Route('/commande/confirmer', name: 'confirmer_commande', methods: ['POST'])]
    public function confirmerCommande(Request $request, MethodePaiementRepository $repoM): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        $cartItems = $this->cartService->getFullCart();
        $total = $this->cartService->getTotal();

        if (empty($cartItems)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('cart');
        }


        // Récupération du moyen de paiement depuis la requête POST
        $paymentMethodID = $request->request->get('payment_method');
        $type = $repoM->find($paymentMethodID);

        if (!$type) {
            $this->addFlash('danger', 'Veuillez choisir un moyen de paiement.');
            return $this->redirectToRoute('commande_recap');
        } elseif (!$type->isTypeSupp()) {
            $this->addFlash('danger', 'Action non autoriser');
            return $this->redirectToRoute('commande_recap');
        }


        // Création de la commande
        $commande = new Commande();
        // Les caractères autorisés pour le matricule
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        // Mélange et sélection aléatoire de caractères
        $matricule = '';
        for ($i = 0; $i < 5; $i++) {
            $matricule .= $characters[random_int(0, strlen($characters) - 1)];
        }
        $commande->setMatricule($matricule);
        $commande->setMethodePaiement($type);
        $commande->setUser($user);
        $commande->setCreatedAt(new \DateTimeImmutable());
        $commande->setPrixtotal($total);
        $commande->setStatus(false);

        $this->entityManager->persist($commande);

        // Ajout des produits de la commande
        foreach ($cartItems as $item) {
            // Vérification de la disponibilité du produit
            $produit = $item['product'];
            $quantityOrdered = $item['quantity'];

            // Si la quantité demandée est supérieure à celle en stock, on affiche un message d'erreur
            if ($produit->getQuantiter() < $quantityOrdered) {
                $this->addFlash('error', "La quantité de '{$produit->getTitre()}' est insuffisante.");
                return $this->redirectToRoute('cart');
            }

            // Création de l'entité CommandeProduits
            $commandeProduit = new CommandeProduits();
            $commandeProduit->setCommande($commande);
            $commandeProduit->setProduit($produit);
            $commandeProduit->setQuantity($quantityOrdered);

            // Décrémentation de la quantité du produit
            $produit->setQuantiter($produit->getQuantiter() - $quantityOrdered);

            // Persister les entités
            $this->entityManager->persist($commandeProduit);
        }

        // Sauvegarder les modifications dans la base de données (mise à jour des produits)
        $this->entityManager->flush();

        // Effacer le panier
        $this->cartService->clearCart();

        $this->addFlash('success', 'Votre commande a été enregistrée avec succès et en cours de preparation.');
        return $this->redirectToRoute('commande_passer', ['id' => $commande->getId(), 'matricule' => $commande->getMatricule()]); // Redirection vers la page success commande ou autre
    }





    //Voir Commande
    #[Route('/commande/success/{matricule}/{id}', name: 'commande_passer')]
    public function success(int $id, string $matricule, CommandeRepository $repoC): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        //En Selectionne la commande
        $commande = $repoC->findByMatriculeAndId($matricule, $id);

        if (empty($commande)) {
            return $this->redirectToRoute('app_logout');
        }

        // Vérifier si l'utilisateur est un client et qu'il n'est pas le propriétaire de la commande
        if (
            in_array('ROLE_CLIENT', $user->getRoles()) &&
            $commande->getUser() !== $user
        ) {
            return $this->redirectToRoute('app_logout');
        }


        // Récupérer les produits de la commande
        $commandeProduits = $commande->getCommandeProduits();

        // Calculer le nombre de produits différents commandés
        $produitsDistincts = count($commandeProduits);

        //Menu Categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();


        return $this->render('Commande/success.html.twig', [
            'commande' => $commande,
            'categoriess' => $categoriess,
            'produitsDistincts' => $produitsDistincts,
            'commandeProduits' => $commandeProduits,
            'userCommande' => $commande->getUser(), // L'utilisateur qui a passé la commande
            "logo" => $lastActiveLogos

        ]);
    }


    #[Route('/commande/generate-pdf/{matricule}/{id}', name: 'generate_pdf')]
    public function generatePDF(int $id, string $matricule, CommandeRepository $repoC): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();

        //En Selectionne la commande
        $commande = $repoC->findByMatriculeAndId($matricule, $id);

        if (empty($commande)) {
            return $this->redirectToRoute('app_logout');
        }

        if (!$commande || $commande->isStatus() !== true) {
            return $this->redirectToRoute('home');
        }

        // Création du PDF avec TCPDF
        $pdf = new TCPDF();
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->SetFont('times', 'B', 25);


        $pdf->Ln(8);

        // Titre "Facture de la commande"
        $pdf->Cell(0, 22, "Facture Commande des produits", 0, 1, 'C');
        $pdf->Line(10, 80, 200, 80);
        $pdf->SetFont('times', 'B', 12);
        $message = "Imprimer le ";

        $pdf->Cell(190, 15, $message . date('d/m/Y') . "", 0, 1, 'R', 0, '', false, 'M', 'M');

        $pdf->Cell(0, 15, "Numeros de la Commande : " . $commande->getMatricule(), 0, 1, 'L', 0, '', false, 'M', 'M');
        $pdf->SetFont('times', 12);

        $message1 = "Téléphone";
        $pdf->Cell(0, 15, "$message1 : (+253) 77128840  /  (+253) 77369958", 0, 1, 'L', 0, '', false, 'M', 'M');
        $pdf->Cell(0, 15, "Email : support@electro-multifix.com", 0, 1, 'L', 0, '', false, 'M', 'M');
        $message2 = "Site web";
        $pdf->Cell(0, 15, "$message2 : https://www.electro-multifix.com", 0, 1, 'L', 0, '', false, 'M', 'M');



        $pdf->Ln(8);

        if ($commande->isstatus()) {

            $pdf->SetFont('times', 'B', 12);
            $message3 = "Information de la commande";
            $pdf->Cell(150, -3, $message3, 0, 1, 'L');

            $pdf->SetFont('times', 12);
            $pdf->Cell(150, -3, $commande->getUser()->getNom(), 0, 1, 'L');



            $pdf->Cell(150, -3, "Téléphone : " . $commande->getUser()->getPhone(), 0, 1, 'L');



            $pdf->SetFont('times', 12);
            $pdf->Cell(150, -3, "Email : " . $commande->getUser()->getEmail(), 0, 1, 'L');


            // Espacement
            $pdf->Ln(10);

            // Titre "Facture de la commande"
            $pdf->Cell(0, 22, "Liste des produits commandées", 0, 1, 'C');

            // Construction de la table en HTML
            $tableHTML = '
        <table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse; text-align:center;">
            <thead style="background-color:#D6C7C4; color:black;">
             <tr>
                <th>Liste des Produits</th>
                <th>Prix Unitaire</th>
                <th>Quantité</th>
                <th>Prix Total </th>
            </tr>
            </thead>
            <tbody>';

            // Ajout des lignes dynamiques pour chaque produit
            foreach ($commande->getCommandeProduits() as $commandeProduit) {
                $produit = $commandeProduit->getProduit();
                $titre = htmlspecialchars($produit->getTitre());
                $prixUnitaire = htmlspecialchars($produit->getPrix());
                $quantite = htmlspecialchars($commandeProduit->getQuantity());
                $prixTotal = htmlspecialchars($commandeProduit->getQuantity() * $produit->getPrix());

                $tableHTML .= '
                            <tr>
                                <td>' . $titre . '</td>
                                <td>' . $prixUnitaire . '</td>
                                <td>' . $quantite . '</td>
                                <td>' . $prixTotal . ' DJF </td>
                            </tr>

                            ';
            }


            // Fermeture de la table
            $tableHTML .= ' <tr>
                                <td></td>
                                <td></td>
                                <td style="float:left">Prix total : </td>
                                <td>' . $commande->getPrixtotal() . ' DJF </td>
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
        $pdf->Cell(0, 10, 'Merci pour votre commande. Contactez-nous pour toute question.', 0, 0, 'C');

        // Générer le PDF et l'envoyer en réponse
        $pdfContent = $pdf->Output('Facture_commande_' . $commande->getMatricule(), 'I');  // S pour renvoyer le contenu au lieu de l'enregistrer dans un fichier
        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="facture_commande_' . $commande->getId() . '.pdf"',
        ]);
    }
}
