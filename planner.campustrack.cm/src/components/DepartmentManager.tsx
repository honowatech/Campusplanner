import { SmartPagination } from '@/src/components/SmartPagination';
import React, { useState, useMemo } from 'react';
import { Department, Teacher, CourseClass, ShiftPlanning } from '@/src/lib/types';
import { Modal } from './Modal';
import {
  Plus,
  Edit2,
  Trash2,
  Search,
  Building2,
  Hash,
  Crown,
  ArrowLeft,
  BookOpen,
  Users,
} from 'lucide-react';
import { useTranslation } from '@/src/utils/i18n';
import { formatDateTime } from '@/src/lib/helpers';

type DepartmentManagerProps = {
  departments: Department[];
  teachers: Teacher[];
  classes: CourseClass[];
  shiftPlannings?: ShiftPlanning[];
  currentPage?: number;
  totalPages?: number;
  onPageChange?: (page: number) => void;
  searchTerm: string;
  onSearchChange: (value: string) => void;
  onAdd: (department: Department) => void;
  onUpdate: (department: Department) => void;
  onDelete: (id: number) => void;
  onDeleteCourse: (id: number) => void;
};

export const DepartmentManager: React.FC<DepartmentManagerProps> = ({
  departments,
  teachers,
  classes,
  shiftPlannings,
  currentPage = 1,
  totalPages = 1,
  onPageChange,
  searchTerm,
  onSearchChange,
  onAdd,
  onUpdate,
  onDelete,
  onDeleteCourse,
}) => {
  const { t } = useTranslation();
  const [isModalOpen, setIsModalOpen] = useState(false);
  // const [isCourseModalOpen, setIsCourseModalOpen] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [selectedDeptId, setSelectedDeptId] = useState<number | null>(null);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [deptToDelete, setDeptToDelete] = useState<number | null>(null);

  // Form State for Department
  const [formData, setFormData] = useState<Partial<Department>>({
    name: '',
    code: '',
  });

  const selectedDept = useMemo(
    () => departments?.find((department) => department.id === Number(selectedDeptId)),
    [selectedDeptId, departments],
  );

  // Get shifts for selected department
  const deptShifts = useMemo(() => {
    if (!selectedDeptId || !shiftPlannings) return [];
    const deptClassIds = classes
      ?.filter((course_class) => course_class.department_id === Number(selectedDeptId))
      .map((course_class) => course_class.id);
    return shiftPlannings.filter((shift) => deptClassIds?.includes(shift.course_class_id));
  }, [selectedDeptId, shiftPlannings, classes]);

  // --- Helpers ---
  const getHeadName = (id?: number) => {
    if (!id) return null;
    const teacher = teachers.find((teacher) => teacher.id === id);
    return teacher ? `${teacher.full_name}` : t('unknown');
  };
  // --- Handlers for Department CRUD ---
  const handleOpenModal = (department?: Department) => {
    if (department) {
      setEditingId(String(department.id));
      setFormData(department);
    } else {
      setEditingId(null);
      setFormData({ name: '', code: '' });
    }
    setIsModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.name || !formData.code) return;

    if (editingId) {
      onUpdate({ id: editingId, ...formData } as Department);
    } else {
      onAdd({
        id: formatDateTime(new Date().toISOString().split('T')[0]),
        ...formData,
      } as Department);
    }
    setIsModalOpen(false);
  };

  const requestDelete = (id: number) => {
    setDeptToDelete(id);
    setIsDeleteModalOpen(true);
  };

  const confirmDelete = () => {
    if (deptToDelete) {
      onDelete(deptToDelete);
      setIsDeleteModalOpen(false);
      setDeptToDelete(null);
    }
  };

  // --- Views ---

  // 1. Detail View
  if (selectedDeptId && selectedDept) {
    return (
      <>
        <div className="space-y-6 animate-in fade-in duration-300">
          {/* Header */}
          <div className="flex items-center justify-between">
            <div className="flex items-center space-x-4">
              <button
                type="button"
                onClick={() => setSelectedDeptId(null)}
                className="p-2 rounded-full bg-white border border-gray-200 hover:bg-gray-50 text-gray-600 transition-colors"
              >
                <ArrowLeft size={20} />
              </button>
              <div>
                <h2 className="text-2xl font-bold text-gray-900 flex items-center">
                  {selectedDept.name}
                  <span className="ml-3 text-sm font-mono bg-purple-100 text-purple-700 px-2 py-1 rounded">
                    {selectedDept.code}
                  </span>
                </h2>
                {selectedDept.head_of_department_id && (
                  <p className="text-gray-500 flex items-center mt-1 text-sm">
                    <Crown size={14} className="mr-1.5 text-yellow-500" />
                    {t('headOfDept')}:{' '}
                    <span className="font-medium ml-1">
                      {getHeadName(selectedDept.head_of_department_id)}
                    </span>
                  </p>
                )}
              </div>
            </div>

            <div className="flex space-x-2">
              <button
                type="button"
                onClick={() => handleOpenModal(selectedDept)}
                className="px-4 py-2 text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg transition-colors text-sm font-medium"
              >
                {t('edit')}
              </button>
            </div>
          </div>

          {/* Stats Cards */}
          <div className="grid grid-cols-3 gap-4">
            <div className="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
              <div className="text-gray-500 text-sm font-medium mb-1">{t('shifts')}</div>
              <div className="text-2xl font-bold text-gray-900">{deptShifts.length}</div>
            </div>
            <div className="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
              <div className="text-gray-500 text-sm font-medium mb-1">{t('classes')}</div>
              <div className="text-2xl font-bold text-gray-900">
                {selectedDept.course_classes?.length}
              </div>
            </div>
          </div>

          {/* Courses Table */}
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div className="p-6 border-b border-gray-100">
              <h3 className="text-lg font-bold text-gray-900">{t('courses')} (UE)</h3>
            </div>
            <div className="overflow-x-auto">
              <table className="w-full text-left border-collapse">
                <thead>
                  <tr className="bg-gray-50 text-xs uppercase text-gray-500 font-semibold tracking-wider">
                    <th className="p-4">{t('courseCode')}</th>
                    <th className="p-4">{t('courseName')}</th>
                    <th className="p-4">{t('teachers')}</th>
                    <th className="p-4 text-right">{t('actions')}</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {selectedDept.courses?.map((course) => {
                    return (
                      <tr key={course.id} className="hover:bg-gray-50 transition-colors text-sm">
                        <td className="p-4 font-mono text-secondary font-medium">{course.code}</td>
                        <td className="p-4 font-medium text-gray-900">
                          <div className="flex items-center">
                            <BookOpen size={16} className="mr-2 text-gray-400" />
                            {course.name}
                          </div>
                        </td>
                        <td className="p-4 text-gray-600">
                          <div className="flex items-center">
                            <Users size={16} className="mr-2 text-gray-400" />
                            {course.teachers?.map((teacher) => (
                              <span
                                key={teacher.id}
                                className="ml-1.5 text-xs bg-gray-100 px-2 py-1 rounded-full"
                              >
                                {teacher.full_name}
                              </span>
                            ))}
                          </div>
                        </td>
                        <td className="p-4 text-right">
                          <button
                            type="button"
                            onClick={() => onDeleteCourse(course.id)}
                            className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                            title={t('delete')}
                          >
                            <Trash2 size={16} />
                          </button>
                        </td>
                      </tr>
                    );
                  })}
                  {selectedDept.courses?.length === 0 && (
                    <tr>
                      <td colSpan={5} className="p-8 text-center text-gray-500">
                        {t('noClassesAssigned')}
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <Modal
          isOpen={isModalOpen}
          onClose={() => setIsModalOpen(false)}
          title={editingId ? t('editDepartment') : t('newDepartment')}
        >
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label htmlFor="department-detail-name" className="block text-sm font-medium text-gray-700 mb-1">
                {t('deptName')}
              </label>
              <input
                id="department-detail-name"
                required
                type="text"
                placeholder="e.g. Computer Science"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              />
            </div>
            <div>
              <label htmlFor="department-detail-code" className="block text-sm font-medium text-gray-700 mb-1">
                {t('deptCode')}
              </label>
              <input
                id="department-detail-code"
                required
                type="text"
                placeholder="e.g. CS"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none uppercase"
                value={formData.code}
                onChange={(e) =>
                  setFormData({
                    ...formData,
                    code: e.target.value.toUpperCase(),
                  })
                }
              />
            </div>
            <div>
              <label htmlFor="department-detail-head" className="block text-sm font-medium text-gray-700 mb-1">
                {t('headOfDept')}
              </label>
              <select
                id="department-detail-head"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.head_of_department_id || ''}
                onChange={(e) =>
                  setFormData({
                    ...formData,
                    head_of_department_id: Number(e.target.value),
                  })
                }
              >
                <option value="">{t('selectTeacher')}</option>
                {teachers?.map((teacher) => (
                  <option key={teacher.id} value={teacher.id}>
                    {teacher.full_name}
                  </option>
                ))}
              </select>
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
                {editingId ? t('save') : t('add')}
              </button>
            </div>
          </form>
        </Modal>
      </>
    );
  }

  // 2. List View (Default)
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
            placeholder={t('searchDepartments')}
            className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all"
            value={searchTerm}
            onChange={(e) => onSearchChange(e.target.value)}
          />
        </div>
        <button
          type="button"
          onClick={() => handleOpenModal()}
          className="flex items-center justify-center space-x-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors w-full sm:w-auto font-medium"
        >
          <Plus size={20} />
          <span>{t('addDepartment')}</span>
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 overflow-y-auto pb-4">
        {departments?.map((dept) => (
          <div
            key={dept.id}
            className="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all group cursor-pointer flex flex-col"
            onClick={() => setSelectedDeptId(dept.id)}
          >
            <div className="p-6 flex-1">
              <div className="flex justify-between items-start mb-4">
                <div className="p-3 bg-purple-50 text-purple-600 rounded-lg">
                  <Building2 size={24} />
                </div>
                <div className="flex space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button
                    type="button"
                    onClick={(e) => {
                      e.stopPropagation();
                      handleOpenModal(dept);
                    }}
                    className="p-2 text-gray-500 hover:text-secondary hover:bg-indigo-50 rounded-full"
                  >
                    <Edit2 size={16} />
                  </button>
                  <button
                    type="button"
                    onClick={(e) => {
                      e.stopPropagation();
                      requestDelete(dept.id);
                    }}
                    className="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-full"
                  >
                    <Trash2 size={16} />
                  </button>
                </div>
              </div>

              <h3 className="text-lg font-bold text-gray-900 mb-1">{dept.name}</h3>

              <div className="flex items-center text-sm text-gray-500 mt-2">
                <Hash size={14} className="mr-1" />
                {t('deptCode')}:{' '}
                <span className="font-mono bg-gray-100 px-1.5 py-0.5 rounded ml-1 text-gray-700">
                  {dept.code}
                </span>
              </div>

              {/* {dept.headOfDepartmentId && (
                <div className="flex items-center text-sm text-gray-500 mt-2">
                  <Crown size={14} className="mr-1 text-yellow-500" />
                  {t("headOfDept")}:{" "}
                  <span className="font-medium ml-1 text-secondary">
                    {getHeadName(dept.headOfDepartmentId)}
                  </span>
                </div>
              )} */}
            </div>
            <div className="h-1 w-full bg-purple-500 rounded-b-xl opacity-80" />
          </div>
        ))}
        {departments?.length === 0 && (
          <div className="col-span-full flex flex-col items-center justify-center py-12 text-gray-400">
            <Building2 size={48} className="mb-4 opacity-20" />
            <p>{t('noResults')}</p>
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
        title={editingId ? t('editDepartment') : t('newDepartment')}
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label htmlFor="department-name" className="block text-sm font-medium text-gray-700 mb-1">{t('deptName')}</label>
            <input
              id="department-name"
              required
              type="text"
              placeholder="e.g. Computer Science"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
            />
          </div>
          <div>
            <label htmlFor="department-code" className="block text-sm font-medium text-gray-700 mb-1">{t('deptCode')}</label>
            <input
              id="department-code"
              required
              type="text"
              placeholder="e.g. CS"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none uppercase"
              value={formData.code}
              onChange={(e) => setFormData({ ...formData, code: e.target.value.toUpperCase() })}
            />
          </div>
          <div>
            <label htmlFor="department-head" className="block text-sm font-medium text-gray-700 mb-1">
              {t('headOfDept')}
            </label>
            <select
              id="department-head"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={formData.head_of_department_id || ''}
              onChange={(e) =>
                setFormData({
                  ...formData,
                  head_of_department_id: Number(e.target.value),
                })
              }
            >
              <option value="">{t('selectTeacher')}</option>
              {teachers?.map((teacher) => (
                <option key={teacher.id} value={teacher.id}>
                  {teacher.full_name}
                </option>
              ))}
            </select>
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
              {editingId ? t('save') : t('add')}
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
          <p className="text-gray-700 mb-6">{t('confirmDeleteDepartment')}</p>
          <div className="flex justify-end space-x-2">
            <button
              type="button"
              onClick={() => setIsDeleteModalOpen(false)}
              className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
              {t('cancel')}
            </button>
            <button
              type="button"
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
