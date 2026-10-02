import React from 'react';
import { DemoModeStatus } from '@/src/lib/types';
import { useTranslation } from '@/src/utils/i18n';
import { FlaskConical, Loader2 } from 'lucide-react';

interface DemoModeManagerProps {
  status?: DemoModeStatus;
  isLoading: boolean;
  onToggle: (enabled: boolean) => void;
}

export const DemoModeManager: React.FC<DemoModeManagerProps> = ({
  status,
  isLoading,
  onToggle,
}) => {
  const { t } = useTranslation();
  const enabled = status?.enabled ?? false;
  const accounts = status?.accounts ?? [];

  return (
    <div className="max-w-5xl mx-auto">
      <div className="flex items-center mb-8">
        <div className="p-3 bg-indigo-100 rounded-xl mr-4">
          <FlaskConical className="text-primary" size={24} />
        </div>
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('demoMode')}</h1>
          <p className="text-gray-500 text-sm">{t('demoModeDescription')}</p>
        </div>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-8 space-y-8">
        <div className="flex items-center justify-between p-4 rounded-lg border border-gray-100 bg-gray-50">
          <div>
            <p className="text-sm font-semibold text-gray-700">{t('enableDemoMode')}</p>
            <p className="text-xs text-gray-500 mt-1">{t('demoModeHint')}</p>
          </div>
          <button
            type="button"
            disabled={isLoading}
            onClick={() => onToggle(!enabled)}
            className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 ${enabled ? 'bg-secondary' : 'bg-gray-200'}`}
          >
            <span
              className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${enabled ? 'translate-x-6' : 'translate-x-1'}`}
            />
          </button>
        </div>

        <div className="pt-6 border-t border-gray-100">
          <h3 className="text-lg font-bold text-gray-900 mb-1">{t('demoAccounts')}</h3>
          <p className="text-sm text-gray-500 mb-6">{t('demoAccountsDescription')}</p>

          {isLoading ? (
            <div className="flex items-center justify-center py-8 text-gray-400">
              <Loader2 size={24} className="animate-spin" />
            </div>
          ) : accounts.length === 0 ? (
            <p className="text-sm text-gray-500">{t('demoAccountsEmpty')}</p>
          ) : (
            <div className="space-y-2">
              {accounts.map((account) => (
                <div
                  key={account.role}
                  className="flex items-center justify-between px-4 py-3 rounded-lg border border-gray-100 bg-gray-50"
                >
                  <div>
                    <p className="text-sm font-semibold text-gray-800">{account.name}</p>
                    <p className="text-xs text-gray-500">{account.role}</p>
                  </div>
                  <span className="text-xs text-gray-500">{account.email}</span>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
