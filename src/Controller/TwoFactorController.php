<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Security\TwoFactorAuthentication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/2fa')]
class TwoFactorController extends AbstractController
{
    #[Route('/verify', name: '2fa_verify')]
    public function verify(
        Request $request,
        TwoFactorAuthentication $twoFactorAuth,
        EntityManagerInterface $em
    ): Response {
        $session = $request->getSession();
        $userId = $session->get('2fa_pending_user_id');

        if (!$userId) {
            return $this->redirectToRoute('app_login');
        }

        $user = $em->getRepository(Utilisateur::class)->find($userId);
        if (!$user) {
            $session->invalidate();
            return $this->redirectToRoute('app_login');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $code = $request->request->get('code', '');

            if ($twoFactorAuth->verifyCode($user, $code)) {
                $session->remove('2fa_pending_user_id');
                $session->set('_security_main_target_path', 'app_dashboard_redirect');
                
                $this->addFlash('success', 'Connexion réussie!');
                return $this->redirectToRoute('app_dashboard_redirect');
            } else {
                $error = 'Code invalide ou expiré';
            }
        }

        return $this->render('security/2fa_verify.html.twig', [
            'email' => $user->getEmail(),
            'error' => $error,
        ]);
    }

    #[Route('/resend', name: '2fa_resend')]
    public function resend(
        Request $request,
        TwoFactorAuthentication $twoFactorAuth,
        EntityManagerInterface $em
    ): Response {
        $session = $request->getSession();
        $userId = $session->get('2fa_pending_user_id');
        
        if ($userId) {
            $user = $em->getRepository(Utilisateur::class)->find($userId);
            if ($user) {
                $twoFactorAuth->generateAndSendCode($user);
                $this->addFlash('success', 'Nouveau code envoyé!');
            }
        }

        return $this->redirectToRoute('2fa_verify');
    }
}