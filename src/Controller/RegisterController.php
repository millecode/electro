<?php

namespace App\Controller;

use DateTime;
use App\Entity\User;
use App\Form\UserType;
use App\Service\MailjetService;
use App\Repository\UserRepository;
use App\Repository\LogosRepository;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterController extends AbstractController
{
    private $categorieRepo;
    private $logoRepo;

    public function __construct(CategorieRepository $categorieRepo, LogosRepository $logoRepo)
    {
        $this->categorieRepo = $categorieRepo;
        $this->logoRepo = $logoRepo;
    }

    #[Route('/inscription', name: 'inscription')]
    public function index(UserRepository $userRepo, Request $request, EntityManagerInterface $manager, MailjetService $mailjetService, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_logout');
        }

        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $emai = $user->getEmail();
            $verificationEmail = $userRepo->findemail($emai);

            if (empty($verificationEmail)) {
                // Récupérer et nettoyer le numéro de téléphone
                $telephone = $user->getPhone();
                if ($telephone) {
                    $user->setPhone(ltrim($telephone, '+')); // Supprimer le signe '+'
                }

                // Les caractères autorisés pour le matricule
                $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

                // Mélange et sélection aléatoire de caractères
                $matricule = '';
                for ($i = 0; $i < 10; $i++) {
                    $matricule .= $characters[random_int(0, strlen($characters) - 1)];
                }

                $user->setFonction('ROLE_CLIENT');
                $user->setMatricule($matricule);
                $user->setRoles(['ROLE_CLIENT']);
                $user->setStatuscompte(true);
                $hashedPassword = $passwordHasher->hashPassword($user, $form->get('password')->getData());
                $user->setPassword($hashedPassword);
                $token = bin2hex(random_bytes(32));
                $user->setToken($token);
                $user->setDate(new DateTime());
                $user->setStatus(false);
                $manager->persist($user);
                $manager->flush();
                //Envoie d'email
                $recipientEmail = $user->getEmail();
                $recipientName = $user->getNom();
                $subject = 'Confirmation de votre email.';
                $textPart = '';
                $htmlPart = '<p>Merci pour votre inscription. Veuillez confirmer votre adresse e-mail afin de continuer à utiliser nos services :</p>' .
                    '<a href="' . $this->generateUrl('confirmation_inscription', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL) . '">Confirmer votre email.</a>';

                $success = $mailjetService->sendEmail($recipientEmail, $recipientName, $subject, $textPart, $htmlPart);

                if ($success) {
                    $this->addFlash('success', 'Merci pour votre inscription. Un e-mail de confirmation vous a été envoyé. Nous vous invitons à le vérifier et à confirmer votre adresse.');
                    return $this->redirectToRoute('inscription');
                } else {
                    $this->addFlash('danger', 'Erreur d\'inscription. Veuillez contactez les administrateurs.');
                    return $this->redirectToRoute('inscription');
                }
            } else {
                $this->addFlash('danger', "Cette email existe deja");
                return $this->redirectToRoute('inscription');
            }
        }

        //Menu de categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('register/register.html.twig', [
            'form' => $form->createView(),
            'categoriess' => $categoriess,
            "logo" => $lastActiveLogos

        ]);
    }


    #[Route('/inscription/confirmation-mail/{token}', name: 'confirmation_inscription')]
    public function ConfirmationMail(Request $request, EntityManagerInterface $manager, $token, UserRepository $repoUser): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_logout');
        }

        $user = $repoUser->findOneBy(['token' => $token]);
        if (empty($user)) {
            $this->addFlash('danger', "Votre adresse e-mail n'existe pas sur cette plateforme. Pour toute information, veuillez contacter l'administration..");
            return $this->redirectToRoute('inscription');
        } else {
            $user->setStatus(true);
            $manager->flush();
            $this->addFlash('success', "Votre adresse e-mail a été confirmée. Vous pouvez maintenant vous connecter.");
            return $this->redirectToRoute('app_login');
        }


        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();


        return $this->render('register/ConfirmationEmailregister.html.twig', [
            'categoriess' => $categoriess,
            "logo" => $lastActiveLogos
        ]);
    }
}
