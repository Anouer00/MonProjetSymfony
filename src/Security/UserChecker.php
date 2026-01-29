<?php

namespace App\Security;

use App\Entity\User as AppUser;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * vérifie le statut du compte utilisateur lors de l'authentification
 * point clé pour la gestion de la vérification d'email et la traçabilité
 */
class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof AppUser) {
            return;
        }

        // on bloque l'accès si l'email n'a pas été vérifié
        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException('Veuillez confirmer votre e-mail avant de vous connecter.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof AppUser) {
            return;
        }

        // mise à jour de la date de dernière connexion
        $user->setLastLogin(new \DateTime());
        
        // le flush est géré automatiquement lors de la redirection si configuré
    }
}