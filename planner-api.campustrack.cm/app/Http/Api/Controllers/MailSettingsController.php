<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MailSetting;
use App\Services\AuditService;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;

/**
 * Compte d'envoi des emails de la plateforme (super-admin) : serveur SMTP et
 * adresse d'expédition des liens de réinitialisation du mot de passe.
 */
class MailSettingsController extends Controller
{
    use HttpResponses;

    public function show(): JsonResponse
    {
        $settings = MailSetting::active() ?? MailSetting::query()->latest('id')->first();

        return $this->success(['settings' => $this->payload($settings)], __('admin.mail_settings.retrieved'));
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mailer' => 'required|in:smtp,log',
            'host' => 'required_if:mailer,smtp|nullable|string|max:255',
            'port' => 'sometimes|nullable|integer|min:1|max:65535',
            'encryption' => 'sometimes|nullable|in:tls,ssl',
            'username' => 'sometimes|nullable|string|max:255',
            'password' => 'sometimes|nullable|string|max:255',
            'from_address' => 'required|email|max:255',
            'from_name' => 'sometimes|nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        $settings = MailSetting::query()->latest('id')->first() ?? new MailSetting;

        foreach (['mailer', 'host', 'port', 'encryption', 'username', 'from_address', 'from_name', 'is_active'] as $field) {
            if (array_key_exists($field, $validated)) {
                $settings->{$field} = $validated[$field];
            }
        }

        // Mot de passe vide = conserver l'actuel.
        if (! empty($validated['password'])) {
            $settings->password = $validated['password'];
        }

        $settings->save();

        AuditService::record('mail_settings.updated', $settings, [], [
            'mailer' => $settings->mailer,
            'host' => $settings->host,
            'from_address' => $settings->from_address,
            'is_active' => (bool) $settings->is_active,
        ]);

        return $this->success(['settings' => $this->payload($settings)], __('admin.mail_settings.updated'));
    }

    /**
     * Teste la configuration enregistrée : connexion au serveur SMTP
     * (authentification comprise), puis envoi d'un email de test.
     */
    public function test(Request $request): JsonResponse
    {
        $validated = $request->validate(['to' => 'sometimes|nullable|email|max:255']);
        $to = $validated['to'] ?? $request->user()->email;

        $settings = MailSetting::query()->latest('id')->first();

        if (! $settings) {
            return $this->error(null, __('admin.mail_settings.missing'), 404);
        }

        $settings->apply();

        try {
            $transport = Mail::mailer($settings->mailer === 'log' ? 'log' : 'smtp')->getSymfonyTransport();

            if ($transport instanceof SmtpTransport) {
                // Ouvre la connexion et s'authentifie sans rien envoyer.
                $transport->start();
                $transport->stop();
            }

            Mail::raw(__('admin.mail_settings.test_body', ['app' => config('app.name')]), function ($message) use ($to) {
                $message->to($to)->subject(__('admin.mail_settings.test_subject', ['app' => config('app.name')]));
            });
        } catch (\Throwable $e) {
            return $this->success(
                ['ok' => false, 'message' => $e->getMessage(), 'to' => $to],
                __('admin.mail_settings.test_failed')
            );
        }

        return $this->success(
            ['ok' => true, 'message' => __('admin.mail_settings.test_sent', ['to' => $to]), 'to' => $to],
            __('admin.mail_settings.test_sent', ['to' => $to])
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(?MailSetting $settings): array
    {
        return [
            'id' => $settings?->id,
            'mailer' => $settings->mailer ?? 'smtp',
            'host' => $settings?->host,
            'port' => $settings?->port,
            'encryption' => $settings?->encryption,
            'username' => $settings?->username,
            'has_password' => filled($settings?->password),
            'from_address' => $settings->from_address ?? MailSetting::DEFAULT_FROM,
            'from_name' => $settings?->from_name,
            // Nouvelle configuration : active dès son premier enregistrement.
            'is_active' => $settings->is_active ?? true,
        ];
    }
}
