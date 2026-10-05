import { SmartPagination } from '@/src/components/SmartPagination';
import React, { useState } from 'react';
import { Course, Teacher, CourseClass, ShiftPlanning } from '@/src/lib/types';
import { Modal } from './Modal';
import { Plus, Edit2, Trash2, Search, BookOpen, Hash } from 'lucide-react';
import { useTranslation } from '@/src/utils/i18n';

interface CourseManagerProps {
  courses: Course[];
  teachers: Teacher[];
  classes: CourseClass[];
  shiftPlannings?: ShiftPlanning[];
  currentPage?: number;
  totalPages?: number;
  onPageChange?: (page: number) => void;
  readOnly?: boolean;
  searchTerm: string;
  onSearchChange: (value: string) => void;
  onAddCourse: (course: Course) => void;
  onUpdateCourse: (course: Course) => void;
  onDeleteCourse: (id: number) => void;
}

export const CourseManager: React.FC<CourseManagerProps> = ({
  courses,
  shiftPlannings,
  currentPage = 1,
  totalPages = 1,
  onPageChange,
  readOnly = false,
  searchTerm,
  onSearchChange,
  onAddCourse,
  onUpdateCourse,
  onDeleteCourse,
}) => {
  const { t } = useTranslation();
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingCourse, setEditingCourse] = useState<Course | null>(null);

  const [formData, setFormData] = useState<Partial<Course>>({
    code: '',
    name: '',
  });

  const handleOpenModal = (course?: Course) => {
    if (course) {
      setEditingCourse(course);
      setFormData(course);
    } else {
      setEditingCourse(null);
      setFormData({
        code: '',
        name: '',
      });
    }
    setIsModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.name || !formData.code) return;

    if (editingCourse) {
      onUpdateCourse({ ...editingCourse, ...formData } as Course);
    } else {
      onAddCourse({
        id: Date.now().toString(),
        ...formData,
      } as Course);
    }
    setIsModalOpen(false);
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
            placeholder={t('searchCourses')}
            className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all"
            value={searchTerm}
            onChange={(e) => onSearchChange(e.target.value)}
          />
        </div>
        {!readOnly && (
          <button
            type="button"
            onClick={() => handleOpenModal()}
            className="flex items-center justify-center space-x-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors w-full sm:w-auto font-medium"
          >
            <Plus size={20} />
            <span>{t('addCourse')}</span>
          </button>
        )}
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-gray-50 border-b border-gray-200">
                <th className="p-4 font-semibold text-gray-600 text-sm">{t('courseCode')}</th>
                <th className="p-4 font-semibold text-gray-600 text-sm">{t('courseName')}</th>
                <th className="p-4 font-semibold text-gray-600 text-sm">{t('shifts')}</th>
                {!readOnly && (
                  <th className="p-4 font-semibold text-gray-600 text-sm text-right">
                    {t('actions')}
                  </th>
                )}
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {courses?.map((course) => {
                const courseShifts = shiftPlannings?.filter((s) => s.course_id === course.id) || [];
                return (
                  <tr key={course.id} className="hover:bg-gray-50 transition-colors">
                    <td className="p-4">
                      <span className="font-mono bg-gray-100 px-2 py-1 rounded text-gray-700 text-sm">
                        {course.code}
                      </span>
                    </td>
                    <td className="p-4">
                      <div className="flex items-center">
                        <div className="p-2 bg-indigo-50 text-secondary rounded-lg mr-3 hidden sm:block">
                          <BookOpen size={18} />
                        </div>
                        <span className="font-medium text-gray-900">{course.name}</span>
                      </div>
                    </td>
                    <td className="p-4">
                      <span className="text-gray-600 text-sm">
                        {courseShifts.length} {t('shifts')}
                      </span>
                    </td>
                    {!readOnly && (
                      <td className="p-4 text-right space-x-2">
                        <button
                          type="button"
                          onClick={() => handleOpenModal(course)}
                          className="p-2 text-gray-400 hover:text-secondary hover:bg-indigo-50 rounded-lg transition-colors"
                        >
                          <Edit2 size={18} />
                        </button>
                        <button
                          type="button"
                          onClick={() => onDeleteCourse(course.id)}
                          className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                        >
                          <Trash2 size={18} />
                        </button>
                      </td>
                    )}
                  </tr>
                );
              })}
              {courses?.length === 0 && (
                <tr>
                  <td colSpan={readOnly ? 3 : 4} className="p-8 text-center text-gray-500">
                    {t('noResults')}
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>

        <SmartPagination
          currentPage={currentPage}
          totalPages={totalPages}
          onPageChange={onPageChange}
        />
      </div>

      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title={editingCourse ? t('editCourse') : t('newCourse')}
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-3 gap-4">
            <div className="col-span-1">
              <label htmlFor="course-code" className="block text-sm font-medium text-gray-700 mb-1">
                {t('courseCode')}
              </label>
              <div className="relative">
                <Hash
                  size={14}
                  className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
                />
                <input
                  required
                  id="course-code"
                  type="text"
                  placeholder="CS101"
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
            <div className="col-span-2">
              <label htmlFor="course-name" className="block text-sm font-medium text-gray-700 mb-1">
                {t('courseName')}
              </label>
              <input
                required
                id="course-name"
                type="text"
                placeholder="e.g. Advanced Calculus"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
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
              {editingCourse ? t('save') : t('add')}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
