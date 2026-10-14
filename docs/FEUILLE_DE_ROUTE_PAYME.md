# Feuille de route — Corriger l'intégration PayMe (MamoniPay) de Campus Planner

- **Dépôt** : `honowatech/Campusplanner`, dossier `planner-api.campustrack.cm` (API Laravel) et `planner.campustrack.cm` (front React/TS)
- **Audit de départ** : commit `907814e`, 14 octobre 2026 (rapport `rapport-audit-mamonipay-campusplanner.md`)
- **Référence qui fonctionne en production** : `honowatech/zoomlab-vitrine`, fichier `app/Payments/MamoniPayGateway.php` (Laravel 13) ; l'essentiel du code est recopié ci-dessous pour que ce document se suffise à lui-même
- **Objectif** : rendre les paiements d'abonnement Campus Planner réellement fonctionnels avec l'API Partenaire PayMe, en conservant ce qui est bien fait (machine à états, idempotence, audit, chiffrement)

Ce document est écrit pour être donné tel quel à une session de travail sur le dépôt Campus Planner. Chaque étape indique les fichiers concernés, le résultat attendu et le test qui le prouve.

---

## 0. Le contrat réel de l'API (à graver avant toute ligne de code)

Le code actuel suit un document `MAMONIPAY_API.md` absent du dépôt et faux. Voici le contrat réel, vérifié par deux intégrations en production (vitrine ZoomLab et back-office ZoomLab).

| Élément | Valeur réelle |
| --- | --- |
| **Adresse de base** | `https://console-v2.mamonipay.me/api` — unique, pas de sandbox, pas de `/v1` à la fin, jamais `/docs` |
| **Connexion** | `POST /v1/auth/login`, corps `{ "login": "237… ou email", "password": "…" }` |
| **Jeton** | `data.access_token` (Bearer), expiration `data.expires_at` (ISO 8601) ; durée ≈ 1 h, mettre en cache un peu moins |
| **Initiation** | `POST /v1/payments/init`, corps `{ "channel": "SELLER_DIRECT", "phone_number": "237XXXXXXXXX", "amount": <entier>, "external_reference": "<unique>", "client_name": "<nom>" }` ; optionnel `client_fees_rate` (entier 0–100) **uniquement s'il est explicitement configuré** |
| **Réponse d'initiation** | `data.gateway_reference` (UUID, à stocker absolument), `data.status` (`Initiated`, `Payment in progress`…), `data.payment_provider_slug` |
| **Vérification** | `POST /v1/payments/verify/{gateway_reference}` — la référence **dans l'URL**, corps vide ; une `external_reference` ou un identifiant numérique répond **404 `TXN-002`** |
| **Réponse de vérification** | `data.status`, `data.payment_provider_slug`, `data.processed_at`, `data.receipt.receipt_url` après succès |
| **Enveloppe** | `{ success, message, data, status }` ; une **erreur métier arrive en HTTP 200 avec `success: false`** (ex. `message: "INSUFFICIENT_FUNDS"`) |
| **Erreurs de validation** | HTTP 422, `{ code: "VAL-001", message: "Erreur de validation", errors: { message, errors: { champ: ["…"] } } }` |
| **Statuts `data.status`** | `Successful` · `Failed` · `Cancelled` · `Expired` · `Initiated` · `Payment in progress` · `Pending` · `Awaiting validation` (avec espaces, casse variable) |
| **HTTP 409 à la vérification** | Transaction toujours en cours : **pas un refus** |
| **Autres appels** | `GET /v1/transactions` (liste), `GET /v1/transactions/{id}` (détail) |
| **Absent de l'API** | Notification/callback, annulation, expiration signalée, environnement de test, remboursement |

---

## 1. Client HTTP — `app/Services/PaymeClient.php`

**Remplacer intégralement** la logique d'appel par celle ci-dessous (adaptée depuis la passerelle ZoomLab). Garder la signature publique si le reste du code en dépend, mais le contenu change.

