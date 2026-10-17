import { useEffect, useRef, useState } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import {
  CreditCard,
  Check,
  RefreshCw,
  Crown,
  Smartphone,
  Loader2,
  CheckCircle2,
  XCircle,
  ExternalLink,
} from 'lucide-react';
import { useSubscription, usePacks } from '@/src/hooks/useSubscription';
import {
  usePayments,
  useCheckout,
  usePaymentStatus,
  useCancelPayment,
  isAwaitingPayment,
} from '@/src/hooks/useBilling';
import { useAuth } from '@/src/auth';
import { useTranslation } from '@/src/utils/i18n';
import { Payment } from '@/src/lib/types';
import { LoadingSpinner } from '@/src/components/LoadingSpinner';
import { getLocale } from '@/src/lib/locale';
import { BillingPeriod, formatAmount, packsForPeriod } from '@/src/lib/packs';

function formatDate(iso?: string): string {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString(getLocale());
}

function statusLabel(status: string): string {
  const labels: Record<string, string> = {
    active: 'Actif',
    trial: 'Essai',
    expired: 'Expiré',
    canceled: 'Annulé',
    past_due: 'En retard',
    pending: 'En attente',
    in_progress: 'En cours',
    paid: 'Payé',
    failed: 'Échoué',
    refunded: 'Remboursé',
    deposited: 'Déposé',
    needs_review: 'À vérifier',
  };
  return labels[status] ?? status;
}

/** Secondes restantes avant l'expiration de la demande (délai PayMe configuré). */
function useRemainingSeconds(payment?: Payment): number | null {
  const [now, setNow] = useState(() => Date.now());

  useEffect(() => {
    if (!isAwaitingPayment(payment)) return;
    const timer = window.setInterval(() => setNow(Date.now()), 1000);
    return () => window.clearInterval(timer);
  }, [payment]);

  if (!payment?.created_at || !payment.metadata?.timeout_minutes) return null;

  const deadline = new Date(payment.created_at).getTime() + payment.metadata.timeout_minutes * 60_000;
  return Math.max(0, Math.round((deadline - now) / 1000));
}

