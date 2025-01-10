<?php

namespace App\Controller;

use DateTime;
use App\Entity\Contacts;
use App\Form\ContactsType;
use App\Service\MailjetService;
use App\Repository\LogosRepository;
use App\Repository\ContactsRepository;
use App\Repository\CategorieRepository;
use App\Repository\CoordonnerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ContactController extends AbstractController
{

    private $categorieRepo;
    private $logoRepo;

    public function __construct(CategorieRepository $categorieRepo, LogosRepository $logoRepo)
    {
        $this->categorieRepo = $categorieRepo;
        $this->logoRepo = $logoRepo;
    }


    #[Route('/contact', name: 'contact')]
    public function index(Request $request, EntityManagerInterface $manager, MailjetService $mailjetService, CoordonnerRepository $repoC): Response
    {
        // Dernier coordonner avec status = true
        $lastActiveCoordonner = $repoC->findLastActiveCoordonner();

        $contact = new Contacts();
        $form = $this->createForm(ContactsType::class, $contact);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $token = bin2hex(random_bytes(32));
            $contact->setToken($token);
            $contact->setDate(new DateTime());
            $contact->setStatusemail(false);
            $manager->persist($contact);
            $manager->flush();
            //Envoie d'email
            $recipientEmail = $contact->getEmail();
            $recipientName = $contact->getNom();
            $subject = 'Confirmation de votre email.';
            $textPart = '';
            $htmlPart = '<p>Nous vous remercions pour votre prise de contact. Afin d\'ouvrir notre discussion dans un souci de transparence optimale, nous vous prions de bien vouloir confirmer votre adresse e-mail." :</p>' .
                '<a href="' . $this->generateUrl('confirmation_contact', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL) . '">Confirmer votre email.</a>';

            $success = $mailjetService->sendEmail($recipientEmail, $recipientName, $subject, $textPart, $htmlPart);

            if ($success) {
                $this->addFlash('success', 'Merci de votre contact, un e-mail de confirmation a été envoyé. Veuillez confirmer.');
                return $this->redirectToRoute('contact');
            }
        }


        //Menu Categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('contact/contact.html.twig', [
            'form' => $form->createView(),
            'categoriess' => $categoriess,
            'lastActiveCoordonner' => $lastActiveCoordonner, // On envoie le dernier coordonner actif
            "logo" => $lastActiveLogos


        ]);
    }


    #[Route('/contact/confirmation-mail/{token}', name: 'confirmation_contact')]
    public function ConfirmationMail(Request $request, EntityManagerInterface $manager, $token, ContactsRepository $repoContacts): Response
    {
        $contacts = $repoContacts->findOneBy(['token' => $token]);
        if (empty($contacts)) {
            return $this->redirectToRoute('contact');
        } else {
            $contacts->setStatusemail(true);
            $manager->flush();
            $this->addFlash('success', "Votre e-mail a bien été confirmé. Nous prendrons contact avec vous dans les 48 heures.");
            return $this->redirectToRoute('contact');
        }

        return $this->redirectToRoute('contact');
    }
}
