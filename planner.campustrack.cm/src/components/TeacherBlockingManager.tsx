import React, { useState } from 'react';
import { TeacherBlocking, Teacher } from '@/src/lib/types';
import { Modal } from './Modal';
import {
  Plus,
  Edit2,
  Trash2,
  Search,
  Calendar,
  AlertTriangle,
  XCircle,
  Briefcase,
  Plane,
  Stethoscope,
  HelpCircle,
  CheckCircle,
  Clock,
} from 'lucide-react';
import { useTranslation } from '@/src/utils/i18n';
import { formatDateTime } from '@/src/lib/helpers';

interface TeacherBlockingManagerProps {
  blockings: TeacherBlocking[];
  teachers: Teacher[];
  onAdd: (b: TeacherBlocking) => void;
  onUpdate: (b: TeacherBlocking) => void;
  onDelete: (id: number) => void;
  onApprove?: (id: number) => void;
  onReject?: (id: number, reason?: string) => void;
  showApprovals?: boolean;
}

const blockingTypeConfig = {
  absence: { icon: XCircle, color: 'text-red-500 bg-red-50', label: 'Absence' },
  vacation: {
    icon: Plane,
    color: 'text-blue-500 bg-blue-50',
    label: 'Vacation',
  },
  training: {
    icon: Briefcase,
    color: 'text-purple-500 bg-purple-50',
    label: 'Training',
  },
  medical: {
    icon: Stethoscope,
    color: 'text-green-500 bg-green-50',
    label: 'Medical',
  },
  other: {
    icon: HelpCircle,
    color: 'text-gray-500 bg-gray-50',
    label: 'Other',
  },
};

const statusConfig = {
  pending: { color: 'bg-amber-100 text-amber-700', label: 'Pending' },
  approved: { color: 'bg-green-100 text-green-700', label: 'Approved' },
  rejected: { color: 'bg-red-100 text-red-700', label: 'Rejected' },
};