### 1.1 Configuration — `config/payme.php`

```php
return [
    'base_url' => env('PAYME_BASE_URL', 'https://console-v2.mamonipay.me/api'),
    'timeout' => (int) env('PAYME_TIMEOUT', 20),
    'endpoints' => [
        'login' => '/v1/auth/login',
        'init' => '/v1/payments/init',
        'verify' => '/v1/payments/verify/{reference}',
    ],
    // Délai (minutes) avant d'abandonner une demande sans confirmation (PayMe ne signale pas l'expiration).
    'timeout_minutes' => (int) env('PAYME_TIMEOUT_MINUTES', 15),
];
```

Supprimer `sandbox_base_url`, `live_base_url`, `login_path`, `init_payment_path`, `payment_status_path`, `token_ttl` (le TTL vient de `expires_at`).

### 1.2 Connexion et jeton

```php
private function token(PaymentGateway $gateway): string
{
    $key = 'payme_token:'.$gateway->id.':'.sha1($this->baseUrl($gateway).'|'.$gateway->user_name);

    if ($token = Cache::get($key)) {
        return $token;
    }

    $url = $this->baseUrl($gateway).config('payme.endpoints.login');
    $response = Http::acceptJson()->asJson()->timeout(config('payme.timeout'))->connectTimeout(10)
        ->post($url, ['login' => $gateway->user_name, 'password' => $gateway->password]);

    $data = (array) $response->json();
    $token = Arr::get($data, 'data.access_token') ?? Arr::get($data, 'access_token');

    if (! $response->successful() || Arr::get($data, 'success') === false || ! $token) {
        $effective = (string) $response->effectiveUri();
        throw new \RuntimeException(sprintf(
            'Connexion PayMe refusée (%d)%s — URL appelée : %s%s',
            $response->status(),
            ($m = self::errorMessage($data)) ? ' : '.$m : '',
            $url,
            $effective !== '' && $effective !== $url ? ' (redirigée vers '.$effective.')' : '',
        ));
    }

    $ttl = 3000;
    if ($expiresAt = Arr::get($data, 'data.expires_at')) {
        try { $ttl = (int) now()->diffInSeconds(CarbonImmutable::parse($expiresAt), false); } catch (\Throwable) {}
    }
    Cache::put($key, (string) $token, max(60, $ttl - 60));

    return (string) $token;
}
```

### 1.3 Normalisation de l'adresse de base

```php
private function baseUrl(PaymentGateway $gateway): string
{
    $base = rtrim((string) ($gateway->endpoint ?: config('payme.base_url')), '/');

    if (preg_match('#/docs(/|$)|/v\d+$#i', $base)) {
        throw new \RuntimeException("Adresse PayMe invalide ({$base}) : indiquer l'adresse de base de l'API, ex. https://console-v2.mamonipay.me/api");
    }

    return $base;
}
```

### 1.4 Lecture des erreurs

```php
public static function errorMessage(array $data): ?string
{
    $fields = Arr::get($data, 'errors.errors');
    if (is_array($fields)) {
        $messages = [];
        foreach ($fields as $list) {
            $first = is_array($list) ? reset($list) : $list;
            if (is_string($first) && $first !== '') {
                $messages[] = ucfirst($first);
            }
        }
        if ($messages) {
            return implode(' ', $messages);
        }
    }

    $errors = Arr::get($data, 'errors');
    if (is_array($errors) && ($flat = array_values(array_filter($errors, 'is_string')))) {
        return implode(' ', $flat);
    }

    foreach (['message', 'errors.message', 'data.message', 'error', 'detail', 'data'] as $path) {
        $value = Arr::get($data, $path);
        if (filled($value) && ! is_array($value)) {
            return (string) $value;
        }
    }

    $code = Arr::get($data, 'code');

    return is_string($code) && $code !== '' ? 'Erreur PayMe ('.$code.')' : null;
}
```

### 1.5 Initiation

