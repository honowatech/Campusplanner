import React, { useState, useEffect } from 'react';
import {
  BarChart,
  Bar,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
} from 'recharts';
import {
  Teacher,
  Course,
  User,
  DashboardOverview,
  DashboardAlerts,
  DashboardActivity,
  Period,
} from '@/src/lib/types';
import {
  generateWorkloadAnalysis,
  suggestScheduleOptimization,
} from '@/src/services/geminiService';
import { dashboardService } from '@/src/services/dashboardService';
import { AlertsPanel } from './AlertsPanel';
import { ActivityFeed } from './ActivityFeed';
import {
  Sparkles,
  BrainCircuit,
  Users,
  AlertTriangle,
  Activity,
  RefreshCw,
  Clock,
  Calendar,
  GraduationCap,
  DoorOpen,
} from 'lucide-react';
import { useTranslation } from '@/src/utils/i18n';
import { useAuth } from '@/src/auth';

interface DashboardProps {
  initialData?: DashboardOverview;
  teachers: Teacher[];
  courses: Course[];
  user: User | null;
}

const COLORS = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#8884d8'];

export const Dashboard: React.FC<DashboardProps> = ({ initialData, teachers, courses, user }) => {
  const { t } = useTranslation();
  const { isAuthenticated } = useAuth();
  const [aiAnalysis, setAiAnalysis] = useState<string>('');
  const [aiSuggestions, setAiSuggestions] = useState<string[]>([]);
  const [loadingAi, setLoadingAi] = useState(false);

  const [apiOverview, setApiOverview] = useState<DashboardOverview | null>(initialData ?? null);
  const [apiAlerts, setApiAlerts] = useState<DashboardAlerts>({
    critical: [],
    warnings: [],
    info: [],
  });
  const [apiActivity, setApiActivity] = useState<DashboardActivity[]>([]);
  const [loadingApi, setLoadingApi] = useState(false);
  const [apiError, setApiError] = useState<string | null>(null);
  const [period, setPeriod] = useState<Period>('7d');

  const fetchDashboardData = async () => {
    if (!isAuthenticated) return;

    setLoadingApi(true);
    setApiError(null);

    try {
      const [overview, alertsData, activityData] = await Promise.all([
        dashboardService.getOverview({ period }),
        dashboardService.getAlerts({ period }),
        dashboardService.getActivity({ period }),
      ]);

      setApiOverview(overview);
      setApiAlerts(alertsData.alerts);
      setApiActivity(activityData.activity);
    } catch (error) {
      console.error('Failed to fetch dashboard data:', error);
      setApiError(t('failedToLoadDashboard'));
    } finally {
      setLoadingApi(false);
    }
  };

  const displayedTeachers = React.useMemo(() => {
    if (user?.role === 'hod' && user.department_id) {
      return teachers.filter((teacher) => teacher.department_id === user.department_id);
    }
    return teachers;
  }, [teachers, user]);

  const stats = React.useMemo(() => {
    const deptName = (id?: number, name?: string): string =>
      name ?? (id != null ? `Dépt ${id}` : 'N/A');

    // Carte ID -> nom, déduite des enseignants (qui embarquent leur département).
    const deptNameById = new Map<number, string>();
    displayedTeachers?.forEach((teacher) => {
      if (teacher.department_id != null && teacher.department?.name) {
        deptNameById.set(teacher.department_id, teacher.department.name);
      }
    });

    const teacherCounts: Record<string, number> = {};
    displayedTeachers?.forEach((teacher) => {
      const name = deptName(teacher.department_id, teacher.department?.name);
      teacherCounts[name] = (teacherCounts[name] || 0) + 1;
    });

    const courseCounts: Record<string, number> = {};
    courses?.forEach((course) => {
      const name = course.department_id != null
        ? (deptNameById.get(course.department_id) ?? `Dépt ${course.department_id}`)
        : 'N/A';
      courseCounts[name] = (courseCounts[name] || 0) + 1;
    });

    return {
      totalTeachers: displayedTeachers?.length,
      totalCourses: courses?.length,
      departmentDistribution: Object.entries(teacherCounts).map(([name, value]) => ({ name, value })),
      coursesPerDepartment: Object.entries(courseCounts).map(([name, value]) => ({ name, value })),
    };
  }, [displayedTeachers, courses]);

  useEffect(() => {
    if (isAuthenticated && !initialData) {
      fetchDashboardData();
    }
  }, [isAuthenticated, period]);

  const handleGenerateInsights = async () => {
    setLoadingAi(true);
    try {
      const [analysis, optimization] = await Promise.all([
        generateWorkloadAnalysis(displayedTeachers, courses),
        suggestScheduleOptimization(displayedTeachers, courses),
      ]);
      setAiAnalysis(analysis);
      setAiSuggestions(optimization.suggestions);
    } catch (error) {
      console.error('Failed to generate insights:', error);
    } finally {
      setLoadingAi(false);
    }
  };

  const realtimeStats = apiOverview?.realtime;
  const heavyStats = apiOverview?.heavy;

  return (
    <div className="space-y-6 animate-in fade-in duration-500">
      {user?.role === 'hod' && (
        <div className="bg-purple-50 border border-purple-100 p-4 rounded-xl mb-4">
          <p className="text-purple-800 font-medium">
            {t('welcomeBack')}, {user.name}. {t('hodRole')} View.
          </p>
        </div>
      )}

      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-2">
          <select
            value={period}
            onChange={(e) => setPeriod(e.target.value as '24h' | '7d' | '30d')}
            className="px-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"
          >
            <option value="24h">{t('last24h')}</option>
            <option value="7d">{t('last7d')}</option>
            <option value="30d">{t('last30d')}</option>
          </select>
          <button
            onClick={fetchDashboardData}
            disabled={loadingApi}
            className="p-1.5 text-gray-400 hover:text-secondary hover:bg-indigo-50 rounded-lg disabled:opacity-50"
            title={t('refresh')}
          >
            <RefreshCw size={16} className={loadingApi ? 'animate-spin' : ''} />
          </button>
        </div>
        {apiOverview?.generated_at && (
          <span className="text-xs text-gray-400">
            {t('lastUpdate')}: {new Date(apiOverview.generated_at).toLocaleString()}
          </span>
        )}
      </div>

      {apiError && (
        <div className="bg-red-50 border border-red-200 p-4 rounded-xl text-red-700 text-sm">
          {apiError}
        </div>
      )}

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-blue-50 p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
          <div className="p-3 text-blue-600 rounded-lg">
            <Users size={24} />
          </div>
          <div>
            <p className="text-sm text-gray-500 font-medium">{t('totalTeachers')}</p>
            <h3 className="text-2xl font-bold text-gray-900">
              {heavyStats?.teachers.total ?? stats.totalTeachers}
            </h3>
            {heavyStats && (
              <p className="text-xs text-gray-400">
                {t('active')}: {heavyStats.teachers.active}
              </p>
            )}
          </div>
        </div>

        <div className="bg-green-50 p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
          <div className="p-3 text-green-600 rounded-lg">
            <GraduationCap size={24} />
          </div>
          <div>
            <p className="text-sm text-gray-500 font-medium">{t('students')}</p>
            <h3 className="text-2xl font-bold text-gray-900">{heavyStats?.students.total ?? 0}</h3>
            {heavyStats && (
              <p className="text-xs text-gray-400">
                +{heavyStats.students.new_this_period} {t('thisPeriod')}
              </p>
            )}
          </div>
        </div>

        <div className="bg-amber-50 p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
          <div className="p-3 text-amber-600 rounded-lg">
            <DoorOpen size={24} />
          </div>
          <div>
            <p className="text-sm text-gray-500 font-medium">{t('rooms')}</p>
            <h3 className="text-2xl font-bold text-gray-900">{heavyStats?.resources.rooms ?? 0}</h3>
            {realtimeStats && (
              <p className="text-xs text-gray-400">
                {realtimeStats.occupied_rooms} {t('occupied')}
              </p>
            )}
          </div>
        </div>

        <div className="bg-red-50 p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
          <div className="p-3 text-red-600 rounded-lg">
            <AlertTriangle size={24} />
          </div>
          <div>
            <p className="text-sm text-gray-500 font-medium">{t('alerts')}</p>
            <h3 className="text-2xl font-bold text-gray-900">
              {apiAlerts.critical.length + apiAlerts.warnings.length + apiAlerts.info.length ||
                (realtimeStats?.schedule_conflicts ?? realtimeStats?.pending_blockings ?? 0)}
            </h3>
            {apiAlerts.critical.length > 0 && (
              <p className="text-xs text-red-500">
                {apiAlerts.critical.length} {t('critical')}
              </p>
            )}
          </div>
        </div>
      </div>

      {realtimeStats && (
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div className="bg-linear-to-br from-blue-50 to-white p-4 rounded-xl border border-blue-100">
            <div className="flex items-center space-x-2 text-blue-600 mb-1">
              <Activity size={16} />
              <span className="text-sm font-medium">{t('activeNow')}</span>
            </div>
            <div className="flex items-baseline space-x-2">
              <span className="text-2xl font-bold text-gray-900">
                {realtimeStats.active_classes}
              </span>
              <span className="text-xs text-gray-500">{t('classes')}</span>
            </div>
          </div>
          <div className="bg-linear-to-br from-green-50 to-white p-4 rounded-xl border border-green-100">
            <div className="flex items-center space-x-2 text-green-600 mb-1">
              <Calendar size={16} />
              <span className="text-sm font-medium">{t('occupiedRooms')}</span>
            </div>
            <div className="flex items-baseline space-x-2">
              <span className="text-2xl font-bold text-gray-900">
                {realtimeStats.occupied_rooms}
              </span>
              <span className="text-xs text-gray-500">{t('rooms')}</span>
            </div>
          </div>
          <div className="bg-linear-to-br from-amber-50 to-white p-4 rounded-xl border border-amber-100">
            <div className="flex items-center space-x-2 text-amber-600 mb-1">
              <Clock size={16} />
              <span className="text-sm font-medium">{t('pendingBlockings')}</span>
            </div>
            <div className="flex items-baseline space-x-2">
              <span className="text-2xl font-bold text-gray-900">
                {realtimeStats.pending_blockings}
              </span>
              <span className="text-xs text-gray-500">{t('requests')}</span>
            </div>
          </div>
          <div className="bg-linear-to-br from-purple-50 to-white p-4 rounded-xl border border-purple-100">
            <div className="flex items-center space-x-2 text-purple-600 mb-1">
              <Users size={16} />
              <span className="text-sm font-medium">{t('newUsers')}</span>
            </div>
            <div className="flex items-baseline space-x-2">
              <span className="text-2xl font-bold text-gray-900">
                {heavyStats?.users.new_this_period ?? 0}
              </span>
              <span className="text-xs text-gray-500">{t('thisPeriod')}</span>
            </div>
          </div>
        </div>
      )}

      {heavyStats?.planning && (
        <div className="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
          <h3 className="font-semibold text-gray-800 mb-3">{t('planningStats')}</h3>
          <div className="grid grid-cols-2 md:grid-cols-6 gap-4 text-center">
            <div>
              <p className="text-2xl font-bold text-gray-900">
                {heavyStats.planning.total_sessions}
              </p>
              <p className="text-xs text-gray-500">{t('totalSessions')}</p>
            </div>
            <div>
              <p className="text-2xl font-bold text-green-600">{heavyStats.planning.completed}</p>
              <p className="text-xs text-gray-500">{t('completed')}</p>
            </div>
            <div>
              <p className="text-2xl font-bold text-blue-600">{heavyStats.planning.ongoing}</p>
              <p className="text-xs text-gray-500">{t('ongoing')}</p>
            </div>
            <div>
              <p className="text-2xl font-bold text-amber-600">{heavyStats.planning.pending}</p>
              <p className="text-xs text-gray-500">{t('pending')}</p>
            </div>
            <div>
              <p className="text-2xl font-bold text-red-600">{heavyStats.planning.canceled}</p>
              <p className="text-xs text-gray-500">{t('canceled')}</p>
            </div>
            <div>
              <p className="text-2xl font-bold text-secondary">
                {heavyStats.planning.utilization_rate.toFixed(1)}%
              </p>
              <p className="text-xs text-gray-500">{t('utilization')}</p>
            </div>
          </div>
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-[400px]">
          <h3 className="text-lg font-semibold text-gray-800 mb-4">{t('deptDistribution')}</h3>
          <ResponsiveContainer width="100%" height="100%">
            <PieChart>
              <Pie
                data={stats.departmentDistribution}
                cx="50%"
                cy="50%"
                outerRadius={100}
                fill="#8884d8"
                dataKey="value"
                label={({ name, percent }) => `${name} ${((percent ?? 0) * 100).toFixed(0)}%`}
              >
                {stats.departmentDistribution.map((_entry, index) => (
                  <Cell key={`cell-${index}`} fill={COLORS[index % COLORS.length]} />
                ))}
              </Pie>
              <Tooltip />
            </PieChart>
          </ResponsiveContainer>
        </div>

        <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 h-[400px]">
          <h3 className="text-lg font-semibold text-gray-800 mb-4">{t('coursesPerDept')}</h3>
          <ResponsiveContainer width="100%" height="100%">
            <BarChart
              data={stats.coursesPerDepartment}
              margin={{ top: 5, right: 30, left: 20, bottom: 5 }}
            >
              <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
              <XAxis dataKey="name" tick={{ fontSize: 12 }} />
              <YAxis />
              <Tooltip cursor={{ fill: '#f9fafb' }} />
              <Bar dataKey="value" fill="#4f46e5" radius={[4, 4, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <AlertsPanel alerts={apiAlerts} />
        <ActivityFeed activities={apiActivity} />
      </div>

      <div
        className="bg-linear-to-br from-indigo-50 to-white p-6 rounded-xl border border-indigo-100 shadow-sm cursor-pointer hover:shadow-md transition-shadow"
        role="button"
        tabIndex={0}
        onClick={handleGenerateInsights}
        onKeyDown={(e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            handleGenerateInsights();
          }
        }}
      >
        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-3">
            <div className="p-3 bg-indigo-100 rounded-lg">
              <Sparkles size={24} className={`text-secondary ${loadingAi ? 'animate-spin' : ''}`} />
            </div>
            <div>
              <h3 className="text-lg font-bold text-gray-900">{t('aiInsights')}</h3>
              <p className="text-sm text-gray-500">
                {loadingAi ? t('analyzing') : t('generateReport')}
              </p>
            </div>
          </div>
          <BrainCircuit size={32} className="text-indigo-300" />
        </div>

        {(aiAnalysis || aiSuggestions.length > 0) && (
          <div className="mt-6 pt-6 border-t border-indigo-100">
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
              <div className="lg:col-span-2">
                <h4 className="text-sm font-semibold text-secondary uppercase tracking-wide mb-2">
                  {t('execSummary')}
                </h4>
                <p className="text-gray-700 whitespace-pre-line leading-relaxed text-sm">
                  {aiAnalysis}
                </p>
              </div>
              <div className="bg-white/70 p-4 rounded-lg border border-indigo-100">
                <h4 className="text-sm font-semibold text-secondary uppercase tracking-wide mb-3">
                  {t('suggestedOptimizations')}
                </h4>
                <ul className="space-y-2">
                  {aiSuggestions.map((sug, idx) => (
                    <li key={idx} className="flex items-start space-x-2 text-sm text-gray-600">
                      <span className="block w-1.5 h-1.5 mt-1.5 rounded-full bg-indigo-400 shrink-0" />
                      <span>{sug}</span>
                    </li>
                  ))}
                </ul>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};
