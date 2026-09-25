import React from 'react';
import { DashboardAlert } from '@/src/lib/types';
import { AlertTriangle, AlertCircle, Info, ChevronRight } from 'lucide-react';
import { useTranslation } from '@/src/utils/i18n';

interface AlertsPanelProps {
  alerts: {
    critical: DashboardAlert[];
    warnings: DashboardAlert[];
    info: DashboardAlert[];
  };
  onAlertClick?: (alert: DashboardAlert) => void;
}

const severityConfig = {
  critical: {
    icon: AlertTriangle,
    bgColor: 'bg-red-50',
    borderColor: 'border-red-200',
    iconColor: 'text-red-500',
    textColor: 'text-red-800',
    badge: 'bg-red-100 text-red-700',
  },
  warning: {
    icon: AlertCircle,
    bgColor: 'bg-amber-50',
    borderColor: 'border-amber-200',
    iconColor: 'text-amber-500',
    textColor: 'text-amber-800',
    badge: 'bg-amber-100 text-amber-700',
  },
  info: {
    icon: Info,
    bgColor: 'bg-blue-50',
    borderColor: 'border-blue-200',
    iconColor: 'text-blue-500',
    textColor: 'text-blue-800',
    badge: 'bg-blue-100 text-blue-700',
  },
};

export const AlertsPanel: React.FC<AlertsPanelProps> = ({ alerts, onAlertClick }) => {
  const { t } = useTranslation();

  const allAlerts = [
    ...alerts.critical.map((alert) => ({
      ...alert,
      severity: 'critical' as const,
    })),
    ...alerts.warnings.map((alert) => ({
      ...alert,
      severity: 'warning' as const,
    })),
    ...alerts.info.map((alert) => ({ ...alert, severity: 'info' as const })),
  ].slice(0, 5);

  const totalAlerts = alerts.critical.length + alerts.warnings.length + alerts.info.length;

  if (totalAlerts === 0) {
    return (
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div className="flex items-center justify-center text-gray-400 py-4">
          <Info size={20} className="mr-2" />
          <span>{t('noAlerts')}</span>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 className="text-lg font-semibold text-gray-900">{t('alerts')}</h3>
        <div className="flex items-center space-x-2">
          {alerts.critical.length > 0 && (
            <span className="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
              {alerts.critical.length} {t('critical')}
            </span>
          )}
          {alerts.warnings.length > 0 && (
            <span className="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
              {alerts.warnings.length} {t('warnings')}
            </span>
          )}
        </div>
      </div>
      <div className="divide-y divide-gray-50">
        {allAlerts.map((alert, index) => {
          const config = severityConfig[alert.severity];
          const Icon = config.icon;

          return (
            <div
              key={`${alert.type}-${index}`}
              onClick={() => onAlertClick?.(alert)}
              className={`p-4 ${config.bgColor} hover:bg-opacity-70 cursor-pointer transition-colors flex items-start space-x-3`}
            >
              <Icon size={18} className={`${config.iconColor} shrink-0 mt-0.5`} />
              <div className="flex-1 min-w-0">
                <p className={`text-sm ${config.textColor} font-medium`}>{alert.message}</p>
                <p className="text-xs text-gray-500 mt-1">
                  {alert.entity_type} #{alert.entity_id}
                </p>
              </div>
              <ChevronRight size={16} className="text-gray-400 shrink-0" />
            </div>
          );
        })}
      </div>
      {totalAlerts > 5 && (
        <div className="px-6 py-3 bg-gray-50 text-center">
          <button className="text-sm text-secondary hover:text-primary font-medium">
            {t('viewAllAlerts')} ({totalAlerts})
          </button>
        </div>
      )}
    </div>
  );
};
