import { useMemo, useState } from 'react';
import { CreditCard, Check, RefreshCw, Crown } from 'lucide-react';
import { useSubscription, usePacks } from '@/src/hooks/useSubscription';
import { usePayments, useCheckout } from '@/src/hooks/useBilling';
import { Pack, Payment } from '@/src/lib/types';
import { LoadingSpinner } from '@/src/components/LoadingSpinner';

function formatAmount(amount: number, currency: string): string {
  return new Intl.NumberFormat('fr-FR').format(amount) + ' ' + currency;
}

function formatDate(iso?: string): string {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString('fr-FR');
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
  };
  return labels[status] ?? status;
}

export function BillingManager() {
  const { data: subscription, isLoading: loadingSub } = useSubscription();
  const { data: packs, isLoading: loadingPacks } = usePacks();
  const { data: payments, isLoading: loadingPayments } = usePayments();
  const checkout = useCheckout();

  const [billingPeriod, setBillingPeriod] = useState<'monthly' | 'annual'>('monthly');
  const [selectedPackId, setSelectedPackId] = useState<number | null>(null);
  const [phone, setPhone] = useState('');

  const visiblePacks = useMemo(() => {
    if (!packs) return [];
    const byTier = new Map<string, { monthly?: Pack; annual?: Pack }>();
    for (const pack of packs) {
      const entry = byTier.get(pack.tier) ?? {};
      entry[pack.billing_period] = pack;
      byTier.set(pack.tier, entry);
    }
    return Array.from(byTier.entries()).map(([tier, periods]) => ({
      tier,
      pack: periods[billingPeriod] ?? periods.monthly ?? periods.annual,
    }));
  }, [packs, billingPeriod]);

  if (loadingSub || loadingPacks) {
    return <LoadingSpinner fullScreen={false} />;
  }

  const handleCheckout = () => {
    if (!selectedPackId || !phone.trim()) return;
    checkout.mutate({
      payload: { pack_id: selectedPackId, phone: phone.trim(), billing_period: billingPeriod },
      idempotencyKey: crypto.randomUUID(),
    });
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
          <p className="text-gray-500 text-sm">Gérez votre pack et suivez vos paiements.</p>
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
                <p className="text-xs text-gray-500 capitalize">/{subscription.billing_period}</p>
              </div>
            )}
          </div>
        ) : (
          <p className="text-gray-500">Aucun abonnement actif.</p>
        )}
      </div>

      {/* Tarifs */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-lg font-bold text-gray-900">Choisir un pack</h2>
          <div className="flex rounded-lg border border-gray-200 overflow-hidden">
            {(['monthly', 'annual'] as const).map((period) => (
              <button
                key={period}
                type="button"
                onClick={() => setBillingPeriod(period)}
                className={`px-4 py-1.5 text-sm font-medium capitalize transition-colors ${
                  billingPeriod === period ? 'bg-primary text-white' : 'text-gray-600 hover:bg-gray-50'
                }`}
              >
                {period === 'monthly' ? 'Mensuel' : 'Annuel'}
              </button>
            ))}
          </div>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {visiblePacks.map(({ tier, pack }) => {
            if (!pack) return null;
            const selected = selectedPackId === pack.id;
            return (
              <button
                key={pack.id}
                type="button"
                onClick={() => setSelectedPackId(pack.id)}
                className={`relative rounded-xl border p-5 text-left transition-all ${
                  selected
                    ? 'border-primary ring-2 ring-primary/20 bg-indigo-50/50'
                    : 'border-gray-200 hover:border-gray-300'
                }`}
              >
                {tier === 'premium' && (
                  <span className="absolute -top-2 right-4 text-xs font-bold bg-amber-400 text-white px-2 py-0.5 rounded-full">
                    Premium
                  </span>
                )}
                <p className="font-bold text-gray-900 capitalize">{pack.name}</p>
                <p className="text-2xl font-bold text-gray-900 mt-2">{formatAmount(pack.price, pack.currency)}</p>
                <p className="text-xs text-gray-500 capitalize">/ {pack.billing_period}</p>
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
            placeholder="Numéro mobile money (ex. 2376xxxxxxx)"
            value={phone}
            onChange={(e) => setPhone(e.target.value)}
            className="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-gray-900"
          />
          <button
            type="button"
            disabled={!selectedPackId || !phone.trim() || checkout.isPending}
            onClick={handleCheckout}
            className="px-6 py-2.5 bg-primary text-white rounded-lg hover:bg-secondary transition-colors font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <CreditCard size={18} />
            {checkout.isPending ? 'Initialisation…' : 'Payer'}
          </button>
        </div>
      </div>

      {/* Historique */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-lg font-bold text-gray-900">Historique des paiements</h2>
          <button
            type="button"
            onClick={() => window.location.reload()}
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
                  <th className="py-2 pr-4 font-medium">Référence</th>
                  <th className="py-2 pr-4 font-medium">Montant</th>
                  <th className="py-2 pr-4 font-medium">Statut</th>
                  <th className="py-2 pr-4 font-medium">Date</th>
                </tr>
              </thead>
              <tbody>
                {(payments.data as Payment[]).map((payment) => (
                  <tr key={payment.id} className="border-b border-gray-50">
                    <td className="py-2 pr-4 text-gray-900 font-mono text-xs">
                      {payment.gateway_reference ?? payment.external_reference ?? `#${payment.id}`}
                    </td>
                    <td className="py-2 pr-4 text-gray-900 font-medium">
                      {formatAmount(payment.amount, payment.currency)}
                    </td>
                    <td className="py-2 pr-4">
                      <span
                        className={`inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${
                          payment.status === 'paid'
                            ? 'bg-green-100 text-green-700'
                            : payment.status === 'failed'
                              ? 'bg-red-100 text-red-700'
                              : 'bg-gray-100 text-gray-600'
                        }`}
                      >
                        {statusLabel(payment.status)}
                      </span>
                    </td>
                    <td className="py-2 pr-4 text-gray-600">{formatDate(payment.created_at)}</td>
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
