import React from 'react';
import { DashboardActivity } from '@/src/lib/types';
import {
  UserPlus,
  CalendarPlus,
  AlertTriangle,
  Clock,
  CheckCircle,
  XCircle,
  FileText,
  User,
} from 'lucide-react';
import { useTranslation } from '@/src/utils/i18n';
import { formatDateTime } from '@/src/lib/helpers';

interface ActivityFeedProps {
  activities: DashboardActivity[];
}

const activityIcons: Record<string, React.ComponentType<{ size?: number; className?: string }>> = {
  blocking_created: AlertTriangle,
  blocking_approved: CheckCircle,
  blocking_rejected: XCircle,
  student_admission: UserPlus,
  teacher_hired: User,
  session_created: CalendarPlus,
  session_completed: CheckCircle,
  session_canceled: XCircle,
  report_generated: FileText,
  default: Clock,
};

const activityColors: Record<string, string> = {
  blocking_created: 'text-amber-500 bg-amber-50',
  blocking_approved: 'text-green-500 bg-green-50',
  blocking_rejected: 'text-red-500 bg-red-50',
  student_admission: 'text-blue-500 bg-blue-50',
  teacher_hired: 'text-purple-500 bg-purple-50',
  session_created: 'text-indigo-500 bg-indigo-50',
  session_completed: 'text-green-500 bg-green-50',
  session_canceled: 'text-red-500 bg-red-50',
  report_generated: 'text-gray-500 bg-gray-50',
  default: 'text-gray-500 bg-gray-50',
};

const statusColors: Record<string, string> = {
  pending: 'bg-amber-100 text-amber-700',
  approved: 'bg-green-100 text-green-700',
  rejected: 'bg-red-100 text-red-700',
  completed: 'bg-blue-100 text-blue-700',
};

export const ActivityFeed: React.FC<ActivityFeedProps> = ({ activities }) => {
  const { t } = useTranslation();

  if (activities.length === 0) {
    return (
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div className="flex items-center justify-center text-gray-400 py-4">
          <Clock size={20} className="mr-2" />
          <span>{t('noRecentActivity')}</span>
        </div>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
      <div className="px-6 py-4 border-b border-gray-100">
        <h3 className="text-lg font-semibold text-gray-900">{t('recentActivity')}</h3>
      </div>
      <div className="divide-y divide-gray-50">
        {activities.slice(0, 10).map((activity, index) => {
          const Icon = activityIcons[activity.type] || activityIcons.default;
          const colorClass = activityColors[activity.type] || activityColors.default;

          return (
            <div
              key={`${activity.type}-${index}`}
              className="p-4 hover:bg-gray-50 transition-colors"
            >
              <div className="flex items-start space-x-3">
                <div className={`p-2 rounded-lg ${colorClass}`}>
                  <Icon size={16} />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm text-gray-700">{activity.description}</p>
                  <div className="flex items-center mt-1 space-x-2">
                    <span className="text-xs text-gray-400">
                      {formatDateTime(activity.created_at)}
                    </span>
                    {activity.status && (
                      <span
                        className={`px-1.5 py-0.5 rounded text-xs font-medium ${statusColors[activity.status] || ''}`}
                      >
                        {t(activity.status)}
                      </span>
                    )}
                  </div>
                </div>
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};
