<?php

namespace App\Controller\Api;

use App\Entity\SellerAccount;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\Account;
use Stripe\AccountLink;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/api/sellers')]
class SellerController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        // Initialisation de Stripe avec la clé secrète globale
        if (isset($_ENV['STRIPE_SECRET_KEY'])) {
            Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        }
    }

    /**
     * Étape 1 : Création d'un compte Stripe Express et génération du lien d'onboarding.
     */
    #[Route('/onboard', name: 'api_sellers_onboard', methods: ['POST'])]
    public function onboard(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['error' => 'Email manquante'], Response::HTTP_BAD_REQUEST);
        }

        // 1. Vérification de l'existence locale du vendeur
        $seller = $this->entityManager->getRepository(SellerAccount::class)->findOneBy(['email' => $email]);

        if (!$seller) {
            $seller = new SellerAccount();
            $seller->setEmail($email);
            $seller->setIsVerified(false);
            $this->entityManager->persist($seller);
        }

        // 2. Création du compte Stripe connecté (si inexistant)
        if (!$seller->getStripeAccountId()) {
            try {
                $stripeAccount = Account::create([
                    'type' => 'express',
                    'country' => 'FR',
                    'email' => $email,
                    'capabilities' => [
                        'card_payments' => ['requested' => true],
                        'transfers' => ['requested' => true],
                    ],
                ]);

                $seller->setStripeAccountId($stripeAccount->id);
                $this->entityManager->flush();
            } catch (\Exception $e) {
                return $this->json(['error' => 'Erreur Stripe : ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        // 3. Génération du lien d'Onboarding (Account Link)
        try {
            $accountLink = AccountLink::create([
                'account' => $seller->getStripeAccountId(),
                'refresh_url' => $this->generateUrl('api_sellers_status', ['id' => $seller->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'return_url' => $this->generateUrl('api_sellers_status', ['id' => $seller->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
                'type' => 'account_onboarding',
            ]);

            return $this->json([
                'seller_id' => $seller->getId(),
                'stripe_account_id' => $seller->getStripeAccountId(),
                'onboarding_url' => $accountLink->url // URL de redirection pour le frontend
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur Lien Stripe : ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Étape 2 : Vérification du statut du vendeur (KYC).
     */
    #[Route('/{id}/status', name: 'api_sellers_status', methods: ['GET'])]
    public function status(SellerAccount $seller): JsonResponse
    {
        if (!$seller->getStripeAccountId()) {
            return $this->json(['status' => 'incomplete', 'message' => 'Aucun compte Stripe lié']);
        }

        try {
            // Récupération des détails du compte Stripe
            $stripeAccount = Account::retrieve($seller->getStripeAccountId());

            // Vérifie si les paiements et virements sont activés (KYC validé)
            $isVerified = $stripeAccount->charges_enabled && $stripeAccount->payouts_enabled;
            
            // Mise à jour de la base de données locale
            if ($seller->isVerified() !== $isVerified) {
                $seller->setIsVerified($isVerified);
                $this->entityManager->flush();
            }

            return $this->json([
                'status' => $isVerified ? 'verified' : 'pending',
                'details' => [
                    'charges_enabled' => $stripeAccount->charges_enabled,
                    'payouts_enabled' => $stripeAccount->payouts_enabled,
                    'requirements' => $stripeAccount->requirements->currently_due // Documents manquants
                ]
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}