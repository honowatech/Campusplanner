<?php

namespace App\Http\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MailSetting;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Mot de passe oublié, pour tous les comptes (super-admin et utilisateurs des
 * tenants) : envoi d'un lien par email, puis choix d'un nouveau mot de passe.
 */
class PasswordResetController extends Controller
{
    use HttpResponses;

    /**
     * Envoie le lien de réinitialisation. La réponse est identique que le
     * compte existe ou non, pour ne pas révéler les adresses inscrites.
     */
    public function forgot(Request $request): JsonResponse
    {
        $validated = $request->validate(['email' => 'required|email|max:255']);

        MailSetting::applyActive();

        try {
            $status = Password::sendResetLink(['email' => $validated['email']]);

            if ($status !== Password::RESET_LINK_SENT) {
                Log::info('Réinitialisation du mot de passe : lien non envoyé.', ['status' => $status]);
            }
        } catch (\Throwable $e) {
            // Serveur d'envoi injoignable : journalisé pour le super-admin,
            // sans changer la réponse (elle ne doit rien révéler).
            Log::error('Réinitialisation du mot de passe : échec d\'envoi de l\'email.', [
                'error' => $e->getMessage(),
            ]);
        }

        return $this->success(null, __('api.password_reset.link_sent'));
    }

    /**
     * Définit le nouveau mot de passe à partir du jeton reçu par email et
     * ferme les autres sessions du compte.
     */
    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset($validated, function (User $user, string $password) {
            $user->forceFill([
                'password' => $password,
                'remember_token' => Str::random(60),
            ])->save();

            if (config('session.driver') === 'database') {
                DB::table((string) config('session.table', 'sessions'))->where('user_id', $user->id)->delete();
            }

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return $this->error(['email' => [__('api.password_reset.invalid_token')]], __('api.password_reset.invalid_token'), 422);
        }

        return $this->success(null, __('api.password_reset.done'));
    }
}
