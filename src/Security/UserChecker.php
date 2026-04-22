<?php
// src/Security/UserChecker.php

namespace App\Security;

use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Utilisateur) {
            return;
        }

        // Vérification compte inactif (non activé par admin)
        if (!$user->isActivated()) {
            throw new CustomUserMessageAccountStatusException(
                'Votre compte est en attente d\'activation par un administrateur.'
            );
        }

        // Vérification ban
        if ($user->isBanned()) {
            $reason  = $user->getBanReason() ?? 'Non précisée';
            $expires = $user->getBanExpiresAt();

            if ($expires !== null) {
                // Ban temporaire — affiche la durée restante
                $now  = new \DateTimeImmutable();
                $diff = $now->diff($expires);

                if ($diff->days > 0) {
                    $duree = $diff->days . ' jour' . ($diff->days > 1 ? 's' : '');
                } elseif ($diff->h > 0) {
                    $duree = $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
                } else {
                    $duree = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
                }

                throw new CustomUserMessageAccountStatusException(
                    "🚫 Votre compte est suspendu.\n" .
                    "Raison : {$reason}\n" .
                    "Levée du ban dans : {$duree} (le " . $expires->format('d/m/Y à H:i') . ")"
                );
            }

            // Ban permanent
            throw new CustomUserMessageAccountStatusException(
                "🚫 Votre compte a été banni définitivement.\n" .
                "Raison : {$reason}"
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void {}
}