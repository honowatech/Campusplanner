import { useState } from 'react';
import {
  Building2,
  Package,
  ListChecks,
  CreditCard,
  Receipt,
  Plus,
  ShieldAlert,
} from 'lucide-react';
import {
  useTenants,
  useCreateTenant,
  useSuspendTenant,
  useReactivateTenant,
  useAdminPacks,
  useSavePack,
  useAdminFeatures,
  useUpdateFeature,
  usePaymentGateway,
  useUpdatePaymentGateway,
  useTestPaymentGateway,
  useAdminPayments,
} from '@/src/hooks/useAdmin';
import { LoadingSpinner } from '@/src/components/LoadingSpinner';
import { Tenant, AdminPack, AdminFeature, PaymentGatewayTestResult } from '@/src/lib/types';
import { getLocale } from '@/src/lib/locale';

type Tab = 'tenants' | 'packs' | 'features' | 'gateway' | 'payments';

function formatAmount(amount: number, currency: string): string {
  return new Intl.NumberFormat(getLocale()).format(amount) + ' ' + currency;
}

export function AdminConsole() {
  const [tab, setTab] = useState<Tab>('tenants');

  const tabs: { id: Tab; label: string; icon: typeof Building2 }[] = [
    { id: 'tenants', label: 'Tenants', icon: Building2 },
    { id: 'packs', label: 'Packs', icon: Package },
    { id: 'features', label: 'Fonctionnalités', icon: ListChecks },
    { id: 'gateway', label: 'PayMe', icon: CreditCard },
    { id: 'payments', label: 'Paiements', icon: Receipt },
  ];

  return (
    <div className="max-w-6xl mx-auto">
      <div className="flex items-center mb-6">
        <div className="p-3 bg-gray-100 rounded-xl mr-4">
          <ShieldAlert className="text-gray-600" size={24} />
        </div>
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Administration plateforme</h1>
          <p className="text-gray-500 text-sm">Gestion des tenants, packs et configuration PayMe.</p>
        </div>
      </div>

      <div className="flex flex-wrap gap-2 mb-6">
        {tabs.map(({ id, label, icon: Icon }) => (
          <button
            key={id}
            type="button"
            onClick={() => setTab(id)}
            className={`flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
              tab === id ? 'bg-primary text-white' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'
            }`}
          >
            <Icon size={16} />
            {label}
          </button>
        ))}
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        {tab === 'tenants' && <TenantsTab />}
        {tab === 'packs' && <PacksTab />}
        {tab === 'features' && <FeaturesTab />}
        {tab === 'gateway' && <GatewayTab />}
        {tab === 'payments' && <PaymentsTab />}
      </div>
    </div>
  );
}

