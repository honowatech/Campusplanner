import { useState } from 'react';
import {
  Building2,
  Package,
  ListChecks,
  CreditCard,
  Receipt,
  Plus,
  ShieldAlert,
  Mail,
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
  useMailSettings,
  useUpdateMailSettings,
  useTestMailSettings,
} from '@/src/hooks/useAdmin';
import { LoadingSpinner } from '@/src/components/LoadingSpinner';
import {
  Tenant,
  AdminPack,
  AdminFeature,
  PaymentGatewayConfig,
  MailSettings,
} from '@/src/lib/types';
import { getLocale } from '@/src/lib/locale';

type Tab = 'tenants' | 'packs' | 'features' | 'gateway' | 'payments' | 'mail';

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
    { id: 'mail', label: 'Emails', icon: Mail },
  ];

  return (
    <div className="max-w-6xl mx-auto">
      <div className="flex items-center mb-6">
        <div className="p-3 bg-gray-100 rounded-xl mr-4">
          <ShieldAlert className="text-gray-600" size={24} />
        </div>
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Administration plateforme</h1>
          <p className="text-gray-500 text-sm">
            Gestion des tenants, packs, configuration PayMe et envoi des emails.
          </p>
        </div>
      </div>

      <div className="flex flex-wrap gap-2 mb-6">
        {tabs.map(({ id, label, icon: Icon }) => (
          <button
            key={id}
            type="button"
            onClick={() => setTab(id)}
            className={`flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
              tab === id
                ? 'bg-primary text-white'
                : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'
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
        {tab === 'mail' && <MailTab />}
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
  const [form, setForm] = useState({
    name: '',
    slug: '',
    admin_name: '',
    admin_email: '',
    admin_password: '',
  });

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
                {tenant.name}{' '}
                {tenant.is_demo && <span className="text-xs text-amber-600">(démo)</span>}
              </td>
              <td className="py-2 pr-4 text-gray-600">{tenant.slug}</td>
              <td className="py-2 pr-4">
                <span
                  className={`inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${
                    tenant.status === 'active'
                      ? 'bg-green-100 text-green-700'
                      : 'bg-red-100 text-red-700'
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
          <input
            placeholder="Nom"
            value={form.name}
            onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
            className="px-3 py-2 border border-gray-300 rounded-lg text-sm"
          />
          <input
            placeholder="Slug"
            value={form.slug}
            onChange={(e) => setForm((f) => ({ ...f, slug: e.target.value }))}
            className="px-3 py-2 border border-gray-300 rounded-lg text-sm"
          />
          <input
            placeholder="Tier"
            value={form.tier}
            onChange={(e) => setForm((f) => ({ ...f, tier: e.target.value }))}
            className="px-3 py-2 border border-gray-300 rounded-lg text-sm"
          />
          <input
            type="number"
            placeholder="Prix"
            value={form.price}
            onChange={(e) => setForm((f) => ({ ...f, price: Number(e.target.value) }))}
            className="px-3 py-2 border border-gray-300 rounded-lg text-sm"
          />
          <select
            value={form.billing_period}
            onChange={(e) =>
              setForm((f) => ({ ...f, billing_period: e.target.value as 'monthly' | 'annual' }))
            }
            className="px-3 py-2 border border-gray-300 rounded-lg text-sm"
          >
            <option value="monthly">Mensuel</option>
            <option value="annual">Annuel</option>
          </select>
          <button
            type="button"
            onClick={handleSave}
            className="col-span-2 md:col-span-5 px-4 py-2 bg-primary text-white rounded-lg text-sm font-medium"
          >
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
          <div
            key={feature.id}
            className="flex items-center justify-between p-3 rounded-lg border border-gray-100 hover:border-gray-200"
          >
            <div>
              <p className="text-sm font-medium text-gray-900">{feature.label}</p>
              <p className="text-xs text-gray-500">{feature.key}</p>
            </div>
            <button
              type="button"
              onClick={() =>
                updateFeature.mutate({ id: feature.id, data: { is_active: !feature.is_active } })
              }
              className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${feature.is_active ? 'bg-secondary' : 'bg-gray-200'}`}
            >
              <span
                className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${feature.is_active ? 'translate-x-6' : 'translate-x-1'}`}
              />
            </button>
          </div>
        ))}
      </div>
    </div>
  );
}

function GatewayTab() {
  const { data: gateway, isLoading } = usePaymentGateway();

  if (isLoading) return <LoadingSpinner fullScreen={false} />;

  // Monté une fois la configuration chargée : le formulaire part des bonnes valeurs.
  return <GatewayForm gateway={gateway ?? null} />;
}

