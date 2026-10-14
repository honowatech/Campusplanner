# Déploiement de l'intégration PayMe (MamoniPay)

Ce document complète la [feuille de route](FEUILLE_DE_ROUTE_PAYME.md) (§7, étape 7) :
ce qu'il faut sur le serveur pour que les paiements d'abonnement fonctionnent.

## 1. Adresse de l'API

- Une seule adresse de base, sans sandbox : `https://console-v2.mamonipay.me/api`.
- Variable d'environnement (optionnelle) : `PAYME_BASE_URL`. Le champ « Adresse de l'API »
  de la console super-admin la surcharge s'il est renseigné.
- Jamais l'adresse de la documentation (`/docs`), jamais de `/v1` à la fin : l'API et le
  formulaire refusent ces adresses.

Variables disponibles (`config/payme.php`) :

| Variable | Défaut | Rôle |
| --- | --- | --- |
| `PAYME_BASE_URL` | `https://console-v2.mamonipay.me/api` | Adresse de base |
| `PAYME_TIMEOUT` | `20` | Délai HTTP (s) par requête |
| `PAYME_TIMEOUT_MINUTES` | `15` | Attente d'une confirmation avant abandon (surchargé par la passerelle) |
| `PAYME_UNINITIATED_TIMEOUT_MINUTES` | `2` | Abandon d'une demande jamais initiée chez PayMe |
| `PAYME_RECOVERY_MINUTES` | `60` | Revérification des demandes expirées/annulées |

## 2. Identifiants

Saisis par le super-admin (console → onglet PayMe) : identifiant (`237…` ou e-mail) et mot
de passe du compte marchand. Le mot de passe est chiffré en base et jamais renvoyé par l'API.
Le bouton **« Tester la connexion »** tente un login et affiche le code HTTP, le message
PayMe et l'URL réellement appelée.

## 3. Cron (obligatoire)

PayMe n'envoie **aucune notification**. La confirmation repose sur la vérification active,
exécutée par le planificateur Laravel chaque minute (`routes/console.php`,
tâche `payme-reconcile`) :

```
* * * * * cd /chemin/vers/planner-api.campustrack.cm && php artisan schedule:run >> /dev/null 2>&1
```

La réconciliation tourne **directement** dans le planificateur : aucun worker de file
(`queue:work`) n'est nécessaire pour les paiements. Le planificateur écrit aussi un
battement de cœur (`scheduler:last_run`) lu par le doctor.

## 4. Cache

Le jeton PayMe (≈ 1 h) est conservé dans le cache applicatif (`CACHE_STORE`, `database`
par défaut). Un cache `array` en production forcerait une connexion à chaque appel.

## 5. Diagnostic avant mise en production

```
php artisan payme:doctor
```

Vérifie dans l'ordre : adresse de base, DNS et TLS de `console-v2.mamonipay.me`, connexion
(code HTTP, redirection, jeton, expiration), acceptation du jeton (vérification sur une
référence factice → 404 `TXN-002` attendu, sans débit), prérequis (PHP ≥ 8.2, `curl`,
`openssl`, cache, planificateur actif).

Initiation réelle d'un petit montant avec suivi du statut :

```
php artisan payme:doctor --live=2376XXXXXXXX --amount=100
```

Ne rien livrer en production tant que les quatre premiers points ne sont pas verts avec les
vrais identifiants.

## 6. Limites de l'API (à connaître)

- Pas de notification : vérification active (page de paiement toutes les 5 s + cron chaque minute).
- Pas d'annulation : « Annuler la demande » clôt la demande côté Campus Planner ; l'invite
  peut rester sur le téléphone, d'où la revérification pendant une heure (`Failed` →
  `Paid` possible uniquement dans ce cas, audité `payment.recovered`).
- Pas d'expiration signalée : le délai configuré fait foi (`EXPIRED`).
- Vérification uniquement par `gateway_reference` : une demande sans référence (panne à
  l'envoi) ne peut pas être retrouvée et passe en échec après 2 minutes.
- Pas d'environnement de test ni de remboursement par l'API : le tenant démo refuse tout
  checkout.

## 7. Cycle de vie d'un paiement

| Statut interne | `gateway_status` | Signification |
| --- | --- | --- |
| `pending` | — | Créé, pas encore envoyé à PayMe |
| `in_progress` | `Initiated`, `Payment in progress`, `Pending`, `Awaiting validation` | Invite envoyée au téléphone |
| `paid` | `Successful` | Confirmé, abonnement activé, `receipt_url` disponible |
| `failed` | `Failed`, `Cancelled`, `Expired` (PayMe) · `EXPIRED`, `CANCELLED` (Campus Planner) | Refusé, expiré ou annulé ; motif dans `metadata.error` |
| `needs_review` | — | Réservé aux réponses contradictoires (non produit automatiquement) |
