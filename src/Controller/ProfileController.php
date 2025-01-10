<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Service\MailjetService;
use App\Repository\LogosRepository;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileController extends AbstractController
{
    private $categorieRepo;
    private $logoRepo;

    public function __construct(CategorieRepository $categorieRepo, LogosRepository $logoRepo)
    {
        $this->categorieRepo = $categorieRepo;
        $this->logoRepo = $logoRepo;
    }


    #[Route('/profile', name: 'profile')]
    public function index(): Response
    {

        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }


        //Menu de categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('profile/profile.html.twig', [
            'user' => $user,
            'categoriess' => $categoriess,
            "logo" => $lastActiveLogos


        ]);
    }



    #[Route('/profile/modification', name: 'modifier_profile')]
    public function editProfile(Request $request, EntityManagerInterface $manager, MailjetService $mailjetService): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }


        if ($request->isMethod('POST')) {

            $nom = $request->request->get('nom');
            $email = $request->request->get('email');
            $phone = $request->request->get('phone');
            $adresse = $request->request->get('adresse');
            $token = bin2hex(random_bytes(25));

            $user->setNom($nom);
            $user->setEmail($email);
            $user->setPhone($phone);
            $user->setAdresse($adresse);
            $user->setStatus(false); // Email à re-valider
            $user->setToken($token); // Nouveau token
            $manager->flush();

            // Envoi d'un email de confirmation
            $recipientEmail = $user->getEmail();
            $recipientName = $user->getNom();
            $subject = 'Confirmation de votre email.';
            $textPart = '';
            $htmlPart = '<p>Bonjour vous venez de faire une modification de votre profile. Veuillez confirmer votre adresse e-mail afin de continuer à utiliser nos services :</p>' .
                '<a href="' . $this->generateUrl('confirmer_email_profil', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL) . '">Confirmer votre email.</a>';

            $success = $mailjetService->sendEmail($recipientEmail, $recipientName, $subject, $textPart, $htmlPart);

            if ($success) {
                // Déconnexion automatique
                $this->container->get('security.token_storage')->setToken(null);
                $request->getSession()->invalidate();

                $this->addFlash('success', 'Vous avez bien modifier votre profile. Un e-mail de confirmation vous a été envoyé. Nous vous invitons à le vérifier et à confirmer votre adresse.');
                return $this->redirectToRoute('app_login');
            } else {
                // Déconnexion automatique
                $this->container->get('security.token_storage')->setToken(null);
                $request->getSession()->invalidate();

                $this->addFlash('danger', 'Erreur de modification. Veuillez contactez les administrateurs.');
                return $this->redirectToRoute('app_login');
            }
        }

        //Menu de categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('profile/edit_profile.html.twig', [
            'user' => $user,
            "logo" => $lastActiveLogos,
            'categoriess' => $categoriess
        ]);
    }



    #[Route('/profile/confirmer-email-profil/{token}', name: 'confirmer_email_profil')]
    public function confirmerEmailProfil(string $token, EntityManagerInterface $manager): Response
    {
        // Recherche de l'utilisateur correspondant au token
        $user = $manager->getRepository(User::class)->findOneBy(['token' => $token]);

        if (!$user) {
            $this->addFlash('danger', 'Le lien de confirmation est invalide ou a expiré.');
            return $this->redirectToRoute('app_login');
        }

        // Mise à jour du statut et suppression du token
        $user->setStatus(true);
        $user->setToken(bin2hex(random_bytes(25)));
        $manager->flush();

        $this->addFlash('success', 'Votre email a été confirmé avec succès. Vous pouvez maintenant vous connecter.');
        return $this->redirectToRoute('app_login');
    }













































    #[Route('/profile/change-password', name: 'modifier_password')]
    public function changePassword(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $manager, MailjetService $mailjetService): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            if (!$hasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('danger', 'Votre mot de passe actuel est incorrect.');
                return $this->redirectToRoute('modifier_password');
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('danger', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('modifier_password');
            }



            $user->setPassword($hasher->hashPassword($user, $newPassword));
            $user->setStatus(false); // Email à re-valider
            $token = bin2hex(random_bytes(25));
            $user->setToken($token); // Nouveau token
            $manager->flush();

            // Envoi d'un email de confirmation
            $recipientEmail = $user->getEmail();
            $recipientName = $user->getNom();
            $textPart = '';
            $subject = 'Modification du Mot de passe.';
            $htmlPart = '<p>Bonjour vous venez de faire une modification de votre mot de passe. Veuillez confirmer votre adresse e-mail afin de continuer à utiliser nos services :</p>' .
                '<a href="' . $this->generateUrl('confirmer_email_password', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL) . '">Confirmer votre email.</a>';

            $success = $mailjetService->sendEmail($recipientEmail, $recipientName, $subject, $textPart, $htmlPart);

            if ($success) {
                // Déconnexion automatique
                $this->container->get('security.token_storage')->setToken(null);
                $request->getSession()->invalidate();

                $this->addFlash('success', 'Vous avez bien modifier votre mot de passe. Un e-mail de confirmation vous a été envoyé. Nous vous invitons à le vérifier et à confirmer votre adresse.');
                return $this->redirectToRoute('app_login');
            } else {
                // Déconnexion automatique
                $this->container->get('security.token_storage')->setToken(null);
                $request->getSession()->invalidate();

                $this->addFlash('danger', 'Erreur de modification. Veuillez contactez les administrateurs.');
                return $this->redirectToRoute('app_login');
            }
        }




        //Menu de categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('profile/change_password.html.twig', [
            'categoriess' => $categoriess,
            "logo" => $lastActiveLogos

        ]);
    }



    #[Route('/profile/confirmer-email-password/{token}', name: 'confirmer_email_password')]
    public function confirmerEmailPassword(string $token, EntityManagerInterface $manager): Response
    {
        // Recherche de l'utilisateur correspondant au token
        $user = $manager->getRepository(User::class)->findOneBy(['token' => $token]);

        if (!$user) {
            $this->addFlash('danger', 'Le lien de confirmation est invalide ou a expiré.');
            return $this->redirectToRoute('app_login');
        }

        // Mise à jour du statut et suppression du token
        $user->setStatus(true);
        $user->setToken(bin2hex(random_bytes(25)));
        $manager->flush();

        $this->addFlash('success', 'Votre email a été confirmé avec succès. Vous pouvez maintenant vous connecter.');
        return $this->redirectToRoute('app_login');
    }
}
