# Audit de l'intégration MamoniPay (PayMe) — Campusplanner

- **Dépôt audité** : `honowatech/Campusplanner`, dossier `planner-api.campustrack.cm` (API Laravel), commit `907814e`
- **Référence de comparaison** : intégrations fonctionnelles de `zoomlab-vitrine` (`app/Payments/MamoniPayGateway.php`) et de `zoomlab-backoffice` (`application/libraries/Mamoni_client_lib.php`)
- **Date** : 14 octobre 2026
- **Portée** : lecture du code, aucune modification apportée au dépôt

## Verdict

L'intégration a été écrite d'après un document `MAMONIPAY_API.md` cité dans les commentaires mais absent du dépôt, et qui décrit une API différente de l'API Partenaire PayMe réellement exposée par `https://console-v2.mamonipay.me/api`. Hôte, chemins, corps de connexion, lecture du jeton et corps d'initiation sont tous différents du contrat réel : **aucun appel ne peut aboutir en l'état**. Les tests passent parce qu'ils simulent ces mêmes mauvais chemins.

L'architecture autour de l'appel API (machine à états, idempotence, audit, chiffrement du mot de passe) est saine et doit être conservée. Le travail consiste à remplacer le client HTTP et la lecture des réponses par ceux qui fonctionnent dans la vitrine.

## Contrat réel de l'API Partenaire PayMe

| Élément | Valeur |
| --- | --- |
| Adresse de base | `https://console-v2.mamonipay.me/api` (pas de sandbox documenté) |
| Connexion | `POST /v1/auth/login`, corps `{ "login": "237… ou email", "password": "…" }` |
| Jeton | `data.access_token`, expiration `data.expires_at`, en-tête `Authorization: Bearer` |
| Initiation | `POST /v1/payments/init`, corps `{ "channel": "SELLER_DIRECT", "phone_number": "237…", "amount": entier, "external_reference": "…", "client_name": "…" }` |
| Référence passerelle | `data.gateway_reference` (UUID) dans la réponse d'initiation |
| Vérification | `POST /v1/payments/verify/{gateway_reference}` — la référence dans l'URL, uniquement la `gateway_reference` (une `external_reference` répond 404 `TXN-002`) |
| Enveloppe | `{ success, message, data, status }` ; une erreur métier peut arriver en **HTTP 200 avec `success: false`** (ex. `INSUFFICIENT_FUNDS`) |
| Erreurs de validation | `{ code: "VAL-001", message, errors: { message, errors: { champ: ["…"] } } }` |
| Statuts (`data.status`) | `Successful`, `Failed`, `Cancelled`, `Expired`, `Initiated`, `Payment in progress`, `Pending`, `Awaiting validation` |
| HTTP 409 à la vérification | Transaction toujours en cours (pas un refus) |
| Notification (callback) | Non documentée : la confirmation repose sur la vérification (polling + cron) |

## Constats

### A. Bloquants — rien ne peut fonctionner tant qu'ils ne sont pas corrigés