export function BillingManager({ initialPackId }: { initialPackId?: number }) {
  const { t } = useTranslation();
  const auth = useAuth();
  const queryClient = useQueryClient();
  const { data: subscription, isLoading: loadingSub } = useSubscription();
  const { data: packs, isLoading: loadingPacks } = usePacks();
  const { data: payments, isLoading: loadingPayments, refetch: refetchPayments } = usePayments();
  const checkout = useCheckout();
  const cancelPayment = useCancelPayment();

  const [billingPeriod, setBillingPeriod] = useState<BillingPeriod>('monthly');
  const [selectedPackId, setSelectedPackId] = useState<number | null>(initialPackId ?? null);
  const [phone, setPhone] = useState('');
  const [trackedId, setTrackedId] = useState<number | null>(null);

  const { data: trackedPayment } = usePaymentStatus(trackedId);
  const remaining = useRemainingSeconds(trackedPayment);
  const previousStatus = useRef<string | undefined>(undefined);

  // Formule choisie à l'inscription : aligne la période affichée.
  useEffect(() => {
    const initial = packs?.find((pack) => pack.id === initialPackId);
    if (initial) setBillingPeriod(initial.billing_period);
  }, [packs, initialPackId]);

  // Reprend le suivi d'une demande encore en attente (ex. page rechargée).
  useEffect(() => {
    if (trackedId !== null) return;
    const awaiting = payments?.data.find((payment) => isAwaitingPayment(payment));
    if (awaiting) setTrackedId(awaiting.id);
  }, [payments, trackedId]);

  // Conclusion du paiement : l'abonnement est déjà activé côté API.
  useEffect(() => {
    const status = trackedPayment?.status;
    if (!status || status === previousStatus.current) return;

    const wasAwaiting = previousStatus.current === undefined || ['pending', 'in_progress'].includes(previousStatus.current);
    previousStatus.current = status;

    if (!wasAwaiting) return;

    if (status === 'paid') {
      toast.success(t('toast.paymentConfirmed'));
      queryClient.invalidateQueries({ queryKey: ['subscription'] });
      queryClient.invalidateQueries({ queryKey: ['payments'] });
      // Rafraîchit les fonctionnalités du pack (menu, gardes).
      auth.refreshUser();
    } else if (status === 'failed') {
      queryClient.invalidateQueries({ queryKey: ['payments'] });
    }
  }, [trackedPayment?.status, t, queryClient, auth]);

  if (loadingSub || loadingPacks) {
    return <LoadingSpinner fullScreen={false} />;
  }

  const visiblePacks = packsForPeriod(packs, billingPeriod);
  const awaiting = isAwaitingPayment(trackedPayment);

  const handleCheckout = () => {
    if (!selectedPackId || !phone.trim()) return;
    checkout.mutate(
      {
        payload: { pack_id: selectedPackId, phone: phone.trim(), billing_period: billingPeriod },
        idempotencyKey: crypto.randomUUID(),
      },
      {
        onSuccess: (payment) => {
          previousStatus.current = undefined;
          queryClient.setQueryData(['payment', payment.id], payment);
          setTrackedId(payment.id);
        },
      },
    );
  };

  const changePeriod = (period: BillingPeriod) => {
    setBillingPeriod(period);
    // Conserve le palier choisi en changeant de période.
    const tier = packs?.find((pack) => pack.id === selectedPackId)?.tier;
    const match = packs?.find((pack) => pack.tier === tier && pack.billing_period === period);
    if (match) setSelectedPackId(match.id);
  };

  return (
    <div className="max-w-5xl mx-auto space-y-8">
      {/* En-tête */}
      <div className="flex items-center mb-2">
        <div className="p-3 bg-indigo-100 rounded-xl mr-4">
          <CreditCard className="text-primary" size={24} />
        </div>
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Abonnement & Facturation</h1>
          <p className="text-gray-500 text-sm">
            Choisissez votre formule et payez par mobile money : elle est activée dès la confirmation.
          </p>
        </div>
      </div>

      {/* Abonnement courant */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 className="text-lg font-bold text-gray-900 mb-4">Mon abonnement</h2>
        {subscription ? (
          <div className="flex flex-wrap items-center gap-6">
            <div className="flex items-center space-x-3">
              <Crown className="text-amber-500" size={28} />
              <div>
                <p className="font-bold text-gray-900">{subscription.pack?.name ?? 'Essai gratuit'}</p>
                <p className="text-sm text-gray-500 capitalize">{statusLabel(subscription.status)}</p>
              </div>
            </div>
            <div className="text-sm text-gray-600">
              <p>
                Début : <span className="font-medium">{formatDate(subscription.starts_at)}</span>
              </p>
              <p>
                {subscription.status === 'trial' ? 'Fin d’essai' : 'Échéance'} :{' '}
                <span className="font-medium">
                  {formatDate(subscription.trial_ends_at ?? subscription.ends_at)}
                </span>
              </p>
            </div>
            {subscription.pack && (
              <div className="ml-auto text-right">
                <p className="text-2xl font-bold text-gray-900">
                  {formatAmount(subscription.pack.price, subscription.pack.currency)}
                </p>
                <p className="text-xs text-gray-500">
                  {subscription.billing_period === 'annual' ? '/ an' : '/ mois'}
                </p>
              </div>
            )}
          </div>
        ) : (
          <p className="text-gray-500">Aucun abonnement actif.</p>
        )}
      </div>

      {/* Suivi de la demande de paiement en cours */}
      {trackedPayment && (
        <PaymentTracker
          payment={trackedPayment}
          remaining={remaining}
          cancelling={cancelPayment.isPending}
          onCancel={() => cancelPayment.mutate(trackedPayment.id)}
          onDismiss={() => setTrackedId(null)}
        />
      )}

      {/* Tarifs */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div className="flex flex-wrap items-center justify-between gap-3 mb-6">
          <h2 className="text-lg font-bold text-gray-900">
            {subscription?.pack ? 'Renouveler ou changer de formule' : 'Choisir une formule'}
          </h2>
          <div className="flex rounded-lg border border-gray-200 overflow-hidden">
            {(['monthly', 'annual'] as const).map((period) => (
              <button
                key={period}
                type="button"
                onClick={() => changePeriod(period)}
                className={`px-4 py-1.5 text-sm font-medium transition-colors ${
                  billingPeriod === period ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50'
                }`}
              >
                {period === 'monthly' ? 'Mensuel' : 'Annuel'}
              </button>
            ))}
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {visiblePacks.map((pack) => {
            const selected = selectedPackId === pack.id;
            return (
              <button
                key={pack.id}
                type="button"
                onClick={() => setSelectedPackId(pack.id)}
                aria-pressed={selected}
                className={`relative rounded-xl border p-5 text-left transition-all ${
                  selected
                    ? 'border-primary ring-2 ring-primary/20 bg-indigo-50/50'
                    : 'border-gray-200 hover:border-gray-300'
                }`}
              >
                {pack.tier === 'premium' && (
                  <span className="absolute -top-2 right-4 text-xs font-bold bg-amber-400 text-white px-2 py-0.5 rounded-full">
                    Premium
                  </span>
                )}
                <p className="font-bold text-gray-900 capitalize">{pack.name}</p>
                <p className="text-2xl font-bold text-gray-900 mt-2">{formatAmount(pack.price, pack.currency)}</p>
                <p className="text-xs text-gray-500">{pack.billing_period === 'annual' ? '/ an' : '/ mois'}</p>
                <p className="text-sm text-gray-600 mt-3">{pack.features.length} fonctionnalités incluses</p>
                {selected && (
                  <span className="absolute bottom-3 right-3 text-primary">
                    <Check size={20} />
                  </span>
                )}
              </button>
            );
          })}
        </div>

        {/* Checkout */}
        <div className="mt-6 pt-6 border-t border-gray-100 flex flex-col sm:flex-row gap-3">
          <input
            type="tel"
            placeholder="Numéro mobile money (ex. 6XX XX XX XX)"
            value={phone}
            onChange={(e) => setPhone(e.target.value)}
            className="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-gray-900"
          />
          <button
            type="button"
            disabled={!selectedPackId || !phone.trim() || checkout.isPending || awaiting}
            onClick={handleCheckout}
            className="px-6 py-2.5 bg-primary text-white rounded-lg hover:bg-secondary transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <CreditCard size={18} />
            {checkout.isPending ? 'Initialisation…' : 'Payer'}
          </button>
        </div>
        <p className="mt-2 text-xs text-gray-500">
          Un paiement en cours d’abonnement prolonge votre échéance : aucune période déjà payée n’est perdue.
        </p>
      </div>

      {/* Historique */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-lg font-bold text-gray-900">Historique des paiements</h2>
          <button
            type="button"
            onClick={() => refetchPayments()}
            className="text-gray-400 hover:text-gray-600"
            aria-label="Rafraîchir"
          >
            <RefreshCw size={16} />
          </button>
        </div>
        {loadingPayments ? (
          <LoadingSpinner fullScreen={false} />
        ) : !payments?.data.length ? (
          <p className="text-gray-500 text-sm">Aucun paiement pour le moment.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left text-gray-500 border-b border-gray-100">
                  <th className="py-2 pr-4 font-medium">Formule</th>
                  <th className="py-2 pr-4 font-medium">Montant</th>
                  <th className="py-2 pr-4 font-medium">Statut</th>
                  <th className="py-2 pr-4 font-medium">Date</th>
                  <th className="py-2 pr-4 font-medium">Reçu</th>
                </tr>
              </thead>
              <tbody>
                {(payments.data as Payment[]).map((payment) => (
                  <tr key={payment.id} className="border-b border-gray-50">
                    <td className="py-2 pr-4 text-gray-900">
                      {payment.metadata?.pack_name ?? `#${payment.id}`}
                      <span className="block font-mono text-[11px] text-gray-400">
                        {payment.gateway_reference ?? payment.external_reference}
                      </span>
                    </td>
                    <td className="py-2 pr-4 text-gray-900 font-medium">
                      {formatAmount(payment.amount, payment.currency)}
                    </td>
                    <td className="py-2 pr-4">
                      <span
                        title={payment.metadata?.error}
                        className={`inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${
                          payment.status === 'paid'
                            ? 'bg-green-100 text-green-700'
                            : payment.status === 'failed'
                              ? 'bg-red-100 text-red-700'
                              : payment.status === 'needs_review'
                                ? 'bg-amber-100 text-amber-700'
                                : 'bg-gray-100 text-gray-600'
                        }`}
                      >
                        {statusLabel(payment.status)}
                      </span>
                    </td>
                    <td className="py-2 pr-4 text-gray-600">{formatDate(payment.created_at)}</td>
                    <td className="py-2 pr-4">
                      {payment.receipt_url ? (
                        <a
                          href={payment.receipt_url}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800"
                        >
                          Voir <ExternalLink size={12} />
                        </a>
                      ) : (
                        <span className="text-gray-400">—</span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}

function PaymentTracker({
  payment,
  remaining,
  cancelling,
  onCancel,
  onDismiss,
}: {
  payment: Payment;
  remaining: number | null;
  cancelling: boolean;
  onCancel: () => void;
  onDismiss: () => void;
}) {
  if (isAwaitingPayment(payment)) {
    const minutes = remaining !== null ? Math.floor(remaining / 60) : null;
    const seconds = remaining !== null ? String(remaining % 60).padStart(2, '0') : null;

    return (
      <div className="bg-indigo-50 border border-indigo-200 rounded-xl p-6 flex flex-col sm:flex-row sm:items-center gap-4" role="status">
        <div className="p-3 bg-white rounded-full shrink-0 self-start">
          <Smartphone className="text-primary" size={24} />
        </div>
        <div className="flex-1">
          <p className="font-bold text-gray-900 flex items-center gap-2">
            <Loader2 size={16} className="animate-spin text-primary" />
            Validez le paiement sur votre téléphone
          </p>
          <p className="text-sm text-gray-600 mt-1">
            {formatAmount(payment.amount, payment.currency)}
            {payment.metadata?.pack_name ? ` — formule ${payment.metadata.pack_name}` : ''}
            {payment.metadata?.phone ? ` — ${payment.metadata.phone}` : ''}. Composez votre code secret
            mobile money à la demande de l’opérateur ; la formule s’active automatiquement.
          </p>
          {minutes !== null && (
            <p className="text-xs text-gray-500 mt-2">
              Délai restant : {minutes}:{seconds}
            </p>
          )}
        </div>
        <button
          type="button"
          onClick={onCancel}
          disabled={cancelling}
          className="px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
        >
          {cancelling ? 'Annulation…' : 'Annuler la demande'}
        </button>
      </div>
    );
  }

  if (payment.status === 'paid') {
    return (
      <div className="bg-green-50 border border-green-200 rounded-xl p-6 flex items-start gap-4" role="status">
        <CheckCircle2 className="text-green-600 shrink-0" size={28} />
        <div className="flex-1">
          <p className="font-bold text-gray-900">Paiement confirmé — votre formule est active</p>
          <p className="text-sm text-gray-600 mt-1">
            Toutes les fonctionnalités de la formule {payment.metadata?.pack_name ?? ''} sont disponibles dès
            maintenant.
          </p>
          {payment.receipt_url && (
            <a
              href={payment.receipt_url}
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-1 mt-2 text-sm text-indigo-600 hover:text-indigo-800"
            >
              Voir le reçu <ExternalLink size={14} />
            </a>
          )}
        </div>
        <button type="button" onClick={onDismiss} className="text-sm text-gray-500 hover:text-gray-700">
          Fermer
        </button>
      </div>
    );
  }

  const cancelled = payment.gateway_status?.toUpperCase() === 'CANCELLED';

  return (
    <div className="bg-red-50 border border-red-200 rounded-xl p-6 flex items-start gap-4" role="alert">
      <XCircle className="text-red-600 shrink-0" size={28} />
      <div className="flex-1">
        <p className="font-bold text-gray-900">
          {cancelled ? 'Demande annulée' : 'Le paiement n’a pas abouti'}
        </p>
        <p className="text-sm text-gray-600 mt-1">
          {payment.metadata?.error ?? 'Aucun montant n’a été débité.'} Vous pouvez relancer le paiement
          ci-dessous.
          {cancelled || payment.gateway_status?.toUpperCase() === 'EXPIRED'
            ? ' Si vous validez quand même sur votre téléphone, la formule sera activée automatiquement.'
            : ''}
        </p>
      </div>
      <button type="button" onClick={onDismiss} className="text-sm text-gray-500 hover:text-gray-700">
        Fermer
      </button>
    </div>
  );
}
