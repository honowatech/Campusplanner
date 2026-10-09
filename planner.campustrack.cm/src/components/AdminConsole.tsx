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
  useAdminPayments,
} from '@/src/hooks/useAdmin';
import { LoadingSpinner } from '@/src/components/LoadingSpinner';
import { Tenant, AdminPack, AdminFeature } from '@/src/lib/types';

type Tab = 'tenants' | 'packs' | 'features' | 'gateway' | 'payments';

function formatAmount(amount: number, currency: string): string {
  return new Intl.NumberFormat('fr-FR').format(amount) + ' ' + currency;
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

  const [form, setForm] = useState({
    user_name: gateway?.user_name ?? '',
    password: '',
    app_id: gateway?.app_id ?? '',
    endpoint: gateway?.endpoint ?? '',
    pay_type_id: gateway?.pay_type_id ?? '',
    mode: (gateway?.mode ?? 'sandbox') as 'sandbox' | 'live',
  });

  if (isLoading) return <LoadingSpinner fullScreen={false} />;

  const handleSave = () => {
    updateGateway.mutate({
      user_name: form.user_name,
      password: form.password || undefined,
      app_id: form.app_id || undefined,
      endpoint: form.endpoint || undefined,
      pay_type_id: form.pay_type_id || undefined,
      mode: form.mode,
    });
  };

  return (
    <div>
      <h2 className="text-lg font-bold text-gray-900 mb-4">Configuration PayMe (MamoniPay)</h2>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">User name</label>
          <input value={form.user_name} onChange={(e) => setForm((f) => ({ ...f, user_name: e.target.value }))} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">Mot de passe</label>
          <input type="password" value={form.password} onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="••••••••" />
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">App ID</label>
          <input value={form.app_id} onChange={(e) => setForm((f) => ({ ...f, app_id: e.target.value }))} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">Pay type ID</label>
          <input value={form.pay_type_id} onChange={(e) => setForm((f) => ({ ...f, pay_type_id: e.target.value }))} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" />
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">Endpoint (base URL)</label>
          <input value={form.endpoint} onChange={(e) => setForm((f) => ({ ...f, endpoint: e.target.value }))} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="https://sandbox.mamonipay.me/api" />
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">Mode</label>
          <select value={form.mode} onChange={(e) => setForm((f) => ({ ...f, mode: e.target.value as 'sandbox' | 'live' }))} className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="sandbox">Sandbox</option>
            <option value="live">Live</option>
          </select>
        </div>
      </div>
      <button type="button" onClick={handleSave} disabled={updateGateway.isPending} className="mt-4 px-6 py-2.5 bg-primary text-white rounded-lg text-sm font-medium disabled:opacity-50">
        Enregistrer
      </button>
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
                <td className="py-2 pr-4 text-gray-600">{payment.created_at ? new Date(payment.created_at).toLocaleDateString('fr-FR') : '—'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}
