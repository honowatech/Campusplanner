<?php

namespace App\Enums\V1;

/**
 * Machine à états des paiements (cf. §7 du plan : idempotence au niveau service).
 *
 * Seules les transitions déclarées sont autorisées ; toute autre transition est
 * un no-op (rejeu d'un webhook déjà traité, etc.).
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Deposited = 'deposited';

    /**
     * États terminaux (aucune transition sortante).
     *
     * @return list<self>
     */
    public static function terminal(): array
    {
        return [self::Failed, self::Refunded, self::Deposited];
    }

    public function isTerminal(): bool
    {
        return in_array($this, self::terminal(), true);
    }

    /**
     * Indique si la transition vers `$next` est légale.
     */
    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->allowedTransitions(), true);
    }

    /**
     * Transitions sortantes autorisées.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::InProgress, self::Paid, self::Failed],
            self::InProgress => [self::Paid, self::Failed],
            self::Paid => [self::Refunded],
            self::Failed, self::Refunded, self::Deposited => [],
        };
    }
}
