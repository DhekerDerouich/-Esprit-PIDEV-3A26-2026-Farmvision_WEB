<?php

namespace App\Security;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class TwoFactorAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private RouterInterface $router,
        private TwoFactorAuthentication $twoFactorAuth
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        $user = $token->getUser();

        if (!$user instanceof Utilisateur) {
            return new RedirectResponse($this->router->generate('app_dashboard_redirect'));
        }

        if ($this->twoFactorAuth->isAdmin($user)) {
            return new RedirectResponse($this->router->generate('app_dashboard_redirect'));
        }

        if ($this->twoFactorAuth->requiresTwoFactor($user)) {
            $user->setTwoFactorEnabled(true);
            $this->em->flush();
            
            $this->twoFactorAuth->generateAndSendCode($user);

            $session = $request->getSession();
            $session->set('2fa_pending_user_id', $user->getId());

            return new RedirectResponse($this->router->generate('2fa_verify'));
        }

        return new RedirectResponse($this->router->generate('app_dashboard_redirect'));
    }
}