function GatewayForm({ gateway }: { gateway: PaymentGatewayConfig | null }) {
  const updateGateway = useUpdatePaymentGateway();
  const testGateway = useTestPaymentGateway();
  const defaultEndpoint = gateway?.default_endpoint ?? 'https://console-v2.mamonipay.me/api';

  const [form, setForm] = useState({
    user_name: gateway?.user_name ?? '',
    password: '',
    endpoint: gateway?.endpoint ?? '',
    client_fees_rate: gateway?.client_fees_rate != null ? String(gateway.client_fees_rate) : '',
    timeout_minutes: String(gateway?.timeout_minutes ?? 15),
    is_active: gateway?.is_active ?? true,
  });

  const handleSave = () => {
    updateGateway.mutate({
      user_name: form.user_name,
      password: form.password || undefined,
      endpoint: form.endpoint.trim() || null,
      client_fees_rate: form.client_fees_rate === '' ? null : Number(form.client_fees_rate),
      timeout_minutes: Number(form.timeout_minutes) || 15,
      is_active: form.is_active,
    });
  };

  const result = testGateway.data;

  return (
    <div>
      <h2 className="text-lg font-bold text-gray-900 mb-1">Configuration PayMe (MamoniPay)</h2>
      <p className="text-sm text-gray-500 mb-4 max-w-2xl">
        Réglage unique de la plateforme : une fois enregistré et testé, les écoles s’inscrivent,
        paient et sont activées automatiquement, sans intervention.
      </p>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">
            Identifiant (237… ou email)
          </label>
          <input
            value={form.user_name}
            onChange={(e) => setForm((f) => ({ ...f, user_name: e.target.value }))}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">Mot de passe</label>
          <input
            type="password"
            value={form.password}
            onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            placeholder={gateway?.has_password ? 'Inchangé si vide' : '••••••••'}
          />
        </div>
        <div className="md:col-span-2">
          <label className="block text-sm font-semibold text-gray-700 mb-1">Adresse de l’API</label>
          <input
            value={form.endpoint}
            onChange={(e) => setForm((f) => ({ ...f, endpoint: e.target.value }))}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            placeholder={defaultEndpoint}
          />
          <p className="mt-1 text-xs text-gray-500">
            Vide = {defaultEndpoint}. Jamais l’adresse de la documentation (/docs) ni un suffixe
            /v1.
          </p>
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">
            Frais à la charge du client (%)
          </label>
          <input
            type="number"
            min={0}
            max={100}
            value={form.client_fees_rate}
            onChange={(e) => setForm((f) => ({ ...f, client_fees_rate: e.target.value }))}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
            placeholder="Réglage du compte marchand"
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">
            Délai de validation (minutes)
          </label>
          <input
            type="number"
            min={2}
            max={60}
            value={form.timeout_minutes}
            onChange={(e) => setForm((f) => ({ ...f, timeout_minutes: e.target.value }))}
            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
          />
        </div>
        <label className="flex items-center gap-2 text-sm text-gray-700 md:col-span-2">
          <input
            type="checkbox"
            checked={form.is_active}
            onChange={(e) => setForm((f) => ({ ...f, is_active: e.target.checked }))}
          />
          Paiement en ligne actif
        </label>
      </div>
      <div className="mt-4 flex flex-wrap gap-3">
        <button
          type="button"
          onClick={handleSave}
          disabled={updateGateway.isPending}
          className="px-6 py-2.5 bg-primary text-white rounded-lg text-sm font-medium disabled:opacity-50"
        >
          Enregistrer
        </button>
        <button
          type="button"
          onClick={() => testGateway.mutate()}
          disabled={testGateway.isPending || !gateway}
          className="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
        >
          {testGateway.isPending ? 'Test en cours…' : 'Tester la connexion'}
        </button>
      </div>
      {result && (
        <div
          className={`mt-4 max-w-2xl p-4 rounded-lg border text-sm ${result.ok ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'}`}
        >
          <p className="font-semibold">{result.ok ? 'Connexion réussie' : 'Connexion refusée'}</p>
          <p className="mt-1 break-words">{result.message}</p>
          {result.url && <p className="mt-1 font-mono text-xs break-all">{result.url}</p>}
        </div>
      )}
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
                <td className="py-2 pr-4 text-gray-900">
                  {formatAmount(payment.amount, payment.currency)}
                </td>
                <td className="py-2 pr-4 text-gray-600">{payment.status}</td>
                <td className="py-2 pr-4 text-gray-600">
                  {payment.created_at
                    ? new Date(payment.created_at).toLocaleDateString(getLocale())
                    : '—'}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
}

function MailTab() {
  const { data: settings, isLoading } = useMailSettings();

  if (isLoading) return <LoadingSpinner fullScreen={false} />;

  // Monté une fois la configuration chargée : le formulaire part des bonnes valeurs.
  return <MailForm settings={settings ?? null} />;
}

function MailForm({ settings }: { settings: MailSettings | null }) {
  const updateSettings = useUpdateMailSettings();
  const testSettings = useTestMailSettings();

  const [form, setForm] = useState({
    mailer: settings?.mailer ?? 'smtp',
    host: settings?.host ?? '',
    port: settings?.port ? String(settings.port) : '587',
    encryption: settings?.encryption ?? 'tls',
    username: settings?.username ?? '',
    password: '',
    from_address: settings?.from_address ?? 'admin@focusrent.cm',
    from_name: settings?.from_name ?? '',
    is_active: settings?.is_active ?? true,
  });
  const [testTo, setTestTo] = useState('');

  const set = (field: keyof typeof form) => (value: string | boolean) =>
    setForm((f) => ({ ...f, [field]: value }));

  const handleSave = () => {
    updateSettings.mutate({
      mailer: form.mailer as 'smtp' | 'log',
      host: form.host.trim() || null,
      port: form.port ? Number(form.port) : null,
      encryption: (form.encryption || null) as 'tls' | 'ssl' | null,
      username: form.username.trim() || null,
      password: form.password || undefined,
      from_address: form.from_address.trim(),
      from_name: form.from_name.trim() || null,
      is_active: form.is_active,
    });
  };

  const smtp = form.mailer === 'smtp';
  const result = testSettings.data;
  const input = 'w-full px-3 py-2 border border-gray-300 rounded-lg text-sm';

  return (
    <div>
      <h2 className="text-lg font-bold text-gray-900 mb-1">Envoi des emails</h2>
      <p className="text-sm text-gray-500 mb-4 max-w-2xl">
        Compte qui envoie les liens de réinitialisation du mot de passe (super-admin et utilisateurs
        des écoles). Enregistrez, puis testez la connexion : un email de test est envoyé.
      </p>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 max-w-2xl">
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">Adresse d’envoi</label>
          <input
            type="email"
            value={form.from_address}
            onChange={(e) => set('from_address')(e.target.value)}
            className={input}
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">Nom affiché</label>
          <input
            value={form.from_name}
            onChange={(e) => set('from_name')(e.target.value)}
            className={input}
            placeholder="Campus Planner"
          />
        </div>
        <div>
          <label className="block text-sm font-semibold text-gray-700 mb-1">Mode d’envoi</label>
          <select
            value={form.mailer}
            onChange={(e) => set('mailer')(e.target.value)}
            className={input}
          >
            <option value="smtp">Serveur SMTP</option>
            <option value="log">Journal (tests, aucun envoi réel)</option>
          </select>
        </div>
        {smtp && (
          <>
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1">Serveur SMTP</label>
              <input
                value={form.host}
                onChange={(e) => set('host')(e.target.value)}
                className={input}
                placeholder="mail.focusrent.cm"
              />
            </div>
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1">Port</label>
              <input
                type="number"
                min={1}
                max={65535}
                value={form.port}
                onChange={(e) => set('port')(e.target.value)}
                className={input}
              />
            </div>
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1">Chiffrement</label>
              <select
                value={form.encryption}
                onChange={(e) => {
                  set('encryption')(e.target.value);
                  set('port')(e.target.value === 'ssl' ? '465' : '587');
                }}
                className={input}
              >
                <option value="tls">STARTTLS (port 587)</option>
                <option value="ssl">SSL/TLS (port 465)</option>
                <option value="">Aucun</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1">Identifiant</label>
              <input
                value={form.username}
                onChange={(e) => set('username')(e.target.value)}
                className={input}
                placeholder="admin@focusrent.cm"
                autoComplete="off"
              />
            </div>
            <div>
              <label className="block text-sm font-semibold text-gray-700 mb-1">Mot de passe</label>
              <input
                type="password"
                value={form.password}
                onChange={(e) => set('password')(e.target.value)}
                className={input}
                placeholder={settings?.has_password ? 'Inchangé si vide' : '••••••••'}
                autoComplete="new-password"
              />
            </div>
          </>
        )}
        <label className="flex items-center gap-2 text-sm text-gray-700 md:col-span-2">
          <input
            type="checkbox"
            checked={form.is_active}
            onChange={(e) => set('is_active')(e.target.checked)}
          />
          Utiliser cette configuration (sinon, celle du fichier .env du serveur)
        </label>
      </div>

      <button
        type="button"
        onClick={handleSave}
        disabled={updateSettings.isPending}
        className="mt-4 px-6 py-2.5 bg-primary text-white rounded-lg text-sm font-medium disabled:opacity-50"
      >
        Enregistrer
      </button>

      <div className="mt-8 pt-6 border-t border-gray-100 max-w-2xl">
        <h3 className="text-sm font-bold text-gray-900 mb-2">Tester la connexion</h3>
        <div className="flex flex-col sm:flex-row gap-3">
          <input
            type="email"
            value={testTo}
            onChange={(e) => setTestTo(e.target.value)}
            className={`${input} flex-1`}
            placeholder="Destinataire (par défaut : votre email)"
          />
          <button
            type="button"
            onClick={() => testSettings.mutate(testTo.trim() || undefined)}
            disabled={testSettings.isPending || !settings?.id}
            className="px-6 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
          >
            {testSettings.isPending ? 'Test en cours…' : 'Tester et envoyer un email'}
          </button>
        </div>
        {!settings?.id && (
          <p className="mt-2 text-xs text-gray-500">Enregistrez d’abord la configuration.</p>
        )}
        {result && (
          <div
            className={`mt-4 p-4 rounded-lg border text-sm ${result.ok ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'}`}
          >
            <p className="font-semibold">
              {result.ok ? 'Connexion réussie' : 'Échec de la connexion'}
            </p>
            <p className="mt-1 break-words">{result.message}</p>
          </div>
        )}
      </div>
    </div>
  );
}
