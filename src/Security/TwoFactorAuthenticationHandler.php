<?php

namespace App\Security;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Twig\Environment;

class TwoFactorAuthenticationHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private RequestStack $requestStack
    ) {}

    public function processTwoFactor(Utilisateur $user): bool
    {
        if (!$user->isTwoFactorEnabled()) {
            return false;
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $user->setTwoFactorCode($code);
        $user->setTwoFactorExpiresAt(new \DateTimeImmutable('+10 minutes'));
        
        $this->em->flush();

        $this->mailer->send((new TemplatedEmail())
            ->from(new Address('dhekerderouiche04@gmail.com', 'FarmVision'))
            ->to($user->getEmail())
            ->subject('🔐 Code de vérification FarmVision')
            ->htmlTemplate('emails/two_factor_code.html.twig')
            ->context([
                'user' => $user,
                'code' => $code,
            ])
        );

        $this->requestStack->getSession()->set('2fa_user_id', $user->getId());
        
        return true;
    }

    public function verifyCode(Utilisateur $user, string $code): bool
    {
        if (!$user->isTwoFactorEnabled()) {
            return true;
        }

        if ($user->getTwoFactorCode() !== $code) {
            return false;
        }

        $now = new \DateTimeImmutable();
        if ($user->getTwoFactorExpiresAt() < $now) {
            return false;
        }

        $user->setTwoFactorCode(null);
        $user->setTwoFactorExpiresAt(null);
        $this->em->flush();

        $this->requestStack->getSession()->remove('2fa_user_id');
        
        return true;
    }

    public function isInTwoFactorFlow(): bool
    {
        return $this->requestStack->getSession()->has('2fa_user_id');
    }

    public function getTwoFactorUserId(): ?int
    {
        return $this->requestStack->getSession()->get('2fa_user_id');
    }
}