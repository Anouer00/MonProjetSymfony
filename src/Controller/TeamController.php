<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TeamController extends AbstractController
{
    #[Route('/team/{name}', name: 'app_team_profile')]
    public function profile(string $name): Response
    {
        // données simulées pour la démonstration
        $teamMembers = [
            'alice' => [
                'role' => 'Développeuse Senior',
                'description' => 'Passionnée par les architectures distribuées et le clean code.',
                'image' => 'https://via.placeholder.com/150/FF5733/FFFFFF?text=Alice'
            ],
            'bob' => [
                'role' => 'Chef de Projet',
                'description' => 'Expert en gestion agile, il assure la bonne marche des projets.',
                'image' => 'https://via.placeholder.com/150/33FF57/FFFFFF?text=Bob'
            ],
            'charlie' => [
                'role' => 'Designer UX/UI',
                'description' => 'Créateur d\'expériences utilisateur intuitives et esthétiques.',
                'image' => 'https://via.placeholder.com/150/3357FF/FFFFFF?text=Charlie'
            ],
        ];

        $nameLower = strtolower($name);
        
        // gestion explicite de l'erreur 404 si le membre n'existe pas
        if (!isset($teamMembers[$nameLower])) {
            throw $this->createNotFoundException("Le membre '$name' n'existe pas dans l'équipe.");
        }

        $memberData = $teamMembers[$nameLower];

        return $this->render('team/profile.html.twig', [
            'name' => ucfirst($name),
            'role' => $memberData['role'],
            'description' => $memberData['description'],
            'image' => $memberData['image'],
        ]);
    }
}