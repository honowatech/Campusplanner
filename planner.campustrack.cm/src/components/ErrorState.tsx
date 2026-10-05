import React from 'react';
import { AlertTriangle, RefreshCw } from 'lucide-react';
import { useTranslation } from '@/src/utils/i18n';

type ErrorStateProps = {
  /** Message d'erreur optionnel (sinon le message générique est affiché). */
  message?: string;
  /** Callback de relance (bouton « Réessayer » masqué si absent). */
  onRetry?: () => void;
};

/**
 * État d'erreur réutilisable pour les listes : icône, message et bouton de relance.
 */
export const ErrorState: React.FC<ErrorStateProps> = ({ message, onRetry }) => {
  const { t } = useTranslation();

  return (
    <div className="flex flex-col items-center justify-center py-16 text-center">
      <div className="p-4 bg-red-50 text-red-500 rounded-full mb-4">
        <AlertTriangle size={40} />
      </div>
      <p className="text-gray-600 mb-4">{message ?? t('loadError')}</p>
      {onRetry && (
        <button
          type="button"
          onClick={onRetry}
          className="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors font-medium"
        >
          <RefreshCw size={16} />
          {t('retry')}
        </button>
      )}
    </div>
  );
};