export const TeacherBlockingManager: React.FC<TeacherBlockingManagerProps> = ({
  blockings,
  teachers,
  onAdd,
  onUpdate,
  onDelete,
  onApprove,
  onReject,
  showApprovals = false,
}) => {
  const { t } = useTranslation();
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isRejectModalOpen, setIsRejectModalOpen] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterStatus, setFilterStatus] = useState<string>('');
  const [filterTeacher, setFilterTeacher] = useState<string>('');
  const [editingId, setEditingId] = useState<number | null>(null);
  const [blockingToReject, setBlockingToReject] = useState<TeacherBlocking | null>(null);
  const [rejectionReason, setRejectionReason] = useState('');

  const [formData, setFormData] = useState<Partial<TeacherBlocking>>({
    teacher_id: undefined,
    start_datetime: '',
    end_datetime: '',
    reason: '',
    blocking_type: 'absence',
  });

  const filteredBlockings = blockings.filter((b) => {
    const teacher = teachers.find((teacher) => teacher.id === b.teacher_id);
    const matchesSearch =
      b.reason.toLowerCase().includes(searchTerm.toLowerCase()) ||
      teacher?.first_name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      teacher?.last_name.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesStatus = !filterStatus || b.status === filterStatus;
    const matchesTeacher = !filterTeacher || b.teacher_id === Number(filterTeacher);
    return matchesSearch && matchesStatus && matchesTeacher;
  });

  const getTeacherName = (teacherId: number) => {
    const teacher = teachers.find((teacher) => teacher.id === teacherId);
    return `${teacher?.full_name}` || '-';
  };

  const handleOpenModal = (blocking?: TeacherBlocking) => {
    if (blocking) {
      setEditingId(blocking.id);
      setFormData(blocking);
    } else {
      setEditingId(null);
      setFormData({
        teacher_id: teachers.length > 0 ? Number(teachers[0].id) : 0,
        start_datetime: '',
        end_datetime: '',
        reason: '',
        blocking_type: 'absence',
      });
    }
    setIsModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (
      !formData.teacher_id ||
      !formData.start_datetime ||
      !formData.end_datetime ||
      !formData.reason
    )
      return;

    if (editingId) {
      onUpdate({ id: editingId, ...formData } as TeacherBlocking);
    } else {
      onAdd({
        id: formatDateTime(new Date().toISOString().split('T')[0]),
        status: 'pending',
        ...formData,
      } as TeacherBlocking);
    }
    setIsModalOpen(false);
  };

  const handleRejectSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (blockingToReject && onReject) {
      onReject(blockingToReject.id, rejectionReason);
      setIsRejectModalOpen(false);
      setBlockingToReject(null);
      setRejectionReason('');
    }
  };

  const openRejectModal = (blocking: TeacherBlocking) => {
    setBlockingToReject(blocking);
    setRejectionReason('');
    setIsRejectModalOpen(true);
  };

  const pendingBlockings = showApprovals ? blockings.filter((b) => b.status === 'pending') : [];

  return (
    <div className="space-y-6 h-full flex flex-col">
      {showApprovals && pendingBlockings.length > 0 && (
        <div className="bg-amber-50 border border-amber-200 rounded-xl p-4">
          <h3 className="font-semibold text-amber-800 mb-3 flex items-center">
            <Clock size={18} className="mr-2" />
            {t('pendingApprovals')} ({pendingBlockings.length})
          </h3>
          <div className="space-y-2">
            {pendingBlockings.slice(0, 3).map((b) => (
              <div key={b.id} className="bg-white rounded-lg p-3 flex items-center justify-between">
                <div>
                  <p className="font-medium text-gray-900">{getTeacherName(b.teacher_id)}</p>
                  <p className="text-sm text-gray-500">{b.reason}</p>
                </div>
                <div className="flex space-x-2">
                  <button
                    type="button"
                    onClick={() => onApprove?.(b.id)}
                    className="p-2 text-green-600 hover:bg-green-50 rounded-lg"
                    title={t('approve')}
                  >
                    <CheckCircle size={18} />
                  </button>
                  <button
                    type="button"
                    onClick={() => openRejectModal(b)}
                    className="p-2 text-red-600 hover:bg-red-50 rounded-lg"
                    title={t('reject')}
                  >
                    <XCircle size={18} />
                  </button>
                </div>
              </div>
            ))}
            {pendingBlockings.length > 3 && (
              <p className="text-sm text-amber-700 text-center">
                +{pendingBlockings.length - 3} {t('more')}
              </p>
            )}
          </div>
        </div>
      )}

      <div className="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div className="flex flex-col sm:flex-row gap-3 w-full sm:w-auto flex-1">
          <div className="relative w-full sm:w-64">
            <Search
              className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
              size={20}
            />
            <input
              type="text"
              placeholder={t('searchBlockings')}
              className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>

          <div className="relative w-full sm:w-40">
            <select
              className="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none appearance-none bg-white"
              value={filterStatus}
              onChange={(e) => setFilterStatus(e.target.value)}
            >
              <option value="">{t('allStatuses')}</option>
              <option value="pending">{t('pending')}</option>
              <option value="approved">{t('approved')}</option>
              <option value="rejected">{t('rejected')}</option>
            </select>
          </div>

          <div className="relative w-full sm:w-48">
            <select
              className="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none appearance-none bg-white"
              value={filterTeacher}
              onChange={(e) => setFilterTeacher(e.target.value)}
            >
              <option value="">{t('allTeachers')}</option>
              {teachers.map((teacher) => (
                <option key={teacher.id} value={teacher.id}>{`${teacher.full_name}`}</option>
              ))}
            </select>
          </div>
        </div>

        <button
          type="button"
          onClick={() => handleOpenModal()}
          className="flex items-center justify-center space-x-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors w-full sm:w-auto font-medium"
        >
          <Plus size={20} />
          <span>{t('addBlocking')}</span>
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 overflow-y-auto pb-4">
        {filteredBlockings.map((blocking) => {
          const typeConfig = blockingTypeConfig[blocking.blocking_type];
          const stConfig = statusConfig[blocking.status];
          const TypeIcon = typeConfig.icon;

          return (
            <div
              key={blocking.id}
              className="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all group"
            >
              <div className="p-4">
                <div className="flex justify-between items-start mb-3">
                  <div className={`p-2 rounded-lg ${typeConfig.color}`}>
                    <TypeIcon size={20} />
                  </div>
                  <div className="flex items-center space-x-2">
                    <span
                      className={`px-2 py-0.5 rounded-full text-xs font-medium ${stConfig.color}`}
                    >
                      {t(blocking.status)}
                    </span>
                  </div>
                </div>

                <h3 className="font-semibold text-gray-900 mb-1">
                  {getTeacherName(blocking.teacher_id)}
                </h3>
                <p className="text-sm text-gray-600 mb-3">{blocking.reason}</p>

                <div className="flex items-center justify-between text-xs">
                  <span className={`px-2 py-0.5 rounded-full ${typeConfig.color}`}>
                    {t(blocking.blocking_type)}
                  </span>
                </div>

                {blocking.rejection_reason && (
                  <div className="mt-2 p-2 bg-red-50 rounded text-xs text-red-600">
                    <strong>{t('rejectionReason')}:</strong> {blocking.rejection_reason}
                  </div>
                )}

                <div className="mt-3 pt-3 border-t border-gray-50 text-xs text-gray-500">
                  <div className="flex items-center mb-1">
                    <Calendar size={12} className="mr-1.5" />
                    {formatDateTime(blocking.start_datetime)}
                  </div>
                  <div className="flex items-center">
                    <Calendar size={12} className="mr-1.5" />
                    {formatDateTime(blocking.end_datetime)}
                  </div>
                </div>

                {blocking.status === 'pending' && showApprovals && (
                  <div className="mt-3 pt-3 border-t border-gray-50 flex justify-end space-x-2">
                    <button
                      type="button"
                      onClick={() => onApprove?.(blocking.id)}
                      className="px-3 py-1.5 text-sm text-green-600 hover:bg-green-50 rounded-lg font-medium"
                    >
                      {t('approve')}
                    </button>
                    <button
                      type="button"
                      onClick={() => openRejectModal(blocking)}
                      className="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-lg font-medium"
                    >
                      {t('reject')}
                    </button>
                  </div>
                )}

                {blocking.status !== 'pending' && (
                  <div className="mt-3 pt-3 border-t border-gray-50 flex justify-end space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button
                      type="button"
                      onClick={() => handleOpenModal(blocking)}
                      className="p-1.5 text-gray-400 hover:text-secondary hover:bg-indigo-50 rounded-lg"
                    >
                      <Edit2 size={14} />
                    </button>
                    <button
                      type="button"
                      onClick={() => onDelete(blocking.id)}
                      className="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg"
                    >
                      <Trash2 size={14} />
                    </button>
                  </div>
                )}
              </div>
            </div>
          );
        })}

        {filteredBlockings.length === 0 && (
          <div className="col-span-full flex flex-col items-center justify-center py-12 text-gray-400">
            <AlertTriangle size={48} className="mb-4 opacity-20" />
            <p>{t('noBlockingsFound')}</p>
          </div>
        )}
      </div>

      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title={editingId ? t('editBlocking') : t('newBlocking')}
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label htmlFor="teacher-blocking-teacher" className="block text-sm font-medium text-gray-700 mb-1">{t('teacher')}</label>
            <select
              required
              id="teacher-blocking-teacher"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={formData.teacher_id}
              onChange={(e) => setFormData({ ...formData, teacher_id: Number(e.target.value) })}
            >
              <option value={0} disabled>
                {t('selectTeacher')}
              </option>
              {teachers.map((teacher) => (
                <option key={teacher.id} value={teacher.id}>{`${teacher.full_name}`}</option>
              ))}
            </select>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label htmlFor="teacher-blocking-start-datetime" className="block text-sm font-medium text-gray-700 mb-1">
                {t('startDateTime')}
              </label>
              <input
                required
                id="teacher-blocking-start-datetime"
                type="datetime-local"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.start_datetime}
                onChange={(e) => setFormData({ ...formData, start_datetime: e.target.value })}
              />
            </div>
            <div>
              <label htmlFor="teacher-blocking-end-datetime" className="block text-sm font-medium text-gray-700 mb-1">
                {t('endDateTime')}
              </label>
              <input
                required
                id="teacher-blocking-end-datetime"
                type="datetime-local"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.end_datetime}
                onChange={(e) => setFormData({ ...formData, end_datetime: e.target.value })}
              />
            </div>
          </div>

          <div>
            <label htmlFor="teacher-blocking-type" className="block text-sm font-medium text-gray-700 mb-1">
              {t('blockingType')}
            </label>
            <select
              id="teacher-blocking-type"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={formData.blocking_type}
              onChange={(e) =>
                setFormData({
                  ...formData,
                  blocking_type: e.target.value as TeacherBlocking['blocking_type'],
                })
              }
            >
              <option value="absence">{t('absence')}</option>
              <option value="vacation">{t('vacation')}</option>
              <option value="training">{t('training')}</option>
              <option value="medical">{t('medical')}</option>
              <option value="other">{t('other')}</option>
            </select>
          </div>

          <div>
            <label htmlFor="teacher-blocking-reason" className="block text-sm font-medium text-gray-700 mb-1">{t('reason')}</label>
            <textarea
              required
              id="teacher-blocking-reason"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              rows={2}
              value={formData.reason}
              onChange={(e) => setFormData({ ...formData, reason: e.target.value })}
            />
          </div>

          <div className="pt-4 flex justify-end space-x-3">
            <button
              type="button"
              onClick={() => setIsModalOpen(false)}
              className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition-colors"
            >
              {t('cancel')}
            </button>
            <button
              type="submit"
              className="px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary font-medium transition-colors shadow-sm"
            >
              {editingId ? t('save') : t('submit')}
            </button>
          </div>
        </form>
      </Modal>

      <Modal
        isOpen={isRejectModalOpen}
        onClose={() => setIsRejectModalOpen(false)}
        title={t('rejectBlocking')}
      >
        <form onSubmit={handleRejectSubmit} className="space-y-4">
          <p className="text-sm text-gray-600">
            {t('rejectingBlockingFor')}{' '}
            <strong>{blockingToReject && getTeacherName(blockingToReject.teacher_id)}</strong>
          </p>
          <div>
            <label htmlFor="teacher-blocking-rejection-reason" className="block text-sm font-medium text-gray-700 mb-1">
              {t('rejectionReason')}
            </label>
            <textarea
              id="teacher-blocking-rejection-reason"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              rows={3}
              value={rejectionReason}
              onChange={(e) => setRejectionReason(e.target.value)}
              placeholder={t('optional')}
            />
          </div>
          <div className="pt-4 flex justify-end space-x-3">
            <button
              type="button"
              onClick={() => setIsRejectModalOpen(false)}
              className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition-colors"
            >
              {t('cancel')}
            </button>
            <button
              type="submit"
              className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium transition-colors shadow-sm"
            >
              {t('reject')}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
