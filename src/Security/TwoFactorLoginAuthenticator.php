<?php

namespace App\Security;

use App\Entity\Utilisateur;
use App\Security\TwoFactorAuthentication;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;

class TwoFactorLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    public function __construct(
        private EntityManagerInterface $em,
        private RouterInterface $router,
        private RequestStack $requestStack,
        private TwoFactorAuthentication $twoFactorAuth
    ) {}

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [new RememberMeBadge()]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
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

            $session = $this->requestStack->getSession();
            $session->set('2fa_pending_user_id', $user->getId());

            return new RedirectResponse($this->router->generate('2fa_verify'));
        }

        return new RedirectResponse($this->router->generate('app_dashboard_redirect'));
    }

    public function getLoginUrl(Request $request): string
    {
        return $this->router->generate('app_login');
    }
}