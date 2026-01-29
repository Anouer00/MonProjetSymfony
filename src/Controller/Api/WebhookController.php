<?php

namespace App\Controller\Api;

use App\Entity\Payment;
use App\Entity\SellerAccount;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

#[Route('/api/webhook')]
class WebhookController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    #[Route('', name: 'api_stripe_webhook', methods: ['POST'])]
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature');
        $webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'];

        try {
            // 1. Vérification de la signature (SÉCURITÉ CRITIQUE)
            // Cela garantit que la requête vient bien de Stripe et non d'un pirate.
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Payload invalide
            return new Response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            // Signature invalide
            return new Response('Invalid signature', 400);
        }

        // 2. Traitement des événements
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;

            case 'account.updated':
                $this->handleAccountUpdated($event->data->object);
                break;

            default:
                // On ignore les autres événements pour ne pas spammer les logs
                break;
        }

        return new Response('Event received', 200);
    }

    /**
     * Gère le succès d'un paiement : Passe le statut local à 'PAID'.
     */
    private function handlePaymentSucceeded($stripePaymentIntent)
    {
        $payment = $this->entityManager->getRepository(Payment::class)->findOneBy([
            'stripePaymentIntentId' => $stripePaymentIntent->id
        ]);

        if ($payment && $payment->getStatus() !== 'PAID') {
            $payment->setStatus('PAID');
            $this->entityManager->flush();
            $this->logger->info("Paiement validé : " . $payment->getId());
        }
    }

    /**
     * Gère l'échec d'un paiement.
     */
    private function handlePaymentFailed($stripePaymentIntent)
    {
        $payment = $this->entityManager->getRepository(Payment::class)->findOneBy([
            'stripePaymentIntentId' => $stripePaymentIntent->id
        ]);

        if ($payment) {
            $payment->setStatus('FAILED');
            $this->entityManager->flush();
            $this->logger->error("Paiement échoué : " . $payment->getId());
        }
    }

    /**
     * Gère la mise à jour d'un compte vendeur (KYC validé ou non).
     */
    private function handleAccountUpdated($stripeAccount)
    {
        $seller = $this->entityManager->getRepository(SellerAccount::class)->findOneBy([
            'stripeAccountId' => $stripeAccount->id
        ]);

        if ($seller) {
            $isVerified = $stripeAccount->charges_enabled && $stripeAccount->payouts_enabled;
            
            if ($seller->isVerified() !== $isVerified) {
                $seller->setIsVerified($isVerified);
                $this->entityManager->flush();
                $this->logger->info("Statut vendeur mis à jour : " . $seller->getId() . " -> " . ($isVerified ? 'Vérifié' : 'Non vérifié'));
            }
        }
    }
}