```php
public function initiatePayment(PaymentGateway $gateway, array $payload): array
{
    // $payload attendu : amount (int), phone_number (237…), external_reference, client_name, client_fees_rate (optionnel)
    $body = [
        'channel' => 'SELLER_DIRECT',
        'phone_number' => $payload['phone_number'],
        'amount' => (int) $payload['amount'],
        'external_reference' => $payload['external_reference'],
        'client_name' => $payload['client_name'] ?? null,
    ];
    if (filled($payload['client_fees_rate'] ?? null)) {
        $body['client_fees_rate'] = (int) $payload['client_fees_rate'];
    }

    $response = $this->authorized($gateway, fn ($http) => $http->post($this->baseUrl($gateway).config('payme.endpoints.init'), $body));

    return $this->normalize($response);
}
```

### 1.6 Vérification

```php
public function paymentStatus(PaymentGateway $gateway, string $gatewayReference): array
{
    if (blank($gatewayReference)) {
        throw new \RuntimeException('Aucune gateway_reference : la demande n\'a pas été initiée chez PayMe.');
    }

    $url = $this->baseUrl($gateway).str_replace('{reference}', rawurlencode($gatewayReference), config('payme.endpoints.verify'));
    $response = $this->authorized($gateway, fn ($http) => $http->post($url));

    return $this->normalize($response);
}
```

### 1.7 Normalisation d'une réponse (init ou verify)

Retourne un tableau `['state' => 'pending'|'success'|'failed', 'gateway_reference', 'gateway_status', 'provider', 'receipt_url', 'message', 'raw']`.

```php
private function normalize(Response $response): array
{
    $data = (array) $response->json();
    $reference = Arr::get($data, 'data.gateway_reference') ?? Arr::get($data, 'gateway_reference');
    $status = Arr::get($data, 'data.status');
    $status = is_numeric($status) ? null : $status;
    $message = self::errorMessage($data);
    $provider = Arr::get($data, 'data.payment_provider_slug');
    $receipt = Arr::get($data, 'data.receipt.receipt_url');
    $base = [
        'gateway_reference' => $reference !== null ? (string) $reference : null,
        'gateway_status' => $status !== null ? (string) $status : null,
        'provider' => $provider ? strtoupper((string) $provider) : null,
        'receipt_url' => is_string($receipt) && str_starts_with($receipt, 'https://') ? $receipt : null,
        'message' => $message,
        'raw' => $data,
    ];

    if ($response->status() === 409) {                       // toujours en cours
        return $base + ['state' => 'pending'];
    }
    if (($response->clientError() && $response->status() !== 404) || ($response->successful() && Arr::get($data, 'success') === false)) {
        return $base + ['state' => 'failed', 'message' => $message ?? 'Paiement refusé par PayMe.'];
    }
    if (! $response->successful()) {                         // 404 TXN-002, 5xx : issue incertaine
        throw new \RuntimeException('PayMe a répondu '.$response->status().($message ? ' : '.$message : ''));
    }

    return $base + ['state' => self::mapStatus($status)];
}

public static function mapStatus(?string $status): string
{
    $s = strtoupper(str_replace([' ', '-'], '_', trim((string) $status)));

    return match (true) {
        in_array($s, ['SUCCESSFUL', 'SUCCESS', 'SUCCEEDED', 'COMPLETED', 'PAID'], true) => 'success',
        in_array($s, ['FAILED', 'FAILURE', 'ERROR', 'REJECTED', 'DECLINED', 'CANCELLED', 'CANCELED', 'EXPIRED', 'TIMEOUT', 'REFUSED', 'INSUFFICIENT_FUNDS'], true) => 'failed',
        default => 'pending',   // Initiated, Payment in progress, Pending, Awaiting validation
    };
}
```

`authorized()` : envoie avec `withToken($this->token($gateway))`, et sur un **401** oublie le jeton et renvoie une seule fois (logique déjà présente dans le code actuel, à conserver). Toujours `acceptJson()->asJson()`.

---

## 2. Service — `app/Services/PaymentService.php`

### 2.1 `checkout()`

