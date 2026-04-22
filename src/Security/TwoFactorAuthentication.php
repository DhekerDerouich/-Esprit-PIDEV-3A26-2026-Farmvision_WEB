<?php

namespace App\Security;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class TwoFactorAuthentication
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
    ) {}

    public function generateAndSendCode(Utilisateur $user): bool
    {
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

        return true;
    }

    public function requiresTwoFactor(Utilisateur $user): bool
    {
        $role = $user->getTypeRole();
        return in_array($role, ['AGRICULTEUR', 'RESPONSABLE_EXPLOITATION']);
    }

    public function isAdmin(Utilisateur $user): bool
    {
        return $user->getTypeRole() === 'ADMINISTRATEUR';
    }
}