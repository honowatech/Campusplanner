<?php

namespace App\Console\Commands;

use App\Models\PaymentGateway;
use App\Services\PaymeClient;
use App\Services\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Diagnostic multi-points de l'intégration PayMe (cf. feuille de route §6).
 *
 * Ne rien livrer en production avant que les quatre premiers points soient
 * verts avec les vrais identifiants : c'est cette commande qui dit si les
 * identifiants, l'adresse et le réseau du serveur sont bons.
 */
class PaymeDoctor extends Command
{
    protected $signature = 'payme:doctor
        {--live= : Numéro mobile money (237…) pour une initiation réelle d\'un petit montant}
        {--amount=100 : Montant (XAF) de l\'initiation réelle}
        {--wait=120 : Durée (s) de suivi du statut après une initiation réelle}';

    protected $description = 'Diagnostique l\'intégration PayMe (adresse, DNS/TLS, connexion, jeton, prérequis)';

    private bool $healthy = true;

    public function handle(PaymeClient $client, PaymentService $payments): int
    {
        $gateway = PaymentGateway::active() ?? PaymentGateway::query()->latest()->first();

        if (! $gateway) {
            $this->ko('Aucune passerelle PayMe enregistrée (console super-admin → PayMe).');

            return self::FAILURE;
        }

        if (! $gateway->is_active) {
            $this->warn('⚠ La passerelle enregistrée est désactivée (is_active = false).');
        }

        $this->line('<comment>PayMe doctor</comment> — passerelle #'.$gateway->id.' (identifiant : '.$gateway->user_name.')');
        $this->newLine();

        // 1. Adresse de base.
        try {
            $base = $client->baseUrl($gateway);
            $this->ok('Adresse de base : '.$base);
            $this->line('    URL de connexion : '.$client->loginUrl($gateway));
        } catch (\RuntimeException $e) {
            $this->ko($e->getMessage());

            return $this->verdict();
        }

        // 2. DNS et TLS.
        $host = (string) parse_url($base, PHP_URL_HOST);
        $this->checkDns($host);
        $this->checkTls($host);

        // 3. Connexion.
        $login = $client->testConnection($gateway);
        if ($login['ok']) {
            $this->ok('Connexion : HTTP '.$login['status'].', jeton obtenu'.($login['expires_at'] ? ', expire le '.$login['expires_at'] : ', expiration non communiquée'));
        } else {
            $this->ko('Connexion : '.$login['message']);
        }
        if ($login['effective_url']) {
            $this->warn('    ⚠ Redirection vers '.$login['effective_url']);
        }

        // 4. Jeton accepté (vérification sur une référence factice → 404 TXN-002 attendu).
        if ($login['ok']) {
            try {
                $response = $client->rawVerify($gateway, PaymeClient::DUMMY_REFERENCE);
                $data = (array) $response->json();
                $code = Arr::get($data, 'code');

                if ($response->status() === 404) {
                    $this->ok('Jeton accepté : verify sur référence factice → 404'.($code ? ' '.$code : '').' (aucun débit)');
                } elseif ($response->status() === 401) {
                    $this->ko('Jeton refusé (401) à la vérification : '.(PaymeClient::errorMessage($data) ?? ''));
                } else {
                    $this->warn('    ⚠ Réponse inattendue à la vérification factice : HTTP '.$response->status().' '.(PaymeClient::errorMessage($data) ?? ''));
                }
            } catch (\Throwable $e) {
                $this->ko('Vérification factice impossible : '.$e->getMessage());
            }
        } else {
            $this->line('  · Jeton : non testé (connexion échouée)');
        }

        // 5. Prérequis.
        $this->newLine();
        $this->checkPrerequisites();

        // 6. Initiation réelle (optionnelle).
        if ($phone = $this->option('live')) {
            $this->newLine();
            $this->liveTest($client, $gateway, (string) $phone, (int) $this->option('amount'), (int) $this->option('wait'));
        }

        $this->newLine();
        $this->line('Délai d\'attente d\'une confirmation : '.$payments->timeoutMinutes($gateway).' min · revérification des demandes expirées/annulées : '.config('payme.recovery_minutes').' min.');

        return $this->verdict();
    }

    private function checkDns(string $host): void
    {
        $ips = @gethostbynamel($host) ?: [];

        if ($ips) {
            $this->ok('DNS : '.$host.' → '.implode(', ', $ips));
        } else {
            $this->ko('DNS : '.$host.' ne se résout pas depuis ce serveur.');
        }
    }

    private function checkTls(string $host): void
    {
        $context = stream_context_create(['ssl' => ['capture_peer_cert' => true, 'SNI_enabled' => true, 'peer_name' => $host]]);
        $socket = @stream_socket_client('ssl://'.$host.':443', $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);

        if (! $socket) {
            $this->ko('TLS : connexion à '.$host.':443 impossible ('.$errno.' '.$errstr.').');

            return;
        }

        $cert = stream_context_get_params($socket)['options']['ssl']['peer_certificate'] ?? null;
        $info = $cert ? openssl_x509_parse($cert) : null;
        $validTo = isset($info['validTo_time_t']) ? date('Y-m-d', $info['validTo_time_t']) : null;
        fclose($socket);

        $this->ok('TLS : certificat de '.$host.' valide'.($validTo ? ' jusqu\'au '.$validTo : ''));
    }

    private function checkPrerequisites(): void
    {
        $missing = [];
        foreach (['payment_gateways' => ['timeout_minutes', 'client_fees_rate'], 'payments' => ['gateway_status', 'provider', 'receipt_url', 'checked_at', 'checks']] as $table => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    $missing[] = $table.'.'.$column;
                }
            }
        }
        $missing === []
            ? $this->ok('Schéma à jour (migration 2026_10_14 appliquée)')
            : $this->ko('Schéma incomplet, colonnes manquantes : '.implode(', ', $missing).' → exécuter `php artisan migrate --force`.');

        version_compare(PHP_VERSION, '8.2.0', '>=')
            ? $this->ok('PHP '.PHP_VERSION)
            : $this->ko('PHP '.PHP_VERSION.' : 8.2 minimum requis');

        foreach (['curl', 'openssl', 'json'] as $ext) {
            extension_loaded($ext) ? $this->ok('Extension '.$ext) : $this->ko('Extension '.$ext.' absente');
        }

        try {
            $probe = 'payme_doctor:'.Str::random(8);
            Cache::put($probe, 'ok', 60);
            Cache::get($probe) === 'ok'
                ? $this->ok('Cache ('.config('cache.default').') utilisable — le jeton PayMe y est conservé')
                : $this->ko('Cache ('.config('cache.default').') : écriture non relue');
            Cache::forget($probe);
        } catch (\Throwable $e) {
            $this->ko('Cache : '.$e->getMessage());
        }

        $lastRun = Cache::get('scheduler:last_run');
        if ($lastRun && now()->diffInMinutes(now()->parse($lastRun)) <= 2) {
            $this->ok('Planificateur actif (dernier schedule:run : '.$lastRun.') — réconciliation PayMe chaque minute');
        } else {
            $this->ko('Planificateur inactif'.($lastRun ? ' (dernier schedule:run : '.$lastRun.')' : ' (jamais exécuté)').' : ajouter la cron `* * * * * php artisan schedule:run`.');
        }

        $this->line('  · Worker de file : non requis pour les paiements (réconciliation exécutée par le planificateur ; QUEUE_CONNECTION='.config('queue.default').')');
    }