| # | Constat | Fichier | Correction |
| --- | --- | --- | --- |
| 1 | Adresses de base inexistantes : `https://sandbox.mamonipay.me` et `https://mamonipay.me`. Le mode `sandbox` par défaut envoie tout vers un hôte qui ne répond pas. | `config/payme.php` | Une seule adresse : `https://console-v2.mamonipay.me/api`. Supprimer la notion sandbox/live. |
| 2 | Chemins faux : `/api/auth/login`, `/api/transaction/init_payment`, `/api/clients/transaction/payment_status`. | `config/payme.php`, `app/Services/PaymeClient.php` | `/v1/auth/login`, `/v1/payments/init`, `/v1/payments/verify/{gateway_reference}` (référence dans l'URL). |
| 3 | Corps de connexion faux : `user_name` au lieu de `login` ; jeton lu dans `data.token` au lieu de `data.access_token`. | `PaymeClient::authenticate()` | Envoyer `login` + `password`, lire `data.access_token`, utiliser `data.expires_at` pour la durée du cache. |
| 4 | Corps d'initiation incomplet : `phone` au lieu de `phone_number`, pas de `channel`, pas de `client_name`, montant décimal. | `PaymentService::checkout()` | `channel: SELLER_DIRECT`, `phone_number`, `amount` en entier, `external_reference`, `client_name`. |
| 5 | `client_fees_rate` forcé à **100** quand le réglage est vide : 100 % des frais de transaction portés par le client. | `PaymentService::clientFeesRate()` | Ne pas envoyer le champ s'il n'est pas explicitement configuré (le réglage du compte marchand s'applique). |

### B. Importants — fausseront les résultats une fois les appels corrigés

| # | Constat | Fichier | Correction |
| --- | --- | --- | --- |
| 6 | Erreur métier en HTTP 200 traitée comme un succès : `success: false` n'est pas lu. Le paiement passe `in_progress` avec `gateway_reference` nulle, n'est jamais réinterrogé (filtre `whereNotNull`) et finit en `needs_review`. | `PaymeClient::initiatePayment()`, `PaymentService::checkout()` | Lire `success` ; si `false`, marquer le paiement en échec avec le `message`. |
| 7 | Correspondance des statuts incomplète : comparaison en majuscules avec `PAYMENT_IN_PROGRESS` (tiret bas) alors que PayMe renvoie « Payment in progress » (espaces) ; `Cancelled` et `Expired` inconnus, donc un paiement annulé reste « en cours » jusqu'à la révision manuelle. | `PaymentService::applyPaymeStatus()` | Normaliser (majuscules, espaces et tirets → `_`) puis : `SUCCESSFUL` → payé ; `FAILED`, `CANCELLED`, `EXPIRED` → échoué ; le reste → en cours. |
| 8 | Réponses 409 et 404 à la vérification ignorées silencieusement via `failed()`. | `PaymeClient::paymentStatus()` | 409 → laisser en cours ; 404 `TXN-002` → référence invalide, à journaliser. |
| 9 | Messages d'erreur tronqués à 200 caractères du corps brut ; le détail par champ (`errors.errors`) est perdu. | `PaymeClient` | Reprendre `MamoniPayGateway::errorMessage()` de la vitrine (détail par champ, sinon `message`, sinon `code`). |

### C. À corriger aussi

| # | Constat | Fichier | Correction |
| --- | --- | --- | --- |
| 10 | Aucun test de connexion pour le super-admin : le formulaire enregistre sans vérifier. | `PaymentGatewayController` | Route `POST admin/payment-gateway/test` : tentative de login, réponse avec code HTTP, message et **URL appelée**. |
| 11 | Champs de configuration sans objet : `app_id`, `pay_type_id`, `mode`. | `payment_gateways`, `PaymentGatewayController`, front `adminService.ts` | Garder `user_name` (identifiant), `password`, `endpoint` (défaut correct, normalisé : pas de `/` final, refus de `/docs` ou d'un `/v1` en trop), `client_fees_rate` optionnel, `is_active`. |
| 12 | Garde-fou du tenant démo commenté : des débits réels deviendront possibles depuis la démo. | `PaymentService::checkout()` | Rétablir le refus sur `is_demo`. |
| 13 | Tests simulant les mauvais chemins et le mauvais format de jeton. | `tests/Feature/PaymentServiceTest.php` | Réécrire sur le contrat réel : login, init, refus en 200, 409, statuts, erreurs de validation (scénarios de `MamoniPayGatewayTest` de la vitrine). |
| 14 | Réconciliation dépendante d'un worker de file : job `ShouldQueue` planifié toutes les 15 s avec `QUEUE_CONNECTION=database`. Sans `queue:work` permanent, rien ne tourne. Pas d'expiration des paiements sans `gateway_reference`. | `routes/console.php`, `PaymeReconciliationJob`, `PaymentService::reconcilePending()` | Exécuter la réconciliation directement dans la tâche planifiée (ou garantir le worker) ; expirer en échec les paiements sans référence après le délai. |

## Ce qui est bien fait et à conserver

- Machine à états `PaymentStatus` (transitions explicites, états terminaux).
- Idempotence : en-tête `Idempotency-Key`, références uniques en base.
- Journal d'audit sur chaque transition.
- Mot de passe chiffré (cast `encrypted`), jeton en cache, renouvellement sur 401.
- Normalisation du numéro de téléphone (`237…`).

## Plan de correction recommandé

1. **Client** : transposer `MamoniPayGateway` de la vitrine dans `PaymeClient` (adresse, chemins, enveloppe, `errorMessage()`), avec `success` lu sur chaque réponse.
2. **Service** : corps d'initiation complet, `client_fees_rate` optionnel, statuts normalisés, 409 toléré, expiration des paiements sans référence, garde-fou démo rétabli.
3. **Configuration** : une seule adresse par défaut, normalisation de `endpoint`, suppression de `mode`, `app_id`, `pay_type_id` (API + front).
4. **Super-admin** : bouton et route « Tester la connexion ».
5. **Tests** : réécriture sur le contrat réel.
6. **Exploitation** : réconciliation sans dépendance au worker, ou worker garanti sur le serveur.

Ce dépôt est la démonstration concrète de l'intérêt d'un paquet Composer partagé `honowatech/mamonipay` (client + « doctor » multi-points) : trois projets, trois clients différents, dont un seul correct au départ.
