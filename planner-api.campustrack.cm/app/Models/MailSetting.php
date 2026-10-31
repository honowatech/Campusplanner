<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

/**
 * Compte d'envoi des emails de la plateforme, géré par le super-admin.
 * Le mot de passe est chiffré (cast `encrypted`) et jamais sérialisé.
 */
class MailSetting extends Model
{
    public const DEFAULT_FROM = 'admin@focusrent.cm';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'mailer',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'from_address',
        'from_name',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'port' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * La configuration active (singleton plateforme).
     */
    public static function active(): ?self
    {
        return static::query()->where('is_active', true)->latest('id')->first();
    }

    /**
     * Applique la configuration active au mailer de l'application (sinon la
     * configuration du `.env` reste en vigueur).
     */
    public static function applyActive(): void
    {
        static::active()?->apply();
    }

    /**
     * Applique cette configuration au mailer par défaut.
     */
    public function apply(): void
    {
        $mailer = $this->mailer === 'log' ? 'log' : 'smtp';

        if ($mailer === 'smtp') {
            config([
                'mail.mailers.smtp.scheme' => $this->encryption === 'ssl' ? 'smtps' : 'smtp',
                'mail.mailers.smtp.url' => null,
                'mail.mailers.smtp.host' => $this->host,
                'mail.mailers.smtp.port' => $this->port ?: ($this->encryption === 'ssl' ? 465 : 587),
                'mail.mailers.smtp.username' => $this->username,
                'mail.mailers.smtp.password' => $this->password,
                'mail.mailers.smtp.timeout' => 15,
            ]);
        }

        config([
            'mail.default' => $mailer,
            'mail.from.address' => $this->from_address ?: self::DEFAULT_FROM,
            'mail.from.name' => $this->from_name ?: config('app.name'),
        ]);

        // Le mailer résolu plus tôt garderait l'ancienne configuration.
        Mail::purge($mailer);
    }
}
