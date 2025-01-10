<?php

namespace App\Controller;

use App\Entity\Demande;
use App\Form\DemandeType;
use App\Service\MailjetService;
use App\Repository\LogosRepository;
use App\Repository\DemandeRepository;
use App\Repository\CategorieRepository;
use App\Repository\ServicessRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ServicesController extends AbstractController
{

    private $categorieRepo;
    private $logoRepo;

    public function __construct(CategorieRepository $categorieRepo, LogosRepository $logoRepo)
    {
        $this->categorieRepo = $categorieRepo;
        $this->logoRepo = $logoRepo;
    }


    #[Route('/services', name: 'service')]
    public function services(ServicessRepository $servicessRepository, PaginatorInterface $paginator, Request $request): Response
    {
        // Récupérer la requête pour les services publiés
        $queryBuilder = $servicessRepository->findAllPublishedServices();

        // Paginer les résultats
        $services = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1), // Page actuelle
            20 // Nombre de services par page
        );

        //Menu categorie
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('services/service.html.twig', [
            'categoriess' => $categoriess,
            'services' => $services,
            "logo" => $lastActiveLogos
        ]);
    }





    #[Route('/demande', name: 'demande_service')]
    public function create(Request $request, EntityManagerInterface $manager, MailjetService $mailjetService): Response
    {
        $demande = new Demande();
        $form = $this->createForm(DemandeType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $demande->setStatus(false);
            $demande->setDate(new \DateTime());
            $token = bin2hex(random_bytes(16));
            $demande->setToken($token);
            $demande->setCode(mt_rand(1000, 9999));
            // Les caractères autorisés pour le matricule
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

            // Mélange et sélection aléatoire de caractères
            $matricule = '';
            for ($i = 0; $i < 20; $i++) {
                $matricule .= $characters[random_int(0, strlen($characters) - 1)];
            }
            $demande->setMatricule($matricule);

            $manager->persist($demande);
            $manager->flush();

            //Envoie d'email
            $recipientEmail = $demande->getEmail();
            $recipientName = $demande->getNom();
            $subject = 'Confirmation de votre email.';
            $textPart = '';
            $htmlPart = '<p>Nous vous remercions pour votre demande de service. Cependant , nous allons verifiez votre email, Veuillez confirmer votre adresse e-mail." :</p>' .
                '<a href="' . $this->generateUrl('confirmation_demande', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL) . '">Confirmer votre email.</a>';

            $success = $mailjetService->sendEmail($recipientEmail, $recipientName, $subject, $textPart, $htmlPart);

            if ($success) {
                $this->addFlash('success', 'Merci de votre demande de service, un e-mail de confirmation a été envoyé vers votre adresse email. Veuillez confirmer.');
                return $this->redirectToRoute('demande_service');
            } else {
                $this->addFlash('danger', 'Nous avons bien recus votre demande de service.');
                return $this->redirectToRoute('demande_service');
            }
        }




        //Menu categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();


        return $this->render('services/demande.html.twig', [
            'form' => $form->createView(),
            "logo" => $lastActiveLogos,
            'categoriess' => $categoriess
        ]);
    }


    //Confirmation d'email
    #[Route('/demande/confirmation-mail/{token}', name: 'confirmation_demande')]
    public function ConfirmationMail(Request $request, EntityManagerInterface $manager, $token, DemandeRepository $repodemande): Response
    {
        $demande = $repodemande->findOneBy(['token' => $token]);
        if (empty($demande)) {
            return $this->redirectToRoute('demande_service');
        } else {
            $demande->setStatus(true);
            $manager->flush();
            $this->addFlash('success', "Votre e-mail a bien été confirmé. Nous analyserons votre demande de service et nous allons vous contactez dans 24 heurs.");
            return $this->redirectToRoute('demande_service');
        }

        return $this->redirectToRoute('demande_service');
    }
}
