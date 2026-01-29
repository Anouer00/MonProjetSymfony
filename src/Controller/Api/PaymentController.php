<?php

namespace App\Controller\Api;

use App\Entity\Payment;
use App\Entity\SellerAccount;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Transfer;
use Stripe\Refund;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/payments')]
class PaymentController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        // Définition de la clé API globale
        if (isset($_ENV['STRIPE_SECRET_KEY'])) {
            Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        }
    }

    /**
     * 1. CRÉATION DU PAIEMENT (Encaissement Plateforme)
     * L'acheteur paie, mais l'argent reste sur le compte plateforme (Escrow).
     */
    #[Route('/intent', name: 'api_payments_create_intent', methods: ['POST'])]
    public function createIntent(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        // Validation des données
        if (empty($data['amount']) || empty($data['seller_id'])) {
            return $this->json(['error' => 'Données manquantes (amount, seller_id)'], Response::HTTP_BAD_REQUEST);
        }

        // Récupération du vendeur
        $seller = $this->entityManager->getRepository(SellerAccount::class)->find($data['seller_id']);
        if (!$seller || !$seller->getStripeAccountId() || !$seller->isVerified()) {
            return $this->json(['error' => 'Vendeur invalide ou non vérifié (KYC)'], Response::HTTP_FORBIDDEN);
        }

        // Calcul de la Commission (TP : 5% - 7%)
        // Exemple : 100€ (10000 cts) -> Commission 7€ (700 cts) -> Vendeur 93€
        $amount = (int) $data['amount'];
        $commissionRate = 0.07; // 7%
        $commissionAmount = (int) round($amount * $commissionRate);

        try {
            // Création du PaymentIntent sur Stripe
            // NOTE : Pas de 'transfer_data' ici pour permettre le séquestre (Escrow manuel)
            $intent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'eur',
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'seller_id' => $seller->getId(), // Utile pour les Webhooks
                    'commission' => $commissionAmount
                ],
            ]);

            // Persistance du paiement en base locale
            $payment = new Payment();
            $payment->setAmount($amount);
            $payment->setCommissionAmount($commissionAmount);
            $payment->setStripePaymentIntentId($intent->id);
            $payment->setStatus('PENDING'); // En attente de paiement client
            $payment->setSeller($seller);

            $this->entityManager->persist($payment);
            $this->entityManager->flush();

            return $this->json([
                'payment_id' => $payment->getId(),
                'client_secret' => $intent->client_secret, // Requis par le Frontend (Stripe.js)
                'amount' => $amount,
                'commission' => $commissionAmount
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 2. CONFIRMATION DE RÉCEPTION (Libération des fonds)
     * Cet endpoint est appelé lorsque l'acheteur confirme la réception.
     */
    #[Route('/{id}/confirm-reception', name: 'api_payments_confirm', methods: ['POST'])]
    public function confirmReception(Payment $payment): JsonResponse
    {
        // Vérification que le paiement a bien été encaissé (PAID)
        // Le statut 'PAID' doit être mis à jour par le Webhook.
        if ($payment->getStatus() !== 'PAID') {
            return $this->json(['error' => 'Le paiement n\'est pas encore validé ou déjà complété.'], Response::HTTP_BAD_REQUEST);
        }

        $seller = $payment->getSeller();
        
        // Calcul du montant net à transférer
        $amountToTransfer = $payment->getAmount() - $payment->getCommissionAmount();

        try {
            // Transfert manuel vers le compte Stripe du vendeur
            $transfer = Transfer::create([
                'amount' => $amountToTransfer,
                'currency' => 'eur',
                'destination' => $seller->getStripeAccountId(),
                'transfer_group' => $payment->getStripePaymentIntentId(), // Lien logique
            ]);

            // Mise à jour du statut local
            $payment->setStatus('COMPLETED');
            $this->entityManager->flush();

            return $this->json([
                'status' => 'success', 
                'transferred_amount' => $amountToTransfer,
                'transfer_id' => $transfer->id
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur de transfert : ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 3. REMBOURSEMENT (Refund)
     * Annulation par l'acheteur AVANT validation de réception.
     */
    #[Route('/{id}/refund', name: 'api_payments_refund', methods: ['POST'])]
    public function refund(Payment $payment): JsonResponse
    {
        // Si les fonds sont déjà transférés, impossible de rembourser simplement (hors scope)
        if ($payment->getStatus() === 'COMPLETED') {
            return $this->json(['error' => 'Remboursement impossible : fonds déjà transférés au vendeur.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Remboursement via Stripe
            $refund = Refund::create([
                'payment_intent' => $payment->getStripePaymentIntentId(),
            ]);

            $payment->setStatus('REFUNDED');
            $this->entityManager->flush();

            return $this->json(['status' => 'refunded', 'refund_id' => $refund->id]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}