- **Rétablir le garde-fou du tenant démo** (lignes commentées `if ($tenant->is_demo) throw …`).
- Construire la charge utile avec les bons noms : `phone_number` (via `normalizePhone`), `amount` entier (`(int) round($pack->price)`), `external_reference`, `client_name` (nom du tenant ou de l'utilisateur).
- **Supprimer `clientFeesRate()` et son défaut à 100.** Envoyer `client_fees_rate` uniquement si `payment_gateways.client_fees_rate` est renseigné (entier 0–100, voir § 4).
- Lire le résultat normalisé :
  - `state === 'failed'` → `Failed` avec `message` dans `metadata.error` et l'audit ;
  - `gateway_reference` nulle alors que `state` n'est pas `failed` → traiter comme échec (« PayMe n'a pas renvoyé de référence ») ;
  - sinon → `InProgress` avec `gateway_reference`, `provider` et `gateway_status` enregistrés.

### 2.2 `applyPaymeStatus()`

Remplacer le `match` sur `strtoupper($status)` par `PaymeClient::mapStatus()` :
- `success` → `Paid` (+ `receipt_url`, `provider`, `paid_at`) ;
- `failed` → `Failed` (le `gateway_status` brut est conservé : `Cancelled`, `Expired`…) ;
- `pending` → aucun changement d'état (`InProgress` reste), mettre à jour `checked_at`.

### 2.3 `reconcilePending()`

- Garder le filtre `whereNotNull('gateway_reference')`, **mais** passer en `Failed` (motif « jamais initiée chez PayMe ») les demandes `Pending`/`InProgress` **sans** référence plus vieilles que 2 minutes, au lieu de les laisser jusqu'au `NeedsReview`.
- Expiration : au-delà de `config('payme.timeout_minutes')`, passer en `Failed` avec `gateway_status = 'EXPIRED'` (et non `NeedsReview`, qui doit rester réservé aux réponses contradictoires). Vérifier une dernière fois auprès de PayMe avant d'expirer.
- Une exception du client (404 TXN-002, 5xx, réseau) n'arrête pas la boucle : journaliser et continuer.
- Revérifier pendant une heure les paiements `Failed` dont `gateway_status` est `EXPIRED` ou `CANCELLED` et qui ont une `gateway_reference` : si PayMe répond `Successful`, repasser en `Paid` et activer l'abonnement (le client a validé tard sur son téléphone). La machine à états doit autoriser `Failed → Paid` **uniquement** dans ce cas (ajouter une transition dédiée ou une méthode `recover()` qui contourne la machine à états avec audit).

### 2.4 Nouveau : `cancel(Payment)`

Le client renonce à attendre : dernière vérification auprès de PayMe, puis `Failed` avec `gateway_status = 'CANCELLED'` et motif « annulée par le client ». Reste revérifiée une heure (§ 2.3). Exposer `POST billing/payments/{payment}/cancel` (admin tenant, propriétaire du paiement uniquement).

---

## 3. Planification — `routes/console.php` et `app/Jobs/PaymeReconciliationJob.php`

- Remplacer `Schedule::job(new PaymeReconciliationJob)->everyFifteenSeconds()` par un appel direct `Schedule::call(fn () => app(PaymentService::class)->reconcilePending())->everyMinute()->withoutOverlapping()`. La version en file (`ShouldQueue`) ne tourne que si un `queue:work` est permanent, ce qui n'est pas garanti sur l'hébergement.
- Si la file est conservée volontairement, documenter l'obligation du worker dans le README de déploiement et ajouter un contrôle (`php artisan queue:monitor` ou alerte) dans le doctor (§ 6).

---

## 4. Configuration passerelle — modèle, migration, contrôleur, front

### 4.1 Base de données — nouvelle migration

