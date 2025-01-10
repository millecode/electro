<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\MailjetService;
use App\Repository\LogosRepository;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    private $categorieRepo;
    private $logoRepo;

    public function __construct(CategorieRepository $categorieRepo, LogosRepository $logoRepo)
    {
        $this->categorieRepo = $categorieRepo;
        $this->logoRepo = $logoRepo;
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        //Menu de categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();


        if ($this->getUser()) {
            return $this->redirectToRoute('app_logout');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();



        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'categoriess' => $categoriess,
            "logo" => $lastActiveLogos

        ]);
    }










    #[Route('/mot-de-passe-oublier', name: 'mot-de-passe-oublier')]
    public function forgotPassword(Request $request, EntityManagerInterface $manager, MailjetService $mailjetService): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $manager->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user) {
                // Générer un token
                $token = bin2hex(random_bytes(25)); // 50 caractères
                $user->setToken($token);
                $user->setStatus(false);
                $manager->flush();

                // Envoyer l'email de réinitialisation
                $recipientEmail = $user->getEmail();
                $recipientName = $user->getNom();
                $subject = 'Réinitialisation de votre mot de passe.';
                $textPart = '';
                $htmlPart = 'Bonjour ' . $user->getNom() . ',<br><br>
                    Cliquez sur le lien suivant pour réinitialiser votre mot de passe :<br>
                    Si vous n\'avez pas demandé cette réinitialisation, ignorez cet e-mail. <br>' .
                    '<a href="' . $this->generateUrl('reset_password', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL) . '">Réinitialiser mon mot de passe.</a>';

                $success = $mailjetService->sendEmail($recipientEmail, $recipientName, $subject, $textPart, $htmlPart);

                if ($success) {
                    $this->addFlash('success', 'Un e-mail de réinitialisation a été envoyé.');
                    return $this->redirectToRoute('mot-de-passe-oublier');
                } else {
                    $this->addFlash('danger', 'Erreur de réinitialisation. Veuillez contactez les administrateurs.');
                    return $this->redirectToRoute('mot-de-passe-oublier');
                }
            } else {
                $this->addFlash('error', "Aucun utilisateur trouvé avec cet e-mail.");
            }

            return $this->redirectToRoute('forgot_password');
        }


        //Menu de categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();


        return $this->render('security/forgot_password.html.twig', [
            "logo" => $lastActiveLogos,
            'categoriess' => $categoriess
        ]);
    }



    #[Route('/reset-password/{token}', name: 'reset_password')]
    public function resetPassword(string $token, Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $passwordHasher): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        // Chercher l'utilisateur avec ce token
        $user = $manager->getRepository(User::class)->findOneBy(['token' => $token]);
        if (!$user) {
            $this->addFlash('danger', 'Réinitialisation expiré, si vous avez oublié votre mot de passe. Veuillez réinitialiser de nouveau.');
            return $this->redirectToRoute('app_login');
        }
        $user->setStatus(true);

        if ($request->isMethod('POST')) {
            $newPassword = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            // Vérifier si les mots de passe correspondent
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('reset_password', ['token' => $token]);
            }

            // Hash du nouveau mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);
            // Générer un nouveau token
            $token = bin2hex(random_bytes(25)); // 50 caractères
            $user->setToken($token);
            $manager->flush();

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès.');
            return $this->redirectToRoute('app_login');
        }

        //Menu de categories
        $categoriess = $this->categorieRepo->findAll();
        // Dernier logo avec status = true
        $lastActiveLogos = $this->logoRepo->findLastActiveLogos();

        return $this->render('security/reset_password.html.twig', [
            'token' => $token,
            "logo" => $lastActiveLogos,
            'categoriess' => $categoriess
        ]);
    }


    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {

        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }


    #[Route('/access-denied-redirect', name: 'app_access_denied_redirect')]
    public function accessDeniedRedirect(): RedirectResponse
    {
        return $this->redirectToRoute('app_logout');
    }
}