    private function liveTest(PaymeClient $client, PaymentGateway $gateway, string $phone, int $amount, int $wait): void
    {
        $phone = $client->normalizePhone($phone);
        $reference = 'doctor-'.Str::uuid();

        $this->line('<comment>Initiation réelle</comment> : '.$amount.' XAF vers '.$phone.' (référence '.$reference.')');

        if (! $this->confirm('Un débit réel sera demandé sur ce numéro. Continuer ?', false)) {
            $this->line('  · Initiation annulée.');

            return;
        }

        try {
            $result = $client->initiatePayment($gateway, [
                'amount' => $amount,
                'phone_number' => $phone,
                'external_reference' => $reference,
                'client_name' => 'PayMe doctor',
            ]);
        } catch (\Throwable $e) {
            $this->ko('Initiation : '.$e->getMessage());

            return;
        }

        if ($result['state'] === 'failed' || blank($result['gateway_reference'])) {
            $this->ko('Initiation refusée : '.($result['message'] ?? 'sans message').' (statut '.($result['gateway_status'] ?? '—').')');

            return;
        }

        $this->ok('Initiation acceptée : gateway_reference '.$result['gateway_reference'].', statut « '.$result['gateway_status'].' »'.($result['provider'] ? ', opérateur '.$result['provider'] : ''));
        $this->line('  · Validez l\'invite sur le téléphone ; suivi du statut pendant '.$wait.' s…');

        $deadline = time() + max(10, $wait);

        while (time() < $deadline) {
            sleep(5);

            try {
                $status = $client->paymentStatus($gateway, $result['gateway_reference']);
            } catch (\Throwable $e) {
                $this->warn('    ⚠ '.$e->getMessage());

                continue;
            }

            $this->line('    '.now()->format('H:i:s').' → « '.($status['gateway_status'] ?? '—').' » ('.$status['state'].')');

            if ($status['state'] !== 'pending') {
                $status['state'] === 'success'
                    ? $this->ok('Paiement confirmé'.($status['receipt_url'] ? ' — reçu : '.$status['receipt_url'] : ''))
                    : $this->ko('Paiement refusé : '.($status['message'] ?? $status['gateway_status'] ?? ''));

                return;
            }
        }

        $this->warn('    ⚠ Toujours en cours après '.$wait.' s : le planificateur continuera la vérification si la demande était un vrai checkout.');
    }

    private function ok(string $message): void
    {
        $this->line('  <info>✓</info> '.$message);
    }

    private function ko(string $message): void
    {
        $this->healthy = false;
        $this->line('  <error>✗</error> '.$message);
    }

    private function verdict(): int
    {
        $this->newLine();

        if ($this->healthy) {
            $this->info('Verdict : intégration PayMe opérationnelle.');

            return self::SUCCESS;
        }

        $this->error('Verdict : au moins un point est en échec, ne pas livrer en production.');

        return self::FAILURE;
    }
}
