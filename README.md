PROJET SYMFONY - TP 7 : MICRO-SERVICE PAIEMENT (STRIPE)


[1] DESCRIPTION DU MODULE
Ce code est un micro-service isolé pour gérer les flux financiers d'une
marketplace (type Vinted/Leboncoin). Il est totalement indépendant
de la partie "Utilisateurs/Livres" du projet principal.

Technologie : Stripe Connect (comptes Express)
Logique     : Système de séquestre (Escrow) avec commission.


[2] FONCTIONNEMENT TECHNIQUE
J'ai implémenté le flux "Separate Charges and Transfers" pour la sécurité :

1. ENCAISSEMENT : L'acheteur paie -> L'argent est bloqué sur la plateforme.
2. COMMISSION   : Le système calcule automatiquement 7% de frais.
3. TRANSFERT    : Le virement au vendeur se fait uniquement après validation.
4. SECURITE     : Utilisation des Webhooks pour valider les statuts (signature vérifiée).


[3] COMMENT TESTER L'API (GUIDE PAS A PAS)
Comme il n'y a pas de Frontend, voici les requêtes à faire avec Postman.

ETAPE 1 : Devenir Vendeur (Onboarding)
> POST /api/sellers/onboard
> Body : {"email": "vendeur@test.com"}
-> RETOUR : Une URL Stripe. L'ouvrir dans le navigateur pour créer le compte fictif.

ETAPE 2 : Vérifier le statut
> GET /api/sellers/{id_vendeur}/status
-> RETOUR : "verified" (une fois l'onboarding fini).

ETAPE 3 : Acheter un produit (Paiement)
> POST /api/payments/intent
> Body : {"amount": 5000, "seller_id": 1}  (Note: 5000 = 50.00 EUR)
-> RETOUR : Crée le paiement. Statut BDD : PENDING.

ETAPE 4 : Simuler la validation Stripe (Webhook)
En local, il faut utiliser le CLI Stripe pour simuler le paiement réussi :
> stripe listen --forward-to localhost:8000/api/webhook
> stripe trigger payment_intent.succeeded
-> RETOUR : Le statut en base de données passe à PAID.

ETAPE 5 : Valider la réception (Débloquer l'argent)
C'est ici que le vendeur reçoit son argent (moins la commission).
> POST /api/payments/{id_paiement}/confirm-reception
-> RETOUR : Transfert effectué vers le compte connecté.


[4] CONFIGURATION (.env)
Il faut ajouter vos clés de test Stripe dans le fichier .env :

STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...


[6] LISTE DES ENDPOINTS

METHODE  | URL                                   | DESCRIPTION
-------- | ------------------------------------- | ------------------------------
POST     | /api/sellers/onboard                  | Création compte vendeur
GET      | /api/sellers/{id}/status              | Vérif. identité (KYC)
POST     | /api/payments/intent                  | Paiement (Argent bloqué)
POST     | /api/payments/{id}/confirm-reception  | Virement au vendeur
POST     | /api/payments/{id}/refund             | Remboursement (Annulation)
POST     | /api/webhook                          | Réception événements Stripe
