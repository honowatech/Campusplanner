import { createFileRoute, Link } from '@tanstack/react-router';
import { useCreatePlanning, usePlannings } from '@/src/hooks/usePlannings';
import { LoadingSpinner } from '@/src/components/LoadingSpinner';
import { Calendar, ChevronRight, Clock, FileText, Plus } from 'lucide-react';
import { useTranslation } from '@/src/utils/i18n';
import { formatDateTime } from '@/src/lib/helpers';
import React, { useState } from 'react';
import { Modal } from '../components/Modal';
// import { ShiftPlanning } from "../lib/types";
// import { useCreateShiftPlanning, useDeleteShiftPlanning } from "../hooks";

export const Route = createFileRoute('/timetables')({
  component: TimetablesPage,
});

function TimetablesPage() {
  const { t } = useTranslation();
  const { data: plannings, isLoading } = usePlannings();
  const [isPeriodModalOpen, setIsPeriodModalOpen] = useState(false);

  const createPlanning = useCreatePlanning();

  const [periodFormData, setPeriodFormData] = useState<{
    name: string;
    startDate: string;
    endDate: string;
  }>({
    name: '',
    startDate: '',
    endDate: '',
  });

  if (isLoading) {
    return <LoadingSpinner fullScreen={false} />;
  }

  const handleAddPeriod = (period: { startDate: string; endDate: string; name: string }) => {
    createPlanning.mutate({
      type: 'weekly',
      starting_date: period.startDate,
      ending_date: period.endDate,
      description: period.name,
    });
  };

  const handlePeriodSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!periodFormData.name || !periodFormData.startDate || !periodFormData.endDate) return;
    handleAddPeriod({
      name: periodFormData.name,
      startDate: periodFormData.startDate,
      endDate: periodFormData.endDate,
    });
    setIsPeriodModalOpen(false);
  };

  const handleCreatePeriod = () => {
    setPeriodFormData({ name: '', startDate: '', endDate: '' });
    setIsPeriodModalOpen(true);
  };

  return (
    <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
      <div className="flex items-center mb-6">
        <Calendar size={20} className="text-secondary mr-2" />
        <h1 className="font-bold text-xl text-gray-900">{t('plannings')}</h1>
        <span className="ml-3 text-sm text-gray-500">({plannings?.length || 0})</span>
        <button
          type="button"
          onClick={handleCreatePeriod}
          className="flex items-center space-x-1 p-3 bg-indigo-50 text-primary rounded-md hover:bg-indigo-100 transition-colors text-sm font-medium ml-auto"
        >
          <Plus size={14} />
          <span>{t('newPeriod')}</span>
        </button>
      </div>

      {!plannings || plannings.length === 0 ? (
        <div className="text-center py-12 text-gray-400">
          <FileText size={48} className="mx-auto mb-4 opacity-20" />
          <p className="text-lg font-medium">{t('noPlannings')}</p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {plannings.map((planning) => (
            <Link
              key={planning.id}
              to="/plannings/$planningId"
              params={{ planningId: String(planning.id) }}
              className="block p-4 rounded-lg border border-gray-200 hover:border-indigo-300 hover:shadow-md transition-all group"
            >
              <div className="flex items-start justify-between">
                <div>
                  <h3 className="font-semibold text-gray-900 group-hover:text-secondary">
                    {planning.description || `${t('planning')} #${planning.id}`}
                  </h3>
                  <p className="text-sm text-gray-500 mt-1">
                    {planning.type === 'weekly' ? t('weekly') : t('monthly')}
                  </p>
                </div>
                <ChevronRight size={18} className="text-gray-400 group-hover:text-secondary" />
              </div>
              <div className="mt-3 pt-3 border-t border-gray-100">
                <div className="flex items-center text-xs text-gray-500">
                  <Clock size={12} className="mr-1" />
                  <span>
                    {formatDateTime(planning.starting_date)} -{' '}
                    {formatDateTime(planning.ending_date)}
                  </span>
                </div>
                <div className="mt-1 text-xs text-gray-500">
                  {planning.shift_plannings?.length || 0} {t('shifts')}
                </div>
              </div>
            </Link>
          ))}
        </div>
      )}

      <Modal
        isOpen={isPeriodModalOpen}
        onClose={() => setIsPeriodModalOpen(false)}
        title={t('createPeriod')}
      >
        <form onSubmit={handlePeriodSubmit} className="space-y-4">
          <div>
            <label htmlFor="period-name" className="block text-sm font-medium text-gray-700 mb-1">
              {t('periodName')}
            </label>
            <input
              required
              id="period-name"
              type="text"
              placeholder="Week 1"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={periodFormData.name}
              onChange={(e) => setPeriodFormData({ ...periodFormData, name: e.target.value })}
            />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label htmlFor="period-start-date" className="block text-sm font-medium text-gray-700 mb-1">
                {t('startDate')}
              </label>
              <input
                required
                id="period-start-date"
                type="date"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={periodFormData.startDate}
                onChange={(e) =>
                  setPeriodFormData({
                    ...periodFormData,
                    startDate: e.target.value,
                  })
                }
              />
            </div>
            <div>
              <label htmlFor="period-end-date" className="block text-sm font-medium text-gray-700 mb-1">{t('endDate')}</label>
              <input
                required
                id="period-end-date"
                type="date"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={periodFormData.endDate}
                onChange={(e) =>
                  setPeriodFormData({
                    ...periodFormData,
                    endDate: e.target.value,
                  })
                }
              />
            </div>
          </div>
          <div className="flex justify-end space-x-2 pt-4">
            <button
              type="button"
              onClick={() => setIsPeriodModalOpen(false)}
              className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
              {t('cancel')}
            </button>
            <button
              type="submit"
              className="px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors"
            >
              {t('save')}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
}
