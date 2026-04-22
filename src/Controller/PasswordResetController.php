<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PasswordResetController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        UtilisateurRepository $userRepository,
        EntityManagerInterface $em,
        MailerInterface $mailer,
    ): Response {
        $errors = [];
        $email  = trim($request->request->get('email', ''));

        if ($request->isMethod('POST')) {

            if ($email === '') {
                $errors['email'] = '❌ L\'email est obligatoire.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = '❌ Format d\'email invalide. Exemple: nom@domaine.com';
            }

            if ($errors === []) {
                $user = $userRepository->findOneBy(['email' => $email]);

                if ($user instanceof Utilisateur) {
                    $token = bin2hex(random_bytes(32));
                    $user->setResetPasswordToken($token);
                    $user->setResetPasswordExpiresAt(new \DateTimeImmutable('+1 hour'));

                    // FIX: flush AVANT l'envoi — le token doit être en base
                    // avant que l'utilisateur clique sur le lien
                    $em->flush();

                    $resetUrl = $this->generateUrl(
                        'app_reset_password',
                        ['token' => $token],
                        UrlGeneratorInterface::ABSOLUTE_URL
                    );

                    try {
                        $mailer->send(
                            (new TemplatedEmail())
                                ->from(new Address('dhekerderouiche04@gmail.com', 'FarmVision'))
                                ->to($user->getEmail())
                                ->subject('Réinitialisation de votre mot de passe - FarmVision')
                                ->htmlTemplate('emails/reset_password.html.twig')
                                ->context([
                                    'user'      => $user,
                                    'resetUrl'  => $resetUrl,
                                    'expiresAt' => $user->getResetPasswordExpiresAt(),
                                ])
                        );

                    } catch (TransportExceptionInterface $e) {
                        // Envoi échoué → annuler le token
                        $user->setResetPasswordToken(null);
                        $user->setResetPasswordExpiresAt(null);
                        $em->flush();

                        $this->addFlash('error', '❌ Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());

                        return $this->render('security/forgot_password.html.twig', [
                            'errors' => $errors,
                            'email'  => $email,
                        ]);
                    }
                }

                $this->addFlash('success', '✅ Si un compte existe avec cet email, un lien de réinitialisation vient d\'être envoyé.');
                return $this->redirectToRoute('app_forgot_password');
            }

            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->render('security/forgot_password.html.twig', [
            'errors' => $errors,
            'email'  => $email,
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        Request $request,
        string $token,
        UtilisateurRepository $userRepository,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = $this->findUserForResetToken($token, $userRepository, $em);

        if (!$user instanceof Utilisateur) {
            $this->addFlash('error', '❌ Ce lien de réinitialisation est invalide ou expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $password        = $request->request->get('password', '');
            $passwordConfirm = $request->request->get('password_confirm', '');

            if ($password === '') {
                $errors['password'] = '❌ Le mot de passe est obligatoire.';
            } elseif (strlen($password) < 6) {
                $errors['password'] = '❌ Le mot de passe doit contenir au moins 6 caractères.';
            } elseif (strlen($password) > 255) {
                $errors['password'] = '❌ Le mot de passe est trop long.';
            } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])/', $password)) {
                $errors['password'] = '❌ Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.';
            } elseif ($password !== $passwordConfirm) {
                $errors['password_confirm'] = '❌ Les mots de passe ne correspondent pas.';
            }

            if ($errors === []) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setResetPasswordToken(null);
                $user->setResetPasswordExpiresAt(null);
                $em->flush();

                $this->addFlash('success', '✅ Votre mot de passe a été réinitialisé avec succès.');
                return $this->redirectToRoute('app_login');
            }

            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'token'  => $token,
            'errors' => $errors,
        ]);
    }

    private function findUserForResetToken(
        string $token,
        UtilisateurRepository $userRepository,
        EntityManagerInterface $em,
    ): ?Utilisateur {
        $user = $userRepository->findOneBy(['resetPasswordToken' => $token]);

        if (!$user instanceof Utilisateur) {
            return null;
        }

        $expiresAt = $user->getResetPasswordExpiresAt();
        if (!$expiresAt instanceof \DateTimeImmutable || $expiresAt < new \DateTimeImmutable()) {
            $user->setResetPasswordToken(null);
            $user->setResetPasswordExpiresAt(null);
            $em->flush();
            return null;
        }

        return $user;
    }
}