- `payment_gateways` : supprimer `app_id`, `pay_type_id`, `mode` ; renommer la sémantique de `endpoint` en « adresse de base de l'API » avec défaut `https://console-v2.mamonipay.me/api` ; `client_fees_rate` devient `unsignedTinyInteger` **nullable** (0–100, vide = réglage du compte marchand) ; ajouter `timeout_minutes` (`unsignedSmallInteger`, défaut 15, 2–60).
- `payments` : ajouter `gateway_status` (string 60, nullable), `provider` (string 30, nullable), `receipt_url` (string 255, nullable), `checked_at` (timestamp nullable), `checks` (unsignedSmallInteger défaut 0).

### 4.2 `app/Models/PaymentGateway.php`

- Retirer `app_id`, `pay_type_id`, `mode` des `fillable` ; ajouter `timeout_minutes`.
- **Réactiver `$hidden = ['password']`** (actuellement commenté).
- Cast `client_fees_rate` en `integer` nullable.

### 4.3 `app/Http/Api/Controllers/PaymentGatewayController.php`

- Validation : `user_name` requis, `password` optionnel (vide = conserver), `endpoint` optionnel `url` https, refusé s'il contient `/docs` ou se termine par `/v1` (message explicite), `client_fees_rate` nullable entier 0–100, `timeout_minutes` entier 2–60, `is_active` booléen.
- Supprimer `mode`, `app_id`, `pay_type_id` de la validation, de `payload()` et des textes `lang/{fr,en}/admin.php`.
- **Nouvelle route `POST admin/payment-gateway/test`** : tente un login avec les identifiants enregistrés (jeton vidé avant), répond `{ ok, status, message, url }` où `url` est l'adresse réellement appelée et la redirection éventuelle. C'est le point qui aurait évité des heures de diagnostic sur ZoomLab.

### 4.4 Front — `planner.campustrack.cm`

- `src/services/adminService.ts` (`GatewayPayload`) et `src/lib/types.ts` (`PaymentGatewayConfig`) : retirer `mode`, `app_id`, `pay_type_id` ; ajouter `timeout_minutes` ; `client_fees_rate` devient entier 0–100 ou vide.
- Page de configuration super-admin : champ « Adresse de l'API » prérempli avec la valeur par défaut et aide « jamais l'adresse de la documentation », champs délai et frais, bouton **« Tester la connexion »** branché sur la nouvelle route avec affichage du message complet.
- Page de paiement tenant (`useBilling.ts`) : pendant `in_progress`, afficher un compte à rebours basé sur `timeout_minutes` et un bouton **« Annuler la demande »** (route § 2.4) ; après `paid`, proposer le reçu si `receipt_url` est renseigné.

---

## 5. Tests — `tests/Feature/PaymentServiceTest.php` (réécriture complète)

Simuler le **contrat réel** avec `Http::fake()` sur `https://console-v2.mamonipay.me/api/v1/*`. Scénarios minimum :

1. **Connexion** : corps `login`/`password`, jeton lu dans `data.access_token`, réutilisé entre deux appels (une seule authentification), renouvelé sur 401.
2. **Connexion refusée** en HTTP 200 `success: false` → exception dont le message contient le `message` PayMe et l'URL appelée.
3. **Initiation** : la requête contient `channel`, `phone_number`, `amount` entier, `external_reference`, `client_name`, pas de `client_fees_rate` sauf s'il est configuré ; le paiement passe `in_progress` avec `gateway_reference`.
4. **Refus métier en 200** (`INSUFFICIENT_FUNDS`) → `failed` avec le message, pas de `gateway_reference`.
5. **Erreur de validation 422** → `failed` avec le détail par champ (« The amount field must be at least 10. »).
6. **Vérification** : URL `…/v1/payments/verify/{gateway_reference}` en POST ; `Successful` → `paid`, abonnement activé, `receipt_url` et `provider` stockés ; `Payment in progress` → inchangé ; `Cancelled`/`Expired` → `failed`.
7. **409** à la vérification → reste `in_progress`.
8. **Sans `gateway_reference`** : aucune requête de vérification envoyée (`Http::assertNotSent`), échec après 2 minutes.
9. **Expiration** : avec `timeout_minutes = 3`, toujours `in_progress` à 2 min, `failed`/`EXPIRED` à 4 min.
10. **Annulation** puis `Successful` à la revérification → `paid` et abonnement activé.
11. **Tenant démo** : `checkout()` lève une exception.
12. **Configuration** : adresse `/docs/api-json` ou `/api/v1` refusée ; `client_fees_rate` 150 refusé ; `timeout_minutes` 1 refusé ; route de test de connexion répond `ok: false` avec l'URL sur un 404 simulé.