function TenantsTab() {
  const { data: tenants, isLoading } = useTenants();
  const createTenant = useCreateTenant();
  const suspendTenant = useSuspendTenant();
  const reactivateTenant = useReactivateTenant();

  const [showForm, setShowForm] = useState(false);
  const [form, setForm] = useState({ name: '', slug: '', admin_name: '', admin_email: '', admin_password: '' });

  if (isLoading) return <LoadingSpinner fullScreen={false} />;

  const handleCreate = () => {
    createTenant.mutate(form);
    setForm({ name: '', slug: '', admin_name: '', admin_email: '', admin_password: '' });
    setShowForm(false);
  };

  return (
    <div>
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-lg font-bold text-gray-900">Tenants</h2>
        <button
          type="button"
          onClick={() => setShowForm((s) => !s)}
          className="flex items-center gap-1.5 px-3 py-1.5 bg-primary text-white rounded-lg text-sm font-medium"
        >
          <Plus size={16} />
          Nouveau
        </button>
      </div>

      {showForm && (
        <div className="mb-6 p-4 rounded-lg bg-gray-50 border border-gray-200 grid grid-cols-1 md:grid-cols-2 gap-3">
          {(
            [
              ['name', 'Nom'],
              ['slug', 'Slug'],
              ['admin_name', 'Nom admin'],
              ['admin_email', 'Email admin'],
              ['admin_password', 'Mot de passe'],
            ] as const
          ).map(([key, label]) => (
            <input
              key={key}
              type={key === 'admin_password' ? 'password' : 'text'}
              placeholder={label}
              value={form[key]}
              onChange={(e) => setForm((f) => ({ ...f, [key]: e.target.value }))}
              className="px-3 py-2 border border-gray-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-indigo-500"
            />
          ))}
          <button
            type="button"
            onClick={handleCreate}
            disabled={createTenant.isPending}
            className="md:col-span-2 px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium disabled:opacity-50"
          >
            Créer le tenant
          </button>
        </div>
      )}

      <table className="w-full text-sm">
        <thead>
          <tr className="text-left text-gray-500 border-b border-gray-100">
            <th className="py-2 pr-4 font-medium">Nom</th>
            <th className="py-2 pr-4 font-medium">Slug</th>
            <th className="py-2 pr-4 font-medium">Statut</th>
            <th className="py-2 pr-4 font-medium">Utilisateurs</th>
            <th className="py-2 pr-4 font-medium">Actions</th>
          </tr>
        </thead>
        <tbody>
          {(tenants?.data ?? []).map((tenant: Tenant) => (
            <tr key={tenant.id} className="border-b border-gray-50">
              <td className="py-2 pr-4 text-gray-900 font-medium">
                {tenant.name} {tenant.is_demo && <span className="text-xs text-amber-600">(démo)</span>}
              </td>
              <td className="py-2 pr-4 text-gray-600">{tenant.slug}</td>
              <td className="py-2 pr-4">
                <span
                  className={`inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${
                    tenant.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                  }`}
                >
                  {tenant.status}
                </span>
              </td>
              <td className="py-2 pr-4 text-gray-600">{tenant.users_count ?? 0}</td>
              <td className="py-2 pr-4">
                {tenant.status === 'active' ? (
                  <button
                    type="button"
                    onClick={() => suspendTenant.mutate(tenant.id)}
                    className="text-red-600 hover:text-red-700 text-xs font-medium"
                  >
                    Suspendre
                  </button>
                ) : (
                  <button
                    type="button"
                    onClick={() => reactivateTenant.mutate(tenant.id)}
                    className="text-green-600 hover:text-green-700 text-xs font-medium"
                  >
                    Réactiver
                  </button>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function PacksTab() {
  const { data: packs, isLoading } = useAdminPacks();
  const savePack = useSavePack();

  const [showForm, setShowForm] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [form, setForm] = useState({
    name: '',
    slug: '',
    tier: '',
    price: 0,
    billing_period: 'monthly' as 'monthly' | 'annual',
  });

  if (isLoading) return <LoadingSpinner fullScreen={false} />;

  const handleSave = () => {
    savePack.mutate({
      id: editingId ?? undefined,
      data: { ...form, price: Number(form.price) },
    });
    setShowForm(false);
    setEditingId(null);
    setForm({ name: '', slug: '', tier: '', price: 0, billing_period: 'monthly' });
  };

  return (
    <div>
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-lg font-bold text-gray-900">Packs d'abonnement</h2>
        <button
          type="button"
          onClick={() => {
            setShowForm(true);
            setEditingId(null);
            setForm({ name: '', slug: '', tier: '', price: 0, billing_period: 'monthly' });
          }}
          className="flex items-center gap-1.5 px-3 py-1.5 bg-primary text-white rounded-lg text-sm font-medium"
        >
          <Plus size={16} />
          Nouveau pack
        </button>
      </div>

      {showForm && (
        <div className="mb-6 p-4 rounded-lg bg-gray-50 border border-gray-200 grid grid-cols-2 md:grid-cols-5 gap-3">
          <input placeholder="Nom" value={form.name} onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))} className="px-3 py-2 border border-gray-300 rounded-lg text-sm" />
          <input placeholder="Slug" value={form.slug} onChange={(e) => setForm((f) => ({ ...f, slug: e.target.value }))} className="px-3 py-2 border border-gray-300 rounded-lg text-sm" />
          <input placeholder="Tier" value={form.tier} onChange={(e) => setForm((f) => ({ ...f, tier: e.target.value }))} className="px-3 py-2 border border-gray-300 rounded-lg text-sm" />
          <input type="number" placeholder="Prix" value={form.price} onChange={(e) => setForm((f) => ({ ...f, price: Number(e.target.value) }))} className="px-3 py-2 border border-gray-300 rounded-lg text-sm" />
          <select value={form.billing_period} onChange={(e) => setForm((f) => ({ ...f, billing_period: e.target.value as 'monthly' | 'annual' }))} className="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="monthly">Mensuel</option>
            <option value="annual">Annuel</option>
          </select>
          <button type="button" onClick={handleSave} className="col-span-2 md:col-span-5 px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium">
            Enregistrer
          </button>
        </div>
      )}

      <table className="w-full text-sm">
        <thead>
          <tr className="text-left text-gray-500 border-b border-gray-100">
            <th className="py-2 pr-4 font-medium">Nom</th>
            <th className="py-2 pr-4 font-medium">Tier</th>
            <th className="py-2 pr-4 font-medium">Prix</th>
            <th className="py-2 pr-4 font-medium">Période</th>
            <th className="py-2 pr-4 font-medium">Fonctionnalités</th>
          </tr>
        </thead>
        <tbody>
          {(packs ?? []).map((pack: AdminPack) => (
            <tr key={pack.id} className="border-b border-gray-50">
              <td className="py-2 pr-4 text-gray-900 font-medium">{pack.name}</td>
              <td className="py-2 pr-4 text-gray-600 capitalize">{pack.tier}</td>
              <td className="py-2 pr-4 text-gray-900">{formatAmount(pack.price, pack.currency)}</td>
              <td className="py-2 pr-4 text-gray-600 capitalize">{pack.billing_period}</td>
              <td className="py-2 pr-4 text-gray-600">{pack.features.length} features</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function FeaturesTab() {
  const { data: features, isLoading } = useAdminFeatures();
  const updateFeature = useUpdateFeature();

  if (isLoading) return <LoadingSpinner fullScreen={false} />;

  return (
    <div>
      <h2 className="text-lg font-bold text-gray-900 mb-4">Fonctionnalités</h2>
      <div className="space-y-2">
        {(features ?? []).map((feature: AdminFeature) => (
          <div key={feature.id} className="flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:border-gray-200">
            <div>
              <p className="text-sm font-medium text-gray-900">{feature.label}</p>
              <p className="text-xs text-gray-500">{feature.key}</p>
            </div>
            <button
              type="button"
              onClick={() => updateFeature.mutate({ id: feature.id, data: { is_active: !feature.is_active } })}
              className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${feature.is_active ? 'bg-secondary' : 'bg-gray-200'}`}
            >
              <span className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${feature.is_active ? 'translate-x-6' : 'translate-x-1'}`} />
            </button>
          </div>
        ))}
      </div>
    </div>
  );
}

function GatewayTab() {
  const { data: gateway, isLoading } = usePaymentGateway();
  const updateGateway = useUpdatePaymentGateway();
  const testGateway = useTestPaymentGateway();
  const [testResult, setTestResult] = useState<PaymentGatewayTestResult | null>(null);

  const defaultEndpoint = gateway?.default_endpoint ?? 'https://console-v2.mamonipay.me/api';

  const [form, setForm] = useState({
    user_name: gateway?.user_name ?? '',
    password: '',
    endpoint: gateway?.endpoint ?? '',
    client_fees_rate: gateway?.client_fees_rate == null ? '' : String(gateway.client_fees_rate),
    timeout_minutes: String(gateway?.timeout_minutes ?? 15),
    is_active: gateway?.is_active ?? true,
  });

  if (isLoading) return <LoadingSpinner fullScreen={false} />;

  const endpointLooksWrong = /\/docs(\/|$)|\/v\d+\/?$/i.test(form.endpoint.trim());
  const feesRate = form.client_fees_rate.trim() === '' ? null : Number(form.client_fees_rate);
  const feesInvalid = feesRate !== null && (!Number.isInteger(feesRate) || feesRate < 0 || feesRate > 100);
  const timeout = Number(form.timeout_minutes);
  const timeoutInvalid = !Number.isInteger(timeout) || timeout < 2 || timeout > 60;

  const handleSave = () => {
    if (endpointLooksWrong || feesInvalid || timeoutInvalid) return;
    setTestResult(null);
    updateGateway.mutate({
      user_name: form.user_name,
      password: form.password || undefined,
      endpoint: form.endpoint.trim() || null,
      client_fees_rate: feesRate,
      timeout_minutes: timeout,
      is_active: form.is_active,
    });
  };

  const handleTest = () => {
    setTestResult(null);
    testGateway.mutate(undefined, { onSuccess: setTestResult });
  };

  const inputClass = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm';

  return (
    <div>
      <h2 className="text-lg font-bold text-gray-900 mb-1">Configuration PayMe (MamoniPay)</h2>
      <p className="text-sm text-gray-500 mb-4">
        Identifiants du compte marchand PayMe (API Partenaire). Enregistrez, puis testez la connexion avant toute
        mise en production.
      </p>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">Identifiant (login)</label>
          <input
            value={form.user_name}
            onChange={(e) => setForm((f) => ({ ...f, user_name: e.target.value }))}
            className={inputClass}
            placeholder="237XXXXXXXXX ou e-mail"
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">Mot de passe</label>
          <input
            type="password"
            value={form.password}
            onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
            className={inputClass}
            placeholder={gateway?.has_password ? '•••••••• (inchangé si vide)' : '••••••••'}
            autoComplete="new-password"
          />
        </div>
        <div className="md:col-span-2">
          <label className="block text-sm font-semibold text-gray-700 mb-1">Adresse de l’API</label>
          <input
            value={form.endpoint}
            onChange={(e) => setForm((f) => ({ ...f, endpoint: e.target.value }))}
            className={`${inputClass} ${endpointLooksWrong ? 'border-red-400' : ''}`}
            placeholder={defaultEndpoint}
          />
          <p className={`text-xs mt-1 ${endpointLooksWrong ? 'text-red-600' : 'text-gray-500'}`}>
            {endpointLooksWrong
              ? 'Adresse invalide : indiquez l’adresse de base de l’API, jamais l’adresse de la documentation (/docs) ni une adresse se terminant par /v1.'
              : `Laissez vide pour utiliser ${defaultEndpoint}. Jamais l’adresse de la documentation (/docs), sans /v1 à la fin.`}
          </p>
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">Frais portés par le client (%)</label>
          <input
            type="number"
            min={0}
            max={100}
            step={1}
            value={form.client_fees_rate}
            onChange={(e) => setForm((f) => ({ ...f, client_fees_rate: e.target.value }))}
            className={`${inputClass} ${feesInvalid ? 'border-red-400' : ''}`}
            placeholder="Réglage du compte marchand"
          />
          <p className="text-xs text-gray-500 mt-1">Entier 0–100. Vide = le réglage du compte marchand s’applique.</p>
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">Délai d’attente (minutes)</label>
          <input
            type="number"
            min={2}
            max={60}
            step={1}
            value={form.timeout_minutes}
            onChange={(e) => setForm((f) => ({ ...f, timeout_minutes: e.target.value }))}
            className={`${inputClass} ${timeoutInvalid ? 'border-red-400' : ''}`}
          />
          <p className="text-xs text-gray-500 mt-1">
            2–60 min. PayMe ne signale pas l’expiration : passé ce délai, la demande est abandonnée.
          </p>
        </div>
        <div className="md:col-span-2 flex items-center gap-2">
          <input
            id="gateway-active"
            type="checkbox"
            checked={form.is_active}
            onChange={(e) => setForm((f) => ({ ...f, is_active: e.target.checked }))}
          />
          <label htmlFor="gateway-active" className="text-sm text-gray-700">
            Passerelle active
          </label>
        </div>
      </div>
      <div className="mt-4 flex flex-wrap gap-3">
        <button
          type="button"
          onClick={handleSave}
          disabled={updateGateway.isPending || !form.user_name.trim() || endpointLooksWrong || feesInvalid || timeoutInvalid}
          className="px-6 py-2.5 bg-primary text-white rounded-lg text-sm font-medium disabled:opacity-50"
        >
          {updateGateway.isPending ? 'Enregistrement…' : 'Enregistrer'}
        </button>
        <button
          type="button"
          onClick={handleTest}
          disabled={testGateway.isPending || !gateway?.has_password}
          className="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium disabled:opacity-50"
          title={!gateway?.has_password ? 'Enregistrez d’abord les identifiants' : undefined}
        >
          {testGateway.isPending ? 'Test en cours…' : 'Tester la connexion'}
        </button>
      </div>
      {testResult && (
        <div
          className={`mt-4 max-w-2xl rounded-lg border p-4 text-sm ${
            testResult.ok ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800'
          }`}
        >
          <p className="font-semibold">
            {testResult.ok ? 'Connexion réussie' : 'Connexion échouée'}
            {testResult.status ? ` (HTTP ${testResult.status})` : ''}
          </p>
          <p className="mt-1 break-words">{testResult.message}</p>
          {testResult.url && (
            <p className="mt-1 font-mono text-xs break-all">
              URL appelée : {testResult.url}
              {testResult.effective_url ? ` → redirigée vers ${testResult.effective_url}` : ''}
            </p>
          )}
          {testResult.ok && testResult.expires_at && (
            <p className="mt-1 text-xs">Jeton valable jusqu’au {new Date(testResult.expires_at).toLocaleString(getLocale())}</p>
          )}
        </div>
      )}
      <div className="mt-6 max-w-2xl text-xs text-gray-500 space-y-1">
        <p className="font-semibold text-gray-600">Limites de l’API PayMe</p>
        <p>• Aucune notification : la confirmation repose sur la vérification active (page de paiement + planificateur chaque minute).</p>
        <p>• Aucune annulation côté PayMe : « Annuler » clôt la demande ici ; l’invite peut rester sur le téléphone, d’où une revérification pendant une heure.</p>
        <p>• Aucune expiration signalée : le délai configuré ci-dessus fait foi.</p>
        <p>• Pas d’environnement de test ni de remboursement par l’API.</p>
      </div>
    </div>
  );
}

function PaymentsTab() {
  const { data: payments, isLoading } = useAdminPayments();

  if (isLoading) return <LoadingSpinner fullScreen={false} />;

  return (
    <div>
      <h2 className="text-lg font-bold text-gray-900 mb-4">Paiements (tous tenants)</h2>
      {!payments?.data.length ? (
        <p className="text-gray-500 text-sm">Aucun paiement.</p>
      ) : (
        <table className="w-full text-sm">
          <thead>
            <tr className="text-left text-gray-500 border-b border-gray-100">
              <th className="py-2 pr-4 font-medium">Tenant</th>
              <th className="py-2 pr-4 font-medium">Montant</th>
              <th className="py-2 pr-4 font-medium">Statut</th>
              <th className="py-2 pr-4 font-medium">Date</th>
            </tr>
          </thead>
          <tbody>
            {payments.data.map((payment) => (
              <tr key={payment.id} className="border-b border-gray-50">
                <td className="py-2 pr-4 text-gray-900 font-medium">{payment.tenant_id}</td>
                <td className="py-2 pr-4 text-gray-900">{formatAmount(payment.amount, payment.currency)}</td>
                <td className="py-2 pr-4 text-gray-600">{payment.status}</td>
                <td className="py-2 pr-4 text-gray-600">{payment.created_at ? new Date(payment.created_at).toLocaleDateString(getLocale()) : '—'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}
