import { SmartPagination } from '@/src/components/SmartPagination';
import React, { useState } from 'react';
import { Student, Department, CourseClass } from '@/src/lib/types';
import { Modal } from './Modal';
import {
  Plus,
  Edit2,
  Trash2,
  Search,
  GraduationCap,
  Phone,
  ArrowRight,
  Filter,
  UserX,
} from 'lucide-react';
import { useTranslation } from '@/src/utils/i18n';
import { formatDateTime } from '@/src/lib/helpers';

interface StudentManagerProps {
  students: Student[];
  departments: Department[];
  classes: CourseClass[];
  currentPage?: number;
  totalPages?: number;
  onPageChange?: (page: number) => void;
  onAddStudent: (student: Student) => void;
  onUpdateStudent: (student: Student) => void;
  onDeleteStudent: (id: number) => void;
  onMoveStudent: (id: number, classId: number) => void;
}

export const StudentManager: React.FC<StudentManagerProps> = ({
  students,
  departments,
  classes,
  currentPage = 1,
  totalPages = 1,
  onPageChange,
  onAddStudent,
  onUpdateStudent,
  onDeleteStudent,
  onMoveStudent,
}) => {
  const { t } = useTranslation();
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isMoveModalOpen, setIsMoveModalOpen] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterClass, setFilterClass] = useState<string>('');
  const [filterDepartment, setFilterDepartment] = useState<string>('');
  const [editingStudent, setEditingStudent] = useState<Student | null>(null);
  const [studentToMove, setStudentToMove] = useState<Student | null>(null);
  const [targetClassId, setTargetClassId] = useState<number | null>(null);

  const [formData, setFormData] = useState<Partial<Student>>({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    date_of_birth: '',
    gender: 'male',
    address: '',
    parent_name: '',
    parent_phone: '',
    matricule: '',
    admission_date: '',
    course_class_id: undefined,
    is_active: true,
  });

  const filteredStudents = students.filter((student) => {
    const matchesSearch =
      `${student.full_name}`.toLowerCase().includes(searchTerm.toLowerCase()) ||
      student.matricule.toLowerCase().includes(searchTerm.toLowerCase()) ||
      student.email.toLowerCase().includes(searchTerm.toLowerCase());

    const matchesClass = !filterClass || student.course_class_id === Number(filterClass);
    const matchesDept =
      !filterDepartment ||
      classes.find((course_class) => course_class.id === student.course_class_id)?.department_id ===
        Number(filterDepartment);

    return matchesSearch && matchesClass && matchesDept;
  });

  const getClassName = (classId: number | undefined) => {
    const cls = classes.find((c) => c.id === classId);
    return cls ? `${cls.name} (${cls.code})` : '-';
  };

  const handleOpenModal = (student?: Student) => {
    if (student) {
      setEditingStudent(student);
      setFormData(student);
    } else {
      setEditingStudent(null);
      setFormData({
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        date_of_birth: '',
        gender: 'male',
        address: '',
        parent_name: '',
        parent_phone: '',
        matricule: '',
        admission_date: '',
        course_class_id: classes.length > 0 ? classes[0].id : 0,
        is_active: true,
      });
    }
    setIsModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.first_name || !formData.last_name || !formData.email || !formData.course_class_id)
      return;

    if (editingStudent) {
      onUpdateStudent({ ...editingStudent, ...formData } as Student);
    } else {
      onAddStudent({
        id: formatDateTime(new Date().toISOString().split('T')[0]),
        ...formData,
      } as Student);
    }
    setIsModalOpen(false);
  };

  const handleMoveSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (studentToMove && targetClassId) {
      onMoveStudent(studentToMove.id, targetClassId);
      setIsMoveModalOpen(false);
      setStudentToMove(null);
      setTargetClassId(null);
    }
  };

  const openMoveModal = (student: Student) => {
    setStudentToMove(student);
    setTargetClassId(student.course_class_id ?? null);
    setIsMoveModalOpen(true);
  };

  return (
    <div className="space-y-6 h-full flex flex-col">
      <div className="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div className="flex flex-col sm:flex-row gap-3 w-full lg:w-auto flex-1">
          <div className="relative w-full sm:w-72">
            <Search
              className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
              size={20}
            />
            <input
              type="text"
              placeholder={t('searchStudents')}
              className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>

          <div className="relative w-full sm:w-48">
            <Filter
              className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
              size={18}
            />
            <select
              className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none appearance-none bg-white"
              value={filterDepartment}
              onChange={(e) => {
                setFilterDepartment(e.target.value);
                setFilterClass('');
              }}
            >
              <option value="">{t('allDepts')}</option>
              {departments.map((department) => (
                <option key={department.id} value={department.id}>
                  {department.name}
                </option>
              ))}
            </select>
          </div>

          <div className="relative w-full sm:w-48">
            <GraduationCap
              className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
              size={18}
            />
            <select
              className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none appearance-none bg-white"
              value={filterClass}
              onChange={(e) => setFilterClass(e.target.value)}
            >
              <option value="">{t('allClasses')}</option>
              {classes
                .filter(
                  (course_class) =>
                    !filterDepartment || course_class.department_id === Number(filterDepartment),
                )
                .map((course_class) => (
                  <option key={course_class.id} value={course_class.id}>
                    {course_class.name}
                  </option>
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
          <span>{t('addStudent')}</span>
        </button>
      </div>

      <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex-1">
        <div className="overflow-x-auto">
          <table className="w-full text-left border-collapse">
            <thead>
              <tr className="bg-gray-50 text-xs uppercase text-gray-500 font-semibold tracking-wider">
                <th className="p-4">{t('student')}</th>
                <th className="p-4">{t('matricule')}</th>
                <th className="p-4">{t('email')}</th>
                <th className="p-4">{t('class')}</th>
                <th className="p-4">{t('status')}</th>
                <th className="p-4 text-right">{t('actions')}</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {filteredStudents.map((student) => (
                <tr key={student.id} className="hover:bg-gray-50 transition-colors">
                  <td className="p-4">
                    <div className="flex items-center space-x-3">
                      <div className="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-secondary font-semibold">
                        {student.first_name.charAt(0)}
                        {student.last_name.charAt(0)}
                      </div>
                      <div>
                        <p className="font-medium text-gray-900">{student.full_name}</p>
                        {student.phone && (
                          <p className="text-xs text-gray-500 flex items-center">
                            <Phone size={12} className="mr-1" />
                            {student.phone}
                          </p>
                        )}
                      </div>
                    </div>
                  </td>
                  <td className="p-4 font-mono text-sm text-gray-600">{student.matricule}</td>
                  <td className="p-4 text-sm text-gray-600">{student.email}</td>
                  <td className="p-4">
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                      {getClassName(student.course_class_id)}
                    </span>
                  </td>
                  <td className="p-4">
                    <span
                      className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${student.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}
                    >
                      {student.is_active ? t('active') : t('inactive')}
                    </span>
                  </td>
                  <td className="p-4 text-right">
                    <div className="flex items-center justify-end space-x-2">
                      <button
                        type="button"
                        onClick={() => openMoveModal(student)}
                        className="p-2 text-gray-400 hover:text-secondary hover:bg-indigo-50 rounded-lg"
                        title={t('moveToClass')}
                      >
                        <ArrowRight size={16} />
                      </button>
                      <button
                        type="button"
                        onClick={() => handleOpenModal(student)}
                        className="p-2 text-gray-400 hover:text-secondary hover:bg-indigo-50 rounded-lg"
                        title={t('edit')}
                      >
                        <Edit2 size={16} />
                      </button>
                      <button
                        type="button"
                        onClick={() => onDeleteStudent(student.id)}
                        className="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg"
                        title={t('delete')}
                      >
                        <Trash2 size={16} />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
              {filteredStudents.length === 0 && (
                <tr>
                  <td colSpan={6} className="p-8 text-center text-gray-500">
                    <UserX size={48} className="mx-auto mb-4 opacity-20" />
                    {t('noStudentsFound')}
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
        title={editingStudent ? t('editStudent') : t('newStudent')}
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label htmlFor="student-first-name" className="block text-sm font-medium text-gray-700 mb-1">
                {t('firstName')}
              </label>
              <input
                required
                id="student-first-name"
                type="text"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.first_name}
                onChange={(e) => setFormData({ ...formData, first_name: e.target.value })}
              />
            </div>
            <div>
              <label htmlFor="student-last-name" className="block text-sm font-medium text-gray-700 mb-1">
                {t('lastName')}
              </label>
              <input
                required
                id="student-last-name"
                type="text"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.last_name}
                onChange={(e) => setFormData({ ...formData, last_name: e.target.value })}
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label htmlFor="student-email" className="block text-sm font-medium text-gray-700 mb-1">{t('email')}</label>
              <input
                required
                id="student-email"
                type="email"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.email}
                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
              />
            </div>
            <div>
              <label htmlFor="student-matricule" className="block text-sm font-medium text-gray-700 mb-1">
                {t('matricule')}
              </label>
              <input
                required
                id="student-matricule"
                type="text"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.matricule}
                onChange={(e) => setFormData({ ...formData, matricule: e.target.value })}
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label htmlFor="student-phone" className="block text-sm font-medium text-gray-700 mb-1">{t('phone')}</label>
              <input
                id="student-phone"
                type="tel"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.phone}
                onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
              />
            </div>
            <div>
              <label htmlFor="student-gender" className="block text-sm font-medium text-gray-700 mb-1">{t('gender')}</label>
              <select
                id="student-gender"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.gender}
                onChange={(e) =>
                  setFormData({
                    ...formData,
                    gender: e.target.value as 'male' | 'female' | 'other',
                  })
                }
              >
                <option value="male">{t('male')}</option>
                <option value="female">{t('female')}</option>
                <option value="other">{t('other')}</option>
              </select>
            </div>
          </div>

          <div>
            <label htmlFor="student-class" className="block text-sm font-medium text-gray-700 mb-1">{t('class')}</label>
            <select
              required
              id="student-class"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={formData.course_class_id}
              onChange={(e) =>
                setFormData({
                  ...formData,
                  course_class_id: Number(e.target.value),
                })
              }
            >
              <option value={0} disabled>
                {t('selectClass')}
              </option>
              {classes.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name} ({c.code})
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
              {editingStudent ? t('save') : t('add')}
            </button>
          </div>
        </form>
      </Modal>

      <Modal
        isOpen={isMoveModalOpen}
        onClose={() => setIsMoveModalOpen(false)}
        title={t('moveStudentToClass')}
      >
        <form onSubmit={handleMoveSubmit} className="space-y-4">
          <p className="text-sm text-gray-600 mb-4">
            {t('movingStudent')}: <strong>{studentToMove?.full_name}</strong>
          </p>
          <div>
            <label htmlFor="student-target-class" className="block text-sm font-medium text-gray-700 mb-1">
              {t('targetClass')}
            </label>
            <select
              required
              id="student-target-class"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={targetClassId || ''}
              onChange={(e) => setTargetClassId(Number(e.target.value))}
            >
              {classes.map((c) => (
                <option key={c.id} value={c.id}>
                  {c.name} ({c.code})
                </option>
              ))}
            </select>
          </div>
          <div className="pt-4 flex justify-end space-x-3">
            <button
              type="button"
              onClick={() => setIsMoveModalOpen(false)}
              className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition-colors"
            >
              {t('cancel')}
            </button>
            <button
              type="submit"
              className="px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary font-medium transition-colors shadow-sm"
            >
              {t('move')}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