---

## 6. Diagnostic multi-points (« doctor ») — `php artisan payme:doctor`

Commande Artisan (et même logique derrière « Tester la connexion ») qui enchaîne, avec un verdict par ligne :

1. Adresse de base bien formée et normalisée ; affichage de l'URL de login complète.
2. Résolution DNS et TLS de `console-v2.mamonipay.me` depuis le serveur.
3. Login : code HTTP, redirection éventuelle, jeton obtenu, `expires_at` lu.
4. Jeton accepté : `verify` sur une référence factice `00000000-0000-0000-0000-000000000000` ; un **404 TXN-002** prouve que l'authentification passe sans rien débiter.
5. Prérequis : PHP ≥ 8.2, extensions `curl` et `openssl`, cache utilisable, planificateur actif (dernière exécution de `schedule:run` < 2 min), worker de file si la réconciliation reste en file.
6. Optionnel, drapeau `--live=2376XXXXXXX --amount=100` : initiation réelle d'un petit montant avec suivi du statut pendant 2 minutes.

---

## 7. Ordre de réalisation et critères d'acceptation

| Étape | Livrable | Preuve |
| --- | --- | --- |
| 1 | `config/payme.php` + `PaymeClient` réécrits (§ 1) | Tests 1 à 7 verts |
| 2 | `PaymentService` corrigé (§ 2), transitions ajustées dans `PaymentStatus` | Tests 8 à 11 verts |
| 3 | Planification sans dépendance au worker (§ 3) | `php artisan schedule:list` montre la réconciliation chaque minute |
| 4 | Migration + modèle + contrôleur + front (§ 4) | Test 12 vert ; formulaire super-admin sans `mode`/`app_id`/`pay_type_id` ; bouton « Tester la connexion » fonctionnel |
| 5 | Doctor (§ 6) | `php artisan payme:doctor` en production : 4 premiers points verts avec les vrais identifiants |
| 6 | Achat réel d'un petit montant sur un tenant de test | Paiement `paid`, abonnement activé, reçu visible |
| 7 | README de déploiement : adresse de l'API, cron, worker, limites de l'API | Section relue |

Ne rien livrer en production avant l'étape 5 : c'est le doctor qui dit si les identifiants, l'adresse et le réseau du serveur sont bons.

---

## 8. Limites de l'API à rappeler dans l'interface et la documentation

- Pas de notification : la confirmation repose sur la vérification active (page + cron chaque minute).
- Pas d'annulation : « Annuler » clôt la demande côté Campus Planner ; l'invite peut rester sur le téléphone, d'où la revérification pendant une heure.
- Pas d'expiration signalée : le délai configuré fait foi.
- Vérification uniquement par `gateway_reference` : une demande sans référence (panne à l'envoi) ne peut pas être retrouvée.
- Pas d'environnement de test ni de remboursement par l'API : prévoir un mode simulation hors production (sur ZoomLab, `FakeGateway` : numéro finissant par 0000 = refus, 9999 = jamais de réponse).

---

## 9. Pour aller plus loin (hors périmètre de cette correction)

Ces règles sont désormais implémentées trois fois (vitrine, back-office CodeIgniter, Campus Planner). Un paquet Composer partagé `honowatech/mamonipay` (client PHP pur + doctor + adaptateurs Laravel et CI3) et un skill Claude `mamonipay` éviteraient de recommencer au prochain projet. Les points 1.2 à 1.7 ci-dessus en constituent le cœur.
