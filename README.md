TP 7 : Micro-service de Paiement (Stripe)
Branche : tp7
Auteur : Anouer OUERGHI

-- Description --
Ce code est un micro-service indépendant pour gérer les paiements d'une marketplace type Vinted.
Il utilise Stripe Connect (comptes Express) pour gérer les vendeurs.

J'ai isolé ce code dans une branche à part car il ne dépend pas du reste de l'appli (User/Book).

-- Fonctionnement Technique --
J'utilise le flux "Separate Charges and Transfers" pour gérer le séquestre (Escrow) :
1. L'acheteur paie -> L'argent est bloqué sur le compte plateforme.
2. Validation -> L'API calcule la commission (7%) et vire le reste au vendeur.
3. Sécurité -> Les statuts sont mis à jour via Webhook (signature vérifiée).

-- Comment tester l'API (Postman) --

1. Créer un vendeur
POST /api/sellers/onboard
Body: {"email": "test@vendeur.com"}
-> Réponse : URL d'onboarding Stripe (à ouvrir pour valider le compte fictif).

2. Initier un paiement
POST /api/payments/intent
Body: {"amount": 1000, "seller_id": 1}
-> Réponse : Crée le paiement en statut PENDING.

3. Simuler le paiement (Webhook)
Comme on est en local, il faut utiliser le CLI Stripe :
> stripe listen --forward-to localhost:8000/api/webhook
> stripe trigger payment_intent.succeeded
-> Résultat : Le statut passe à PAID en base de données.

4. Débloquer l'argent (Transfert)
POST /api/payments/{id}/confirm-reception
-> Résultat : Virement effectif vers le vendeur.

-- Configuration --
Renommer .env et ajouter les clés de test Stripe :
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
