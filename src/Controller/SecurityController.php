<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * gère l'affichage du formulaire de connexion et les erreurs associées
     */
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, EntityManagerInterface $entityManager): Response
    {
        // si l'utilisateur est déjà connecté, on le redirige vers son profil
        if ($this->getUser()) {
             $entityManager->flush(); // sauvegarde le lastLogin mis à jour par UserChecker
             return $this->redirectToRoute('app_team_profile', ['name' => 'alice']);
        }

        // récupère l'erreur de connexion s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        
        // dernier identifiant saisi par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername, 
            'error' => $error
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        // cette méthode reste vide, elle est interceptée par le firewall
        throw new \LogicException('Cette méthode est interceptée par la clé logout du firewall.');
    }
}