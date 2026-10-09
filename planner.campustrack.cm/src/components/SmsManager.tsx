import { useState } from 'react';
import { MessageSquare, Send, Save } from 'lucide-react';
import { useSmsSettings, useUpdateSmsSettings, useTestSms, useSmsLogs } from '@/src/hooks/useSms';
import { LoadingSpinner } from '@/src/components/LoadingSpinner';

function statusLabel(status: string): string {
  const labels: Record<string, string> = {
    sent: 'Envoyé',
    pending: 'En attente',
    failed: 'Échoué',
  };
  return labels[status] ?? status;
}

export function SmsManager() {
  const { data: credential, isLoading } = useSmsSettings();
  const updateSettings = useUpdateSmsSettings();
  const testSms = useTestSms();
  const { data: logs, isLoading: loadingLogs } = useSmsLogs();

  const [user, setUser] = useState(credential?.user ?? '');
  const [password, setPassword] = useState('');
  const [senderId, setSenderId] = useState(credential?.sender_id ?? '');
  const [isActive, setIsActive] = useState(credential?.is_active ?? true);
  const [testTo, setTestTo] = useState('');
  const [testMessage, setTestMessage] = useState('');

  if (isLoading) {
    return <LoadingSpinner fullScreen={false} />;
  }

  const handleSave = () => {
    updateSettings.mutate({
      user: user || undefined,
      password: password || undefined,
      sender_id: senderId || undefined,
      is_active: isActive,
    });
  };

  const handleTest = () => {
    if (!testTo.trim()) return;
    testSms.mutate({ to: testTo.trim(), message: testMessage || undefined });
  };

  return (
    <div className="max-w-5xl mx-auto space-y-8">
      <div className="flex items-center mb-2">
        <div className="p-3 bg-orange-100 rounded-xl mr-4">
          <MessageSquare className="text-orange-600" size={24} />
        </div>
        <div>
          <h1 className="text-2xl font-bold text-gray-900">SMS (Nexah)</h1>
          <p className="text-gray-500 text-sm">Configurez vos crédences SMS et envoyez des notifications.</p>
        </div>
      </div>

      {/* Configuration */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 className="text-lg font-bold text-gray-900 mb-4">Configuration</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-1">Utilisateur Nexah</label>
            <input
              type="text"
              value={user}
              onChange={(e) => setUser(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-gray-900"
              placeholder="user"
            />
          </div>
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-1">Mot de passe</label>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-gray-900"
              placeholder="••••••••"
            />
          </div>
          <div>
            <label className="block text-sm font-semibold text-gray-700 mb-1">Sender ID (max 11)</label>
            <input
              type="text"
              value={senderId}
              maxLength={11}
              onChange={(e) => setSenderId(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-gray-900"
              placeholder="CAMPUS"
            />
          </div>
          <div className="flex items-end">
            <label className="flex items-center space-x-2 cursor-pointer">
              <input
                type="checkbox"
                checked={isActive}
                onChange={(e) => setIsActive(e.target.checked)}
                className="size-4 accent-primary"
              />
              <span className="text-sm font-medium text-gray-700">Compte actif</span>
            </label>
          </div>
        </div>
        <div className="mt-4 flex justify-end">
          <button
            type="button"
            onClick={handleSave}
            disabled={updateSettings.isPending}
            className="flex items-center gap-2 px-6 py-2.5 bg-primary text-white rounded-lg hover:bg-secondary transition-colors font-medium disabled:opacity-50"
          >
            <Save size={18} />
            {updateSettings.isPending ? 'Enregistrement…' : 'Enregistrer'}
          </button>
        </div>
      </div>

      {/* Test */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 className="text-lg font-bold text-gray-900 mb-4">Envoyer un SMS de test</h2>
        <div className="flex flex-col sm:flex-row gap-3">
          <input
            type="tel"
            placeholder="Numéro (ex. 2376xxxxxxx)"
            value={testTo}
            onChange={(e) => setTestTo(e.target.value)}
            className="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-gray-900"
          />
          <input
            type="text"
            placeholder="Message (optionnel)"
            value={testMessage}
            onChange={(e) => setTestMessage(e.target.value)}
            className="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none text-gray-900"
          />
          <button
            type="button"
            onClick={handleTest}
            disabled={!testTo.trim() || testSms.isPending}
            className="px-6 py-2.5 bg-primary text-white rounded-lg hover:bg-secondary transition-colors font-medium disabled:opacity-50 flex items-center gap-2"
          >
            <Send size={18} />
            {testSms.isPending ? 'Envoi…' : 'Envoyer'}
          </button>
        </div>
      </div>

      {/* Logs */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 className="text-lg font-bold text-gray-900 mb-4">Journal des envois</h2>
        {loadingLogs ? (
          <LoadingSpinner fullScreen={false} />
        ) : !logs?.data.length ? (
          <p className="text-gray-500 text-sm">Aucun SMS envoyé.</p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="text-left text-gray-500 border-b border-gray-100">
                  <th className="py-2 pr-4 font-medium">Destinataire</th>
                  <th className="py-2 pr-4 font-medium">Message</th>
                  <th className="py-2 pr-4 font-medium">Statut</th>
                  <th className="py-2 pr-4 font-medium">Erreur</th>
                </tr>
              </thead>
              <tbody>
                {logs.data.map((log) => (
                  <tr key={log.id} className="border-b border-gray-50">
                    <td className="py-2 pr-4 text-gray-900 font-mono text-xs">{log.to}</td>
                    <td className="py-2 pr-4 text-gray-600 max-w-xs truncate">{log.message}</td>
                    <td className="py-2 pr-4">
                      <span
                        className={`inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${
                          log.status === 'sent'
                            ? 'bg-green-100 text-green-700'
                            : log.status === 'failed'
                              ? 'bg-red-100 text-red-700'
                              : 'bg-gray-100 text-gray-600'
                        }`}
                      >
                        {statusLabel(log.status)}
                      </span>
                    </td>
                    <td className="py-2 pr-4 text-red-500 text-xs">{log.error ?? '—'}</td>
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
