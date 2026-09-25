import { SmartPagination } from '@/src/components/SmartPagination';
import React, { useState } from 'react';
import { CourseClass, Department, User } from '@/src/lib/types';
import { Modal } from './Modal';
import {
  Plus,
  Edit2,
  Trash2,
  Search,
  Users,
  Shapes,
  Building2,
  GraduationCap,
  Lock,
  Hash,
} from 'lucide-react';
import { useTranslation } from '@/src/utils/i18n';
import { formatDateTime } from '@/src/lib/helpers';

type StudentGroupManagerProps = {
  classes: CourseClass[];
  departments: Department[];
  user: User;
  currentPage?: number;
  totalPages?: number;
  onPageChange?: (page: number) => void;
  onAdd: (courseClass: CourseClass) => void;
  onUpdate: (courseClass: CourseClass) => void;
  onDelete: (id: number) => void;
};

export const StudentGroupManager: React.FC<StudentGroupManagerProps> = ({
  classes,
  departments,
  user,
  currentPage = 1,
  totalPages = 1,
  onPageChange,
  onAdd,
  onUpdate,
  onDelete,
}) => {
  const { t } = useTranslation();
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [editingId, setEditingId] = useState<number | null>(null);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [classToDelete, setClassToDelete] = useState<number | null>(null);

  const [formData, setFormData] = useState<Partial<CourseClass>>({
    name: '',
    code: '',
    department_id: undefined,
    level: '',
    capacity: 30,
  });

  const filtered = classes?.filter((course_class) => {
    const matchesSearch =
      course_class.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      course_class.code?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      course_class.level?.toLowerCase().includes(searchTerm.toLowerCase());

    if (user.role === 'hod' && user.department_id) {
      return matchesSearch && course_class.department_id === user.department_id;
    }

    return matchesSearch;
  });

  const getDepartmentName = (id: number) => {
    return departments.find((department: Department) => department.id === id)?.name || t('unknown');
  };

  const handleOpenModal = (classItem?: CourseClass) => {
    if (classItem) {
      setEditingId(classItem.id);
      setFormData(classItem);
    } else {
      setEditingId(null);
      const defaultDeptId =
        user.role === 'hod' && user.department_id
          ? user.department_id
          : departments.length > 0
            ? departments[0].id
            : undefined;

      setFormData({
        name: '',
        code: '',
        department_id: defaultDeptId,
        level: '',
        capacity: 30,
      });
    }
    setIsModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.name || !formData.department_id || !formData.code) return;

    if (editingId) {
      onUpdate({ id: editingId, ...formData } as CourseClass);
    } else {
      onAdd({
        id: formatDateTime(new Date().toISOString().split('T')[0]),
        ...formData,
      } as CourseClass);
    }
    setIsModalOpen(false);
  };

  const requestDelete = (id: number) => {
    setClassToDelete(id);
    setIsDeleteModalOpen(true);
  };

  const confirmDelete = () => {
    if (classToDelete) {
      onDelete(classToDelete);
      setIsDeleteModalOpen(false);
      setClassToDelete(null);
    }
  };

  return (
    <div className="space-y-6 h-full flex flex-col">
      <div className="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div className="relative w-full sm:w-96">
          <Search
            className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
            size={20}
          />
          <input
            type="text"
            placeholder={t('searchClasses')}
            className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>
        <button
          onClick={() => handleOpenModal()}
          className="flex items-center justify-center space-x-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors w-full sm:w-auto font-medium"
        >
          <Plus size={20} />
          <span>{t('addClass')}</span>
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 overflow-y-auto pb-4">
        {classes?.map((classItem) => (
          <div
            key={classItem.id}
            className="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all group"
          >
            <div className="p-6">
              <div className="flex justify-between items-start mb-4">
                <div className="p-3 bg-emerald-50 text-emerald-600 rounded-lg">
                  <Shapes size={24} />
                </div>
                <div className="flex space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button
                    onClick={() => handleOpenModal(classItem)}
                    className="p-2 text-gray-500 hover:text-secondary hover:bg-indigo-50 rounded-full"
                  >
                    <Edit2 size={16} />
                  </button>
                  <button
                    onClick={() => requestDelete(classItem.id)}
                    className="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-full"
                  >
                    <Trash2 size={16} />
                  </button>
                </div>
              </div>

              <div className="flex justify-between items-center mb-2">
                <h3 className="text-lg font-bold text-gray-900">{classItem.name}</h3>
                <span className="font-mono text-xs bg-emerald-100 text-emerald-700 px-2 py-1 rounded">
                  {classItem.code}
                </span>
              </div>

              <div className="space-y-2 mb-4">
                <div className="flex items-center text-sm text-gray-500">
                  <Building2 size={14} className="mr-2 text-gray-400" />
                  {getDepartmentName(classItem.department_id)}
                </div>
              </div>

              <div className="pt-4 border-t border-gray-50 flex justify-between items-center text-sm text-gray-600">
                <span className="flex items-center bg-gray-100 px-2 py-1 rounded-md text-xs font-medium">
                  <GraduationCap size={12} className="mr-1" />
                  {classItem.level}
                </span>
                <span className="flex items-center">
                  <Users size={14} className="mr-1" />
                  {classItem.capacity} {t('students')}
                </span>
              </div>
            </div>
            <div className="h-1 w-full bg-emerald-500 rounded-b-xl opacity-80" />
          </div>
        ))}
        {filtered?.length === 0 && (
          <div className="col-span-full flex flex-col items-center justify-center py-12 text-gray-400">
            <Shapes size={48} className="mb-4 opacity-20" />
            <p>
              No filières found.
              {user.role === 'hod'
                ? ' Add one to your department.'
                : ' Add one linked to a department.'}
            </p>
          </div>
        )}
      </div>

      <SmartPagination
        currentPage={currentPage}
        totalPages={totalPages}
        onPageChange={onPageChange}
      />

      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title={editingId ? t('editClass') : t('newClass')}
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-3 gap-4">
            <div className="col-span-2">
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('classDisplayName')}
              </label>
              <input
                required
                type="text"
                placeholder="e.g. Génie Logiciel"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              />
            </div>
            <div className="col-span-1">
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('classCode')}
              </label>
              <div className="relative">
                <Hash
                  size={14}
                  className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
                />
                <input
                  required
                  type="text"
                  placeholder="GL1"
                  className="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none uppercase font-mono"
                  value={formData.code}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      code: e.target.value.toUpperCase(),
                    })
                  }
                />
              </div>
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t('department')}
            </label>
            <div className="relative">
              <select
                required
                className={`w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none ${user.role === 'hod' ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : ''}`}
                value={formData.department_id}
                onChange={(e) =>
                  setFormData({
                    ...formData,
                    department_id: Number(e.target.value),
                  })
                }
                disabled={user.role === 'hod'}
              >
                <option value="" disabled>
                  {t('selectDept')}
                </option>
                {departments?.map((d) => (
                  <option key={d.id} value={d.id}>
                    {d.name} ({d.code})
                  </option>
                ))}
              </select>
              {user.role === 'hod' && (
                <Lock
                  size={14}
                  className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"
                />
              )}
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('level')}</label>
              <input
                required
                type="text"
                placeholder="e.g. L1, M2"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.level}
                onChange={(e) => setFormData({ ...formData, level: e.target.value })}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('numStudents')}
              </label>
              <input
                required
                type="number"
                min="1"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.capacity}
                onChange={(e) =>
                  setFormData({
                    ...formData,
                    capacity: parseInt(e.target.value) || 0,
                  })
                }
              />
            </div>
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
              {editingId ? t('save') : t('addClass')}
            </button>
          </div>
        </form>
      </Modal>

      <Modal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        title={t('confirmDelete')}
      >
        <div className="p-4">
          <p className="text-gray-700 mb-6">{t('confirmDeleteClass')}</p>
          <div className="flex justify-end space-x-2">
            <button
              onClick={() => setIsDeleteModalOpen(false)}
              className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
              {t('cancel')}
            </button>
            <button
              onClick={confirmDelete}
              className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
            >
              {t('delete')}
            </button>
          </div>
        </div>
      </Modal>
    </div>
  );
};
